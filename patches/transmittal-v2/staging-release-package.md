# ORDO Transmittal V2 — Staging Release Package

**Package state:** Prepared for review; not an executable deployment plan and not staging-verified.
**Production state:** NOT AUTHORIZED.
**Local repository:** `transmittal-v2-integration`, `HEAD` `3b3d99ae57c565069a1aa0b8c22e361898bca039`; the candidate is a dirty working tree, not an immutable artifact.

## Release scope and file manifest

The complete candidate includes all preserved branch changes. Review the entire diff and every untracked path before freezing a release artifact. The V2 inclusion areas are:

- `app/Http/Controllers/TransmittalV2/`, `app/Http/Middleware/TransmittalV2/`, `app/Jobs/TransmittalV2/`, `app/Models/`, `app/Enums/`, `app/Policies/`, `app/Providers/`, and `app/Services/TransmittalV2/`.
- `config/transmittal_v2.php`, V2-only `routes/transmittal-v2.php`, Vite inputs/configuration, and `resources/views/transmittals/`, `resources/views/transmittal-v2/`, and `resources/views/emails/transmittal-v2/`.
- The 14 non-executable schema candidates under `patches/transmittal-v2/migrations/` and the route/security manifest.
- Isolated V1/V2/attachment tests, source contracts and fixtures under `tests/`.

The outbox-specific code manifest is `app/Services/TransmittalV2/TransmittalDispatchPayloadCipher.php`, `TransmittalDispatchProvider.php`, `MockTransmittalDispatchProvider.php`, `TransmittalDispatchResult.php`, `TransmittalEmailDispatchService.php`, `TransmittalEmailDispatchAttemptRecorder.php`, `TransmittalEmailDispatchResender.php`, `TransmittalManualResendAuthorization.php`, `TransmittalDispatchRecovery.php`, `TransmittalProviderEventContract.php`, `TransmittalProviderEventProcessor.php`, and `TransmittalV2DispatchWorker.php`; `app/Jobs/TransmittalV2/DispatchTransmittalEmail.php`; `app/Http/Controllers/TransmittalV2/TransmittalProviderEventController.php`; `app/Models/TransmittalEmailDispatch.php` and `TransmittalProviderEvent.php`; `app/Enums/TransmittalEmailDispatchAttemptEvent.php` and `TransmittalEmailDispatchState.php`; and the integrated `transmit`/`manualResend` methods in `app/Http/Controllers/TransmittalV2/TransmittalController.php`. This enumerates logical release inputs, not a frozen Git artifact; all untracked V2 files still require final full-tree review.

The minimum manual V2 flow does not require OCR or Smart Upload. Their routes and UI controls are separately gated and default off. PDF and ZIP compression/extraction remain unavailable. Preserve their historical expected hash assertions; the Smart Upload hash mismatch is unresolved.

The candidate also contains the earlier V1 workflow-boundary compatibility changes in `app/Http/Controllers/TransmittalController.php`, `app/Http/Controllers/TransmittalReceiptController.php`, `app/Http/Controllers/CorporateApprovalController.php`, `app/Models/Transmittal.php`, `app/Models/TransmittalItem.php`, and `app/Models/TransmittalReceipt.php`. These require explicit full-diff review; isolated V1 regressions passed. No unrelated module change is part of the intended scope.

## Migration order and prerequisites

All entries below are `.php.candidate` files outside Laravel migration discovery. None was executed or copied into `database/migrations/`.

1. `2026_11_09_000001_add_transmittal_v2_columns.php.candidate`
2. `2026_11_09_000002_create_transmittal_recipients_table.php.candidate`
3. `2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate`
4. `2026_11_09_000004_create_transmittal_documents_table.php.candidate`
5. `2026_11_09_000005_create_proof_of_transmissions_table.php.candidate`
6. `2026_11_09_000006_create_approval_authority_codes_table.php.candidate`
7. `2026_11_09_000007_create_transmittal_histories_table.php.candidate`
8. `2026_11_09_000008_create_external_acknowledgments_table.php.candidate`
9. `2026_11_09_000009_create_transmittal_upload_sessions_table.php.candidate`
10. `2026_11_09_000010_create_transmittal_workspace_items_table.php.candidate`
11. `2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate`
12. `2026_11_12_000001_create_transmittal_email_dispatches_table.php.candidate`
13. `2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php.candidate`
14. `2026_11_12_000003_create_transmittal_provider_events_table.php.candidate`

