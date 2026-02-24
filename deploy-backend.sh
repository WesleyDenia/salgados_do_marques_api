#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

echo "Atualizando repositório..."
git fetch --all --prune
git checkout main
git pull --ff-only origin main

SITE_DIR="$REPO_DIR/salgados-site"

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
  read -r -p "Executar limpeza do Docker (imagens/cache não usados)? [s/N]: " CLEAN_DOCKER
  CLEAN_DOCKER="$(normalize_input "$CLEAN_DOCKER")"
  if [ "$CLEAN_DOCKER" = "s" ] || [ "$CLEAN_DOCKER" = "sim" ] || [ "$CLEAN_DOCKER" = "y" ] || [ "$CLEAN_DOCKER" = "yes" ]; then
    echo "Limpando cache do Docker (imagens e build cache não usados)..."
    docker system prune -af || true
    docker builder prune -af || true
  else
    echo "Pulando limpeza do Docker."
  fi

  echo "Deploy da API: fluxo resiliente (NÃO toca no banco mariadb)."

  echo "Construindo imagem da aplicação (app)..."
  docker-compose build --pull app

  echo "Recriando serviço app sem dependências (preserva mariadb)..."
  docker-compose up -d --no-deps --force-recreate app

  echo "Recriando serviço nginx sem rebuild de imagem (preserva mariadb)..."
  docker-compose up -d --no-deps --force-recreate nginx

  echo "Ajustando permissões no container app..."
  docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
  docker-compose exec app chmod -R 775 storage bootstrap/cache

  echo "Executando migrations..."
  docker exec salgados-app sh -lc '/usr/local/bin/load-secrets.sh php artisan migrate'

  echo "Iniciando serviços de sincronização com o Vendus..."
  docker-compose exec app php artisan vendus:sync-coupons
  docker-compose exec app php artisan vendus:sync-documents
  docker-compose exec app php artisan vendus:sync-loyalty 20
  docker-compose exec app php artisan queue:restart
}

deploy_db() {
  local db_option=""

  echo ""
  echo "=== Manutenção de Banco (mariadb) ==="
  echo "Atenção: este fluxo afeta diretamente o serviço de banco."
  echo "Use apenas para manutenção operacional explícita."
  echo "1) Subir DB"
  echo "2) Parar DB"
  echo "3) Reiniciar DB"
  echo "4) Cancelar / Voltar"
  read -r -p "Escolha uma opção [1-4]: " db_option
  db_option="$(normalize_input "$db_option")"

  case "$db_option" in
    1|subir|up|start)
      echo "Você está prestes a SUBIR o serviço mariadb."
      if ! confirm_dangerous_action "Confirmar ação no banco?"; then
        echo "Ação de banco cancelada. Nenhum comando foi executado."
        return 0
      fi
      docker-compose up -d mariadb
      echo "Ação de banco concluída: subir mariadb."
      ;;
    2|parar|stop)
      echo "Você está prestes a PARAR o serviço mariadb."
      if ! confirm_dangerous_action "Confirmar ação no banco?"; then
        echo "Ação de banco cancelada. Nenhum comando foi executado."
        return 0
      fi
      docker-compose stop mariadb
      echo "Ação de banco concluída: parar mariadb."
      ;;
    3|reiniciar|restart)
      echo "Você está prestes a REINICIAR o serviço mariadb."
      if ! confirm_dangerous_action "Confirmar ação no banco?"; then
        echo "Ação de banco cancelada. Nenhum comando foi executado."
        return 0
      fi
      docker-compose restart mariadb
      echo "Ação de banco concluída: reiniciar mariadb."
      ;;
    4|cancelar|voltar|sair|exit|q)
      echo "Operação de banco cancelada."
      return 0
      ;;
    *)
      echo "Opção de DB inválida. Nenhum comando foi executado."
      return 0
      ;;
  esac
}

echo "Qual deploy deseja executar?"
echo "1) Site"
echo "2) API"
echo "3) DB (manutenção sensível)"
echo "4) Sair"
read -r -p "Escolha uma opção [1-4]: " DEPLOY_OPTION

DEPLOY_OPTION="$(normalize_input "$DEPLOY_OPTION")"
FINAL_MESSAGE=""

case "$DEPLOY_OPTION" in
  1|site|s)
    deploy_site
    FINAL_MESSAGE="Deploy do site concluído com sucesso!"
    ;;
  2|api|a)
    deploy_api
    FINAL_MESSAGE="Deploy da API concluído com sucesso!"
    ;;
  3|db|banco)
    deploy_db
    FINAL_MESSAGE="Fluxo de manutenção de banco finalizado."
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

echo "$FINAL_MESSAGE"
