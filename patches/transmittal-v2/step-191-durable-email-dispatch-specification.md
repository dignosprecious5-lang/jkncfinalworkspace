# STEP 191 — V2 Durable Email Dispatch Implementation Specification

**Date:** 2026-11-12
**Scope:** V2-only specification based on the STEP 189 design and STEP 190 gate review.
**Readiness:** **NOT READY — implementation remains approval-gated.**

This is a documentation-only artifact. It does not authorize code changes, schema execution, queue/worker activation, V2 activation, provider calls, production or Forge access, or deployment. Preserve the existing V1 sender and historical data. All recommendations below require the approval gates stated in this specification before implementation.

## 1. Evidence and scope

### Verified from local source only

- V2 `TransmittalController::transmit` authorizes a V2 parent, takes a cache lock, preflights unsent recipients, calls Brevo synchronously, and only then commits the recipient token hash/expiry, `sent_at`, proof, history, and parent status in a database transaction.
- `BrevoTransmittalService` sends a fresh `X-Entity-Ref-ID` per request and does not set an `IdempotencyKey`. It validates current attachment readability and SHA-256 before attaching. The returned `messageId` can be empty.
- `TransmittalRecipient` has one acknowledgment token hash/expiry/revocation set and a `sent_at` timestamp. `ProofOfTransmission` is immutable for ownership identity and has a nullable provider message ID. These structures do not represent durable dispatch attempts or delivery events.
- No V2 outbox, dispatch-attempt, provider-event model/table, V2 email queue job, or provider-event ingestion path was found in the reviewed local source.
- The ten existing V2 `.candidate` migrations do not define those dispatch/attempt/event tables. The nullable workflow-version migration is retained as a `.candidate` outside executable migration discovery; its production application status is unknown.
- `config/queue.php` and `.env.example` default to `sync`; database/Redis connections specify `after_commit => false`. The local initial migration defines the Laravel `jobs`, `job_batches`, and `failed_jobs` tables. None of these facts proves the corresponding production configuration or migration history.
- The route manifest says V2 is inactive. No Forge script, supervisor configuration, deployed queue configuration, production schema, or production migration ledger was inspected.

### External documentation facts reviewed for STEP 189/190

- Brevo's current transactional send API reference shows `IdempotencyKey` as an example custom header. A separate idempotency guide specifies UUID keys and a 30-minute TTL for batch requests; an older transactional email changelog specifies 15 minutes. Those sources conflict, and the newer guide is batch-focused. Exact single-message behavior, TTL, and duplicate response are not established for this application's use.
- Brevo documents provider message IDs and transactional events. A message ID is useful when available, but cannot be assumed present before a response is received. Event/API lookup is reconciliation evidence, not proof that an ambiguous search found no accepted send.
- Brevo documents Bearer, Basic, and custom-header webhook authentication options. The deployed webhook mechanism/configuration is unknown.
- Laravel supports after-commit queue dispatch and worker retry/timeout controls. Queue retry by itself cannot atomically coordinate an external email provider with the database; a committed outbox and recovery poller are still required.