The sequence is a review order only. Before staging execution, inspect actual staging `migrations` history and schema, confirm each existing column/index/FK, database engine/version and DDL lock behavior, then rehearse the exact set against a protected disposable clone. Resolve the nullable workflow classification prerequisite before route activation. Historical NULL remains V1; no backfill is included. Preserve `transmittal_items`, existing V1 receipt numbers, records and stored files. The 2026-11-04 synchronization migration is explicitly excluded. Candidate rollback is preservation-oriented and may refuse drops; do not plan rollback as `migrate:rollback`.

Historical MySQL 9.6 evidence covered the earlier 13-candidate set only. It does not verify the added provider-event schema or the current complete candidate against staging. Current HTTP/database tests use isolated SQLite `:memory:` schemas constructed by test fixtures, not these migrations.

## Configuration inventory — names only

Do not copy values into this package. Configure and verify privately in staging:

- `ORDO_TRANSMITTAL_V2_ENABLED` — keep false until manual-flow staging gates pass.
- `ORDO_TRANSMITTAL_V2_DISPATCH_ENABLED` and `ORDO_TRANSMITTAL_V2_DISPATCH_PROVIDER` — keep dispatch disabled until approved staging acceptance. `mock` is local/testing-only; `brevo` requires the additional default-off `ORDO_TRANSMITTAL_V2_BREVO_OUTBOX_ENABLED` gate.
- `ORDO_TRANSMITTAL_V2_ENCRYPTION_CURRENT_KEY_ID` and `ORDO_TRANSMITTAL_V2_ENCRYPTION_KEYRING` — use a separately managed 32-byte V2 key encoded with Laravel-compatible `base64:` notation. Missing/invalid keys stop dispatch. Never use or rotate `APP_KEY` for this purpose.
- `ORDO_TRANSMITTAL_V2_OPERATIONS_MANAGER_USER_IDS` and `ORDO_TRANSMITTAL_V2_DESIGNATED_ADMIN_USER_IDS` — explicit user-ID designation only; verify authorized identities before any staging resend test. Existing role labels alone do not grant resend authority.
- `ORDO_TRANSMITTAL_V2_DISPATCH_MAX_ATTEMPTS`, `ORDO_TRANSMITTAL_V2_DISPATCH_MAX_ATTACHMENT_BYTES`, and `ORDO_TRANSMITTAL_V2_DISPATCH_LEASE_SECONDS` — review limits against staging worker memory/time and provider contract.
- `ORDO_TRANSMITTAL_V2_PROVIDER_EVENT_SECRET` and `ORDO_TRANSMITTAL_V2_PROVIDER_EVENT_CLOCK_SKEW_SECONDS` — mock event HMAC uses these settings; the Brevo branch compares a configured Bearer token in constant time. Provision the same protected token in Brevo's webhook configuration only after staging owner approval; never put it in a URL or source. Event ID, timestamp, tags, message ID and retry semantics still require controlled staging evidence.
- Existing Brevo settings reused by the adapter: `ORDO_TRANSMITTAL_BREVO_API_KEY`, `ORDO_TRANSMITTAL_BREVO_SENDER_EMAIL`, `ORDO_TRANSMITTAL_BREVO_SENDER_NAME`, and `ORDO_TRANSMITTAL_BREVO_TIMEOUT_SECONDS`. Verify privately in staging; no values are recorded here and no parallel credentials were introduced.
- `ORDO_TRANSMITTAL_SMART_UPLOAD_ENABLED` and `ORDO_TRANSMITTAL_OCR_ENABLED` — keep false; optional feature release is deferred.
- Existing `QUEUE_CONNECTION`, `FILESYSTEM_DISK`, `CACHE_STORE`, `SESSION_DRIVER`, `APP_KEY`, and provider settings — verify status privately. The repository `.env.example` uses `QUEUE_CONNECTION=sync`, `FILESYSTEM_DISK=local`, file cache and file sessions; these are not evidence of staging/production suitability.

