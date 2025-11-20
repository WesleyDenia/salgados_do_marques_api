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

echo "[5/6] Executando migrations..."
docker-compose exec app php artisan migrate --force

echo "[6/6] Iniciando serviços de sincronização com o Vendus..."
docker-compose exec app php artisan vendus:sync-coupons
docker-compose exec app php artisan vendus:sync-documents
docker-compose exec app php artisan vendus:sync-loyalty 20
docker-compose exec app php artisan queue:restart


echo "Deploy concluído com sucesso!"
