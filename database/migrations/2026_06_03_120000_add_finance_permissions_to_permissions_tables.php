<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'access_finance',
        'create_finance',
        'approve_finance',
        'access_finance_supplier',
        'access_finance_service',
        'access_finance_product',
        'access_finance_chart_account',
        'access_finance_bank_account',
        'access_finance_pr',
        'access_finance_po',
        'access_finance_ca',
        'access_finance_lr',
        'access_finance_err',
        'access_finance_dv',
        'access_finance_pda',
        'access_finance_crf',
        'access_finance_ibtf',
        'access_finance_arf',
    ];

    public function up(): void
    {
        foreach (['role_permissions', 'user_permissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach ($this->columns as $column) {
                    if (! Schema::hasColumn($tableName, $column)) {
                        $table->boolean($column)->default(false);
                    }
                }
            });
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('role', ['SuperAdmin', 'Admin'])
                ->update(array_fill_keys($this->columns, true));
        }
    }

    public function down(): void
    {
        foreach (['user_permissions', 'role_permissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $existingColumns = array_values(array_filter(
                $this->columns,
                fn (string $column) => Schema::hasColumn($tableName, $column)
            ));

            if (!$existingColumns) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