Key recovery requires restoring the exact protected keyring version together with the matching database snapshot. Rotation must retain old key IDs while ciphertext uses them. No automatic payload deletion or retention interval is implemented; approve a separate retention policy before imposing one.

## Dispatch, queue and provider limits

- Dispatch payloads are envelope-encrypted with a random data key and an environment-managed V2 keyring. The mock adapter records fingerprints and attachment digests, not raw payloads. Attachment content is loaded only after ownership, size and digest validation for submission.
- The outbox persists stable dispatch and attempt evidence with uniqueness constraints; parent/recipient/dispatch row locks serialize claims. Provider acceptance is distinct from delivery. Unknown outcomes become terminal for automatic processing and require authenticated reconciliation or authorized manual resend.
- Only provider-confirmed safe failures receive bounded delayed retries. The queue job has one queue attempt, so Laravel does not automatically replay ambiguous submission. Lease recovery marks interrupted processing unknown and does not resubmit.
- Manual resend requires a designated Operations Manager or explicitly designated administrator, a reason and request UUID; it retains the original dispatch and rotates the recipient acknowledgment token. Tests use synthetic recipients/data.
- A V2 adapter now reuses the existing `BrevoTransmittalService` HTTP client, `/v3/smtp/email` endpoint, credentials and sender configuration. Its separate outbox gate defaults off. Tests used a fake provider response; no live Brevo request was made. The Brevo event branch uses the documented Bearer webhook authentication option and maps documented transactional events into sanitized evidence; provider-side webhook configuration and staging delivery/replay behavior remain unverified. No scheduled recovery command is included.
- Staging queue supervision, persistent queue backend, worker user/queue name, timeout/restart behavior, failed-job monitoring and a controlled recovery invocation must be agreed before worker activation. No worker or scheduler is enabled by this package.

## Backup, restore and rollback checklist

- [ ] Record staging identity, exact immutable code artifact, asset manifest, database engine/version and migration history; do not rely on moving branch names.
- [ ] Obtain consistent database and matching shared/private storage backups, including V1 attachments, proof, receipts, acknowledgments and in-progress upload data; protect configuration/key references separately.
- [ ] Restore into a separate isolated target, verify V1 values/relationships and representative file hashes, and rehearse the exact migration set there.
- [ ] Record restore owner, recovery time, acceptable outage/data loss and how to reconcile writes after the backup. Never restore over newer records without a separately approved reconciliation plan.
- [ ] Preserve the prior release and shared storage. A code rollback does not reverse migrations, email, uploads, approvals or acknowledgment activity. Leave additive schema in place unless a separately reviewed data-safe cleanup is approved.
- [ ] Exclude the irreversible `2026_11_04_142800_sync_transmittal_v1_and_v2_columns.php` transformation from this release.
- [ ] Confirm V1 route, permissions, records, item quantities/order, receipt numbers and attachment paths/hashes before and after staging rehearsal.

## Staging activation and verification gates

