# STEP 189 — V2 Durable Email Dispatch Design and Safety Review

**Date:** 2026-11-12
**Scope:** Read-only application review; this document is a design proposal, not an implementation.
**Overall production readiness:** **NOT READY**

No application source, migration, configuration, or test file was changed for this step. No provider request, database operation, production/Forge access, deployment, or runtime test occurred. V2 remains inactive. This review uses the local working tree only; it does not establish the production deployment's code or configuration.

## 1. Current V2 email transmission sequence

Source reviewed: `app/Http/Controllers/TransmittalV2/TransmittalController.php::transmit`, `app/Services/TransmittalV2/BrevoTransmittalService.php`, `TransmittalRecipient`, `ProofOfTransmission`, `TransmittalHistory`, `config/transmittal_v2.php`, `config/queue.php`, and the local jobs-table migration.

1. The controller requires a V2 parent and authorizes `transmittal-v2.transmit`.
2. It obtains a cache lock keyed by transmittal ID (`block(10)`; lock TTL 360 seconds), then repeats the V2/authorization check.
3. It rejects an empty recipient list and preflights every unsent email address before sending any recipient. Already-sent recipients are skipped.
4. For each unsent recipient it generates a new 64-character acknowledgment token and calls `BrevoTransmittalService::sendAcknowledgmentRequest()` synchronously.
5. The service checks the V2 feature flag and Brevo config, validates the recipient and parent, renders the email, loads every V2 attachment, checks file readability and SHA-256 against metadata, base64-encodes attachments, then POSTs to `https://api.brevo.com/v3/smtp/email`. It sends a newly generated `X-Entity-Ref-ID` per call, but no stable `IdempotencyKey`. `messageId` is treated as optional and becomes an empty string if omitted.
6. Only after a successful HTTP response, the controller starts a database transaction, locks/reloads the V2 parent and recipient, checks authorization/state, then stores the token hash/expiry/`sent_at`, creates `ProofOfTransmission`, creates history, and transitions the parent from approved to sent. Those local writes are grouped atomically, but the provider side effect is outside that transaction.
7. The controller returns the count of locally recorded sends. Earlier recipients stay committed if a later recipient fails. A cache lock only coordinates callers if its cache backend is shared and lock-capable; that production property is unknown.

The current proof status `sent` therefore means the local code received a successful provider response and committed the local evidence; it does not mean mailbox delivery. Delivery/bounce event ingestion is not present in the reviewed send path. The separate direct-document service method is not the proposed minimum V2 transmission route and must not be used as an automatic fallback.

## 2. Failure-window analysis

| Window | Current outcome / risk | Required durable behavior |
|---|---|---|
| Before provider submission | No local send evidence is written. Validation/config/file errors stop the request. A client may retry; current code generates a new token and provider reference. | Persist one dispatch intent and its correlation/idempotency identifiers before any provider call. Validation failures should be visible and should not create a send attempt. |
| Provider accepts, process fails before local transaction | Email may have been accepted, but recipient token hash, `sent_at`, proof and history are absent. The recipient gets an email whose token may not validate. A repeat can send a second email. | In one local transaction, persist dispatch intent and the token hash/expiry before queueing. Worker must send the same encrypted token and stable provider key. Accepted response/event later projects to recipient/proof/history. |
| Database commit succeeds, response is lost | Local state says sent, so a browser retry should be idempotently answered as already queued/accepted; current cache lock is not durable across request retries. | Unique client request/dedupe key and durable dispatch ID make repeat submission return the existing dispatch without creating another logical send. |
| Network timeout/connection reset or HTTP response lost | Provider acceptance is unknown. Current generic exception reports failure; user retry can duplicate. A timeout does not prove rejection. | Mark `unknown`, retain correlation and attempt evidence, do not automatically create a new key or resend after the provider dedupe window. Reconcile by provider event/list evidence or require authorized manual resolution. |
| HTTP error | Some errors may be definitive pre-acceptance; 429/5xx semantics must be validated for this endpoint. Treating all exceptions alike risks duplicate or lost sends. | Classify only documented, observed definitive rejections as retryable/non-retryable. Any outcome that could follow acceptance is `unknown`, not ordinary retry. |
| Duplicate user submissions | Per-transmittal cache lock may serialize some requests but does not provide a durable dedupe record; backend may be process-local or otherwise not shared. | Unique logical dispatch/dedupe key and transactionally constrained recipient/purpose/generation. UI retries return existing dispatch status. |
| Multiple application instances | Cache lock configuration and cluster sharing are not verified. Distinct instances can send concurrently if lock is not shared; DB state is checked only after provider call. | Database-backed unique constraints plus atomic row claim/lease are the correctness boundary. Shared cache may reduce contention but is not the evidence source. Test with actual target database/cache in isolated staging. |
| Worker interruption | No V2 worker/outbox exists. If added naïvely, Laravel job retry can replay an HTTP side effect after a worker timeout/crash. | Outbox table is authoritative; a job is a wake-up hint. Expired `processing` leases become conservative `unknown` unless evidence proves submission did not start. Reconciler recovers missed jobs. |
| Attachment change/missing file before retry | Current request validates attachment bytes at send time; a later retry might see different/missing bytes. | Persist an immutable manifest of document IDs, paths, filenames, sizes and SHA-256 hashes. Revalidate all hashes immediately before each submission; block if content differs or is unavailable. Do not duplicate binary files into outbox rows. |

