<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $columns = [
        'tracking_number' => 'VARCHAR(255) NULL',
        'external_reference' => 'VARCHAR(255) NULL',
        'subject' => 'VARCHAR(255) NULL',
        'purpose' => 'TEXT NULL',
        'direction' => 'VARCHAR(255) NULL',
        'origin_office' => 'VARCHAR(255) NULL',
        'recipient_address' => 'TEXT NULL',
        'related_company' => 'VARCHAR(255) NULL',
        'delivery_method' => 'VARCHAR(255) NULL',
        'delivery_actions' => 'JSON NULL',
        'status' => 'VARCHAR(255) NULL',
        'prepared_by' => 'BIGINT UNSIGNED NULL',
        'rejected_by' => 'BIGINT UNSIGNED NULL',
        'rejected_at' => 'TIMESTAMP NULL',
        'rejection_reason' => 'TEXT NULL',
        'cancelled_by' => 'BIGINT UNSIGNED NULL',
        'cancelled_from_status' => 'VARCHAR(255) NULL',
        'cancelled_at' => 'TIMESTAMP NULL',
        'cancellation_reason' => 'TEXT NULL',
        'operations_manager' => 'VARCHAR(160) NULL',
        'processing_company' => 'VARCHAR(255) NULL',
        'confidentiality' => 'VARCHAR(255) NULL',
        'approved_snapshot' => 'JSON NULL',
        'approval_authority_code' => 'VARCHAR(255) NULL',
        'edit_token_hash' => 'VARCHAR(64) NULL',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('This online-DDL candidate is MySQL-specific; no changes were made.');
        }
        if (! Schema::hasTable('transmittals') || ! Schema::hasTable('users')) {
            throw new RuntimeException('V2 prerequisite tables are absent; reconcile before applying this candidate.');
        }

        $engine = DB::selectOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transmittals'");
        if (strtolower((string) ($engine->ENGINE ?? '')) !== 'innodb') {
            throw new RuntimeException('transmittals must be InnoDB for this online-DDL candidate.');
        }

        $names = array_keys($this->columns);
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $existing = DB::select(
            "SELECT COLUMN_NAME, LOWER(COLUMN_TYPE) AS COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transmittals' AND COLUMN_NAME IN ({$placeholders})",
            $names,
        );

        if ($existing !== []) {
            $actual = [];
            foreach ($existing as $column) {
                $actual[$column->COLUMN_NAME] = [$column->COLUMN_TYPE, $column->IS_NULLABLE];
            }
            if (count($actual) !== count($this->columns)) {
                throw new RuntimeException('Partial transmittal V2 columns exist; stop and reconcile the schema before retrying.');
            }
            foreach ($this->columns as $name => $definition) {
                $expectedType = strtolower((string) preg_replace('/\s+NULL$/i', '', $definition));
                $actualType = strtolower((string) ($actual[$name][0] ?? ''));
                if ($actualType !== $expectedType || ($actual[$name][1] ?? null) !== 'YES') {
                    throw new RuntimeException("Existing transmittal column {$name} does not match the nullable V2 definition.");
                }
            }

            return;
        }

        $clauses = [];
        foreach ($this->columns as $name => $definition) {
            $clauses[] = "ADD COLUMN `{$name}` {$definition}";
        }

        // Explicit INSTANT prevents MySQL from silently falling back to a copy.
        // Bound metadata-lock waiting so a busy production table fails promptly.
        $lockWaitTimeout = max(1, (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS timeout')->timeout);
        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
            DB::statement('ALTER TABLE `transmittals` '.implode(', ', $clauses).', ALGORITHM=INSTANT');
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.$lockWaitTimeout);
        }
    }

    public function down(): void
    {
        // Keep additive columns and V2 values when application code is rolled back.
    }
};
