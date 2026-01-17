#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

echo "[1/5] Atualizando repositório..."
git fetch --all --prune
git checkout main
git pull --ff-only origin main

echo "[2/5] Parando containers existentes..."
docker-compose down

echo "[3/5] Construindo imagens..."
docker-compose build --pull

echo "[4/5] Subindo containers..."
docker-compose up -d
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache

echo "[5/6] Executando migrations..."
docker exec -it salgados-app sh -lc '/usr/local/bin/load-secrets.sh php artisan migrate'

echo "[6/6] Iniciando serviços de sincronização com o Vendus..."

docker-compose exec app php artisan vendus:sync-coupons
docker-compose exec app php artisan vendus:sync-documents
docker-compose exec app php artisan vendus:sync-loyalty 20
docker-compose exec app php artisan queue:restart


echo "Deploy concluído com sucesso!"
