<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TransmittalV2SchemaCandidateContractTest extends TestCase
{
    private function candidate(string $name): string
    {
        $path = dirname(__DIR__, 2).'/patches/transmittal-v2/migrations/'.$name;
        $contents = file_get_contents($path);
        self::assertIsString($contents, "Unable to read {$name}");

        return $contents;
    }

    public function test_v2_workspace_items_use_a_separate_table_and_preserve_v1_items(): void
    {
        $workspace = $this->candidate('2026_11_09_000010_create_transmittal_workspace_items_table.php.candidate');
        $all = implode("\n", array_map('file_get_contents', glob(dirname(__DIR__, 2).'/patches/transmittal-v2/migrations/*.php.candidate') ?: []));

        self::assertStringContainsString("Schema::create('transmittal_workspace_items'", $workspace);
        self::assertStringNotContainsString('transmittal_items', $all);
        self::assertStringNotContainsString("Schema::table('transmittal_items'", $all);
    }

    public function test_new_v2_foreign_keys_are_restrictive_and_candidate_rollbacks_do_not_drop_data(): void
    {
        $paths = glob(dirname(__DIR__, 2).'/patches/transmittal-v2/migrations/*.php.candidate') ?: [];
        self::assertCount(14, $paths);
        foreach ($paths as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            self::assertStringNotContainsString('cascadeOnDelete', $source, basename($path));
            self::assertStringNotContainsString('nullOnDelete', $source, basename($path));
            self::assertStringNotContainsString('Schema::drop', $source, basename($path));
            self::assertStringNotContainsString('DB::table', $source, basename($path));
            self::assertStringNotContainsString('Storage::', $source, basename($path));
            self::assertStringNotContainsString('attachment_path', $source, basename($path));
            if (str_contains($source, '->constrained(')) {
                self::assertStringContainsString('->restrictOnDelete()', $source, basename($path));
            }
        }
    }

    public function test_additive_column_candidate_only_adds_nullable_values_without_backfill(): void
    {
        $columns = $this->candidate('2026_11_09_000001_add_transmittal_v2_columns.php.candidate');
        self::assertStringContainsString("Schema::table('transmittals'", $columns);
        $columnGuard = <<<'SOURCE'
Schema::hasColumn('transmittals', $column)
SOURCE;
        self::assertStringContainsString($columnGuard, $columns);
        self::assertStringContainsString("\$table->string('status')->nullable()", $columns);
        self::assertStringContainsString("\$table->string('operations_manager', 160)->nullable()", $columns);
        self::assertStringNotContainsString("'deleted_at'", $columns);
        self::assertStringNotContainsString("\$table->timestamp('deleted_at')", $columns);
        self::assertStringNotContainsString('update(', $columns);
        self::assertStringNotContainsString('default(', $columns);
    }

    public function test_v2_uses_existing_role_system_without_custom_user_role_schema(): void
    {
        $candidatePaths = glob(dirname(__DIR__, 2).'/patches/transmittal-v2/migrations/*.php.candidate') ?: [];
        $candidates = implode("\n", array_map('file_get_contents', $candidatePaths));
        $authorization = file_get_contents(dirname(__DIR__, 2).'/app/Services/TransmittalV2Authorization.php');
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/TransmittalV2/TransmittalController.php');

        self::assertStringNotContainsString('transmittal_role', $candidates);
        self::assertStringNotContainsString('transmittal_role', $authorization);
        self::assertStringNotContainsString('transmittal_role', $controller);
        self::assertStringContainsString('$user->isAdmin()', $authorization);
        self::assertStringContainsString('$user->isSuperAdmin()', $authorization);
        self::assertStringContainsString("get(['id', 'name'])", $controller);
    }

    public function test_v2_signatory_column_does_not_replace_v1_written_manager_fields(): void
    {
        $baseline = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_04_10_165905_create_transmittals_table.php');
        $candidate = $this->candidate('2026_11_09_000001_add_transmittal_v2_columns.php.candidate');
        self::assertIsString($baseline);
        self::assertStringContainsString("\$table->string('approved_by_name')->nullable()", $baseline);
        self::assertStringContainsString("\$table->string('approved_position')->nullable()", $baseline);
        self::assertStringContainsString("\$table->string('operations_manager', 160)->nullable()", $candidate);
        self::assertStringNotContainsString("\$table->string('approved_by_name'", $candidate);
        self::assertStringNotContainsString("\$table->string('approved_position'", $candidate);
    }

    public function test_receipt_candidates_preserve_existing_receipt_no_and_add_recipient_uniqueness(): void
    {
        $receiptCandidate = $this->candidate('2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate');
        $v1Migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_04_10_173623_create_transmittal_receipts_table.php');
        self::assertIsString($v1Migration);
        self::assertStringContainsString("\$table->string('receipt_no')->unique()", $v1Migration);
        self::assertStringContainsString("\$table->foreignId('recipient_id')->nullable()->unique", $receiptCandidate);
        self::assertStringNotContainsString("'receipt_no' =>", $receiptCandidate);
        self::assertStringNotContainsString("max('id')", $receiptCandidate);
    }

    public function test_acknowledgment_path_keeps_database_writes_transactional_and_has_no_file_cleanup(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
        self::assertIsString($controller);
        self::assertStringContainsString('DB::transaction(', $controller);
        self::assertStringContainsString('->lockForUpdate()', $controller);
        self::assertStringContainsString('$locked->receipt()->exists()', $controller);
        self::assertStringNotContainsString('Storage::', $controller);
        self::assertStringNotContainsString('ip-api.com', $controller);
    }
}
