# Workflow Version Classification Acceptance Specification

Status: PROPOSED ACCEPTANCE TESTS - NOT EXECUTED.
Production readiness: NOT READY.

## Contract and scope

Management approved a nullable, server-controlled classification with exact values `v1`, `v2`, or NULL, and no automatic historical V1 classification. The application field remains outside `$fillable`.

The three confirmed creators must use `Transmittal::createForWorkflowVersion()` with fixed server arguments: original `store()` uses `v1`; V2 `store()` and `createFromSmartUpload()` use `v2`. Classification is included in the initial insert, not a later update. Ordinary existing-record changes, including NULL-to-version assignments, are prohibited. No historical assignment API or fallback is included.

The foundation does not change Corporate Dashboard dispatch, permissions, transitions, receipts, notifications, unrelated ORDO modules, or V2 activation. Other proposed revision/archive decisions are outside this specification.

## Execution status and authorization

This document specifies future acceptance work only. No case below has been executed by creating this document. Prior source reviews and historical suites are not execution evidence for these cases.

Tests, database access, migrations, historical reconciliation, V2 activation, and production deployment require separate authorization. Preserve one-command-per-approval controls.

## Common preconditions

Before any execution, obtain separate approval for a verified isolated harness, disposable compatible database, synthetic users/records/uploads, and private temporary storage. Exclude production credentials, real records, business-file mounts, and external delivery. Stub notifications and external services. Do not enable V2 in the integration workspace merely to run tests; any isolated feature configuration requires explicit test approval.

Capture the approved code revision/deltas, framework and engine versions, synthetic schema, and baseline hashes. Provide the workflow_version column before testing classified inserts. For migration cases, use a separate disposable schema. Use identifiers and sanitized evidence only.

Each case inherits these preconditions. Report PASS only after its required evidence is independently reviewed. Otherwise report NOT EXECUTED, BLOCKED, or FAIL with the reason.

## Acceptance cases

### 1. V1 creation assignment

- Objective: Verify fixed server assignment in the original controller's `store()`.
- Preconditions: Valid synthetic V1 request; no workflow_version key; compatible synthetic schema.
- Procedure: Invoke the actual handler through the approved harness; inspect the created parent and legacy outputs.
- Expected result: Exactly one parent with `workflow_version = v1`; existing attributes, reference generation, response, item and attachment behavior remain compatible.
- Required evidence: Sanitized request/response assertions, persisted synthetic row, relationship assertions, and storage evidence.

### 2. V2 standard creation assignment

- Objective: Verify fixed v2 assignment in V2 `store()`.
- Preconditions: Approved isolated V2 harness; valid draft and submission inputs tested separately.
- Procedure: Exercise each intent through the actual handler and inspect its parent and child records.
- Expected result: Each created parent has `workflow_version = v2`; existing intent/status mappings and related writes remain intact.
- Required evidence: Handler assertions, persisted parent/child checks, and response assertions for both intents.

### 3. V2 Smart Upload assignment

- Objective: Verify v2 assignment without changing upload ownership or consumption.
- Preconditions: Synthetic direct uploads and completed session-owned resumable uploads; valid budget.
- Procedure: Exercise direct, resumable, and mixed submissions; include an invalid ownership/incomplete-upload control.
- Expected result: Successful parents are v2; ownership, consumption, document association, tracking and history remain correct. Invalid controls create no parent.
- Required evidence: Parent/child assertions, session/upload-state checks, and synthetic file-integrity evidence.

### 4. Client version-key rejection

- Objective: Reject presence rather than only conflicting values.
- Preconditions: Otherwise valid requests for original store, V2 store, V2 update, and Smart Upload.
- Procedure: Supply workflow_version as NULL, empty string, v1, v2, another scalar, empty array, and nonempty array. Include key-absent controls.
- Expected result: Every present-key request fails validation, with no parent/child changes, upload consumption, or permanent file writes. Original store retains its JSON 422/errors/reference contract. V2 uses its established validation response contract for the request type. Existing update authorization may reject unauthorized callers first.
- Required evidence: Field-error/status assertions, unchanged synthetic data/file hashes, and upload-state checks.

### 5. Factory input rejection

- Objective: Restrict the factory to exact versions and prohibit embedded overrides.
- Preconditions: Model harness with persistence-call observation.
- Procedure: Try invalid versions including empty, uppercase, padded and unknown strings; try embedded workflow_version attributes with NULL, empty, scalar and array values; include valid fixed-version controls.
- Expected result: Invalid inputs throw before persistence. Exact v1/v2 controls can create records. Embedded keys are always rejected, even if matching the factory version.
- Required evidence: Exception assertions and zero-save/zero-insert checks for rejected inputs.

### 6. Initial insert and persistence failure

- Objective: Verify classification precedes the insert and is not a second-write repair.
- Preconditions: Isolated SQL/event observation and controlled save-cancellation/failure injection.
- Procedure: Observe factory insert attributes and normal model events; cancel saving and inject an insert failure in separate cases.
- Expected result: The initial INSERT includes the fixed version; the factory calls save once and returns no success when saving fails. Existing controller transactions retain their behavior. Later reference-number updates are allowed and are not classification assignment.
- Required evidence: Sanitized SQL/event sequence, save-call count, exception assertions, and transaction outcome checks.

