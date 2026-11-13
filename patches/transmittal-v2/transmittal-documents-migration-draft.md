# Transmittal documents migration draft (non-executable)

This is a reviewed design draft only, not a PHP migration. Do not copy this
snippet into `database/migrations` or execute it without separate approval and
the schema/ledger checks below.

## Proposed table contract

```php
Schema::create('transmittal_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transmittal_id')
        ->constrained('transmittals')
        ->restrictOnDelete();
    $table->foreignId('uploaded_by')
        ->nullable()
        ->constrained('users')
        ->restrictOnDelete();
    $table->string('category')->default('document')->index();
    $table->string('original_name');
    $table->string('storage_path')->unique();
    $table->string('mime_type', 127);
    $table->unsignedBigInteger('size_bytes');
    $table->char('sha256', 64);
    $table->longText('ocr_text')->nullable();
    $table->json('extracted_data')->nullable();
    $table->timestamps();
    $table->index(['transmittal_id', 'category']);
});
```

The types, indexes, and unique storage path follow protected-source
`2026_10_14_000004_create_transmittal_documents_table.php`. `uploaded_by` is
nullable from the outset, reflecting the final intent of protected-source
`2026_10_14_000011_allow_public_transmittal_submissions.php` without carrying
over its required-then-alter sequence. Both deletion rules are intentionally
restrictive for preservation; the source's transmittal cascade is not copied.

No update/backfill, existing-table replacement, file operation, or destructive
rollback is proposed. If the table already exists, reconcile its schema and
migration ledger; do not blindly create, skip, alter, or drop it.

## Ordering prerequisite

The integration's `transmittals` and `users` parent tables and keys must be
verified first. This document-table definition must be established before the
separate `transmittal_workspace_items` table because its nullable `document_id`
references this table. Confirm actual migration ledger ordering and target
schema before preparing executable migration code. No database or ledger was
inspected for this draft.
