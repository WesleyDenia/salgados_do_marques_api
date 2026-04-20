#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

SITE_DIR="$REPO_DIR/salgados-site"
NETWORK_NAME="salgados_backend_net"
DB_DATA_DIR="${DB_DATA_DIR:-/srv/salgados/mariadb_data}"
DB_UID="${DB_UID:-999}"
DB_GID="${DB_GID:-999}"

normalize_input() {
  echo "${1:-}" | tr '[:upper:]' '[:lower:]' | xargs
}

confirm_dangerous_action() {
  local prompt="$1"
  local answer=""
  read -r -p "$prompt [s/N]: " answer
  answer="$(normalize_input "$answer")"

  case "$answer" in
    s|sim|y|yes) return 0 ;;
    *) return 1 ;;
  esac
}

detect_compose_command() {
  if docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD=(docker compose)
    return
  fi

  if command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD=(docker-compose)
    return
  fi

  echo "Erro: docker-compose (v1) ou docker compose (v2) não encontrado."
  exit 1
}

compose_app() {
  "${COMPOSE_CMD[@]}" -f docker-compose.app.yml "$@"
}

compose_db() {
  "${COMPOSE_CMD[@]}" -f docker-compose.db.yml "$@"
}

ensure_docker_network() {
  if ! docker network inspect "$NETWORK_NAME" >/dev/null 2>&1; then
    echo "Criando rede Docker compartilhada: $NETWORK_NAME"
    docker network create "$NETWORK_NAME" >/dev/null
  fi
}

run_privileged() {
  if "$@" >/dev/null 2>&1; then
    return
  fi

  if command -v sudo >/dev/null 2>&1; then
    sudo "$@"
    return
  fi

  echo "Erro: comando sem permissão e sudo não está disponível: $*"
  exit 1
}

ensure_db_data_dir() {
  echo "Garantindo diretório de dados do DB: $DB_DATA_DIR"
  run_privileged mkdir -p "$DB_DATA_DIR"
  run_privileged chown -R "${DB_UID}:${DB_GID}" "$DB_DATA_DIR"
  run_privileged chmod 750 "$DB_DATA_DIR"
}

recreate_service_without_db() {
  local service="$1"

  echo "Recriando serviço ${service} sem dependências (preserva mariadb)..."
  compose_app stop "$service" || true
  compose_app rm -f "$service" || true
  compose_app up -d --no-deps "$service"
}

require_app_running() {
  local app_container_id
  local is_running

  app_container_id="$(compose_app ps -q app 2>/dev/null || true)"
  if [ -z "$app_container_id" ]; then
    echo "Container app não encontrado em execução pelo compose de aplicação."
    echo "Execute primeiro a opção '2) API' para subir a aplicação."
    exit 1
  fi

  is_running="$(docker inspect -f '{{.State.Running}}' "$app_container_id" 2>/dev/null || echo "false")"
  if [ "$is_running" != "true" ]; then
    echo "Container app está parado."
    echo "Execute primeiro a opção '2) API' para subir a aplicação."
    exit 1
  fi
}

run_artisan_with_secrets() {
  local artisan_command="$1"
  docker exec salgados-app sh -lc "/usr/local/bin/load-secrets.sh php artisan ${artisan_command}"
}

start_db_service() {
  ensure_docker_network
  ensure_db_data_dir

  echo "Subindo serviço mariadb via docker-compose.db.yml..."
  compose_db stop mariadb || true
  compose_db rm -f mariadb || true
  compose_db up -d mariadb
}

stop_db_service() {
  echo "Parando serviço mariadb..."
  compose_db stop mariadb || true
}

restart_db_service() {
  echo "Reiniciando serviço mariadb..."
  stop_db_service
  start_db_service
}

show_db_status() {
  echo ""
  echo "Status do banco:"
  compose_db ps mariadb || true
}

deploy_site() {
  echo "Build do site..."
  if [ -d "$SITE_DIR" ]; then
    cd "$SITE_DIR"
    if command -v bun >/dev/null 2>&1; then
      bun install
      bun run build
    else
      echo "bun não encontrado. Instale o bun antes de rodar o deploy do site."
      exit 1
    fi
    cd "$REPO_DIR"

    BUILD_DIR=""
    if [ -d "$SITE_DIR/dist" ]; then
      BUILD_DIR="$SITE_DIR/dist"
    elif [ -d "$SITE_DIR/public/build" ]; then
      BUILD_DIR="$SITE_DIR/public/build"
    elif [ -d "$REPO_DIR/public/build" ]; then
      BUILD_DIR="$REPO_DIR/public/build"
    fi

    if [ -z "$BUILD_DIR" ]; then
      echo "Diretório de build não encontrado (dist ou public/build)."
      exit 1
    fi

    if [ ! -f "$BUILD_DIR/index.html" ]; then
      echo "index.html não encontrado em $BUILD_DIR"
      exit 1
    fi

    rm -rf "$REPO_DIR/public/site/salgados-site-build"
    mkdir -p "$REPO_DIR/public/site/salgados-site-build"
    cp -a "$BUILD_DIR/." "$REPO_DIR/public/site/salgados-site-build/"
  else
    echo "Diretório do site não encontrado em $SITE_DIR"
    exit 1
  fi
}