Sources: [Brevo send API](https://developers.brevo.com/reference/send-transac-email), [Brevo idempotency guide](https://developers.brevo.com/docs/heterogenous-versions-batch-emails), [Brevo idempotency changelog](https://developers.brevo.com/changelog/2021/11/10), [Brevo transactional webhooks](https://developers.brevo.com/docs/transactional-webhooks), [Brevo secured webhooks](https://developers.brevo.com/docs/secured-webhooks), [Laravel 12 queues](https://laravel.com/framework/docs/12.x/queues).

## 2. Decision and approval matrix

**Labels:** **Verified** means established from local source or cited documentation only. **Recommended default** is a conservative implementation proposal, not an approved requirement. **Approval required** identifies a choice that must be explicitly accepted before implementation. No row below is silently treated as approved.

| Topic | Verified fact | Recommended default | Approval required / open decision |
|---|---|---|---|
| Dispatch state | No durable dispatch state currently exists. | `queued` → `processing` → `accepted`; `processing` may become `retry_wait`, `failed`, `blocked_attachment`, or `unknown`; operator-only outcomes may be `resolved_accepted`, `resolved_not_accepted`, or `cancelled`. Provider events are separately recorded and projected. | Approve the final enum/state graph, terminal states, and whether an accepted message can later have a separate `delivered`, `deferred`, `bounced`, `blocked`, or `complaint` projection. |
| Recipient state | Recipient stores one token hash, expiry, revocation timestamp, and `sent_at`. | Preserve those fields; keep token usable before provider call only when the outbox intent commits in the same transaction. Set V2 `sent_at` only after provider acceptance is durably recorded. Store delivery evidence separately; never equate `sent_at` with mailbox delivery. | Approve the meaning of `sent_at`, token activation timing, and whether a separate recipient dispatch state/projection is needed. Do not reinterpret V1 fields. |
| Provider acceptance vs delivery | Current proof `sent` is written after a successful provider response; no delivery webhook ingestion was found. | Distinguish `accepted_by_provider` from `delivered`. A 2xx response or message ID records provider acceptance only. Delivery is recorded only from authenticated provider evidence. | Approve UI labels and audit language so users do not see “delivered” when only accepted. |
| Parent status | Current controller changes approved parent to `sent` after a recipient is processed. | Recommended V2 rule: leave parent approved while any required recipient is queued, processing, failed, or unknown; transition to `sent` only when every required recipient has a durably recorded provider acceptance. Keep delivery projections separate. | Management must approve parent status semantics, partial-success behavior, and handling when some recipients are accepted but others remain unknown or fail. If the existing status model cannot express needed states, approve any additive V2-only extension. |
| Unknown outcome | A timeout or process failure cannot establish whether Brevo accepted the request. | `unknown` is a hold state. Never automatically resend an unknown outcome; never generate a new provider key/token for it. Preserve the attempt and correlation evidence for reconciliation. | Approve the staff workflow and escalation/service-level target for unresolved unknowns. |
| Dispatch identity / uniqueness | No database uniqueness currently prevents duplicate logical email dispatches. | `dispatch_uuid` unique; provider idempotency UUID unique; dedupe key unique for `(transmittal_id, recipient_id, purpose, generation)` or a narrowly scoped client-request key. Prevent a second active initial dispatch for the same recipient/purpose. | Technical approval required for exact dedupe scope, treatment of a legitimate repeat, generation semantics, and database engine/index support. Concurrent correctness must be verified on the approved target engine. |
| Attempts and provider events | No attempt ledger or event ingestion exists. | Attempts append-only, unique `(dispatch_id, attempt_no)`. Provider events append-only with unique provider/account/event identity; if provider event IDs are not stable, use an approved deterministic fingerprint. Never place bearer tokens in event rows. | Approve event uniqueness scope, account scoping, retention and handling of unmatched events. Confirm event IDs and repeated webhook behavior in staging. |
| Retry eligibility / limits | Current code has no durable retry policy. Brevo's documented idempotency TTL sources conflict; current endpoint-specific semantics are unverified. | No retry for `unknown`. Retry only a definitive provider rejection proven not accepted, with same request, same key, bounded attempts and backoff/jitter. A duplicate-key response after uncertainty becomes `unknown` pending reconciliation, not proof of a known message ID. Stop retries when classification or evidence is ambiguous. | Approve max attempts, backoff, status/error classification and rate limits. Confirm Brevo behavior and TTL for single-recipient requests before any automatic provider retry. Safest fallback if not confirmed: no automated provider resubmission; reconcile/manual authorization only. |
| Idempotency | Brevo's send API reference includes a custom-header example; documentation does not resolve exact TTL/semantics for this request. | Create one stable provider key per logical dispatch. Never rotate it for transport retries of that dispatch. Treat provider idempotency as an additional bounded safeguard, not the correctness source or exactly-once guarantee. | Written/authoritative provider confirmation plus controlled staging proof required before relying on it. Without that, implementation must disable automatic external retries entirely, including same-key retries. |
| Immutable payload | Current service renders the message at send time and reads files at send time. | Freeze recipient, sender identity reference, subject, rendered body/template version, token, correlation/tags, and canonical request fingerprint at enqueue. Encrypt sensitive snapshot fields. For each retry, reconstruct attachment bytes only from original document IDs/path references and require exact stored size/hash; never substitute changed files. | Approve whether the rendered body is persisted encrypted or regenerated from a pinned immutable template/input version. The chosen approach must prove byte-equivalent retry payload; no live mutable template/input rendering. |
| Attachment evidence | Current V2 service checks file readability and metadata SHA-256 before initial send. | Store manifest rows or canonical JSON containing document ID, disk, path reference, original name, byte size, MIME type if known, and SHA-256. Revalidate immediately before every provider request. Missing/change/mismatch → `blocked_attachment`; no provider call and no deletion or copy of source file. | Approve manifest format and retention; verify shared storage and disk visibility for actual worker topology in staging. |
| Encryption / secret handling | Current acknowledgment link uses a bearer token; only its hash is stored on the recipient. No outbox encryption key policy exists. | Never persist plaintext token or put it in job payload/logs/provider events. Use authenticated encryption with a dedicated, access-restricted, versioned key managed through the approved secret-management mechanism. Keep key material out of source, database rows, logs, and deployment output. Fail closed if key unavailable. | Technical/security approval required for key source, rotation/decryption compatibility, backup exposure and recovery. Management/security must approve ciphertext and snapshot retention/purge periods. Do not presume `APP_KEY` rotation or replacement. |
| Retention / audit | Current proof/history are domain records; no dispatch retention policy exists. | Keep dispatches, attempts, state transitions, provider IDs, resolutions and resend audit append-only for the approved business retention period. Retain encrypted token/body only through acknowledgment/expiry plus a defined recovery window, then audibly purge sensitive ciphertext while retaining nonsecret evidence. Never delete V1 records/files. | Approve legally/business-required retention periods, purge authority and minimum evidence retention. The recovery window is currently undefined. |
| Worker / queue | Local default is `sync`; database queue tables exist in migration source only; production worker status is unknown. | V2-named job/queue, payload contains dispatch UUID only, `afterCommit()` for wake-up, database outbox poller as recovery source, bounded lease and lock/claim transaction. Queue retries may re-read state but must not blindly repeat provider calls. Do not change global queue defaults. | Ops/technical approval required for driver, queue name, worker count, timeout/retry_after, lease duration, poll cadence, supervisor/restart behavior, alert thresholds, and release path. Verify shared DB/cache/storage and deployed jobs tables in authorized staging/Forge review. |
| Webhook authentication / replay | Brevo documents Basic/Bearer/custom-header authentication and event fields; no endpoint exists. | Prefer a dedicated Bearer/custom-header secret over credentials embedded in URL; require TLS, constant-time credential validation, request limits/rate controls, durable event dedupe, and persistence before 2xx. Use source-IP allowlisting only as supplemental control. Do not trust event fields before authentication. | Security/ops approval for auth method, secret provisioning/rotation, proxy/header behavior, endpoint exposure, IP ranges and event retention. Confirm whether provider offers a signature before specifying signature verification. |
| Manual reconciliation | No outbox reconciliation workflow exists. | Restrict to a dedicated least-privilege permission; require reviewer, reason, evidence/source, timestamp, and state transition audit. Message ID/correlation lookup may support `accepted`; absence of a match alone does not prove `not_accepted`. Unknown remains held absent adequate evidence. | Management approves responsible roles, whether two-person review is required, evidence threshold, service target, and whether an operator may resolve to not accepted. Never auto-grant privilege. |
| Manual resend | No durable resend audit/authorization exists. | Explicit separate resend authorization, reason, duplicate-delivery warning, and a new dispatch generation only after reconciliation or accepted risk review. Preserve old attempts/proofs. Reuse existing valid acknowledgment token unless an explicitly approved token reissue revokes and audits the old token. | Management approves authorized roles and dual-control requirements. An unknown outcome cannot be resent automatically. |
| V1 compatibility | V1 and V2 share application models/tables in places; V1 sender behavior is separate. | Keep V1 sender synchronous and unchanged; no global model scope, queue default change, broad shared-model mutation, or V1 data/file rewrite. Outbox queries always require V2 parent and child associations. | Every implementation phase requires V1 behavior/data/attachment regression sign-off. |

## 3. Recommended architecture and transaction boundaries

This section defines the proposed shape after approvals; it is not authorization to implement it.

### Request transaction — create intent, do not send

Under locks on the V2 parent and recipient, recheck V2 classification, authorization, sendable parent state, recipient ownership/email, current acknowledgment/revocation status and attachments. In one database transaction:

1. Enforce logical-dispatch uniqueness and insert `transmittal_email_dispatches` in `queued` state with stable `dispatch_uuid`, provider UUID key, purpose and generation.
2. Create the acknowledgment token once; store its hash and expiry on the recipient and encrypted token material in restricted dispatch payload. The transaction makes the accepted-message link valid before the worker can submit it.
3. Freeze the canonical email payload or immutable rendering inputs according to the approved byte-equivalence policy. Store a request fingerprint and attachment manifest, not attachment/base64 bytes.
4. Append a queued audit/history event distinct from provider acceptance and delivery.
5. Commit. Then enqueue a job containing only the dispatch UUID. If queue publication fails or the job is lost, the poller finds committed queued rows.

No email is sent inside the request transaction. A rolled-back transaction must leave no queued job and no usable token state. A committed outbox record must remain durable even if the queue is unavailable.

### Worker transaction and provider boundary

The V2 worker loads by dispatch UUID, verifies parent/child V2 ownership, validates dispatch state, claims an eligible row using an atomic target-engine-supported lease, and commits an append-only attempt row as `submitting` before making the network request. It then validates every attachment's original size/hash and sends the frozen payload with the stable provider key/correlation.

- Clear provider acceptance: in a new transaction, persist attempt outcome and provider ID if present; move dispatch to `accepted`; append proof/history; set V2 recipient `sent_at`; update parent only under the approved all-recipient policy. No claim of delivered.
- Definitive, validated non-acceptance: persist response class/code. Retry only under approved bounded rules, using exact payload and same key; otherwise mark failed.
- Timeout, reset, ambiguous HTTP outcome, process termination after attempt began, or success followed by a failed result transaction: leave durable attempt evidence and transition to `unknown` when recovery evaluates the expired lease. Do not resend. Reconcile by authenticated events/API evidence and authorized review.
- If provider accepted but the local result transaction cannot commit, the prior outbox intent, valid recipient token hash, and `submitting` attempt remain. Correlation may permit later event/API matching. If no reliable match is found, retain `unknown`; never infer non-acceptance from an empty search.

### Proposed additive schema candidates

Create non-executable candidates, not active migrations, after approval. Suggested dependency order:

1. Confirm/reconcile the existing workflow-version and core V2 parent/recipient/document candidates; do not alter or run them in this step.
2. `transmittal_email_dispatches` after V2 parent and recipient tables (and document references available for manifest validation).
3. `transmittal_email_dispatch_attempts` after dispatches.
4. `transmittal_email_provider_events` after dispatches; event association may be nullable for unmatched/quarantined events.

Minimum field groups:

- **Dispatch:** PK, UUID, V2 parent/recipient FKs, purpose, generation, dedupe key, state, attempt count, `next_attempt_at`, lease owner/expiry, provider name, stable provider idempotency UUID, nullable message ID, provider accepted timestamp, template/payload version and hash, encrypted payload/token plus key version, attachment manifest/hash, uncertainty timestamp, sanitized last-error code, requester, and resolution actor/reason/time.
- **Attempt:** dispatch FK, attempt sequence unique per dispatch, start/end times, worker identity, outcome, sanitized HTTP/provider code, nullable message ID, request fingerprint, and elapsed time. No secrets, token, full body, or attachment data.
- **Provider event:** provider/account scope, provider event ID or approved fingerprint, dispatch/correlation and message IDs, normalized event and occurrence/receive times, authentication result, payload hash, minimal sanitized fields, and unmatched/quarantine state. No acknowledgment bearer token.

Use restrictive foreign keys, no cascade deletes, and indexes for due queue scans and reconciliation. Consider a composite recipient/parent FK to prevent mismatched associations if supported by the confirmed target engine; otherwise enforce parent ownership in service and test it under concurrency. Schema constraints must prevent duplicate logical initial sends and duplicate attempt/event identities. Any rollback candidate must refuse to drop populated dispatch evidence; it must not remove outbox/history/proof rows. Exact indexes, lengths, JSON support, UUID storage and FK behavior await target-engine confirmation.

## 4. Exact implementation dependencies and gates

### Source/config dependencies

- V2 transmit controller must replace synchronous provider invocation with idempotent dispatch-intent creation and a status response; it must not invoke legacy V1 sending or the direct-document email method as fallback.
- V2-only dispatch/attempt/event models must validate parent `workflow_version='v2'` and child association on every access. Avoid global scopes or shared-model changes affecting V1.
- V2-only services: intent creator, immutable payload builder, attachment-manifest validator, worker/claim service, Brevo adapter, status projector, event authenticator/ingestor, reconciliation/resend service, and poller.
- Existing authorization must remain on dispatch creation. New reconciliation/resend abilities require a reviewed policy/provider change and explicit permission mapping.
- V2-specific configuration must be opt-in/default-disabled. Do not change global `QUEUE_CONNECTION`, other modules' queue behavior, or credentials. Secret values must be entered through the separately approved secret-management process.
- A new webhook route, if approved, must be separately authenticated, throttled, replay-safe, and remain inactive until its security/configuration acceptance gate passes.
- Existing migrations/candidates must be reconciled for exact parent/child FKs and preservation behavior. No November 4 synchronization/backfill migration is part of this design.
- The worker/poller must not start or submit email until a separate activation approval. V2 remains default-disabled.

### Decisions not safely made by this specification

Management/security approval is required for workflow/status meanings, who may reconcile/resend, evidence threshold, dual control, retention and token-reissue policy. Technical/operations approval is required for target DB engine/version, constraint/locking strategy, queue driver/name, worker topology/timeouts/leases/poller frequency, shared cache/storage assumptions, webhook authentication/secret lifecycle, and release compatibility. Brevo's endpoint-specific idempotency behavior and rate/error semantics require authoritative confirmation and staging proof. No current production setting was independently verified.

## 5. Phased implementation plan and acceptance criteria

Each phase is separately gated. Passing one phase does not authorize the next phase, V2 activation, sending, or production change.

### A. Additive schema candidates

Prepare dispatch, attempt, and provider-event `.candidate` files outside executable migration discovery. Reconcile with existing V2 candidates and workflow-version migration; specify dependencies, indexes, uniqueness, restrictive FKs, lengths, and no-backfill behavior.

**Acceptance:** source review confirms additive-only operations, no update/delete/backfill/reclassification/file access, no V1 table rewrite, no cascading delete, duplicate constraints are explicit, populated evidence cannot be dropped by rollback, and candidates remain non-executable. Target-engine behavior passes only in a verified disposable schema after separate test approval.

### B. V2-only models and services

Prepare bounded V2 models and intent/payload/attachment/status/reconciliation services. Avoid global scopes, V1 model behavior changes and plaintext secret serialization.

**Acceptance:** source contracts enforce V2 parent and recipient/document ownership at every boundary; payload/token encryption policy and key version are explicit; dispatch creation is transactional and never sends externally; duplicate calls return the existing logical dispatch; attachment mutation blocks submission; audit rows are append-only.

### C. Isolated queue worker and poller

Prepare a V2-only worker taking a dispatch UUID, DB-backed claim/lease and a poller that recovers committed intents if queue publication fails. Queue dispatch is after commit; no global connection/default changes.

**Acceptance:** isolated multi-worker tests prove one claim under the approved DB engine, expired lease classification is conservative, job loss is recovered, queue payload contains no token/email/body/file bytes, and worker retry does not directly resend `unknown`. No worker is registered/launched or V2 activated in this phase.

### D. Provider adapter and reconciliation

Prepare adapter sending the frozen payload and stable correlation/key, plus authenticated webhook/event persistence and provider-query/manual reconciliation. No second transport fallback.

**Acceptance:** fake-provider tests cover success with and without message ID, definitive rejection, duplicate-key response, timeout/reset, provider acceptance followed by DB-result-commit failure, webhook authentication/replay/out-of-order events, and missing/corrupt attachments. Unknown outcomes never auto-resend. Controlled staging validates Brevo single-message semantics/TTL, correlation propagation, event auth and lookup windows before any retry policy is enabled.

### E. Isolated automated tests

Use an explicitly verified disposable database with the same engine/version as the approved target; fake all provider calls. Do not use production connection settings or unrestricted fixtures.

**Acceptance:** constraints/concurrency, transaction rollback, token validity, exact payload repeatability, queue recovery, lease expiration, event projection, authorization, retention/purge audit, V1 regression, historical V1 record/attachment preservation and no external network are demonstrated with recorded test output. No claim of runtime acceptance without execution evidence.

### F. Staging validation

After separate staging access and controlled-send approval, validate target-like DB, multiple app instances, shared storage/cache/queue, worker supervision/restart, poller schedule, monitoring, Brevo behavior and recovery drills. Use pre-approved recipients only.

**Acceptance:** controlled timeout after provider acceptance is recovered as accepted only with provider evidence; otherwise remains unknown with no automatic resend. Rollback/redeploy retains outbox, attempts, provider events, proofs, history, V1 rows and attachments. V1 flows and unrelated modules pass signed regression. Operations signs worker, alert, backup/restore and manual recovery runbooks. Production gates remain separate.

## 6. Remaining blockers and tracker

### Production Deployment Readiness Tracker after STEP 191

| Status | Evidence / work |
|---|---|
| Verified — source/docs only | STEP 189 design and STEP 190 findings reconciled; current synchronous provider-before-database ordering; no stable idempotency key; optional message ID; V2 remains inactive; local queue defaults/config and candidate gaps documented; official Brevo docs expose TTL conflict; official Laravel docs describe after-commit capability. |
| Recommended defaults — not approved | Outbox is source of truth; unknown never automatically resends; acceptance is separate from delivery; exact payload and attachment hashes are immutable; V2-only queue/worker/poller; append-only attempts/events; restrictive FKs; authenticated/replay-deduped webhook; V1 sender and global queue settings unchanged. |
| Pending approval | State and parent-status semantics; dedupe/generation/index strategy; retry limits/classification; encrypted snapshot/key rotation and retention; manual reconciliation/resend roles and dual control; webhook auth and secret lifecycle; target DB/queue/worker/poller configuration; staging send authorization. |
| Blocked | Brevo single-message idempotency TTL/duplicate semantics and correlation behavior unresolved; no outbox/attempt/event schema or runtime exists; production schema, migration history, worker supervision, shared queue/cache/storage and Forge deploy script remain unverified. Isolated acceptance, staging recovery, backup/restore rehearsal, V1 regression and separate release approval remain outstanding. |
| Overall | **NOT READY.** No V2 activation, email, migration, DB operation, production/Forge access, or deployment is authorized by this specification. |

### Required release gate

Do not begin implementation until management, security, and technical/operations owners explicitly approve the pending matrix items or formally select the conservative no-automatic-retry path for unknown outcomes. Do not enable an automatic provider retry based only on the documented idempotency header while its single-message semantics and TTL remain unverified. Even after implementation and staging acceptance, production requires separately approved migration, backup/recovery, Forge/worker verification, V1 regression, release target and deployment action.