1. Freeze and review the complete local diff, resolve Smart Upload hash provenance, and create an immutable artifact only through separately authorized release steps. The current tree is dirty and no commit/artifact was created here.
2. Obtain and review the actual staging deployment script and hooks. The repository has no Forge script. `composer setup` runs `key:generate` and `migrate --force`; the import command can run `migrate:fresh` with its `--fresh` option and can force migration/seeding. Do not use these routines for an existing environment. The user-reported Forge script with multiple forced migrations remains unreviewed.
3. Confirm staging PHP/CLI extensions, Composer/npm lock compatibility, HTTPS, database engine/schema/history, shared private storage, sessions/cache/locks, queue backend/worker supervision, scheduler ownership and Vite manifest paths.
4. Keep V2 and dispatch gates off. Apply only reviewed additive candidates to an isolated staging clone after migration-specific approval and successful restore/rehearsal. Do not activate the V2 UI until V1 preservation and manual V2 authorization checks pass.
5. Verify manual V2 creation/edit, workspace-item snapshots, PDF/upload/private download, approval/rejection, receipt PDF and acknowledgment. Exercise unauthorized user/recipient access and old V1 records read-only. Keep Smart Upload/OCR off.
6. Do not test email sending on staging until the existing Brevo adapter has approved credentials/sender configuration, the secured Bearer webhook is provisioned and verified, key provisioning, queue/recovery controls, sender/inbox authorization, retention policy and separate send approval are ready. The provider adapter exists locally but remains disabled and unverified against Brevo; mock transport is not exposed in staging.
7. Review redacted application, queue, storage, web and PHP logs; check duplicate dispatches, unknown outcomes, attachment hashes, worker backlog and provider events. Stop and preserve evidence on any mismatch.

## Current gate result

| Gate | Result | Evidence / blocker |
|---|---|---|
| Local manual V2 implementation | **PASS** | Isolated V2 HTTP suite and prior manual PDF upload/private download acceptance; no live services. |
| Local mock outbox | **PASS, isolated** | Encrypted payload, mock acceptance, acknowledgment/PDF, bounded safe retry, unknown recovery/reconciliation, manual resend and HMAC replay tests. Not provider delivery acceptance. |
| Existing Brevo outbox adapter | **PASS, mocked only** | Reuses the existing transport/configuration and records provider acceptance separately from delivery; synthetic HTTP 201 and 429 tests passed. No live API call. |
| Current migration set | **BLOCKED** | 14 candidate files remain non-executable; no current full-set actual-engine rehearsal. |
| Smart Upload/OCR | **DEFERRED** | Optional, default off; Smart Upload integrity suite has one unresolved expected-hash mismatch; local OCR runtime guard rejects lexbor. |
| Staging | **NOT GO** | No authorized staging access/evidence, script, schema, restore, worker or provider verification. |
| Production | **NOT AUTHORIZED** | No Forge/database/provider access or deployment approval in this work. |

## Brevo adapter follow-up — local evidence, 2026-11-13

- Existing transport reuse is isolated to V2's `submitOutboxPayload` adapter method. The old direct-send methods and their call sites were not changed. No second API key, sender configuration or independent client was introduced.
- The current V2 gate is `ORDO_TRANSMITTAL_V2_BREVO_OUTBOX_ENABLED=false` by default. Do not enable it in any live environment without separately approved staging configuration and send tests.
- Final local V2 suite after Brevo event-name correction: **135 tests, 2,665 assertions passed**. Synthetic HTTP responses verified 201 acceptance, correlation tags/reference, and 429 unknown outcome without a retry. Mocked webhook cases verified `hard_bounce`, `invalid_email`, `blocked`, `soft_bounce`, and `deferred` state handling. The in-memory Guzzle handler prevents a network transport from being initialized, while the no-stray-request guard remains enabled.
- Brevo's public documentation describes Bearer-token authentication for secured webhooks and transactional events with message IDs, tags and event timestamps. Documented event labels include `delivered`, `hard_bounce`, `soft_bounce`, `invalid_email`, `blocked`, and `deferred`; the parser uses these exact labels. The adapter uses documented send tags and `X-Entity-Ref-ID` as correlation aids, not an idempotency guarantee. Exact deployed webhook configuration and event replay/ordering behavior must be proven in staging.
- The V2 route file is registered behind default-off middleware; migration files remain non-executable candidates, and queue/worker activation remains unconfigured.

## Final local release-preparation audit — 2026-11-13

### Repository and V1 boundary

