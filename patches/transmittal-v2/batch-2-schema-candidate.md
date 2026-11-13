# V2 Batch 2: non-destructive schema candidate

This is a review specification, not an executable migration or schema approval.
No migration files are added to automatic discovery. No SQL, migration, import,
database connection, backfill or schema operation has been executed.

Source: the preserved `transmittal-v2-release` working tree based on
`a61c7758bcae31788ca2d71c785b8701948180a4`, including its protected local edits.
Compare the actual target engine/version/schema and migration history before
converting any proposal below into separately reviewed migration code.

## Rules for an existing ORDO database

- Existing tables, columns, values, identifiers, attachment paths and constraints
  remain authoritative. Never drop/recreate a table merely because a V2 create
  migration exists. An existing table requires definition reconciliation, not a skip.
- Add missing V2 columns as NULLABLE, without invented legacy statuses, defaults,
  actor IDs, quantities or party mappings. New V2 requests must explicitly supply
  required values. Do not populate legacy rows with draft/send/electronic defaults.
- Do not change existing type, nullability, precision, collation or uniqueness as
  part of this candidate. Any necessary change needs a separate compatibility and
  data-preservation review. Match FK signedness/width to actual parent keys.
- Candidate new-table foreign keys must preserve audit/business rows: propose
  restrictive deletion semantics, not the source's cascading deletion of evidence.
  Verify intentional lifecycle behavior separately before approving constraints.
- Indexes/uniqueness require metadata, duplicate and engine-specific NULL behavior
  checks before approval. No constraint is approved solely from a model definition.
- Never place automatic destructive down/drop routines in an additive candidate.
  Rollback must preserve newly written V2 records and remain separately reviewed.

## Existing tables: additive column inventory

All entries mean ADD ONLY IF ABSENT, NULLABLE, no data update. Existing columns
remain unchanged. Exact lengths/types must be checked against the serving schema.

| Table | Proposed missing columns and source-compatible types |
| --- | --- |
| transmittals | `tracking_number`, `external_reference`, `subject`: string(255); `purpose`: text; `direction`, `origin_office`, `related_company`, `delivery_method`, `operations_manager`, `processing_company`, `confidentiality`, `status`: string(255); `recipient_address`: text; `delivery_actions`, `approved_snapshot`: JSON; `edit_token_hash`: string(64); `approval_authority_code`: string(255); `prepared_by`, `rejected_by`, `cancelled_by`: parent-compatible user IDs; `rejected_at`, `cancelled_at`, `deleted_at`: timestamps; `rejection_reason`, `cancellation_reason`: text; `cancelled_from_status`: string(255) |
| transmittal_items | `document_id`: document-compatible ID; `position`: unsigned small integer; `quantity`: string(40). Existing `item_no`, `qty` and `attachment_path` remain unchanged. |
| transmittal_receipts | `recipient_id`: recipient-compatible ID; `signed_name`: string(255); `comments`: text; `receipt_code`: string(255); `snapshot`: JSON; `ip_address`: string(45); `user_agent`: text |
| users (later authorization batch) | `transmittal_role`: role field reviewed against existing account permissions; no automatic role grants or account edits |

Do not alter existing `transmittal_date`, approval/signatory fields, `received_at`,
receipt identifiers or V1 workflow/approval values. The source workspace migration
also backfills dates; that update is explicitly excluded.

`deleted_at` is only a proposed nullable field. Shared-model SoftDeletes is NOT
enabled in Batch 2; a global scope would change V1 queries and requires its own
controller/model compatibility design before V2 lifecycle activation.

## New tables: definition candidates

Create only after proving that each table is absent. All proposed foreign keys
are subject to the preservation rules above. Standard timestamps apply where noted.

