#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

SITE_DIR="$REPO_DIR/salgados-site"
WORKSPACE_DIR="$(cd "$REPO_DIR/.." && pwd)"
PANEL_CANDIDATE_DIRS=(
  "$WORKSPACE_DIR/salgados-encomendas"
  "$WORKSPACE_DIR/salgados_do_marques_encomendas"
)
NETWORK_NAME="salgados_backend_net"
DB_DATA_DIR="${DB_DATA_DIR:-/srv/salgados/mariadb_data}"
DB_UID="${DB_UID:-999}"
DB_GID="${DB_GID:-999}"
PANEL_REPO_DIR=""
PANEL_COMPOSE_FILE=""
PANEL_ENV_FILE=""
PANEL_ENV_EXAMPLE_FILE=""

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

compose_panel() {
  "${COMPOSE_CMD[@]}" -f "$PANEL_COMPOSE_FILE" "$@"
}

resolve_panel_repo_dir() {
  local candidate=""

  for candidate in "${PANEL_CANDIDATE_DIRS[@]}"; do
    if [ -f "$candidate/docker-compose.yml" ] && [ -f "$candidate/Dockerfile" ]; then
      PANEL_REPO_DIR="$candidate"
      PANEL_COMPOSE_FILE="$PANEL_REPO_DIR/docker-compose.yml"
      PANEL_ENV_FILE="$PANEL_REPO_DIR/.env.production"
      PANEL_ENV_EXAMPLE_FILE="$PANEL_REPO_DIR/.env.production.example"
      return 0
    fi
  done

  echo "Erro: repositório do painel não encontrado."
  echo "Caminhos verificados:"
  printf ' - %s\n' "${PANEL_CANDIDATE_DIRS[@]}"
  exit 1
}

ensure_docker_network() {
  if ! docker network inspect "$NETWORK_NAME" >/dev/null 2>&1; then
    echo "Criando rede Docker compartilhada: $NETWORK_NAME"
    docker network create "$NETWORK_NAME" >/dev/null
  fi
}

get_default_branch() {
  local repo_dir="$1"
  local branch

  branch="$(git -C "$repo_dir" symbolic-ref --quiet --short refs/remotes/origin/HEAD 2>/dev/null || true)"
  branch="${branch#origin/}"

  if [ -n "$branch" ]; then
    echo "$branch"
    return
  fi

  echo "main"
}

update_repository() {
  local repo_dir="$1"
  local repo_name="$2"
  local branch

  if [ ! -d "$repo_dir/.git" ]; then
    echo "Erro: repositório ${repo_name} não encontrado em $repo_dir"
    exit 1
  fi

  branch="$(get_default_branch "$repo_dir")"

  echo "Atualizando repositório ${repo_name}..."
  git -C "$repo_dir" fetch --all --prune
  git -C "$repo_dir" checkout "$branch"
  git -C "$repo_dir" pull --ff-only origin "$branch"
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

ensure_panel_env_file() {
  resolve_panel_repo_dir

  if [ -f "$PANEL_ENV_FILE" ]; then
    return
  fi

  if [ ! -f "$PANEL_ENV_EXAMPLE_FILE" ]; then
    echo "Erro: arquivo de exemplo do ambiente do painel não encontrado em $PANEL_ENV_EXAMPLE_FILE"
    exit 1
  fi

  cp "$PANEL_ENV_EXAMPLE_FILE" "$PANEL_ENV_FILE"
  echo "Arquivo $PANEL_ENV_FILE criado a partir do exemplo."
  echo "Configure SESSION_SECRET antes de subir o painel."
  exit 1
}

ensure_gateway_nginx() {
  local nginx_container_id
  local is_running

  nginx_container_id="$(compose_app ps -q nginx 2>/dev/null || true)"
  is_running="false"

  if [ -n "$nginx_container_id" ]; then
    is_running="$(docker inspect -f '{{.State.Running}}' "$nginx_container_id" 2>/dev/null || echo "false")"
  fi

  if [ "$is_running" = "true" ]; then
    echo "Recarregando nginx do backend para aplicar o host do painel..."
    compose_app exec nginx nginx -s reload || recreate_service_without_db nginx
    return
  fi

  echo "Subindo nginx do backend para expor o painel..."
  compose_app up -d --no-deps nginx
}

start_panel_service() {
  ensure_docker_network
  ensure_panel_env_file

  echo "Subindo painel de agendamentos..."
  compose_panel up -d panel
  ensure_gateway_nginx
}

rebuild_panel_service() {
  ensure_docker_network
  ensure_panel_env_file

  echo "Rebuildando imagem do painel de agendamentos..."
  compose_panel build --pull panel
  compose_panel up -d --force-recreate panel
  ensure_gateway_nginx
}

stop_panel_service() {
  resolve_panel_repo_dir
  echo "Parando painel de agendamentos..."
  compose_panel stop panel || true
  compose_panel rm -f panel || true
}

panel_operations_menu() {
  local panel_option=""

  echo ""
  echo "=== Painel agendamentos ==="
  echo "1) Subir containers"
  echo "2) Rebuildar containers"
  echo "3) Parar containers"
  echo "4) Voltar"
  read -r -p "Escolha uma opção [1-4]: " panel_option
  panel_option="$(normalize_input "$panel_option")"

  case "$panel_option" in
    1|subir|up)
      resolve_panel_repo_dir
      update_repository "$PANEL_REPO_DIR" "$(basename "$PANEL_REPO_DIR")"
      start_panel_service
      echo "Painel iniciado com sucesso."
      ;;
    2|rebuild|rebuildar|build)
      resolve_panel_repo_dir
      update_repository "$PANEL_REPO_DIR" "$(basename "$PANEL_REPO_DIR")"
      rebuild_panel_service
      echo "Painel rebuildado com sucesso."
      ;;
    3|parar|stop)
      echo "Atenção: esta operação para apenas o container do painel."
      if ! confirm_dangerous_action "Confirmar parada do painel de agendamentos?"; then
        echo "Parada do painel cancelada."
        return 0
      fi
      stop_panel_service
      echo "Painel parado com sucesso."
      ;;
    4|voltar|sair|exit|q)
      echo "Operações do painel encerradas."
      return 0
      ;;
    *)
      echo "Opção inválida."
      return 1
      ;;
  esac
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

echo "Observação: docker-compose.yml está legado."
echo "Fluxo oficial usa docker-compose.app.yml (API/Site) e docker-compose.db.yml (DB)."

echo "Qual deploy deseja executar?"
echo "1) Site"
echo "2) API"
echo "3) Operações no DB"
echo "4) Painel agendamentos"
echo "5) Sair"
read -r -p "Escolha uma opção [1-5]: " deploy_option

deploy_option="$(normalize_input "$deploy_option")"
final_message=""

case "$deploy_option" in
  1|site|s)
    update_repository "$REPO_DIR" "salgados-api"
    deploy_site
    final_message="Deploy do site concluído com sucesso!"
    ;;
  2|api|a)
    update_repository "$REPO_DIR" "salgados-api"
    deploy_api
    final_message="Deploy da API concluído com sucesso!"
    ;;
  3|db|banco|operacoes)
    update_repository "$REPO_DIR" "salgados-api"
    ensure_docker_network
    db_operations_menu
    final_message="Fluxo de operações no DB finalizado."
    ;;
  4|painel|panel|agendamentos)
    panel_operations_menu
    final_message="Fluxo do painel finalizado."
    ;;
  5|sair|exit|q)
    echo "Saindo."
    exit 0
    ;;
  *)
    echo "Opção inválida."
    exit 1
    ;;
esac

echo "$final_message"
