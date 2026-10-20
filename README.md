# ORDO Records Management System

High-performance records management prototype: intake → classify → file,
with asynchronous OCR text extraction, role-based confidentiality scoping,
row-locked sequence allocation, and printable SVG QR code record labels.

## Requirements

| Component | Version |
|---|---|
| PHP | ^8.3 (with pdo_mysql) |
| Composer | 2.x |
| Node.js | ≥ 20.19 (22 LTS recommended) |
| Database | MySQL or MariaDB (tested on MariaDB 10.4.32) |

## Quickstart

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment configuration
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate

# 3. Database setup — schema, tenant/companies, classification scheme, sources
php artisan migrate --seed

# 4. Build frontend assets (Tailwind CSS 4 + Vite 8)
npm run build

# 5. Verify with the full test suite
php artisan test
```

One-command alternative: `composer setup` runs steps 1–4 but uses `migrate --force`
without seeding — follow with `php artisan migrate --seed`.

## Run It

```bash
php artisan dev               # all-in-one dev server (app + Vite)
# or
npm run dev & php artisan serve
```

## Queue Worker

Asynchronous OCR / PDF text extraction runs on the database queue. Jobs declare
`$tries = 3`, `$timeout = 120`, `$backoff = [10, 60, 300]` — a worker must allow
more than 120 seconds per job or it will kill reads mid-extraction:

```bash
php artisan queue:work --tries=3 --timeout=300
```

Keep it running while uploading intake items; with no worker, files sit at
`ocr_status=queued` indefinitely. In production, run the worker under a process
supervisor (systemd / Supervisor / Windows Service) — see
`docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md` §2.

## Seeded Data

| Item | Values |
|---|---|
| Tenant | `ordo` |
| Companies | `DEFAULT`, `SECOND` (second exists so tenant/company isolation is verifiable) |
| Classifications | 11 standard codes with indexes: `101` Client Master, `201` Personnel, `301` Entity & Ownership, `401` BIR & Tax, `601` Local Government, `701` Finance, `801` Banking, `901` Legal, `1001` Operations, `1101` Real Property, `2001` Correspondence |
| Sources | 10 standard sources: SEC, BIR, internal orgs, banks (RCBC, BDO), supplier, Other |
| Test user | `test@example.com` |

## Notes

- **No sign-in (prototype).** Every request resolves its role from `ORDO_DEFAULT_ROLE`
  in `.env` — values: `system_admin`, `records_manager`, `auditor`, `standard_user`.
  Set `records_manager` for the demo experience. Do not expose this instance beyond
  localhost until authentication lands — see the deployment checklist item #0.
- **No `storage:link` needed.** Files live on the protected private disk and stream
  through authorized `ordo.record` endpoints — never the public web directory.
- **Tests:** 730 tests (2,276 assertions) pass on MySQL/MariaDB. On SQLite, 727 pass
  and 3 `lockForUpdate()` tests skip by engine limitation. `composer test` clears
  config before running the suite.

## Confidentiality

Proprietary and confidential — internal ORDO project. Not for redistribution,
mirroring, or external publication.
