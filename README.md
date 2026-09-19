# HR SaaS

Modul za upravljanje ljudskim resursima na Core platformi (port **8004**). Specifikacija: [`docs/`](docs/01-vizija.md).

## Dev setup

```powershell
cd C:\Users\zoran.havriluk\hr
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate --seed
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8004
```

## Integracija s Core konzolom

- **Application slug:** `hr-saas`
- **Sync API:** `GET/PATCH /api/admin/organizations` (Bearer `ADMIN_SYNC_API_KEY`)
- **Core auth:** `IDENTITY_CORE_AUTH_ENABLED=true` → prijava preko `/api/v1/auth/login`

## Test prijava (lokalno bez Core-a)

- E-mail: `vlasnik@hr.test`
- Lozinka: `password`

U `.env` za lokalni login bez redirecta na konzolu: `IDENTITY_CORE_AUTH_ENABLED=false` i `IDENTITY_UNIFIED_LOGIN_ENABLED=false`.

## Demo tenant (sve uloge)

```powershell
C:\xampp\php\php.exe artisan hr:seed-demo
```

Na VPS-u: `cd /var/www/hr-saas && php artisan hr:seed-demo`

| Uloga | E-mail | Lozinka |
|---|---|---|
| Vlasnik | `vlasnik@hr.demo` | `DemoHr.2026` |
| HR | `hr@hr.demo` | `DemoHr.2026` |
| Voditelj | `voditelj@hr.demo` | `DemoHr.2026` |
| Računovodstvo | `knjigovo@hr.demo` | `DemoHr.2026` |
| Radnik | `radnik@hr.demo` | `DemoHr.2026` |

Organizacija: **Demo HR d.o.o.** (`/demo-hr`). Premium paket, kadar, šihterica (14.–18. 9. 2026), otvoreni GO za voditelja i prekovremeni.

## Produkcija

Isti VPS kao Udruga SaaS i konzola. Checklist: [`DEPLOY.md`](DEPLOY.md).

| | |
|---|---|
| URL | https://hr.superskytech.com |
| Put | `/var/www/hr-saas` |
| Deploy | `bash /home/ubuntu/bin/deploy-hr-from-git.sh` |
