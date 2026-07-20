#!/usr/bin/env bash
#
# Sophentis DB restore (Roadmap D1 — a backup is not "done" until a restore is
# proven). Reverses db-backup.sh: decrypt → gunzip → mysql.
#
# USAGE:
#   BACKUP_ENV=/etc/sophentis-backup.env \
#     bash scripts/backup/db-restore.sh <file.sql.gz.enc> [target_database]
#
# SAFETY: if you omit target_database it defaults to "<DB_DATABASE>_restore_test"
# — a SCRATCH database, never the live one — so a routine restore-test cannot
# clobber production. To restore OVER the live DB you must name it explicitly
# AND type the confirmation phrase when prompted.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/schoollms}"
BACKUP_ENV="${BACKUP_ENV:-/etc/sophentis-backup.env}"

if [[ -f "$BACKUP_ENV" ]]; then
    # shellcheck disable=SC1090
    source "$BACKUP_ENV"
fi
: "${BACKUP_PASSPHRASE:?Set BACKUP_PASSPHRASE in $BACKUP_ENV to decrypt}"

BACKUP_FILE="${1:?Usage: db-restore.sh <file.sql.gz.enc> [target_database]}"
[[ -f "$BACKUP_FILE" ]] || { echo "No such file: $BACKUP_FILE" >&2; exit 1; }

ENV_FILE="$APP_DIR/.env"
env_get() { grep -E "^$1=" "$ENV_FILE" | tail -n1 | cut -d= -f2- | tr -d '"'"'"'\r'; }
DB_HOST="$(env_get DB_HOST)";     DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)";     DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"
LIVE_DB="$(env_get DB_DATABASE)"

TARGET_DB="${2:-${LIVE_DB}_restore_test}"

if [[ "$TARGET_DB" == "$LIVE_DB" ]]; then
    echo "!! You are about to restore OVER THE LIVE DATABASE ($LIVE_DB) on $DB_HOST."
    echo "!! This REPLACES all current data. Make a fresh backup first."
    read -r -p 'Type "OVERWRITE LIVE" to proceed: ' CONFIRM
    [[ "$CONFIRM" == "OVERWRITE LIVE" ]] || { echo "Aborted."; exit 1; }
fi

echo "[$(date -Is)] ensuring target database ${TARGET_DB} exists"
MYSQL_PWD="$DB_PASSWORD" mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" \
    -e "CREATE DATABASE IF NOT EXISTS \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "[$(date -Is)] restoring ${BACKUP_FILE} → ${TARGET_DB}"
openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE -in "$BACKUP_FILE" \
    | gunzip \
    | MYSQL_PWD="$DB_PASSWORD" mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$TARGET_DB"

echo "[$(date -Is)] restore complete → ${TARGET_DB}"
echo "Verify, e.g.:  MYSQL_PWD=… mysql -u${DB_USERNAME} ${TARGET_DB} -e 'SELECT COUNT(*) FROM users;'"
