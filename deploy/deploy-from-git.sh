#!/usr/bin/env bash
# Deploy HR SaaS to /var/www/hr-saas from GitHub zhavrilu-collab/SuperSkyCrew.
#
# Usage:
#   bash deploy-from-git.sh [hr_ref]
# Default: cursor/hr-saas-kadar-i-evidencija
#
# Install on VPS:
#   cp deploy/deploy-from-git.sh /home/ubuntu/bin/deploy-hr-from-git.sh && chmod +x /home/ubuntu/bin/deploy-hr-from-git.sh
set -euo pipefail

HR_REF="${1:-cursor/hr-saas-kadar-i-evidencija}"
HR_SRC="/home/ubuntu/src/hr-saas"
HR_APP="/var/www/hr-saas"
TS="$(date +%Y%m%d-%H%M%S)"
BACKUP="/home/ubuntu/deploy-backups/$TS"

fix_app_permissions() {
  local app="$1"
  mkdir -p "$app/storage/logs" "$app/storage/framework/cache/data" "$app/storage/framework/sessions" "$app/storage/framework/views" "$app/storage/app/private" "$app/storage/app/public" "$app/bootstrap/cache"
  # php-fpm radi kao www-data; ubuntu smije chmod jer je vlasnik.
  chmod -R a+rwX "$app/storage" "$app/bootstrap/cache"
  if [[ -w "$app/bootstrap/cache" && -w "$app/storage/logs" ]]; then
    return 0
  fi
  echo "== fixing permissions on $app (sudo) =="
  sudo chown -R ubuntu:www-data "$app/storage" "$app/bootstrap/cache"
  sudo chmod -R ug+rwX "$app/storage" "$app/bootstrap/cache"
  sudo find "$app/storage" "$app/bootstrap/cache" -type d -exec chmod g+s {} \;
}

sync_git_repo() {
  local src="$1"
  local repo_ssh="$2"
  local ref="$3"
  local label="$4"

  if [[ ! -d "$src/.git" ]]; then
    echo "== clone $label =="
    git clone "$repo_ssh" "$src"
  fi

  echo "== update $label ($ref) =="
  git -C "$src" fetch --prune origin
  git -C "$src" checkout -f "$ref"
  git -C "$src" reset --hard "origin/$ref"
  git -C "$src" clean -fd
}

rsync_app() {
  local from="$1"
  local to="$2"

  mkdir -p "$to"
  rsync -a --delete \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='storage/' \
    --exclude='bootstrap/cache/' \
    --exclude='.git/' \
    "$from" "$to"
}

prepare_laravel_app() {
  local app="$1"
  local label="$2"

  echo "== composer + artisan $label =="
  fix_app_permissions "$app"
  cd "$app"
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan migrate --force
  php artisan storage:link --force >/dev/null 2>&1 || true
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
}

mkdir -p /home/ubuntu/src /home/ubuntu/deploy-backups "$BACKUP"

sync_git_repo "$HR_SRC" "git@github.com:zhavrilu-collab/SuperSkyCrew.git" "$HR_REF" "HR SaaS"

echo "== backup .env =="
cp -a "$HR_APP/.env" "$BACKUP/hr.env" 2>/dev/null || true

echo "== sync HR =="
rsync_app "$HR_SRC/" "$HR_APP/"

prepare_laravel_app "$HR_APP" "HR"

echo "== done =="
echo "hr: $(git -C "$HR_SRC" rev-parse --short HEAD) ($HR_REF)"
echo "backup: $BACKUP"