| Table | Candidate fields |
| --- | --- |
| transmittal_recipients | bigint PK `id`; `transmittal_id`; nullable strings `name`, `email`, `organization`; nullable unique string(64) `acknowledgment_token_hash`; nullable timestamps `acknowledgment_expires_at`, `acknowledgment_revoked_at`, `sent_at`; timestamps; index `(transmittal_id,email)` |
| transmittal_documents | bigint PK `id`; `transmittal_id`; nullable `uploaded_by` for public drafts; strings `category`, `original_name`, unique `storage_path`; string(127) `mime_type`; unsigned bigint `size_bytes`; char(64) `sha256`; nullable long text `ocr_text`; nullable JSON `extracted_data`; timestamps; index `(transmittal_id,category)` |
| proof_of_transmissions | bigint PK `id`; `transmittal_id`; nullable IDs `recipient_id`, `document_id`, `sent_by`; strings `channel`, `status`; nullable string `provider_message_id`; nullable indexed string(40) `transaction_reference`; nullable timestamps `transmitted_at`, `acknowledged_at`; nullable JSON `provider_metadata`; timestamps |
| approval_authority_codes | bigint PK `id`; unique `transmittal_id`; `approved_by`; unique string `code`; JSON `frozen_snapshot`; char(64) `snapshot_hash`; timestamps `approved_at`, `created_at` |
| transmittal_histories | bigint PK `id`; `transmittal_id`; nullable `actor_id`; string `action`; text `description`; nullable JSON `metadata`; timestamp `created_at`; index `(transmittal_id,created_at)` |
| external_acknowledgments | bigint PK `id`; `transmittal_id`, `recipient_id`; nullable `receipt_id`; string `event`; nullable JSON `details`; timestamp `occurred_at`; index `(recipient_id,occurred_at)` |
| transmittal_upload_sessions | bigint PK `id`; unique UUID `upload_id`; indexed char(64) `owner_session_hash`; char(64) `upload_token_hash`; string(255) `original_name`; string(127) `mime_type`; unsigned bigint `expected_size`; unsigned integers `chunk_size`, `chunk_count`; nullable JSON `chunk_hashes`; indexed string `status`; nullable string `storage_path`; nullable char(64) `sha256`; indexed timestamp `expires_at`; timestamps |

Candidate dependency order: retain existing users/transmittals; add nullable
transmittal fields; recipients/documents; item and receipt nullable additions;
proofs/authority/history/acknowledgment tables; standalone upload sessions.
Review all constraints before constructing an executable dependency order.

## Explicitly unresolved write compatibility

- V1 `qty` is an integer contract. V2 quantity can contain text/decimals. Do not
  reproduce numeric truncation, regex extraction or default-to-one saving hooks.
- Keep V1 item order (`item_no`) and V2 workspace order (`position`) explicit.
  Do not backfill one from the other during unrelated saves.
- Keep legacy public attachment paths distinct from private V2 document paths.
- Existing receipt_no constraints may reject source V2 receipt writes. Prepare
  explicit race-safe numbering/receipt snapshots; do not import max(id)+1 hooks
  or make existing receipt_no nullable without separate review.
- Frozen snapshots, document ownership and immutable histories need scoped V2
  guards BEFORE activation. Do not remove the preserved source guards merely to
  make legacy fixtures pass. Batch 2 shared-model metadata is not that guard layer.
- Audit lifecycle archives and deletion are a later dependency; no lifecycle
  archive table or permanent-deletion behavior is activated here.

## Source references and exclusions

Reconcile the nineteen `database/migrations/2026_10_14_000001...000019` source
files, `2026_10_15_000001_make_proof_recipient_optional.php`, and the three
October 28 receipt/recipient changes as definitions, not as an executable batch.
Their cascade/drop/alter/backfill operations are not approved by this specification.

`2026_11_04_142800_sync_transmittal_v1_and_v2_columns.php` is NOT imported.
Its existing-row updates and irreversible backfill require a separate lossless
reconciliation design, isolated rehearsal, recovery evidence and explicit approval.
