# Produkcijski deploy — HR SaaS

HR ide na **isti VPS** kao Udruga SaaS i Super-Admin konzola.

| Stavka | Vrijednost |
|---|---|
| VPS | `ubuntu@148.163.187.14:2222` |
| Domena | https://hr.superskytech.com |
| GitHub | `zhavrilu-collab/SuperSkyCrew` |
| Grana | `cursor/hr-saas-kadar-i-evidencija` |
| Izvor na VPS | `/home/ubuntu/src/hr-saas` |
| Aplikacija | `/var/www/hr-saas` |
| Baza | MySQL `hr_saas` (ne SQLite) |
| Konzola | https://admin.superskytech.com |
| Udruga | https://app.superskytech.com |

```bash
ssh -p 2222 -i $env:USERPROFILE\.ssh\supersky_vps ubuntu@148.163.187.14
bash /home/ubuntu/bin/deploy-hr-from-git.sh
```

## 1. Prije prvog go-livea

- [ ] DNS **A** zapis `hr.superskytech.com` → `148.163.187.14` (isto kao `app` i `admin`)
- [ ] Na GitHubu `SuperSkyCrew` VPS deploy ključ može `git clone` (ako padne, dodaj isti ključ kao na `Saas`)
- [ ] Konzola na `main` ima `HrSaasSyncDriver` i `HrApplicationSeeder` (commit + `deploy-from-git.sh master main`)
- [ ] **Ne** pokretati `AdminConsoleSeeder` na produkciji (demo tenanti)

## 2. Prvi provision (sudo, jednom)

Na VPS-u, nakon clonea izvora:

```bash
git clone git@github.com:zhavrilu-collab/SuperSkyCrew.git /home/ubuntu/src/hr-saas
sudo bash /home/ubuntu/src/hr-saas/deploy/provision-vps.sh
cp /home/ubuntu/src/hr-saas/deploy/deploy-from-git.sh /home/ubuntu/bin/deploy-hr-from-git.sh
chmod +x /home/ubuntu/bin/deploy-hr-from-git.sh
bash /home/ubuntu/bin/deploy-hr-from-git.sh
cd /var/www/hr-saas && php artisan key:generate --force
```

Skripta radi: MySQL bazu i usera, nginx + Let's Encrypt, supervisor queue, cron `schedule:run`, `.env` (SMTP i sync ključevi s udruga-saas), `HR_SAAS_API_*` u konzoli.

## 3. `.env` (produkcija)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hr.superskytech.com
APP_FORCE_HTTPS=true
DB_CONNECTION=mysql
DB_DATABASE=hr_saas
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
TRUSTED_PROXIES=*
ADMIN_CONSOLE_API_URL=https://admin.superskytech.com
ADMIN_CONSOLE_WEBHOOK_URL=https://admin.superskytech.com/api/webhooks/tenants/registered
ADMIN_CONSOLE_APPLICATION_SLUG=hr-saas
ADMIN_CONSOLE_HTTP_VERIFY=true
ADMIN_CONSOLE_HTTP_RESOLVE_LOOPBACK=true
IDENTITY_CORE_AUTH_ENABLED=true
IDENTITY_CORE_API_URL=https://admin.superskytech.com
IDENTITY_UNIFIED_LOGIN_ENABLED=true
```

`ADMIN_SYNC_API_KEY` = `HR_SAAS_API_KEY` na konzoli. Webhook secret isti kao `SAAS_WEBHOOK_SECRET`.

## 4. Konzola — registracija aplikacije

Nakon deploya konzole s HR driverom:

```bash
cd /var/www/admin-console
php artisan db:seed --class=HrApplicationSeeder --force
php artisan config:clear
```

Provjera u UI: `/admin/aplikacije` → **HR SaaS**, API URL `https://hr.superskytech.com`.

## 5. Redoviti deploy

```bash
bash /home/ubuntu/bin/deploy-hr-from-git.sh
```

Radi `migrate --force`, ne dira `.env` ni `storage/`.

## 6. Provjera

1. `curl -I https://hr.superskytech.com/up` → 200
2. `curl -H "Authorization: Bearer <ADMIN_SYNC_API_KEY>" https://hr.superskytech.com/api/admin/organizations`
3. Registracija organizacije na HR → tenant `pending` u konzoli
4. Odobrenje u konzoli → `active` na HR
5. Cron: `hr:reminders`, `hr:close-time`, `hr:retention-propose` (dnevno)

## 7. Sigurnost

- [ ] `APP_DEBUG=false`
- [ ] Demo seeder (`vlasnik@hr.test`) **nije** pokrenut
- [ ] 2FA na super-adminima konzole
- [ ] Backup MySQL `hr_saas` uz postojeći `backup.sh`
