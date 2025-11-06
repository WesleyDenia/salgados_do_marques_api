#!/bin/sh
set -e

host="$1"
shift

until mysql -h"$host" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "select 1" > /dev/null 2>&1; do
  echo "⏳ Aguardando banco de dados em $host..."
  sleep 2
done

echo "✅ Banco de dados disponível!"
exec "$@"
