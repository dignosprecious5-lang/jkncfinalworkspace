# Transmittal V2 document-schema prerequisite (non-executable)

**Status:** Design draft only. This file is not a Laravel migration and is not
authorization to change a database. No live schema or migration ledger has been
inspected.

## Protected-source references

The read-only V2 source defines the document table in
`database/migrations/2026_10_14_000004_create_transmittal_documents_table.php`.
It creates:

| Column | Source definition |
| --- | --- |
| `id` | `$table->id()` |
| `transmittal_id` | Required `foreignId`, constrained to `transmittals`, source `cascadeOnDelete()` |
| `uploaded_by` | `foreignId` constrained to `users`, initially required and source `restrictOnDelete()` |
| `category` | String, default `document`, indexed |
| `original_name` | String |
| `storage_path` | String, unique |
| `mime_type` | String(127) |
| `size_bytes` | Unsigned big integer |
| `sha256` | Char(64) |
| `ocr_text` | Nullable long text |
| `extracted_data` | Nullable JSON |
| timestamps | Standard Laravel timestamps |

`database/migrations/2026_10_14_000011_allow_public_transmittal_submissions.php`
later changes `transmittal_documents.uploaded_by` to nullable. Its rollback refuses
to restore non-nullability when null uploader rows exist.

The source migration also creates the index on `(transmittal_id, category)`.
Thus `category` has its source single-column index, `storage_path` its unique
index, and the transmittal/category composite index. `foreignId` is the source
key convention for both `transmittal_id` and `uploaded_by`.

The integration workspace's `transmittals` table uses `$table->id()`, and its
legacy item table uses `foreignId('transmittal_id')`. Its migration inventory
does not contain a `transmittal_documents` migration. These local migration
definitions do not prove the actual target database schema or migration ledger.

## Proposed local implementation contract for review

This section describes the desired final definition, not executable migration
code:

- Use the source-compatible field types, indexes, uniqueness, hash length and
  timestamps listed above, after verifying the actual target parent key types.
- Create `uploaded_by` nullable from the start, because the V2 candidate supports
  public submissions. Constrain it to `users` with restrictive deletion. This
  represents the source's final state after migration `000011` and avoids a
  temporary required state. Do not copy or execute `000011` as an alteration
  without separately reviewing the target's existing rows and nullability.
- Require every V2 document to reference its transmittal. The protected source
  uses cascade deletion from `transmittals`; the integration schema proposal
  requires preservation-oriented, restrictive behavior for new evidence FKs.
  For this local proposal, prefer restrict-on-delete for the transmittal FK so
  document metadata cannot be cascaded away. This is a deliberate divergence
  from source behavior and needs explicit approval before executable migration
  code. Existing V1 attachment rows/files must not be changed either way.
- Preserve `storage_path` uniqueness only if metadata and collation checks show
  it is enforceable without conflicting with existing definitions. Do not rename
  or delete files as part of schema creation.
- Do not add backfills, legacy-item links, inferred owners, status defaults,
  or automatic copy/synchronization logic.
- Do not add a destructive `down()` that drops this table or document records.
  Rollback should be a separately reviewed operational plan that disables new
  writes while retaining data.

## Existing-table decision matrix

1. **Table absent:** verify the parent `transmittals` and `users` key types,
   referenced tables, engine support, and ordering prerequisites. Only after a
   separately approved executable migration is prepared may the table be created.
2. **Table exists and matches the approved definition:** do not recreate or
   alter it. Compare migration ledger evidence and schema metadata, then decide
   whether an executable migration should no-op or be omitted. This draft does
   not prescribe a runtime skip.
3. **Table exists with missing/different columns, types, nullability, indexes,
   uniqueness, or FK actions:** do not assume it is empty, do not drop/recreate,
   and do not silently “repair” it. Inventory rows, constraints, storage paths,
   and application use in an authorized non-production environment. Prepare a
   separate additive reconciliation proposal for each discrepancy; any type,
   nullability, uniqueness, FK or data change requires specific approval.
4. **Migration ledger says applied but table is absent, or says absent while
   table exists:** treat as drift. Stop and reconcile migration history and
   schema evidence before creating or altering anything.

## Ordering before workspace items

Required logical order:

1. `transmittals` and `users` parent tables exist with compatible keys.
2. The approved `transmittal_documents` final schema is established.
3. Only then may a separately approved
   `transmittal_workspace_items` migration reference `transmittal_documents`.

The protected source's `000004` creation precedes its `000011` uploader
nullability adjustment, then `000014` adds a document link to the legacy
`transmittal_items`, and `000016` links proofs. These are source references, not
instructions to import those migrations. A future local workspace-items
migration must have a migration timestamp/order after the local document-table
prerequisite and must also verify the actual applied migration ledger. No
workspace-items migration is included here.

## Required pre-execution verification

Before any executable migration is proposed or run, a reviewer must obtain
read-only evidence from an authorized non-production target for:

- database engine and version, table storage/constraint support, and identifier
  signedness/width for `transmittals.id`, `users.id`, and any existing document
  table key;
- existence and exact definitions of the parent tables and
  `transmittal_documents`, including columns, nullability, indexes, unique
  constraints, FK targets/actions, collation, and timestamp precision;
- migration ledger entries for the source-equivalent document creation,
  uploader-nullability change, and any local/proposed equivalent;
- row counts and evidence of existing document references, unique storage-path
  collisions, null `uploaded_by` values, and any schema drift, without changing
  data;
- whether the proposed preservation-oriented transmittal FK action is approved
  and compatible with existing lifecycle/archive behavior.

No production connection, query, migration, or schema operation is authorized
by this checklist.

## Dependency and readiness

The dedicated `transmittal_workspace_items` migration depends on the approved
document schema and compatible `document_id` key. The FK alone does not ensure
that a document belongs to the same transmittal as its item; that ownership
invariant must be enforced by the V2 write path or a separately reviewed
composite constraint.

This draft does not authorize creation of the document table, workspace-item
table, model, relationship, or V2 endpoint. The Batch 2B foundation remains
blocked pending review of this contract and the non-production schema/ledger
evidence above.
