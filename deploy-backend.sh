#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

echo "[1/5] Atualizando repositório..."
git fetch --all --prune
git checkout main
git pull --ff-only origin main

echo "[2/5] Parando containers existentes..."
docker compose down

echo "[3/5] Construindo imagens..."
docker compose build --pull

echo "[4/5] Subindo containers..."
docker compose up -d

echo "[5/5] Executando migrations..."
docker compose exec app php artisan migrate --force

echo "Deploy concluído com sucesso!"
