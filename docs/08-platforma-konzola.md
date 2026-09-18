# 08 — Platforma i admin konzola

hr-saas je **četvrti proizvodni modul** uz udruga-saas, smb-saas i planirani reporting-saas. Uzorak integracije: smb-saas.

Konzola je control plane. Ovaj repo je data plane.

## Granica odgovornosti

| Konzola radi | hr-saas radi |
| --- | --- |
| Application `hr-saas` | Kadar, ugovori, dosje |
| Tenant CRUD, pending/active/suspended | Punch, šihterica, TimeEntry |
| Paketi, feature flagovi, limiti | GO, workflow, izvještaji |
| Stripe / uplata na račun | Clock PWA |
| Sync statusa i plana | Lokalni audit punchova i predaja inspekciji |
| Platformski audit (status, plan, sync) | ESS/MSS/HR UI |
| Core identity (login) | Autorizacija uloga unutar tenanta |

Konzola **nema** kartice radnika, OIB-e, punchove ni šihtericu.

Ista pravna osoba može biti tenant u udruga-saas i u hr-saas. U v1 se osobe **ne dijele**.

## Registracija aplikacije

U `multi-tenant console`:

- Slug: `hr-saas`
- Ime: HR SaaS (radni naziv; marketing ime kasnije)
- Dev `base_url`: `http://127.0.0.1:8004`
- Driver: `HrSaasSyncDriver` (isti skeleton kao `SmbSaasSyncDriver`)
- Unos u `config/saas_applications.php` (`HR_SAAS_API_URL`, `HR_SAAS_API_KEY`)
- `AdminConsoleSeeder` + seed paketa
- Feature katalog po slug-u (ne `isSmbApplication` if/else)
- `.env.example` i `DEPLOY.md`

## Sync ugovor (kopija SMB)

Modul → Core:

- `POST /api/webhooks/tenants/registered` nakon registracije organizacije

Core → Modul (Bearer `ADMIN_SYNC_API_KEY`):

- `GET /api/admin/organizations`
- `PATCH /api/admin/organizations/{id}` — `status`, `plan`, Stripe ID-ovi
- `DELETE /api/admin/organizations/{id}` — gašenje tenanta

Shared-DB: sve HR tablice imaju `organization_id` i global scope. Rute tenanta: `/{slug}/...`.

Identity: Core platform login, kao udruga/SMB. Clock PWA koristi isti identitet (token/session). Nema odvojenog HR IdP-a.

## Scaffold

1. Kopija smb-saas (registracija, webhook, org API, tim/pozivnice, layout, locale `hr`).
2. Port 8004.
3. Uloge zamijeniti HR ulogama (vlasnik, HR, računovodstvo, voditelj, radnik).
4. Zatim domena: people, punch, time entries, leave, workflow.

Lokalno SQLite kao ostali moduli; produkcija Postgres.

Back-office: Blade + Bootstrap 5 + Alpine.

Clock: zasebna PWA na istom API-ju (nije Blade tablica).

## Paketi i značajke

Nije TI model imenovane licence po operateru. Limit je na **broj aktivnih osoba u evidenciji** (`employee_limit` = zbroj employee + assigned + other_fo + contractor koji koriste clock; volonteri po `volunteer_module`).

Primjer flagova:

| Key | Tip | Značenje |
| --- | --- | --- |
| `employee_limit` | limit | Aktivne osobe |
| `clock_mobile` | bool | PWA |
| `clock_kiosk` | bool | Kiosk |
| `clock_geofence` | bool | Geofence |
| `shift_planning` | bool | Plan → šihterica |
| `workflow` | bool | Radni slijed (u v1 uključen) |
| `document_templates` | bool | Word/PDF akti |
| `inspection_export` | bool | Inspekcijski paket |
| `volunteer_module` | bool | Udruge |

Seed tri paketa (npr. basic / standard / premium) pri registraciji aplikacije. Točne cijene nisu dio ove specifikacije.

## Što sljedeće nakon ovog docs paketa

1. Scaffold Laravel iz smb-saas u ovaj repo.
2. Registrirati `hr-saas` u konzoli (driver, seeder, env).
3. Implementirati Fazu 0 + kadar + Clock API + PWA + šihtericu, tim redoslijedom.