deploy_api() {
  local clean_docker=""

  ensure_docker_network

  read -r -p "Executar limpeza do Docker (imagens/cache não usados)? [s/N]: " clean_docker
  clean_docker="$(normalize_input "$clean_docker")"

  if [ "$clean_docker" = "s" ] || [ "$clean_docker" = "sim" ] || [ "$clean_docker" = "y" ] || [ "$clean_docker" = "yes" ]; then
    echo "Limpando cache do Docker (imagens e build cache não usados)..."
    docker system prune -af || true
    docker builder prune -af || true
  else
    echo "Pulando limpeza do Docker."
  fi

  echo "Deploy da API: fluxo resiliente (NÃO toca no banco mariadb)."

  echo "Construindo imagem da aplicação (app)..."
  compose_app build --pull app

  recreate_service_without_db app

  echo "Recriando serviço nginx sem rebuild de imagem (preserva mariadb)..."
  recreate_service_without_db nginx

  echo "Ajustando permissões no container app..."
  compose_app exec app chown -R www-data:www-data storage bootstrap/cache
  compose_app exec app chmod -R 775 storage bootstrap/cache

  echo "Executando migrations..."
  run_artisan_with_secrets "migrate --force --no-interaction"

  echo "Iniciando serviços de sincronização com o Vendus..."
  run_artisan_with_secrets "vendus:sync-coupons"
  run_artisan_with_secrets "vendus:sync-documents"
  run_artisan_with_secrets "vendus:sync-loyalty 20"
  run_artisan_with_secrets "queue:restart"
}

db_operations_menu() {
  local db_option=""

  echo ""
  echo "=== Operações no DB ==="
  echo "1) Subir DB"
  echo "2) Parar DB"
  echo "3) Reiniciar DB"
  echo "4) Status DB"
  echo "5) Migrate"
  echo "6) Seeder"
  echo "7) Refresh Seed"
  echo "8) Voltar"
  read -r -p "Escolha uma opção [1-8]: " db_option
  db_option="$(normalize_input "$db_option")"

  case "$db_option" in
    1|subir|up|db-up|db_up)
      start_db_service
      echo "DB iniciado com sucesso."
      ;;
    2|parar|stop|db-stop|db_stop)
      echo "Atenção: esta operação para apenas o container do banco."
      if ! confirm_dangerous_action "Confirmar parada do mariadb?"; then
        echo "Parada do DB cancelada."
        return 0
      fi
      stop_db_service
      echo "DB parado com sucesso."
      ;;
    3|reiniciar|restart|db-restart|db_restart)
      echo "Atenção: esta operação reinicia apenas o container do banco."
      if ! confirm_dangerous_action "Confirmar reinício do mariadb?"; then
        echo "Reinício do DB cancelado."
        return 0
      fi
      restart_db_service
      echo "DB reiniciado com sucesso."
      ;;
    4|status|ps)
      show_db_status
      ;;
    5|migrate)
      require_app_running
      run_artisan_with_secrets "migrate --force --no-interaction"
      echo "Migrate concluído com sucesso."
      ;;
    6|seeder|seed)
      require_app_running
      run_artisan_with_secrets "db:seed --force --no-interaction"
      echo "Seeder concluído com sucesso."
      ;;
    7|refresh|refresh-seed|refresh_seed)
      require_app_running
      echo "Atenção: Refresh Seed recria schema e dados da aplicação."
      if ! confirm_dangerous_action "Confirmar execução de migrate:refresh --seed?"; then
        echo "Refresh Seed cancelado."
        return 0
      fi
      run_artisan_with_secrets "migrate:refresh --seed --force --no-interaction"
      echo "Refresh Seed concluído com sucesso."
      ;;
    8|voltar|sair|exit|q)
      echo "Operações no DB encerradas."
      return 0
      ;;
    *)
      echo "Opção inválida."
      return 1
      ;;
  esac
}

detect_compose_command

echo "Atualizando repositório..."
git fetch --all --prune
git checkout main
git pull --ff-only origin main

echo "Observação: docker-compose.yml está legado."
echo "Fluxo oficial usa docker-compose.app.yml (API/Site) e docker-compose.db.yml (DB)."

echo "Qual deploy deseja executar?"
echo "1) Site"
echo "2) API"
echo "3) Operações no DB"
echo "4) Sair"
read -r -p "Escolha uma opção [1-4]: " deploy_option

deploy_option="$(normalize_input "$deploy_option")"
final_message=""

case "$deploy_option" in
  1|site|s)
    deploy_site
    final_message="Deploy do site concluído com sucesso!"
    ;;
  2|api|a)
    deploy_api
    final_message="Deploy da API concluído com sucesso!"
    ;;
  3|db|banco|operacoes)
    ensure_docker_network
    db_operations_menu
    final_message="Fluxo de operações no DB finalizado."
    ;;
  4|sair|exit|q)
    echo "Saindo."
    exit 0
    ;;
  *)
    echo "Opção inválida."
    exit 1
    ;;
esac

echo "$final_message"
