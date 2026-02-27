#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

NETWORK_NAME="${NETWORK_NAME:-salgados_backend_net}"
DB_DATA_DIR="${DB_DATA_DIR:-/srv/salgados/mariadb_data}"
DB_COMPOSE_FILE="${DB_COMPOSE_FILE:-docker-compose.db.yml}"
APP_COMPOSE_FILE="${APP_COMPOSE_FILE:-docker-compose.app.yml}"
DB_SERVICE_NAME="${DB_SERVICE_NAME:-mariadb}"
DB_CONTAINER_NAME="${DB_CONTAINER_NAME:-salgados-mariadb}"
APP_SERVICE_NAME="${APP_SERVICE_NAME:-app}"
NGINX_SERVICE_NAME="${NGINX_SERVICE_NAME:-nginx}"
APP_CONTAINER_NAME="${APP_CONTAINER_NAME:-salgados-app}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-rootpass}"
DB_APP_USER="${DB_APP_USER:-laravel}"
DB_APP_NAME="${DB_APP_NAME:-salgados}"
DB_UID="${DB_UID:-999}"
DB_GID="${DB_GID:-999}"

START_DB=true
START_APP=false
RUN_MIGRATIONS=false
RECONCILE_DB_USER=false

usage() {
  cat <<'EOF'
Uso:
  ./bootstrap-infra.sh [opções]

Opções:
  --with-app          Sobe app + nginx após subir o DB
  --with-migrate      Sobe app + nginx e executa php artisan migrate
  --reconcile-db-user Alinha usuário/senha do app no MariaDB usando Docker secret
  --no-db-up          Não sobe o DB (apenas prepara rede e diretório)
  --helper            Lista comandos de uso rápido
  --list-commands     Alias de --helper
  --help              Exibe esta ajuda

Variáveis de ambiente para reutilização em outros projetos:
  NETWORK_NAME        (default: salgados_backend_net)
  DB_DATA_DIR         (default: /srv/salgados/mariadb_data)
  DB_COMPOSE_FILE     (default: docker-compose.db.yml)
  APP_COMPOSE_FILE    (default: docker-compose.app.yml)
  DB_SERVICE_NAME     (default: mariadb)
  DB_CONTAINER_NAME   (default: salgados-mariadb)
  DB_ROOT_PASSWORD    (default: rootpass)
  DB_APP_USER         (default: laravel)
  DB_APP_NAME         (default: salgados)
  APP_SERVICE_NAME    (default: app)
  NGINX_SERVICE_NAME  (default: nginx)
  APP_CONTAINER_NAME  (default: salgados-app)
  DB_UID              (default: 999)
  DB_GID              (default: 999)
EOF
}

helper_commands() {
  cat <<'EOF'
Comandos disponíveis:
  ./bootstrap-infra.sh
    Prepara rede + diretório e sobe apenas o DB

  ./bootstrap-infra.sh --with-app
    Prepara infra e sobe DB + app + nginx

  ./bootstrap-infra.sh --with-migrate
    Prepara infra, sobe stack e executa migrate

  ./bootstrap-infra.sh --reconcile-db-user
    Sobe DB e alinha usuário/senha do app no MariaDB

  ./bootstrap-infra.sh --with-app --reconcile-db-user
    Sobe tudo e faz reconciliação de credencial do usuário de app
EOF
}

while (($# > 0)); do
  case "$1" in
    --with-app)
      START_APP=true
      ;;
    --with-migrate)
      START_APP=true
      RUN_MIGRATIONS=true
      ;;
    --reconcile-db-user)
      RECONCILE_DB_USER=true
      ;;
    --no-db-up)
      START_DB=false
      ;;
    --helper|--list-commands)
      helper_commands
      exit 0
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Opção inválida: $1"
      usage
      exit 1
      ;;
  esac
  shift
done

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

compose_db() {
  "${COMPOSE_CMD[@]}" -f "$DB_COMPOSE_FILE" "$@"
}

compose_app() {
  "${COMPOSE_CMD[@]}" -f "$APP_COMPOSE_FILE" "$@"
}

ensure_network() {
  if ! docker network inspect "$NETWORK_NAME" >/dev/null 2>&1; then
    echo "Criando rede Docker: $NETWORK_NAME"
    docker network create "$NETWORK_NAME" >/dev/null
  else
    echo "Rede Docker já existe: $NETWORK_NAME"
  fi
}

ensure_db_data_dir() {
  echo "Garantindo diretório de dados do DB: $DB_DATA_DIR"
  run_privileged mkdir -p "$DB_DATA_DIR"
  run_privileged chown -R "${DB_UID}:${DB_GID}" "$DB_DATA_DIR"
  run_privileged chmod 750 "$DB_DATA_DIR"
}

start_db() {
  echo "Subindo serviço de banco (${DB_SERVICE_NAME}) via ${DB_COMPOSE_FILE}..."
  # Workaround para docker-compose v1.29.x:
  # evita KeyError 'ContainerConfig' em cenários de recreate.
  compose_db stop "$DB_SERVICE_NAME" || true
  compose_db rm -f "$DB_SERVICE_NAME" || true
  compose_db up -d "$DB_SERVICE_NAME"
}

start_app_stack() {
  echo "Subindo app (${APP_SERVICE_NAME}) e nginx (${NGINX_SERVICE_NAME}) via ${APP_COMPOSE_FILE}..."
  compose_app up -d "$APP_SERVICE_NAME" "$NGINX_SERVICE_NAME"
}

run_migrate() {
  echo "Executando migrations no container ${APP_CONTAINER_NAME}..."
  docker exec "$APP_CONTAINER_NAME" sh -lc '/usr/local/bin/load-secrets.sh php artisan migrate --force --no-interaction'
}

reconcile_db_user() {
  echo "Alinhando usuário ${DB_APP_USER} no MariaDB com senha do secret..."
  docker exec "$DB_CONTAINER_NAME" sh -lc "
DB_PASS=\$(cat /run/secrets/db_password)
mariadb -uroot -p'${DB_ROOT_PASSWORD}' <<SQL
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'%' IDENTIFIED BY '\${DB_PASS}';
ALTER USER '${DB_APP_USER}'@'%' IDENTIFIED BY '\${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_APP_NAME}.* TO '${DB_APP_USER}'@'%';
FLUSH PRIVILEGES;
SQL
"
}

show_status() {
  echo ""
  echo "Status DB:"
  compose_db ps "$DB_SERVICE_NAME" || true

  if [ "$START_APP" = true ]; then
    echo ""
    echo "Status app/nginx:"
    compose_app ps "$APP_SERVICE_NAME" "$NGINX_SERVICE_NAME" || true
  fi
}

detect_compose_command
ensure_network
ensure_db_data_dir

if [ "$START_DB" = true ]; then
  start_db
else
  echo "Subida do DB ignorada (--no-db-up)."
fi

if [ "$START_APP" = true ]; then
  start_app_stack
fi

if [ "$RUN_MIGRATIONS" = true ]; then
  run_migrate
fi

if [ "$RECONCILE_DB_USER" = true ]; then
  reconcile_db_user
fi

show_status
echo ""
echo "Bootstrap concluído."
