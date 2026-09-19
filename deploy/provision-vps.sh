#!/usr/bin/env bash
# Jednokratni provision HR SaaS-a na VPS-u gdje već rade udruga-saas i admin-console.
# Pokreni NA VPS-u, s sudo:
#   sudo bash /home/ubuntu/src/hr-saas/deploy/provision-vps.sh
#
# Prije toga:
#   1) DNS A zapis hr.superskytech.com → 148.163.187.14
#   2) git clone SuperSkyCrew u /home/ubuntu/src/hr-saas
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Pokreni s sudo." >&2
  exit 1
fi

DOMAIN="${HR_DOMAIN:-hr.superskytech.com}"
HR_SRC="${HR_SRC:-/home/ubuntu/src/hr-saas}"
HR_APP="${HR_APP:-/var/www/hr-saas}"
SAAS_APP="${SAAS_APP:-/var/www/udruga-saas}"
ADMIN_APP="${ADMIN_APP:-/var/www/admin-console}"
DB_NAME="${HR_DB_NAME:-hr_saas}"
DB_USER="${HR_DB_USER:-hr_saas}"

if [[ ! -d "$HR_SRC" ]]; then
  echo "Nema izvora u $HR_SRC. Prvo: git clone git@github.com:zhavrilu-collab/SuperSkyCrew.git $HR_SRC" >&2
  exit 1
fi

env_val() {
  local file="$1" key="$2" line=""
  [[ -f "$file" ]] || return 0
  line="$(grep -E "^[[:space:]]*${key}=" "$file" 2>/dev/null | tail -n1)" || line=""
  [[ -z "$line" ]] && return 0
  line="${line#*=}"
  printf '%s' "$line" \
    | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//" \
    | tr -d '\r'
  return 0
}

echo "== MySQL baza $DB_NAME =="
DB_PASS="$(openssl rand -hex 16)"
SYNC_KEY="$(env_val "$SAAS_APP/.env" ADMIN_SYNC_API_KEY)"
WEBHOOK_SECRET="$(env_val "$SAAS_APP/.env" ADMIN_CONSOLE_WEBHOOK_SECRET)"
MAIL_HOST="$(env_val "$SAAS_APP/.env" MAIL_HOST)"
MAIL_PORT="$(env_val "$SAAS_APP/.env" MAIL_PORT)"
MAIL_USERNAME="$(env_val "$SAAS_APP/.env" MAIL_USERNAME)"
MAIL_PASSWORD="$(env_val "$SAAS_APP/.env" MAIL_PASSWORD)"
MAIL_FROM="$(env_val "$SAAS_APP/.env" MAIL_FROM_ADDRESS)"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
mysql -e "ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1'; FLUSH PRIVILEGES;"

echo "== direktorij $HR_APP =="
mkdir -p "$HR_APP"
chown ubuntu:ubuntu "$HR_APP"

echo "== nginx HTTP vhost =="
cp "$HR_SRC/deploy/nginx/hr-saas.conf" /etc/nginx/sites-available/hr-saas
ln -sfn /etc/nginx/sites-available/hr-saas /etc/nginx/sites-enabled/hr-saas
nginx -t
systemctl reload nginx

echo "== Let's Encrypt $DOMAIN =="
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect -m noreply@superskytech.com || {
  echo "Certbot nije uspio. Provjeri DNS A zapis za $DOMAIN." >&2
  exit 1
}

echo "== supervisor queue =="
cp "$HR_SRC/deploy/supervisor/hr-saas.conf" /etc/supervisor/conf.d/hr-saas.conf
mkdir -p /var/log/supervisor
supervisorctl reread
supervisorctl update

echo "== cron scheduler =="
CRON_LINE="* * * * * cd ${HR_APP} && php artisan schedule:run >> /dev/null 2>&1"
if ! crontab -u ubuntu -l 2>/dev/null | grep -F "$HR_APP" >/dev/null; then
  (crontab -u ubuntu -l 2>/dev/null || true; echo "$CRON_LINE") | crontab -u ubuntu -
fi

WATCHDOG="/home/ubuntu/bin/queue-watchdog.sh"
if [[ -f "$WATCHDOG" ]] && ! grep -q "hr-saas" "$WATCHDOG"; then
  echo "start_worker ${HR_APP} hr-saas" >> "$WATCHDOG"
fi

echo "== početni .env =="
if [[ ! -f "$HR_APP/.env" ]]; then
  install -o ubuntu -g ubuntu -m 600 /dev/null "$HR_APP/.env"

  cat > "$HR_APP/.env" <<EOF
APP_NAME="HR SaaS"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${DOMAIN}
APP_FORCE_HTTPS=true
APP_LOCALE=hr
APP_FALLBACK_LOCALE=hr
APP_FAKER_LOCALE=hr_HR
LOG_CHANNEL=stack
LOG_LEVEL=error
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_FROM_ADDRESS=${MAIL_FROM}
MAIL_FROM_NAME="HR SaaS"
TRUSTED_PROXIES=*
ADMIN_SYNC_API_KEY=${SYNC_KEY}
ADMIN_CONSOLE_API_URL=https://admin.superskytech.com
ADMIN_CONSOLE_APPLICATION_SLUG=hr-saas
ADMIN_CONSOLE_WEBHOOK_SECRET=${WEBHOOK_SECRET}
ADMIN_CONSOLE_WEBHOOK_URL=https://admin.superskytech.com/api/webhooks/tenants/registered
ADMIN_CONSOLE_HTTP_VERIFY=true
ADMIN_CONSOLE_HTTP_RESOLVE_LOOPBACK=true
IDENTITY_CORE_AUTH_ENABLED=true
IDENTITY_CORE_API_URL=https://admin.superskytech.com
IDENTITY_CORE_API_VERSION=v1
IDENTITY_GOOGLE_OAUTH_ENABLED=false
IDENTITY_MICROSOFT_OAUTH_ENABLED=false
IDENTITY_UNIFIED_LOGIN_ENABLED=true
EOF
  chown ubuntu:ubuntu "$HR_APP/.env"
  chmod 644 "$HR_APP/.env"
fi

if [[ -f "$ADMIN_APP/.env" ]]; then
  if ! grep -q '^HR_SAAS_API_URL=' "$ADMIN_APP/.env"; then
    echo "HR_SAAS_API_URL=https://${DOMAIN}" >> "$ADMIN_APP/.env"
  else
    sed -i "s|^HR_SAAS_API_URL=.*|HR_SAAS_API_URL=https://${DOMAIN}|" "$ADMIN_APP/.env"
  fi
  if ! grep -q '^HR_SAAS_API_KEY=' "$ADMIN_APP/.env"; then
    echo "HR_SAAS_API_KEY=${SYNC_KEY}" >> "$ADMIN_APP/.env"
  fi
fi

echo "== gotovo. Dalje kao ubuntu: =="
echo "  bash /home/ubuntu/bin/deploy-hr-from-git.sh"
echo "  cd $HR_APP && php artisan key:generate --force && php artisan migrate --force"
echo "  cd $ADMIN_APP && php artisan config:clear && php artisan db:seed --class=HrApplicationSeeder --force"
echo "  curl -I https://${DOMAIN}/up"
