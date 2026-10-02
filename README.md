# ORDO Laravel workspace

A working local Laravel baseline with database-backed projects, shared Blade navigation, scoped records, private attachments, saved reports, and server-side workflow checks. The latest static V10 SOW, V2 Review, and V2 NTP designs remain in the parent folder; see `../LARAVEL_MAPPING.md` for the precise feature mapping and remaining host-application integrations.

Requirements: PHP 8.4+, Composer 2, and PHP extensions required by Laravel, including PDO SQLite for the default database.

On Windows, run `START_ORDO.bat`. It creates a missing `.env`/SQLite database, installs missing dependencies, generates a missing app key, and migrates/seeds without replacing existing project records. It serves only on localhost.

Manual setup in PowerShell:

```powershell
Copy-Item .env.example .env
New-Item -ItemType File database/database.sqlite
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1
```

Use the first two commands only on initial setup. Open `http://127.0.0.1:8000`. For MySQL, set the `DB_*` variables before migrating.

```powershell
composer test
```

Tests use a separate in-memory SQLite database and cover page rendering, rejected premature execution, timer preservation/isolation, and the complete approval-to-closure flow.

Project data is stored in `projects.data` (JSON), with private uploads under `storage/app/private/projects`. Preferences use the session. Browser localStorage is not used for Laravel data or timers.

This baseline is intended for local use and integration. It does not include authentication, role authorization, external client messages, or the host Task Manager/Records Management services. Connect those before shared deployment; approval buttons currently record the local operator's actions.
