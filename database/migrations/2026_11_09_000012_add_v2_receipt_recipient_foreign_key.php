<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasTable('transmittal_receipts') || ! Schema::hasTable('transmittal_recipients')) {
            throw new RuntimeException('MySQL receipt/recipient prerequisites are required.');
        }
        if (! Schema::hasColumn('transmittal_receipts', 'recipient_id')) {
            throw new RuntimeException('Run the nullable receipt-column migration first.');
        }

        $db = DB::selectOne('SELECT DATABASE() AS name');
        $engines = DB::select(
            'SELECT TABLE_NAME, LOWER(ENGINE) AS ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (?, ?)',
            [$db->name, 'transmittal_receipts', 'transmittal_recipients'],
        );
        $byName = [];
        foreach ($engines as $table) {
            $byName[$table->TABLE_NAME] = $table->ENGINE;
        }
        if (($byName['transmittal_receipts'] ?? null) !== 'innodb' || ($byName['transmittal_recipients'] ?? null) !== 'innodb') {
            throw new RuntimeException('Both receipt and recipient tables must be InnoDB.');
        }

        $recipientId = DB::selectOne(
            'SELECT LOWER(COLUMN_TYPE) AS COLUMN_TYPE, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db->name, 'transmittal_recipients', 'id'],
        );
        if (strtolower((string) ($recipientId->COLUMN_TYPE ?? '')) !== 'bigint unsigned' || ($recipientId->COLUMN_KEY ?? null) !== 'PRI') {
            throw new RuntimeException('transmittal_recipients.id must be a BIGINT UNSIGNED primary key.');
        }

        $nonnull = DB::selectOne('SELECT COUNT(*) AS n FROM transmittal_receipts WHERE recipient_id IS NOT NULL');
        if ((int) ($nonnull->n ?? -1) !== 0) {
            throw new RuntimeException('recipient_id is not empty; validate uniqueness and references before adding constraints.');
        }

        $index = DB::select(
            'SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$db->name, 'transmittal_receipts', 'transmittal_receipts_recipient_id_unique'],
        );
        $foreign = DB::select(
            'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$db->name, 'transmittal_receipts', 'transmittal_receipts_recipient_id_foreign'],
        );
        if ($index !== [] || $foreign !== []) {
            $indexMatches = count($index) === 1 && $index[0]->COLUMN_NAME === 'recipient_id' && (int) $index[0]->NON_UNIQUE === 0;
            $foreignMatches = count($foreign) === 1 && $foreign[0]->COLUMN_NAME === 'recipient_id' && $foreign[0]->REFERENCED_TABLE_NAME === 'transmittal_recipients' && $foreign[0]->REFERENCED_COLUMN_NAME === 'id';
            if ($indexMatches && $foreignMatches) {
                return;
            }

            throw new RuntimeException('Partial or conflicting receipt recipient index/foreign key exists; reconcile before retrying.');
        }

        $state = DB::selectOne('SELECT @@SESSION.foreign_key_checks AS foreign_key_checks, @@SESSION.lock_wait_timeout AS lock_wait_timeout');
        $foreignKeyChecks = (int) $state->foreign_key_checks;
        $lockWaitTimeout = max(1, (int) $state->lock_wait_timeout);
        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
            DB::statement('SET SESSION foreign_key_checks = 0');
            DB::statement(<<<'SQL'
ALTER TABLE `transmittal_receipts`
    ADD UNIQUE INDEX `transmittal_receipts_recipient_id_unique` (`recipient_id`),
    ADD CONSTRAINT `transmittal_receipts_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `transmittal_recipients` (`id`) ON DELETE RESTRICT,
    ALGORITHM=INPLACE, LOCK=NONE
SQL);
        } finally {
            DB::statement('SET SESSION foreign_key_checks = '.($foreignKeyChecks === 0 ? '0' : '1'));
            DB::statement('SET SESSION lock_wait_timeout = '.$lockWaitTimeout);
        }
    }

    public function down(): void
    {
        // Preserve the unique relation and receipt evidence on rollback.
    }
};
