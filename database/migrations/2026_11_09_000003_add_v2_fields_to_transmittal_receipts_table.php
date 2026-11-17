<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $columns = [
        'recipient_id' => 'BIGINT UNSIGNED NULL',
        'signed_name' => 'VARCHAR(255) NULL',
        'comments' => 'TEXT NULL',
        'receipt_code' => 'VARCHAR(255) NULL',
        'snapshot' => 'JSON NULL',
        'ip_address' => 'VARCHAR(45) NULL',
        'user_agent' => 'TEXT NULL',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('This online-DDL candidate is MySQL-specific; no changes were made.');
        }
        if (! Schema::hasTable('transmittal_receipts') || ! Schema::hasTable('transmittal_recipients')) {
            throw new RuntimeException('Receipt or recipient prerequisites are absent.');
        }

        $engine = DB::selectOne("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transmittal_receipts'");
        if (strtolower((string) ($engine->ENGINE ?? '')) !== 'innodb') {
            throw new RuntimeException('transmittal_receipts must be InnoDB for this online-DDL candidate.');
        }

        $names = array_keys($this->columns);
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $existing = DB::select(
            "SELECT COLUMN_NAME, LOWER(COLUMN_TYPE) AS COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transmittal_receipts' AND COLUMN_NAME IN ({$placeholders})",
            $names,
        );

        if ($existing !== []) {
            $actual = [];
            foreach ($existing as $column) {
                $actual[$column->COLUMN_NAME] = [$column->COLUMN_TYPE, $column->IS_NULLABLE];
            }
            if (count($actual) !== count($this->columns)) {
                throw new RuntimeException('Partial V2 receipt columns exist; stop and reconcile the schema before retrying.');
            }
            foreach ($this->columns as $name => $definition) {
                $expectedType = strtolower((string) preg_replace('/\s+NULL$/i', '', $definition));
                $actualType = strtolower((string) ($actual[$name][0] ?? ''));
                if ($actualType !== $expectedType || ($actual[$name][1] ?? null) !== 'YES') {
                    throw new RuntimeException("Existing receipt column {$name} does not match the nullable V2 definition.");
                }
            }

            return;
        }

        $clauses = [];
        foreach ($this->columns as $name => $definition) {
            $clauses[] = "ADD COLUMN `{$name}` {$definition}";
        }

        // Existing V1 receipt_no and its unique index remain untouched.
        // Nullable additions keep every historical V1 recipient_id NULL.
        $lockWaitTimeout = max(1, (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS timeout')->timeout);
        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
            DB::statement('ALTER TABLE `transmittal_receipts` '.implode(', ', $clauses).', ALGORITHM=INSTANT');
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.$lockWaitTimeout);
        }
    }

    public function down(): void
    {
        // Keep nullable V2 receipt fields and values on rollback.
    }
};