- Local HEAD is `3b3d99ae57c565069a1aa0b8c22e361898bca039` on `transmittal-v2-integration`; the working tree contains preserved modified and untracked files and is not itself an immutable release.
- The reviewed tracked V1 changes are limited to the transmittal controllers/models, the shared corporate-approval resolver's transmittal branch, route/provider registration, package/Vite inputs, and the V1 transmittal view. No unrelated ORDO module source was identified in the current Git status.
- V1 query boundaries include NULL and exact `v1`; new V1 creation writes exact `v1`, and exact `v2` is used for V2. No global model scope was added. `items()`, the legacy receipt relation, and attachment relation remain present; V2 workspace items are separate.
- Deployment dependency: the V1 writer now writes `workflow_version`, but the required column is still only candidate 11 below. Do not release this code before the nullable column is included through a reviewed, ordered schema change. Existing NULL rows remain historical V1 and must not be backfilled.
- V2 HTTP/provider route registration is behind the default-off V2 middleware. Dispatch and Brevo outbox have independent default-off gates. Keep all gates off until the corresponding staging gate is approved.

### Fourteen-candidate migration dependency and risk review

These files remain `.php.candidate` under `patches/transmittal-v2/migrations`, outside Laravel discovery. Nothing was executed. Existing baseline migrations create the legacy `transmittals`, `transmittal_items`, `transmittal_receipts`, and attachments schema. The current checkout has no executable November 4 synchronization migration; the expressly prohibited synchronization/backfill migration remains excluded from this candidate set.

| Order | Candidate | Change/dependency | Preservation and rollback risk | Minimum V2 |
|---:|---|---|---|---|
| 1 | `2026_11_09_000001_add_transmittal_v2_columns.php.candidate` | Nullable V2 fields and user FKs on `transmittals`; requires existing `transmittals` and `users`. | No backfill; down is a no-op. Existing overlapping proposed columns cause a fail-closed exception for manual reconciliation. | Yes |
| 2 | `2026_11_09_000002_create_transmittal_recipients_table.php.candidate` | New recipient table; FK to `transmittals`. | New table only; down preserves it. | Yes |
| 3 | `2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate` | Nullable V2 receipt metadata and unique nullable recipient FK; requires candidates 2 and baseline receipts. | No historical row update; existing `receipt_no` and its unique index untouched; down preserves. Validate nullable-unique semantics and DDL behavior on the exact target engine. | Yes |
| 4 | `2026_11_09_000004_create_transmittal_documents_table.php.candidate` | New private-document metadata table; FK to parent and users. | New table only; down preserves. Storage path uniqueness and disk behavior need staging checks. | Yes |
| 5 | `2026_11_09_000005_create_proof_of_transmissions_table.php.candidate` | New proof table; depends on candidates 2 and 4, plus users. | New table only; down preserves. FKs do not prove same-parent ownership; application checks remain required. | Yes |
| 6 | `2026_11_09_000006_create_approval_authority_codes_table.php.candidate` | New authority-code table; parent/user FKs and unique parent/code. | New table only; down preserves. | Yes |
| 7 | `2026_11_09_000007_create_transmittal_histories_table.php.candidate` | New audit-history table; parent/user FKs. | New table only; down preserves. | Yes |
| 8 | `2026_11_09_000008_create_external_acknowledgments_table.php.candidate` | New acknowledgment evidence; depends on recipients and baseline/extended receipts. | New table only; down preserves. Parent consistency remains application-validated. | Yes |
| 9 | `2026_11_09_000009_create_transmittal_upload_sessions_table.php.candidate` | New upload-session table; UUID/session/token hashes and expiry; no FK. | New table only; down preserves. Confirm shared-cache/session assumptions separately. | Yes, for resumable upload |
| 10 | `2026_11_09_000010_create_transmittal_workspace_items_table.php.candidate` | Separate V2 workspace-item table; depends on candidate 4. | Does not transform or delete `transmittal_items`; down preserves. | Yes |
| 11 | `2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate` | Nullable two-character classifier on `transmittals`; prerequisite for explicit V1/V2 separation and V1 writer. | No backfill; `down()` deliberately throws because removing classification is unsafe. Schedule before deploying code that writes the column. | Yes |
| 12 | `2026_11_12_000001_create_transmittal_email_dispatches_table.php.candidate` | New outbox table; requires candidate 11 and recipients; unique dispatch UUID, dedupe fingerprint and resend UUID. | No parent/recipient FK yet; down throws. Candidate's table-level unique indexes need exact-engine rehearsal. | Yes, only for email-enabled scope |
| 13 | `2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php.candidate` | Append-only attempt table; requires dispatch table; unique event and attempt-event identity. | No FK yet; down throws. | Yes, only for email-enabled scope |
| 14 | `2026_11_12_000003_create_transmittal_provider_events_table.php.candidate` | Sanitized provider-event evidence; requires dispatch table; unique provider/event identity. | No FK yet; down throws. | Yes, only for email-enabled scope |

