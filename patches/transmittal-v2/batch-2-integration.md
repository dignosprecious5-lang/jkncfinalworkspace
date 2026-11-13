# V2 Batch 2: model foundation and inactive controller candidate

V2 remains the primary implementation. This batch integrates its relationship
foundation into the production-based worktree without activating the V2 workflow.

## Actual application changes

Seven new classes under `app/Models/` are byte-preserved copies of the V2 source:
TransmittalDocument, TransmittalRecipient, TransmittalHistory, TransmittalUpload,
ExternalAcknowledgment, ProofOfTransmission and ApprovalAuthorityCode.
Their casts, relationships and source immutability guards are retained.
They perform no queries merely by autoloading; using them requires the reviewed
schema. Existing routes/controllers do not instantiate them in this batch.

The existing Transmittal, TransmittalItem and TransmittalReceipt models receive
only additive fillable metadata, casts and relationships. Existing model methods,
legacy casts, HasFactory and approved-manager mutator remain intact. No saving,
creating, updating or deleting callbacks, query scopes or SoftDeletes are added to
these three shared models. No model automatically writes compatibility mappings.

Legacy `items()` still orders by item_no, `receipt()` remains the original hasOne,
and `attachments()` still targets TransmittalAttachment with its original order.
V2 uses separate documents/recipients/proofs/receipts/history/authority relations;
`workspaceItems()` explicitly orders V2 items by position then id.
Existing from/to/delivery/action accessors remain unchanged pending V2 adapters.

Existing active save callers were inspected: their Transmittal/receipt creates use
explicit arrays. No active controller validation contract is broadened by this
batch. New fillable metadata is not authorization to accept raw request arrays.

## Prepared, inactive controller integration

`staged/TransmittalController.php.candidate` is the complete preserved V2 controller
with EXACTLY the five reviewed Batch 1 hunks applied. It is outside application
autoloading, uses a non-PHP discovery suffix, and is not registered or required.
The active app/Http/Controllers/TransmittalController.php is unchanged.

The candidate includes the guest non-null ownership fix, both qualified Throwable
catches with failure retention, updateProof authorization before lookup/validation,
and private prewrite intent journals with byte/hash verification. Batch 1 patch,
regression draft and README remain unchanged. No other candidate workflow is
silently replaced or repaired in this batch.

The candidate is NOT activation-ready. It still contains source dependencies and
other existing cleanup/lifecycle operations requiring independent reconciliation.
Do not rename/copy it into app or point routes at it as an activation shortcut.

## Schema preparation

`batch-2-schema-candidate.md` supplies a non-executable additive schema proposal.
No migration file is copied into database/migrations. Existing values and schema
are not inspected through a live connection or changed. There is no sync backfill,
automatic downgrade/drop, table replacement or default legacy state conversion.

## Next concrete integration dependency

Prepare scoped V2 model/write compatibility and immutability adapters against the
candidate schema before controller activation:

1. Preserve V2 approved snapshot/item/receipt guards without imposing new-table
   queries or rewriting fields on legacy saves. The source shared-model guards
   are intentionally NOT wholesale imported; activation is blocked until this
   layer is reconciled and independently reviewed.
2. Map V2 controller reads/writes to workspaceItems versus legacy items, plural
   receipts versus legacy receipt, and private documents versus public attachments.
   Define quantity precision, explicit status/party mapping and receipt numbering.
3. Reconcile SoftDeletes/lifecycle scope separately from legacy model queries.
4. Reconcile User::canApproveTransmittals, controller AuthorizesRequests support,
   policy registration, approval/upload/Brevo services, config and dependencies.
5. Keep hardened OCR and Stage A upload limits/completeness/retention contracts;
   source V2's expanded OCR contract and document categories need explicit adapters.

Routes, UI, shared JavaScript, User, providers, locks, server configuration and all
existing test harnesses are unchanged. No PHP, tests, ORM, SQL or migrations ran.
An independently verified isolated environment and approved V2 TestCase/schema
remain required before execution; model availability is not functional verification.

Production deployment remains blocked. Stop after this foundation batch for review.
