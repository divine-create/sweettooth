#!/usr/bin/env bash
#
# Deploy to the live server FROM your local machine, in one command:
#
#     ./bin/deploy-live.sh            # deploys 'main'
#     ./bin/deploy-live.sh main       # explicit branch
#
# It pushes the branch to GitHub, then SSHes in and runs ./deploy.sh on the server.
#
# Connection settings (override via env vars if they ever change):
#   SWEETTOOTH_SSH_KEY   path to the .pem key   (default: ~/.ssh/sweettooth-key.pem)
#   SWEETTOOTH_SSH_HOST  user@ip                (default: ubuntu@51.20.184.194)
#   SWEETTOOTH_APP_DIR   remote project path    (default: /var/www/sweettooth)
#
# Pass-through deploy options (forwarded to the remote deploy.sh):
#   MAINTENANCE=1 ./bin/deploy-live.sh     # enable maintenance window
#   SKIP_BACKUP=1 / SKIP_BUILD=1           # skip those steps
#
set -euo pipefail

BRANCH="${1:-main}"
SSH_KEY="${SWEETTOOTH_SSH_KEY:-$HOME/.ssh/sweettooth-key.pem}"
SSH_HOST="${SWEETTOOTH_SSH_HOST:-ubuntu@51.20.184.194}"
APP_DIR="${SWEETTOOTH_APP_DIR:-/var/www/sweettooth}"

# Forward optional deploy flags to the remote script if they are set locally.
REMOTE_ENV=""
for v in MAINTENANCE SKIP_BACKUP SKIP_BUILD PHP_FPM_SERVICE KEEP_BACKUPS; do
    if [ -n "${!v:-}" ]; then REMOTE_ENV+="$v=${!v} "; fi
done

echo "==> Pushing '$BRANCH' to origin..."
git push origin "$BRANCH"

echo "==> Running remote deploy on $SSH_HOST ($APP_DIR)..."
ssh -i "$SSH_KEY" "$SSH_HOST" "cd '$APP_DIR' && ${REMOTE_ENV}BRANCH='$BRANCH' ./deploy.sh"

echo "==> Done."
