#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

echo "Atualizando repositório..."
git fetch --all --prune
git checkout main
git pull --ff-only origin main

SITE_DIR="$REPO_DIR/salgados-site"

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
  CLEAN_DOCKER="$(echo "$CLEAN_DOCKER" | tr '[:upper:]' '[:lower:]' | xargs)"
  if [ "$CLEAN_DOCKER" = "s" ] || [ "$CLEAN_DOCKER" = "sim" ] || [ "$CLEAN_DOCKER" = "y" ] || [ "$CLEAN_DOCKER" = "yes" ]; then
    echo "Limpando cache do Docker (imagens e build cache não usados)..."
    docker system prune -af || true
    docker builder prune -af || true
  else
    echo "Pulando limpeza do Docker."
  fi

  echo "Parando containers existentes..."
  docker-compose down

  echo "Construindo imagens..."
  docker-compose build --pull

  echo "Subindo containers..."
  docker-compose up -d
  docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
  docker-compose exec app chmod -R 775 storage bootstrap/cache

  echo "Executando migrations..."
  docker exec -it salgados-app sh -lc '/usr/local/bin/load-secrets.sh php artisan migrate'

  echo "Iniciando serviços de sincronização com o Vendus..."
  docker-compose exec app php artisan vendus:sync-coupons
  docker-compose exec app php artisan vendus:sync-documents
  docker-compose exec app php artisan vendus:sync-loyalty 20
  docker-compose exec app php artisan queue:restart
}

echo "Qual deploy deseja executar?"
echo "1) Site"
echo "2) API"
echo "3) Sair"
read -r -p "Escolha uma opção [1-3]: " DEPLOY_OPTION

DEPLOY_OPTION="$(echo "$DEPLOY_OPTION" | tr '[:upper:]' '[:lower:]' | xargs)"

case "$DEPLOY_OPTION" in
  1|site|s)
    deploy_site
    ;;
  2|api|a)
    deploy_api
    ;;
  3|sair|exit|q)
    echo "Saindo."
    exit 0
    ;;
  *)
    echo "Opção inválida."
    exit 1
    ;;
esac

echo "Deploy concluído com sucesso!"
