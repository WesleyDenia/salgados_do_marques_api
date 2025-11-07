#!/bin/sh
set -e

# Lê e exporta segredos
[ -f "$DB_PASSWORD_FILE" ] && export DB_PASSWORD=$(cat "$DB_PASSWORD_FILE")
[ -f "$MAIL_PASSWORD_FILE" ] && export MAIL_PASSWORD=$(cat "$MAIL_PASSWORD_FILE")
[ -f "$VENDUS_API_KEY_FILE" ] && export VENDUS_API_KEY=$(cat "$VENDUS_API_KEY_FILE")
[ -f "$WAPIFY_API_KEY_FILE" ] && export WAPIFY_API_KEY=$(cat "$WAPIFY_API_KEY_FILE")

echo "✅ Secrets carregados com sucesso. Iniciando PHP-FPM..."
exec "$@"
