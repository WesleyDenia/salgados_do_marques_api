#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

echo "Atualizando repositório..."
git fetch --all --prune
git checkout main
git pull --ff-only origin main

SITE_DIR="$REPO_DIR/../site/salgados-site"

deploy_site() {
  echo "Build do site..."
  if [ -d "$SITE_DIR" ]; then
    cd "$SITE_DIR"
    npm ci
    npm run build
    cd "$REPO_DIR"

    rm -rf "$REPO_DIR/public/site"
    mkdir -p "$REPO_DIR/public/site"
    cp -a "$SITE_DIR/dist/." "$REPO_DIR/public/site/"
  else
    echo "Diretório do site não encontrado em $SITE_DIR"
    exit 1
  fi
}

deploy_api() {
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
echo "3) Site + API"
echo "4) Sair"
read -r -p "Escolha uma opção [1-4]: " DEPLOY_OPTION

case "$DEPLOY_OPTION" in
  1)
    deploy_site
    ;;
  2)
    deploy_api
    ;;
  3)
    deploy_site
    deploy_api
    ;;
  4)
    echo "Saindo."
    exit 0
    ;;
  *)
    echo "Opção inválida."
    exit 1
    ;;
esac

echo "Deploy concluído com sucesso!"