The proposed ordering is a dependency review, not authorization to convert, register, or execute files. Candidates 1–11 support manual V2; 12–14 support durable email evidence. All 14 require inspection against the actual staging schema and migration ledger, plus a restore-and-rehearse run on a disposable clone using the exact staging engine/version. MySQL 9.6 checks on an earlier 13-candidate set are historical evidence only. No actual staging schema, applied-migration state, index/constraint inventory, or DDL locking behavior is available locally. No data backfill is proposed. Do not run `migrate:rollback` as a recovery plan.

### Deployment hooks and staging gates

- No Forge deployment script, Forge hook, or CI deployment workflow was found in this repository scan. The user-reported live Forge script with forced migrations is therefore unreviewed and remains a hard blocker.
- Local Composer evidence: `composer setup` runs `key:generate` and `migrate --force`; `project-data:import --fresh` runs `migrate:fresh --force`; import also disables FK checks around imported data. These are unsuitable as an existing-environment deployment procedure. Do not use them in staging or production as a shortcut. Preserve existing `APP_KEY`.
- Before staging: obtain the redacted complete staging hook/script and verify triggers; establish staging identity and baseline; capture consistent database plus shared/private storage backup; restore to a separate target; verify representative V1 row relationships and attachment hashes; rehearse candidates 1–11 (and 12–14 only for approved email scope); then independently verify rollback/recovery and post-backup writes.
- For email staging, verify protected V2 key provisioning/recovery, existing Brevo sender/key configuration, authenticated webhook setup and replay handling, durable queue, worker supervision/restart/failed-job monitoring, manual-resend user IDs, and operator recovery. Keep dispatch off until those checks and send approval pass. No exactly-once guarantee is asserted.

## Updated local readiness determination

Local mocked tests remain the only evidence for V2 dispatch/provider behavior. The last reported local suites are V2 **135/2,665**, V1 **26/222**, attachments **12/104**, and outbox source contracts **118**; this audit made no application-code changes and did not rerun unchanged suites. One Smart Upload preservation hash remains unresolved and OCR is blocked by the local `lexbor` allowlist; both are optional and remain disabled for a manual-only scope. The current full migration set is not executable or target-engine verified, the dirty tree is not a frozen artifact, and Forge/staging facts are unavailable.

| Gate | Final result |
|---|---|
| Local source and mocked acceptance | **PASS on previously reported evidence**; no new runtime behavior is claimed by this read-only audit. |
| Reproducible local release snapshot | **Prepared locally** outside the repository. It contains the baseline tracked diff, every untracked file, and a SHA256SUMS manifest; no secret/generated paths were excluded. The exact temporary path and archive hash are recorded in the final session report. This is a review snapshot, not a signed, built, or deployable release artifact. |
| Migration package | **NO-GO** until current 14 candidates are reconciled and rehearsed against an authorized staging clone; deploy code only after candidate 11 is safely applied. |
| Staging | **NO-GO** until staging identity, script, backup/restore, actual schema, worker/provider configuration and authorized test window are verified. |
| Production | **NO-GO / NOT AUTHORIZED**; no production or Forge access/change is approved here. |
