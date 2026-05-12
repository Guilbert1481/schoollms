#!/usr/bin/env bash
###############################################################################
# SchoolLMS deploy script — run on the Contabo VPS
#
# Usage:
#   ./deploy.sh           # normal redeploy (pull + build + migrate)
#   ./deploy.sh --fresh   # first-time install (clones-style bootstrap)
#   ./deploy.sh --no-npm  # skip npm build (if you committed /public/build)
#
# Assumes:
#   - Project lives at $APP_DIR (default /var/www/schoollms)
#   - Web server runs as www-data
#   - PHP >= 8.2, Composer, Node 18+, npm available on PATH
#   - .env already configured on the server (NOT in git)
###############################################################################

set -euo pipefail

# ---------- Config ----------------------------------------------------------
APP_DIR="${APP_DIR:-/var/www/schoollms}"
BRANCH="${BRANCH:-main}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

DO_NPM=1
FRESH=0
for arg in "$@"; do
  case "$arg" in
    --no-npm) DO_NPM=0 ;;
    --fresh)  FRESH=1 ;;
    -h|--help)
      sed -n '2,15p' "$0"; exit 0 ;;
    *) echo "Unknown arg: $arg" >&2; exit 1 ;;
  esac
done

log() { printf '\033[1;36m[deploy]\033[0m %s\n' "$*"; }
err() { printf '\033[1;31m[deploy]\033[0m %s\n' "$*" >&2; }

cd "$APP_DIR"

# ---------- Safety checks ---------------------------------------------------
if [[ ! -f .env ]]; then
  err ".env not found at $APP_DIR/.env — copy .env.example and configure it first."
  exit 1
fi

if [[ ! -d .git ]]; then
  err "$APP_DIR is not a git repo. Clone first:"
  err "  git clone https://github.com/Guilbert1481/schoollms.git $APP_DIR"
  exit 1
fi

# ---------- Maintenance mode ------------------------------------------------
if [[ $FRESH -eq 0 ]]; then
  log "Enabling maintenance mode..."
  $PHP_BIN artisan down --render="errors::503" --retry=15 || true
fi

trap 'log "Bringing app back up (trap)..."; $PHP_BIN artisan up || true' EXIT

# ---------- Pull latest -----------------------------------------------------
log "Fetching latest from origin/$BRANCH..."
git fetch --all --prune
git reset --hard "origin/$BRANCH"
git clean -fd -- storage/framework/views

# ---------- Composer --------------------------------------------------------
log "Installing PHP dependencies..."
$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ---------- App key + storage link (fresh only) -----------------------------
if [[ $FRESH -eq 1 ]]; then
  if ! grep -q '^APP_KEY=base64:' .env; then
    log "Generating APP_KEY..."
    $PHP_BIN artisan key:generate --force
  fi
  log "Linking storage..."
  $PHP_BIN artisan storage:link || true
fi

# ---------- NPM build -------------------------------------------------------
if [[ $DO_NPM -eq 1 ]]; then
  log "Building frontend assets..."
  $NPM_BIN ci --no-audit --no-fund
  $NPM_BIN run build
else
  log "Skipping npm build (--no-npm)"
fi

# ---------- Migrations ------------------------------------------------------
log "Running migrations..."
$PHP_BIN artisan migrate --force

# ---------- Caches ----------------------------------------------------------
log "Refreshing caches..."
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache || true

# ---------- Permissions -----------------------------------------------------
log "Fixing permissions on storage and bootstrap/cache..."
chown -R "$WEB_USER:$WEB_GROUP" storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

# ---------- Queue / Horizon restart -----------------------------------------
log "Restarting queue workers..."
$PHP_BIN artisan queue:restart || true

# ---------- Maintenance off (clean exit) ------------------------------------
trap - EXIT
log "Disabling maintenance mode..."
$PHP_BIN artisan up

log "Deploy complete. Current commit: $(git rev-parse --short HEAD)"