### 7. Existing-record immutability

- Objective: Reject ordinary reclassification.
- Preconditions: Synthetic parents with persisted NULL, v1, and v2; originals loaded.
- Procedure: Attempt NULL-to-v1/v2, v1/v2-to-NULL, v1-to-v2, v2-to-v1, and invalid values; exercise ordinary mass assignment including forceFill. Also test supplying classification on a partially selected model without its original classification.
- Expected result: Attempts fail without persisted change. Missing-original attempts fail closed. Same-value ordinary saves remain possible; mass assignment of the key is rejected even for the same value.
- Required evidence: Exceptions, independently reloaded values, unchanged evidence/child records, and partial-selection assertions.

### 8. Historical NULL preservation

- Objective: Ensure no implicit historical classification.
- Preconditions: Synthetic pre-existing NULL rows, including records with populated legacy or V2-like fields.
- Procedure: Perform permitted unrelated saves and inspect migration source; later execute migration only in separately approved disposable tests.
- Expected result: NULL remains NULL regardless of other fields. No fallback, heuristic inference, or reconciliation write occurs.
- Required evidence: Before/after classification assertions and source/migration evidence.

### 9. Unrelated model updates

- Objective: Preserve normal Eloquent behavior.
- Preconditions: NULL/v1/v2 parents and synthetic legacy relationships; include partial selections without classification.
- Procedure: Update unrelated permitted attributes without supplying the version; exercise casts, approved_by_name mutator, relationship ordering, and event cancellation controls.
- Expected result: Existing behavior remains intact and classification is unchanged. Partial models that neither load nor supply classification can save unrelated changes.
- Required evidence: Cast/mutator/event assertions, relationship checks, and persisted before/after comparisons.

### 10. Migration and rollback

- Objective: Verify additive schema behavior and retention on rollback.
- Preconditions: Separate approved disposable schemas with and without the target column.
- Procedure: Inspect and test `migrations/2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate`; only after separately approved isolated schema testing, run up against disposable schemas and attempt down after placing synthetic classification data. The `.candidate` file is not executable by Laravel migration discovery.
- Expected result: Adds only nullable string(2), with no explicit default, index, or historical update. Existing-column case throws without altering its definition. Down throws and retains the column/data. Existing rows remain NULL; inspect engine metadata separately from source intent.
- Required evidence: Schema/data diffs, exception assertions, and unchanged existing-column/data checks.

### 11. Existing V1 compatibility and preservation

- Objective: Detect changes outside the authorized foundation.
- Preconditions: Approved synthetic V1 baseline and authorized file-delta manifest.
- Procedure: Compare valid creation and relevant existing Dashboard/preview behavior; review file deltas and dependency preservation. Exercise historical NULL updates without introducing classification dispatch.
- Expected result: Only fixed new-record assignment and explicit override rejection differ. Corporate permissions, transitions, routes, views, receipts, notifications, and unrelated modules remain unchanged.
- Required evidence: Focused regression assertions, reviewed diffs, unaffected protected-file hashes, and preservation of all 162 historical dependency records under documented exceptions. Retain the historical fixture unchanged.

### 12. V2 remains inactive

- Objective: Ensure the foundation does not authorize or activate V2.
- Preconditions: Recorded route/provider/feature/Vite baseline; disposable runtime checks separately authorized if needed.
- Procedure: Review activation-related files and approved deltas; optionally verify the disabled gate in isolation.
- Expected result: No new route/provider registration, feature enabling, Vite activation or deployment. Source declarations are not runtime registration proof.
- Required evidence: Activation-file comparisons and, only if authorized, isolated disabled-gate assertions.

## Known enforcement and evidence limitations

- Model hooks and fill() do not protect direct SQL, query-builder updates, deliberate event bypasses, or privileged database writes.
- Immutability compares hydrated original values; it is not an atomic database concurrency guarantee. External observers/listeners may affect event ordering or attributes and require acceptance review.
- Ordinary non-factory creation can still produce NULL; no fallback assignment is introduced. Writer discovery was bounded, not a proof against every dynamic or external writer.
- The migration does not impose a database allowed-value or immutability constraint. Target-engine DDL behavior, locking and schema compatibility remain unverified.
- A precheck for an existing column is not an atomic guard against concurrent schema changes.
- File hashes and dependency preservation do not establish functional or data acceptance. Source-only checks do not establish runtime behavior.
- No historical provenance can be inferred from fields, receipts, relationships, status or tracking numbers. Reconciliation remains separately authorized.
- Operational rollback must retain classification data; arbitrary older-code rollback may create unclassified records or bypass protections. A separately reviewed recovery plan is required.

## Evidence record template

For each case record: case ID, authorization reference, isolated environment/code/schema identity, synthetic fixture IDs, command and exit code, sanitized assertions, actual result, reviewer, and limitations. Do not record secrets or business data.

Current execution record: ALL CASES NOT EXECUTED. No acceptance or production clearance is granted by this document.
