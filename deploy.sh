#!/usr/bin/env bash
#
# Sweettooth production deploy script — run ON the live server, from the project root:
#
#     cd /var/www/sweettooth && ./deploy.sh
#
# It performs, in order:
#   1. git fast-forward pull of the target branch
#   2. (optional) database backup  -> ~/db-backups/
#   3. composer install (optimized autoloader)
#   4. php artisan migrate --force   (only NEW, additive migrations run)
#   5. front-end asset build (vite)
#   6. config/route/view cache refresh
#   6b. chatbot help-index rebuild (new/edited user guides become searchable)
#   7. php-fpm reload
#
# Environment overrides (all optional):
#   BRANCH=main             branch to deploy
#   MAINTENANCE=1           put the site in maintenance mode during migrate/cache (default: 0 / off)
#   SKIP_BACKUP=1           skip the DB backup
#   SKIP_BUILD=1            skip the npm asset build
#   PHP_FPM_SERVICE=php8.5-fpm
#   BACKUP_DIR=$HOME/db-backups
#   KEEP_BACKUPS=10         how many backups to retain
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${BRANCH:-main}"
MAINTENANCE="${MAINTENANCE:-0}"
SKIP_BACKUP="${SKIP_BACKUP:-0}"
SKIP_BUILD="${SKIP_BUILD:-0}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.5-fpm}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/db-backups}"
KEEP_BACKUPS="${KEEP_BACKUPS:-10}"

cd "$APP_DIR"
log()  { printf "\n\033[1;34m==> %s\033[0m\n" "$*"; }
warn() { printf "\033[1;33m[warn] %s\033[0m\n" "$*"; }

log "Deploying '$BRANCH' from $APP_DIR"

# 1. Pull latest code -------------------------------------------------------
log "Fetching and fast-forwarding code"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"
echo "Now at: $(git log --oneline -1)"

# 2. Database backup --------------------------------------------------------
if [ "$SKIP_BACKUP" != "1" ]; then
    log "Backing up database"
    mkdir -p "$BACKUP_DIR"
    # Load DB_* from .env into the environment WITHOUT printing any secret.
    set -a; . <(grep -E "^DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=" .env | tr -d '\r'); set +a
    OUT="$BACKUP_DIR/sweettooth-$(date +%F-%H%M%S).sql.gz"
    MYSQL_PWD="${DB_PASSWORD:-}" mysqldump --no-tablespaces --single-transaction --quick --routines --triggers \
        -h"${DB_HOST:-127.0.0.1}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME}" "${DB_DATABASE}" | gzip > "$OUT"
    echo "Backup: $OUT ($(du -h "$OUT" | cut -f1))"
    # Retention: keep only the newest $KEEP_BACKUPS dumps.
    ls -1t "$BACKUP_DIR"/sweettooth-*.sql.gz 2>/dev/null | tail -n +"$((KEEP_BACKUPS + 1))" | xargs -r rm -f
else
    warn "Skipping DB backup (SKIP_BACKUP=1)"
fi

# 3. PHP dependencies -------------------------------------------------------
log "Installing PHP dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

# Optional maintenance window around the schema/cache changes only, to keep
# downtime minimal. Off by default (additive migrations are non-disruptive).
if [ "$MAINTENANCE" = "1" ]; then
    log "Enabling maintenance mode"
    php artisan down --retry=15 || true
    # Always bring the site back up, even if a later step fails.
    trap 'php artisan up || true' EXIT
fi

# 4. Migrations -------------------------------------------------------------
log "Running migrations"
php artisan migrate --force

# 5. Front-end assets -------------------------------------------------------
if [ "$SKIP_BUILD" != "1" ]; then
    log "Building front-end assets"
    [ -d node_modules ] || npm ci
    npm run build
else
    warn "Skipping asset build (SKIP_BUILD=1)"
fi

# 6. Cache refresh ----------------------------------------------------------
log "Refreshing caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6b. Chatbot help index ----------------------------------------------------
# Rebuild so any new/edited user guides become searchable this release.
# Non-fatal: a help-index hiccup must never block a deploy.
log "Rebuilding chatbot help index"
php artisan chatbot:reindex-help || warn "Help reindex failed (non-fatal)"

# 7. Reload PHP-FPM (clears opcache so new code is served) ------------------
log "Reloading $PHP_FPM_SERVICE"
sudo systemctl reload "$PHP_FPM_SERVICE" || sudo systemctl restart "$PHP_FPM_SERVICE"

# Bring the site back up if maintenance mode was used.
if [ "$MAINTENANCE" = "1" ]; then
    log "Disabling maintenance mode"
    php artisan up || true
    trap - EXIT
fi

log "✅ Deploy complete: $(git log --oneline -1)"
