# HR SaaS — agenti

- PHP 8.2 · Laravel 12 · Blade · Bootstrap 5.3 CDN · vanilla JS. Dev: `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8004`
- UI i poruke na **hrvatskom**. Tenant path `/{slug}/...`
- **Ne commitaj** bez eksplicitnog zahtjeva korisnika
- Dizajn: obvezna pravila u `.cursor/rules/ui-design.mdc` i `.cursor/rules/ui-conventions.mdc` — tirkizna zadana paleta, Fledge shell (svijetli lijevi izbornik + tamni SuperSkyCrew brand + topbar), Postavke/Izgled. Ne izmišljaj paletu ni chrome.
- Izvorni tokeni i komponente: `resources/views/partials/platform-styles.blade.php`, tenant override `partials/organization-theme.blade.php`, palete `app/Support/OrganizationThemes.php`
