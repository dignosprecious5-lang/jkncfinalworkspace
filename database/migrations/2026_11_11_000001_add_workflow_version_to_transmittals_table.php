<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('This online-DDL candidate is MySQL-specific; no changes were made.');
        }
        if (! Schema::hasTable('transmittals')) {
            throw new RuntimeException('transmittals table is absent.');
        }

        $db = DB::selectOne('SELECT DATABASE() AS name');
        $engine = DB::selectOne(
            'SELECT LOWER(ENGINE) AS ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db->name, 'transmittals'],
        );
        if (($engine->ENGINE ?? null) !== 'innodb') {
            throw new RuntimeException('transmittals must be InnoDB for this online-DDL candidate.');
        }

        $column = DB::selectOne(
            'SELECT LOWER(COLUMN_TYPE) AS COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db->name, 'transmittals', 'workflow_version'],
        );
        if ($column !== null
            && (strtolower((string) $column->COLUMN_TYPE) !== 'varchar(2)' || $column->IS_NULLABLE !== 'YES')) {
            throw new RuntimeException('Existing workflow_version does not match nullable VARCHAR(2); reconcile before proceeding.');
        }

        $indexRows = DB::select(
            'SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND NON_UNIQUE = 0',
            [$db->name, 'transmittals'],
        );
        $indexes = [];
        foreach ($indexRows as $row) {
            $indexes[$row->INDEX_NAME][] = $row;
        }
        $hasParentUniqueIndex = false;
        foreach ($indexes as $name => $rows) {
            usort($rows, fn ($left, $right) => (int) $left->SEQ_IN_INDEX <=> (int) $right->SEQ_IN_INDEX);
            $columns = array_map(fn ($row) => $row->COLUMN_NAME, $rows);
            if ($name === 'transmittals_workflow_version_id_unique'
                && $columns !== ['workflow_version', 'id']) {
                throw new RuntimeException('The workflow parent index name exists with a conflicting definition.');
            }
            if ($columns === ['workflow_version', 'id']) {
                $hasParentUniqueIndex = true;
            }
        }

        $lockWaitTimeout = max(1, (int) DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS timeout')->timeout);
        try {
            DB::statement('SET SESSION lock_wait_timeout = 5');

            if ($column === null) {
                // Existing records remain NULL and retain historical V1 classification.
                DB::statement('ALTER TABLE `transmittals` ADD COLUMN `workflow_version` VARCHAR(2) NULL, ALGORITHM=INSTANT');
            }

            if (! $hasParentUniqueIndex) {
                // InnoDB can add this secondary unique index online. LOCK=NONE and INPLACE prevent a table-copy fallback.
                DB::statement('ALTER TABLE `transmittals` ADD UNIQUE INDEX `transmittals_workflow_version_id_unique` (`workflow_version`, `id`), ALGORITHM=INPLACE, LOCK=NONE');
            }
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = '.$lockWaitTimeout);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Destructive rollback is prohibited: preserve workflow_version and classification data.');
    }
};