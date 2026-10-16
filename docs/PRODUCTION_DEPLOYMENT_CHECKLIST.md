# ORDO Records Management — Production Deployment Checklist

Every item below is stated as something to **verify**, not to assume. Where the current
local configuration is wrong for production, the reason is given rather than just the
correct value.

The application is at `v2.0.0`. Test state at that tag: **730 tests, 730 passing,
2276 assertions** on MariaDB, and **727 passing / 3 skipped / 2270 assertions** on SQLite
(the three skips are `lockForUpdate()` assertions that SQLite's grammar cannot express).

---

## 0. Blocking precondition — read before anything else

**The application has no authentication.** `bootstrap/app.php` registers no `auth`
middleware. Every request therefore resolves a role from `config('ordo.default_role')`,
which the current `.env` sets to `system_admin`.

The practical effect: **anyone who can reach the URL is a System Admin.** They can edit
the classification scheme, rewrite record numbering, apply legal holds, and read
confidential records. None of this is a bug in the policy layer — the policies are
correct and tested. They are simply consulting a fallback that currently grants
everything.

Two settings, and they are the whole problem:

| Setting | Now | Effect |
|---|---|---|
| `ORDO_DEFAULT_ROLE=system_admin` | `system_admin` | Anonymous = full rights |
| Auth middleware | absent | Nothing to stop it |

Setting `ORDO_DEFAULT_ROLE=standard_user` locks the app down, but then nobody can use
it either, because there is no sign-in to grant a real role.

**Do not deploy to a network this application can be reached from until authentication
lands.** It is item 1 on the v2.1 roadmap for this reason.

---

## 1. Database & Migrations

- [ ] **Confirm the engine.** `.env` declares `DB_CONNECTION=mysql`, but the local server
      is **MariaDB 10.4.32**. Both are supported; they are not identical. Confirm which
      one production runs and test against *that*.
- [ ] **Run migrations.** `php artisan migrate --force`, then `php artisan migrate:status`
      and confirm **no rows read `Pending`**.
- [ ] **Run the full suite against the production engine**, not only SQLite:
      ```
      $env:DB_CONNECTION='mysql'; $env:DB_DATABASE='ordo_records_management_test'
      php vendor/bin/phpunit
      ```
      Expect **730 passing, 0 skipped**. On SQLite three row-lock tests skip.
- [ ] **Row locking (`lockForUpdate()`).** Verified working on MariaDB 10.4 by
      `RecordNumberingAtomicityTest` (7 tests, 0 skips) — it asserts the lock is taken on
      the `companies` row *before* the sequence counter is read. This is the guarantee
      behind sequential Record IDs and must pass on the real engine.
- [ ] **Confirm transaction isolation is at least READ COMMITTED.** The filing service
      relies on `lockForUpdate()` to serialise the counter; `REPEATABLE READ` (MySQL's
      default) is fine and is what was tested, but confirm no session-level override.
- [ ] **Confirm the unique indexes exist** — they are the authoritative guarantee, ahead
      of any lock:
      - `records_tenant_company_doc_ref_unique` on `(tenant_id, company_id, document_reference)`
      - `records_tenant_company_record_number_unique` on `(tenant_id, company_id, record_number)`
      - `record_files_tenant_company_intake_index` for the queue

## 2. Background Queue Worker

- [ ] **Install a process supervisor** (systemd unit, Supervisor, or Windows Service).
      `queue:work` is not a daemon to be left in a terminal — it exits when the shell
      does.
- [ ] **Run the worker under a production queue driver.** Current `.env` is
      `QUEUE_CONNECTION=database`. That works and is proven (see §6), but polling a table
      adds latency. For Redis: set `QUEUE_CONNECTION=redis` and confirm `REDIS_HOST`,
      `REDIS_PORT`, `REDIS_PASSWORD` point at a real instance — `REDIS_PASSWORD` is
      currently set but Redis is unused, because both queue and cache are on `database`.
- [ ] **Supervisor config must include `--tries`, `--timeout` and `--sleep`.** The job
      declares `$tries = 3`, `$backoff = [10, 60, 300]` and `$timeout = 120`, so the worker
      must allow **more than 120 seconds** per job or it will kill reads mid-extraction.
- [ ] **Ensure at least one worker is running before accepting uploads.** With the
      database driver and no worker, files sit at `ocr_status=queued` indefinitely.
- [ ] **Monitor `failed_jobs`.** The job settles failures into `Needs Review` by design and
      does not retry forever, but a rising count means the extractor or storage is
      misconfigured.

### Deployment hazard — stale queue payloads
Queued jobs are serialised PHP objects. **A worker running new code cannot process a job
queued by old code if the class's properties changed.** This was observed live: 14 jobs
queued before `ProcessIntakeOcr` gained `$recordFileId` failed with
`Typed property … $recordFileId must not be accessed before initialization`.

- [ ] **Drain or flush the queue before deploying** (`php artisan queue:flush`), or deploy
      workers in step with the code that queued the work.
- [ ] **Add `queue:flush` to the deploy runbook**, not just this checklist.

## 3. Assets & Storage

- [ ] `npm ci && npm run build` — assets are **not** committed (`public/build` is
      gitignored), so a deploy that skips this serves a stale bundle. The JS is served via
      `@vite`; a missing manifest is a hard 500.
- [ ] Verify the build is warning-free. A **syntax error in `resources/js/app.js` will not
      fail the PHP suite at all** — it was caught only by running the build.
- [ ] `php artisan storage:link` — already present locally as a junction to
      `storage/app/public`. Idempotent; run it anyway on a fresh host and verify.
- [ ] `storage/` and `bootstrap/cache/` writable by the web user.
- [ ] `php artisan config:cache` **and** `php artisan route:cache`. Note: route caching is
      safe here — all routes are closures-free, but the six Setup tabs use a `whereIn` on
      the tab parameter, which is cache-safe.
- [ ] `php artisan optimize` clears old caches before rebuilding them.

## 4. Environment Variables

`.env` is gitignored and was verified never tracked. No real secrets are committed;
`.env.example` contains empty placeholders only. Audit the deployed `.env`:

| Key | Required for production | Current local value |
|---|---|---|
| `APP_ENV` | `production` | `local` ⚠ |
| `APP_DEBUG` | `false` | `true` ⚠ **stack traces leak to users** |
| `APP_URL` | real origin | `http://localhost:8000` ⚠ |
| `APP_KEY` | real, from `php artisan key:generate` | set (local) |
| `DB_USERNAME` | a least-privilege role | `root` ⚠ |
| `DB_PASSWORD` | set | **empty** ⚠ |
| `DB_HOST` / `DB_PORT` | real host | `127.0.0.1:3306` |
| `SESSION_ENCRYPT` | `true` | `false` ⚠ |
| `SESSION_DRIVER` | `database` or `redis` | `database` ✅ |
| `CACHE_STORE` | `redis` preferred at volume | `database` |
| `QUEUE_CONNECTION` | `redis` or `database` | `database` ✅ |
| `FILESYSTEM_DISK` | `local` or `s3` | `local` ✅ |
| `AWS_*` | only if S3 is used | all empty ✅ (unused) |
| `MAIL_MAILER` | real transport in production | `log` |
| `ORDO_DEFAULT_ROLE` | **see §0** | `system_admin` ⚠⚠ |

- [ ] **File-size ceilings cannot be set here.** `max:20480` (intake, 20 MB) and
      `max:51200` (supporting/versions, 50 MB) are hard-coded in the FormRequests. There
      is no `.env` key. Changing them is a code change — roadmap item 5.
- [ ] Confirm no `.env` is deployed inside the release artefact.

## 5. Post-Deploy Verification

- [ ] Load the register. **Search specifically for a term containing `%`.** This is the
      regression that mattered: `ESCAPE '\'` is a syntax error on MySQL/MariaDB and the
      register search returned 500 on every MySQL connection while the suite passed on
      SQLite. Fixed at v2.0.0; `RecordSearchEscapeTest` (6 tests) now pins the clause
      shape so it cannot silently return.
- [ ] Open a confidential record as a Standard User and confirm 404, not 403, on
      `/records/{id}/preview`, `/records/{id}/label`, and the Audit Log page and both of
      its exports.
- [ ] Print a Record Label and scan the QR. It must resolve to the record preview, and
      must **not** resolve for a record the scanning user's role may not see.
- [ ] Upload a PDF through `/records/intake/upload`, then confirm on the worker host that
      `ocr_text` is populated and `ocr_status` reaches `ready`.
- [ ] Upload a **scanned** PDF (no text layer) and confirm it lands in `needs_review` with
      a reason — not `ready`. The pre-v2 stub reported `ready` for every file while writing
      an error message.
- [ ] Confirm `queue:failed` is empty after the first hour.

## 6. Verified Locally

Recorded so the next person does not have to rediscover it:

- **Live worker, end to end.** A real PDF was uploaded through the ordinary intake path
  (so the job was genuinely queued, not fabricated), then processed with
  `php artisan queue:work --once` on MariaDB 10.4.32:
  - `RUNNING` → `DONE` in **55.62ms**
  - `ocr_status` → `ready`, `ocr_error` → `null`
  - `ocr_text` → `Retention period is ninety days from the date of filing.` (exact match)
  - Stored bytes intact; queue drained to 0; no other file touched
- **Full suite on MariaDB 10.4.32: 730 passed, 2276 assertions, 0 skipped.**
- **Full suite on SQLite: 727 passed, 3 skipped, 2270 assertions.**
- `public/storage` junction verified present.
- No pending migrations.