## 3. Brevo idempotency and reconciliation evidence

Official documentation reviewed on 2026-11-12:

- [Transactional send API](https://developers.brevo.com/reference/send-transac-email) documents custom headers and an `IdempotencyKey` example; the send response's `messageId` is optional in the schema.
- [Brevo idempotency guide](https://developers.brevo.com/docs/heterogenous-versions-batch-emails) says to use a UUID key, reuse it for the same request, and describes a 30-minute TTL. Its page is about batch emails, so it does not by itself settle the exact behavior of this application's one-recipient `/v3/smtp/email` request.
- The older [Brevo idempotency changelog](https://developers.brevo.com/changelog/2021/11/10) describes transactional email idempotency with a 15-minute TTL. This conflicts with the newer guide's 30-minute statement.
- Brevo's [transactional webhook event reference](https://developers.brevo.com/docs/transactional-webhooks) includes event, recipient, `message-id`, timestamp, tags, and custom header data. [Secure webhook calls](https://developers.brevo.com/docs/secured-webhooks) supports configured Basic/Bearer/custom-header authentication and discusses source-IP allowlisting. It does not make unvalidated payloads trustworthy by default.
- The [email events report API](https://developers.brevo.com/reference/get-email-event-report) can report events filtered by known message ID, email, time and other fields; the [transactional email list API](https://developers.brevo.com/reference/get-transac-emails-list) supports lookup by recipient or known message ID for a limited lookback. These can help reconcile a known message/correlation but are not proof that a timed-out unknown request was not accepted.

**Conclusion:** Brevo documents a supported idempotency header/key, so it is a candidate safeguard. It is bounded deduplication, not exactly-once delivery. The TTL conflict and single-message behavior must be confirmed with Brevo and proven in staging before automated retries rely on it. Use one stable UUID for each logical dispatch and exact-payload retry; never generate a new key merely because the client or worker timed out. Persist Brevo's `messageId` whenever returned, but do not require it to exist. Add a stable dispatch UUID in supported message tags/custom correlation data and verify that it is returned in webhook/event records. Reconcile unknown sends with known `messageId` first, then dispatch correlation plus recipient/time window; an ambiguous search is not a negative result.

Webhook endpoint should require Brevo-configured authentication (prefer Bearer/custom header or Basic credential supported by Brevo), TLS, request-size limits, rate controls, and replay/deduplication using a durable provider event key. Verify whether Brevo supplies a cryptographic signature before assuming signature validation is available. Do not place webhook credentials in a URL where proxies/logs expose them unless that is the chosen, secured Brevo-supported method. Do not log API keys, raw tokens, full payloads, or attachment contents.

## 4. Recommended architecture

Use a **transactional outbox as the source of truth**, plus an explicit V2-only worker. Do not simply move the current provider call into a retrying Laravel job: the queue by itself cannot atomically coordinate the database and Brevo.

### Request transaction

Under the V2 parent and recipient locks, recheck workflow, sendable state, ownership/authorization, recipient email, current token/acknowledgment state and attachment manifest. Generate the token once. In a single DB transaction:

1. Insert one outbox dispatch for `(transmittal, recipient, purpose, generation)` with a unique dispatch UUID and stable Brevo idempotency UUID.
2. Store the acknowledgment token hash/expiry on the recipient so any accepted email points to a valid token. Store the raw token only as authenticated ciphertext in a restricted outbox payload for delivery/retry; never store it plaintext or in queue payload/logs. Keep the ciphertext only until token acknowledgment/expiry plus the approved recovery window, then purge it under an audited retention rule.
3. Snapshot immutable recipient/template inputs and document attachment manifest (document ID, storage disk/path reference, display name, byte size, SHA-256, plus a canonical payload fingerprint). Avoid copying base64 attachments into the database.
4. Create audit/history that a dispatch was queued, clearly distinct from provider acceptance and delivery.
5. Commit; only after commit, enqueue a job containing the dispatch UUID. If enqueue fails, a poller must still find the committed outbox row.

Do not set recipient `sent_at`, mark a proof as `sent`, or transition the parent to sent merely because an intent was queued. Preserve current V1 semantics and ensure V2's UI distinguishes queued, accepted by provider, delivered, failed, and unknown. Define whether parent status is sent when all intended recipients are provider-accepted, and document that meaning.

### Worker and provider submission

The worker accepts only a dispatch UUID, loads current V2 parent/recipient and validates links, checks current dispatch state, validates attachment manifest/file hashes, and atomically claims a due row with a lease. It commits a separate append-only attempt row as `submitting` immediately before the external request. Then it submits the immutable request using the same stable `IdempotencyKey` and stable correlation identifier for that logical dispatch. On a clear acceptance, record `accepted`, response metadata/message ID, `submitted_at`, proof, history and relevant recipient/parent projection atomically. Provider acceptance is not delivery.

Concurrent correctness must rely on target-database constraints/transactions, not a cache lock alone. A lease prevents ordinary duplicate worker work, while the unique dispatch key and provider key bound duplicates. Do not assume `SKIP LOCKED` or lock semantics before the production database engine/version is independently identified and reproduced in isolated testing.

Laravel queue `after_commit` helps avoid dispatching a job before its transaction commits, but is not a complete outbox guarantee. The committed outbox poller repairs a lost queue wake-up. Queue job retries are only for re-reading state/reconciliation; provider resubmission is controlled by dispatch state and provider idempotency policy, not blindly by Laravel's `tries` setting.

### Proposed logical tables/fields

Names are proposals only; no schema change is authorized by this design step.

**`transmittal_email_dispatches`** — one durable logical email request:

- `id` (PK), `dispatch_uuid` (unique UUID), `transmittal_id`, `recipient_id`, `purpose`, `generation`.
- `requested_by`, `request_dedupe_key` (unique within the intended actor/recipient operation), `provider`.
- `state`, `attempt_count`, `next_attempt_at`, `lease_owner`, `lease_expires_at`.
- `provider_idempotency_key` (unique UUID), `provider_message_id` (nullable; index/uniqueness only if provider semantics and scope support it), `provider_accepted_at`, `delivered_at` projection if used.
- `template_version`, recipient/payload snapshot or immutable version references, `payload_sha256`, attachment-manifest JSON/reference and manifest hash.
- encrypted bearer-token/email payload field, encryption/key version, `uncertain_since`, sanitized `last_error_code`, `created_at`, `updated_at`, `resolved_by`, `resolution_reason`, `resolved_at`.
- restrictive FKs to V2 parent/recipient and uniqueness to prevent duplicate logical submissions. No cascade delete; never delete an outbox dispatch as ordinary cleanup.

**`transmittal_email_dispatch_attempts`** — append-only provider attempt ledger:

- `id`, `dispatch_id`, `attempt_no` (unique per dispatch), `started_at`, `finished_at`, `outcome` (`submitting`, `accepted`, `definitive_rejection`, `unknown`), sanitized HTTP status/provider error code, nullable provider message ID, elapsed time, request fingerprint and worker identifier.
- Do not store API credentials, raw provider responses, raw tokens, complete email bodies, or base64 attachments. Retain enough sanitized facts for audit and recovery.

**`transmittal_email_provider_events`** — append-only webhook/reconciliation evidence:

- `id`, provider/account scope, provider event ID or a stable composite idempotency fingerprint (unique), dispatch UUID/correlation, message ID, normalized event, event time, received time, payload hash, verification result and minimal sanitized metadata.
- Restrictive dispatch relation where resolvable. Unknown/unmatched events go to a separately monitored quarantine path and are not allowed to mutate transmittal data.

Keep current `ProofOfTransmission` and `TransmittalHistory` as domain-facing evidence, linked to the dispatch/attempt where practical. Confirm the candidate schema can add those links additively; do not rewrite existing V1 proof/history. Existing `jobs`, `job_batches`, and `failed_jobs` tables are defined in the local initial migration, but their production migration history and actual availability are not verified. The outbox remains necessary even if the database queue exists.

## 5. State machine, retry, reconciliation, and recovery

Suggested states:

`queued` → `processing` → `accepted` → provider events (`delivered`, `deferred`, `bounced`, `blocked`, etc.); `processing` can become `retry_wait` only on an established definitive transient rejection; `processing` can become `failed` for definitive permanent rejection or `blocked_attachment`; any uncertain request becomes `unknown`; operator can resolve it to `accepted`, `not_accepted`, or `cancelled` with actor, evidence and reason. State transitions and webhook projections are append-audited and must tolerate duplicate/out-of-order events.

Rules:

- **Retry:** retry only documented/validated definitive transient rejections (for example, a provider response proven not to have accepted the message). Use bounded attempts and exponential backoff with jitter and provider rate limits; exact retry schedule and maximum need staging/provider approval. Reuse the same idempotency key and identical payload for retries of the same dispatch. An HTTP status class alone is not enough to classify ambiguous outcomes.
- **Timeout/connection loss/worker crash after submission begins:** `unknown`, not `failed` and not a fresh send. Retry the exact request with the exact key only inside a provider TTL that has been confirmed for this endpoint, or reconcile first. Never retry blindly after that window.
- **Accepted response:** record provider acceptance even if `messageId` is absent; do not resubmit. If a message ID is returned, retain it. Delivery and bounce are separate later evidence.
- **Duplicate click/browser retry:** return existing dispatch state by a unique dedupe key; do not mint a new token or dispatch.
- **Manual reconciliation:** authorized staff compare known message ID, stable dispatch correlation, recipient, event timestamps and available provider events. Record evidence/source and reviewer. A search with no match is not proof of non-acceptance. Unknown state remains held until adequate evidence or explicit risk-approved resend.
- **Manual resend:** require a distinct permission and an explicit actor, reason, and duplicate-delivery warning. Only create a new generation after reconciliation and authorization. Preserve prior attempts/proofs. Do not revoke or rotate a valid acknowledgment token as an incidental resend action. For a deliberate token reissue, explicitly invalidate the old token and audit both changes.
- **Expired leases:** recovery process classifies a row as safe-to-retry only if evidence proves no provider call began; otherwise mark unknown and reconcile. Reconciler polls queued/due rows and expired leases, and alerts on old queued/unknown rows. It never “fixes” unknown rows by generating a new key.
- **Events:** authenticate incoming webhooks, verify event source/configured credential, persist deduplicated event before returning success, then project state transactionally. Keep provider event times and receive times; late `deferred` events must not overwrite a later `delivered` event. Keep raw payload only if approved retention/security needs require it; default to a payload hash plus normalized fields.

## 6. Attachments, audit, privacy, and permissions

- At enqueue, snapshot V2 document associations and expected hashes. Immediately before submission, require every referenced file to exist/read and match its expected hash; a mismatch blocks the dispatch without sending. Reconcile the correct filesystem/disk and release/shared-storage paths in staging. No V1 attachment path is to be migrated or rewritten.
- Reference the canonical existing V2 document storage object; do not duplicate or delete file data when dispatches are retried or cleaned up. Keep retention of documents consistent with existing legal/business policy. Keep the encrypted token only for the bounded recovery need described above; purge ciphertext after acknowledgment/expiry only with an auditable policy. Do not put attachment contents into outbox rows or logs.
- Only existing authorized V2 transmit admins may create dispatches. Add a narrower, separately reviewed reconciliation/resend permission; do not grant it automatically to users or alter V1 roles. Recipient acknowledgment continues through its scoped bearer token and existing expiry/revocation rules.
- Treat the outbox, encrypted token, recipient email, provider message ID, attachment paths and event data as sensitive. Limit model/API serialization, logs, queue payloads, backups, support access and retention. Audit every manual resend, unknown resolution, webhook rejection and token reissue.

## 7. Laravel 12 and current deployment compatibility

Local repository evidence:

- `composer.json` requires Laravel `^12.0` (the installed/runtime version is not independently verified here). Native Laravel queue jobs and database transactions can support a V2-only worker without a new vendor dependency.
- `config/queue.php` defaults to `QUEUE_CONNECTION=sync`; the local `.env.example` also documents `sync`. The database/Redis queue configurations show `after_commit => false`; dispatch code would need explicit after-commit behavior, while the outbox poller remains the recovery path.
- `database/migrations/0001_01_01_000002_create_jobs_table.php` defines `jobs`, `job_batches`, `failed_jobs`. This is repository source, not proof these tables exist in the deployed schema.
- The local Composer development script uses `queue:listen --tries=1`; this is not production worker supervision evidence.
- A repository search found no tracked Forge deployment script or supervisor/worker configuration. Deployment script, current branch/commit, migration history, shared-storage mount, queue connection, cache topology and supervisor state are therefore **not independently verified**. Do not infer that Forge deploys or runs workers from local config.

Keep the V1 sender synchronous and unchanged in the minimum V2 outbox implementation. Do not change global queue defaults for unrelated modules. Use an explicitly named V2 job/queue and outbox poller. During a future release, schema changes must be additive and ordered so the old release can continue during a staged rollout; rollback of application code must not delete outbox rows, attempts, provider events, proof, history, or documents. A code rollback cannot retract email already accepted by Brevo.

Forge prerequisites to verify **later with separate authorization**, without assumptions: actual deployed branch/commit and release script; migration command order and forced options; queue connection and shared database; a supervised worker process with correct PHP binary/version, site/release path, user, working directory, queue names and restart-on-deploy behavior; graceful worker shutdown; durable shared cache only as an optimization; storage shared path visible to workers; scheduler/reconciler cadence; failed-job/outbox alerting; log/secret redaction; backups for DB and shared storage; and a documented pause/resume/recovery runbook. After deploying compatible schema/code, restart long-lived workers so they load the intended release. No Forge setting or service was inspected or changed in this step.

## 8. What can be tested where

**Isolated local tests (after approved implementation, with a verified disposable database only):** schema constraints against the same engine/version; outbox creation/token hash in one transaction; rollback behavior; duplicate request uniqueness; simultaneous dispatch inserts/claims; multi-worker lease recovery; exact stable provider header and immutable payload across retry; fake HTTP 2xx, missing message ID, definitive 4xx/429/5xx and timeout; accepted-response-before-DB-failure simulation; worker death around submission; webhook authentication, replay/deduplication, out-of-order events; attachment missing/hash mismatch; no queue/provider activity after rollback; no V1 table/attachment/behavior changes. Fakes must not call Brevo. No tests were run in Step 189.

**Staging evidence required:** actual target database locking/unique-index behavior; multiple Forge-like app instances and shared cache/queue/storage; queue supervisor/release restart; real configured sender identity and controlled recipient; Brevo's exact single-email idempotency semantics, TTL and duplicate response; correlation tags visible in webhooks/events; webhook auth/retry behavior; controlled network uncertainty (proxy drops response after provider acceptance); API lookup retention and matching; rate limits; attachment size limits and shared-file access; rollback with dispatch rows retained. Send only staging mail to pre-approved controlled recipients after explicit approval.

**Production-only checks after a separately authorized release:** confirm deployed commit/config/migration ledger, health/queue state and attachment availability; inspect only approved, redacted logs and outbox/event metrics; do not infer successful delivery from queue completion. Real production mail, data, Forge, and migrations remain outside this step.

## 9. Required decisions and approval gates

1. Approve the semantics of `sent_at`, proof `sent`, and parent `sent` versus outbox queued/provider accepted/delivered.
2. Approve the proposed outbox/attempt/event schema, uniqueness scopes, token ciphertext/key-retention policy, and audit/retention periods; reconcile them with the ten existing V2 candidates without editing them in this step.
3. Confirm target database engine/version and supported lock/index behavior from authorized staging evidence.
4. Obtain written/authoritative confirmation from Brevo for single-message `IdempotencyKey`, TTL, exact duplicate response, message-ID behavior, correlation propagation, webhook authentication/delivery/retry semantics, event and message lookup windows, and rate limits. Then prove with controlled staging tests.
5. Approve manual resend/reconciliation role boundaries and duplicate-delivery warnings; no automatic role grants.
6. Independently verify Forge deployment script, worker supervisor, shared cache/queue/storage, release restart, backup and restore process before proposing production steps.
7. Approve a staged rollout and recovery plan. No V2 route/provider activation until isolated acceptance, staging evidence, migration review, backup/restore rehearsal, and V1 regression sign-off are complete.

## 10. Sources

- [Brevo transactional email API reference](https://developers.brevo.com/reference/send-transac-email)
- [Brevo idempotency guide](https://developers.brevo.com/docs/heterogenous-versions-batch-emails)
- [Brevo idempotency changelog](https://developers.brevo.com/changelog/2021/11/10)
- [Brevo transactional webhook events](https://developers.brevo.com/docs/transactional-webhooks)
- [Brevo secure webhook calls](https://developers.brevo.com/docs/secured-webhooks)
- [Brevo email event report](https://developers.brevo.com/reference/get-email-event-report)
- [Brevo transactional email list](https://developers.brevo.com/reference/get-transac-emails-list)
- [Laravel 12 queues](https://laravel.com/docs/12.x/queues)
