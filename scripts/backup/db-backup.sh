#!/usr/bin/env bash
#
# Sophentis DB backup (Roadmap D1 + D5 dead-man switch).
#
# What it does, in order:
#   1. mysqldump the app database (creds read from the Laravel .env — never
#      duplicated here),
#   2. gzip, then encrypt at rest with AES-256 (openssl) so a stolen backup
#      file is useless without the passphrase,
#   3. rotate local copies (keep N days),
#   4. optionally push off-site (rclone remote — ransomware/disk-failure
#      protection; a backup that lives only on the same disk is not a backup),
#   5. ping a dead-man monitor on success so a SILENT failure alerts you (D5).
#
# INSTALL (operator, on the VPS — this script is committed but inert until you
# configure and schedule it):
#   1. cp scripts/backup/backup.env.example /etc/sophentis-backup.env
#      and fill in BACKUP_PASSPHRASE, HEALTHCHECK_URL, RCLONE_REMOTE.
#      chmod 600 /etc/sophentis-backup.env   (it holds the passphrase)
#   2. Test once by hand:  BACKUP_ENV=/etc/sophentis-backup.env bash scripts/backup/db-backup.sh
#   3. Restore-test it (D1 is not done until a restore is proven):
#      bash scripts/backup/db-restore.sh <the-file>.sql.gz.enc   (into a SCRATCH db)
#   4. Schedule via cron, e.g. daily 02:15:
#      15 2 * * * BACKUP_ENV=/etc/sophentis-backup.env bash /var/www/schoollms/scripts/backup/db-backup.sh >> /var/log/sophentis-backup.log 2>&1
#
# The passphrase and the off-site remote are the two things you must not lose:
# without the passphrase the backups cannot be decrypted; store it in your
# password manager, NOT on the same server.

set -euo pipefail

# --- Configuration -----------------------------------------------------------
APP_DIR="${APP_DIR:-/var/www/schoollms}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/sophentis}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
BACKUP_ENV="${BACKUP_ENV:-/etc/sophentis-backup.env}"

# Backup-specific secrets live OUTSIDE the repo (see backup.env.example).
if [[ -f "$BACKUP_ENV" ]]; then
    # shellcheck disable=SC1090
    source "$BACKUP_ENV"
fi

: "${BACKUP_PASSPHRASE:?Set BACKUP_PASSPHRASE in $BACKUP_ENV — refusing to write an unencrypted backup}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-}"
RCLONE_REMOTE="${RCLONE_REMOTE:-}"

# --- Read DB creds from the Laravel .env (single source of truth) ------------
ENV_FILE="$APP_DIR/.env"
[[ -f "$ENV_FILE" ]] || { echo "No .env at $ENV_FILE" >&2; exit 1; }

env_get() {
    # last match wins (mirrors dotenv), strip quotes/CR
    grep -E "^$1=" "$ENV_FILE" | tail -n1 | cut -d= -f2- | tr -d '"'"'"'\r'
}

DB_HOST="$(env_get DB_HOST)";     DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_get DB_PORT)";     DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"

: "${DB_DATABASE:?DB_DATABASE missing from .env}"

fail() {
    echo "[$(date -Is)] BACKUP FAILED: $*" >&2
    # Signal failure to the monitor too, so a crash still alerts (D5).
    [[ -n "$HEALTHCHECK_URL" ]] && curl -fsS -m 10 --retry 3 "${HEALTHCHECK_URL}/fail" >/dev/null 2>&1 || true
    exit 1
}
trap 'fail "unexpected error on line $LINENO"' ERR

# --- Dump → gzip → encrypt ---------------------------------------------------
mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$BACKUP_DIR/${DB_DATABASE}-${STAMP}.sql.gz.enc"
TMP="$(mktemp "${BACKUP_DIR}/.inprogress.XXXXXX")"

echo "[$(date -Is)] dumping ${DB_DATABASE} → ${OUT}"

# --single-transaction: consistent dump without locking a live InnoDB DB.
# Password via env (MYSQL_PWD) so it never appears in the process list.
MYSQL_PWD="$DB_PASSWORD" mysqldump \
    --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" \
    --single-transaction --quick --routines --triggers --events \
    "$DB_DATABASE" \
    | gzip -9 \
    | openssl enc -aes-256-cbc -pbkdf2 -salt -pass env:BACKUP_PASSPHRASE \
    > "$TMP"

# mysqldump sits in a pipe; PIPESTATUS[0] is its real exit code.
[[ "${PIPESTATUS[0]}" -eq 0 ]] || fail "mysqldump exited non-zero"
[[ -s "$TMP" ]] || fail "backup file is empty"

mv "$TMP" "$OUT"
SIZE="$(du -h "$OUT" | cut -f1)"
echo "[$(date -Is)] wrote ${OUT} (${SIZE})"

# --- Off-site copy (optional but strongly recommended) -----------------------
if [[ -n "$RCLONE_REMOTE" ]]; then
    echo "[$(date -Is)] copying off-site → ${RCLONE_REMOTE}"
    rclone copy "$OUT" "$RCLONE_REMOTE" --no-traverse || fail "off-site copy failed"
else
    echo "[$(date -Is)] WARNING: RCLONE_REMOTE unset — backup is LOCAL ONLY (not disaster-safe)"
fi

# --- Rotate local copies -----------------------------------------------------
find "$BACKUP_DIR" -name "${DB_DATABASE}-*.sql.gz.enc" -type f -mtime "+${RETENTION_DAYS}" -delete
echo "[$(date -Is)] pruned local backups older than ${RETENTION_DAYS} days"

# --- Dead-man switch: ping ONLY after a fully successful run (D5) -------------
if [[ -n "$HEALTHCHECK_URL" ]]; then
    curl -fsS -m 10 --retry 3 "$HEALTHCHECK_URL" >/dev/null 2>&1 \
        && echo "[$(date -Is)] pinged dead-man monitor" \
        || echo "[$(date -Is)] WARNING: dead-man ping failed (backup itself is OK)"
else
    echo "[$(date -Is)] WARNING: HEALTHCHECK_URL unset — a silent backup failure will NOT alert you"
fi

trap - ERR
echo "[$(date -Is)] backup complete"
