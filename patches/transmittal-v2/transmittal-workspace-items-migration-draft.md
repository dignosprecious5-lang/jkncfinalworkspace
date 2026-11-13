# Transmittal workspace items migration draft (non-executable)

This is a design draft only, not an executable Laravel migration. Do not place
it in `database/migrations` or execute it without separate approval and
non-production schema/ledger verification.

## Proposed table contract

```php
Schema::create('transmittal_workspace_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transmittal_id')
        ->constrained('transmittals')
        ->restrictOnDelete();
    $table->foreignId('document_id')
        ->nullable()
        ->constrained('transmittal_documents')
        ->restrictOnDelete();
    $table->unsignedInteger('position');
    $table->unsignedInteger('line_number');
    $table->string('particular')->nullable();
    $table->string('unique_id')->nullable();
    $table->string('quantity', 40)->nullable();
    $table->text('description')->nullable();
    $table->text('remarks')->nullable();
    $table->timestamps();
    $table->index(['transmittal_id', 'position', 'id']);
    $table->index('document_id');
});
```

`quantity` is stored and exposed as a string; do not coerce it to the legacy
integer `qty`. `position` and `line_number` are separate from legacy
`item_no`. Item deletion must not delete documents or files. Restrictive
foreign keys prevent deleting a referenced transmittal or document while item
rows remain.

The document FK guarantees that a document exists, but not that it belongs to
the same transmittal. The future V2 transactional write service must validate
`document.transmittal_id === item.transmittal_id` under the parent lock. A
composite database constraint is a possible alternative only after separate
engine/schema review.

No migration `down()` that drops a populated table is proposed. Rollback must
disable V2 writes while preserving workspace item rows and linked documents.

## Required ordering and schema checks

1. Verify actual `transmittals.id`, `users.id`, engines, and applied migration
   ledger; their definitions must accept the proposed `foreignId` keys.
2. Establish the approved `transmittal_documents` schema first, using
   `transmittal-documents-migration-draft.md` as its separate prerequisite.
3. Only then prepare a separately approved executable migration for
   `transmittal_workspace_items`; confirm the migration timestamp/ledger places
   it after the document table.
4. If either table already exists or differs, stop and reconcile definitions,
   FKs, indexes, and existing records without replacing or backfilling data.

No database or production state was inspected. Neither draft classifies or
copies rows from legacy `transmittal_items`.
