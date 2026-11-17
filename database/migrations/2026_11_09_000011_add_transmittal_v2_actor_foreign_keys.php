<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasTable('transmittals') || ! Schema::hasTable('users')) {
            throw new RuntimeException('MySQL transmittals/users prerequisites are required.');
        }

        $definitions = [
            ['index' => 'transmittals_prepared_by_index', 'column' => 'prepared_by', 'foreign' => 'transmittals_prepared_by_foreign'],
            ['index' => 'transmittals_rejected_by_index', 'column' => 'rejected_by', 'foreign' => 'transmittals_rejected_by_foreign'],
            ['index' => 'transmittals_cancelled_by_index', 'column' => 'cancelled_by', 'foreign' => 'transmittals_cancelled_by_foreign'],
        ];
        foreach ($definitions as $definition) {
            if (! Schema::hasColumn('transmittals', $definition['column'])) {
                throw new RuntimeException("Missing nullable column {$definition['column']}; run the column-only migration first.");
            }
        }

        $db = DB::selectOne('SELECT DATABASE() AS name');
        $tableEngines = DB::select(
            'SELECT TABLE_NAME, LOWER(ENGINE) AS ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (?, ?)',
            [$db->name, 'transmittals', 'users'],
        );
        $engines = [];
        foreach ($tableEngines as $table) {
            $engines[$table->TABLE_NAME] = $table->ENGINE;
        }
        if (($engines['transmittals'] ?? null) !== 'innodb' || ($engines['users'] ?? null) !== 'innodb') {
            throw new RuntimeException('Both transmittals and users must be InnoDB.');
        }

        $userId = DB::selectOne(
            'SELECT LOWER(COLUMN_TYPE) AS COLUMN_TYPE, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db->name, 'users', 'id'],
        );
        if (strtolower((string) ($userId->COLUMN_TYPE ?? '')) !== 'bigint unsigned' || ($userId->COLUMN_KEY ?? null) !== 'PRI') {
            throw new RuntimeException('users.id must be a BIGINT UNSIGNED primary key.');
        }

        $nonnull = DB::selectOne('SELECT COUNT(*) AS n FROM transmittals WHERE prepared_by IS NOT NULL OR rejected_by IS NOT NULL OR cancelled_by IS NOT NULL');
        if ((int) ($nonnull->n ?? -1) !== 0) {
            throw new RuntimeException('New actor columns are not empty; validate all references before adding constraints.');
        }

        $names = array_column($definitions, 'index');
        $indexRows = DB::select(
            'SELECT INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME IN (?, ?, ?)',
            [$db->name, 'transmittals', ...$names],
        );
        $foreignNames = array_column($definitions, 'foreign');
        $foreignRows = DB::select(
            'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME IN (?, ?, ?)',
            [$db->name, 'transmittals', ...$foreignNames],
        );

        if ($indexRows !== [] || $foreignRows !== []) {
            $indexesMatch = count($indexRows) === count($definitions);
            foreach ($definitions as $definition) {
                $indexesMatch = $indexesMatch && count(array_filter($indexRows, fn ($row) => $row->INDEX_NAME === $definition['index'] && $row->COLUMN_NAME === $definition['column'])) === 1;
            }
            $foreignMatch = count($foreignRows) === count($definitions);
            foreach ($definitions as $definition) {
                $foreignMatch = $foreignMatch && count(array_filter($foreignRows, fn ($row) => $row->CONSTRAINT_NAME === $definition['foreign'] && $row->COLUMN_NAME === $definition['column'] && $row->REFERENCED_TABLE_NAME === 'users' && $row->REFERENCED_COLUMN_NAME === 'id')) === 1;
            }
            if ($indexesMatch && $foreignMatch) {
                return;
            }

            throw new RuntimeException('Partial or conflicting actor indexes/foreign keys exist; reconcile before retrying.');
        }

        $state = DB::selectOne('SELECT @@SESSION.foreign_key_checks AS foreign_key_checks, @@SESSION.lock_wait_timeout AS lock_wait_timeout');
        $foreignKeyChecks = (int) $state->foreign_key_checks;
        $lockWaitTimeout = max(1, (int) $state->lock_wait_timeout);
        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');
            DB::statement('SET SESSION foreign_key_checks = 0');
            DB::statement(<<<'SQL'
ALTER TABLE `transmittals`
    ADD INDEX `transmittals_prepared_by_index` (`prepared_by`),
    ADD INDEX `transmittals_rejected_by_index` (`rejected_by`),
    ADD INDEX `transmittals_cancelled_by_index` (`cancelled_by`),
    ADD CONSTRAINT `transmittals_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `transmittals_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `transmittals_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    ALGORITHM=INPLACE, LOCK=NONE
SQL);
        } finally {
            DB::statement('SET SESSION foreign_key_checks = '.($foreignKeyChecks === 0 ? '0' : '1'));
            DB::statement('SET SESSION lock_wait_timeout = '.$lockWaitTimeout);
        }
    }

    public function down(): void
    {
        // Keep constraints and V2 data. Reversal requires a separate preservation review.
    }
};
