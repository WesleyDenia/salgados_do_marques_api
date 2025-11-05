#!/bin/bash

# Sobe o MySQL com docker-compose
docker compose -f docker-compose.dev.yml up -d

# Verifica se o container subiu com sucesso
if [ $? -eq 0 ]; then
  echo "Containers iniciados com sucesso. Iniciando Laravel..."
  php artisan serve
else
  echo "Erro ao iniciar containers."
fi
