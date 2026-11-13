# V2 Batch 1: review corrections prepared, not integrated

Primary source: `C:\Users\Admin\Documents\original\jknc_project`.
Base branch: `transmittal-v2-release`.
Base HEAD: `a61c7758bcae31788ca2d71c785b8701948180a4`.
The preserved working tree includes local changes beyond HEAD. This patch targets
the preserved controller with SHA-256
`0A45D044A4A23D97D580733568793C1D08A351089AD7ECED6F8EF0864EEB5F6C`.

## Scope

`batch-1-critical-repairs.patch` contains five controller hunks:

- Require a non-null authenticated owner ID for the ownership alternative in
  `canManageLifecycle`. Existing administrator access and record-specific hashed
  session-token validation remain intact.
- Qualify the two proof-handler catches as `\Throwable` AND replace their failed
  operation deletion with retention and protected failure reporting. Exception
  qualification must never be integrated separately from the retention changes.
- Authorize `updateProof` using the existing `uploadProof` record policy BEFORE
  proof lookup, validation or writes. That policy permits an authenticated owner
  in draft/pending approval or an administrator in its existing permitted states.
  Session ownership and recipient attachment visibility do not grant proof editing.
  The existing record-scoped `proofs()->findOrFail` rejects cross-record proof IDs.
- Replace `storeDocument` failure deletion with retained private intent journals,
  copied-byte/hash verification and retention after every failure or uncertain outcome.

The patch has NOT been applied to the active integration controller or original.
Do not apply it directly to the current integration controller: it contains V1,
OCR and Attachment Stage A protections and is not this patch's base.
Reconcile these changes into the eventual V2 controller integration separately.

## Prepared regression coverage

`Batch1RegressionTest.php` targets the actual V2 HTTP handlers, not copied
authorization logic. Its prepared coverage includes:

- Ten guest recall/cancel denials: missing/wrong tokens, a real second record's
  distinct token under its own key, that token remapped to the target record, and
  the original token after the target's stored hash is actually rotated.
- Two authenticated-owner successes and two valid guest-session-token successes.
- Two authenticated-nonowner denials and two existing-administrator successes.
- Two successful lifecycle decisions using the newly rotated token.
- Four proof-edit denials: guest (even with a valid draft token), nonowner,
  addressed recipient and owner in a disallowed state. Business row attributes,
  document rows, histories and all fake file hashes must remain unchanged.
- Cross-record proof-ID rejection and an allowed owner's successful replacement,
  preserving the previous proof file.
- Eight upload/update failure cases: RuntimeException/Error, each after the real
  handler callback has created its document, for rollback and commit-then-error.
  A ninth case also makes diagnostic logging fail after commit.
- Two document-row creation failures, verifying retained bytes and intent evidence.
- An intent-journal write failure, requiring zero document writes or row changes.

The update-proof cases execute the REAL transaction closure before injecting a
failure; they no longer throw before replacement creation. The test then rolls
back or commits on the approved in-memory connection. Committed proof/document
references must retain their file; rollback must also retain it. Assertions require
the original exception object, the old file hash, the new file and its journal.
There are no cleanup sinks, deletion expectations or real/protected file inputs.

## Fail-closed retention and reconciliation

Before writing a document, `storeDocument` generates a UUID path and writes an
intent journal under the PRIVATE local disk's `transmittal-attempts/` directory.
The journal contains the attempt reference, transmittal ID, intended relative path,
expected size/hash, timestamp and `write_intended_outcome_unverified` state.
Failure to record that evidence stops before the document write. Existing path
collisions are refused; current document path layout and relationships are retained.
Copied size and hash must match the uploaded bytes before row creation.

These are durable filesystem intent records, not database rows. They survive a
database rollback, lost connection or lost commit acknowledgment. They do NOT
declare that any commit succeeded or failed. Every journal and possible attempt
file is retained, including after apparent success. There are no row-absence
queries or automatic failed-save deletions in these patched proof/document paths.
Warning/error handling never decides whether a file may be deleted.

Failure logging includes only a phase, exception class and journal directory;
logging failure cannot mask the primary Throwable or remove files/evidence.
Reconciliation must be separately reviewed against actual references, attempt
hashes and database outcomes. No reconciler, deletion or retention expiry is added.
Retained files/journals consume space and need operational capacity monitoring.

## Execution blockers and integration prerequisites

No tests have been executed. The PHP draft is outside standard test discovery.
It refuses loading without explicit V2 Batch 1/launcher flags, disabled proc_open,
valid V2 isolation state and a future mapped `tests/Support/TransmittalV2TestCase.php`.
That TestCase and an approved runner do not exist yet. This is deliberate:
flags and PHP restrictions alone do not prove OS-level isolation.

Before execution, independently approve OS filesystem/network/UNC/credential
isolation, an isolated TestCase, manually prepared synthetic SQLite :memory:
schema and fresh application/event dispatcher per test, required V2 models/policy/
routes, and the repaired V2 controller. Keep database lifecycle traits disabled
and preserve V1/OCR harnesses. Confirm the local disk is private, durable and
outside the public web root. No journal or failed attempt may be public or swept
by an existing cleanup task. Ordinary SQLite memory tests cannot certify real
production-engine commit behavior or OS confinement.
No environment files, existing database files or real records are test inputs.

Separate safety blockers remain outside Batch 1:

- The preserved source itself is unchanged. Proof/document deletion is removed
  only in this prepared patch. All five hunks require independent review together.
- Resumable assembly failure cleanup and explicit lifecycle permanent deletion
  remain outside these proof/document hunks and need separate safety review before
  their integration/activation. No claim is made that every V2 deletion is repaired.
- Reconcile V2 schema/model synchronization, upload limits/completeness, expanded
  OCR contracts, dependencies, receipt authorization and migration preservation.

The diff is source-hash-pinned and has zero-context hunks. Static verification
reconstructs the intended controller in memory only; do not blindly apply it to
another revision. No PHP parser, application, database or regression suite has run.

No migration, cleanup, launch, deployment, schema change or Batch 2 work is included.
