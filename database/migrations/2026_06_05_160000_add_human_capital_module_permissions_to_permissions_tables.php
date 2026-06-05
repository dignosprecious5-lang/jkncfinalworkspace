<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'access_hc_organizational',
        'access_hc_payroll',
        'access_hc_employee_profile',
        'access_hc_recruitment',
        'access_hc_onboarding',
        'access_hc_deployment',
        'access_hc_offboarding',
        'access_hc_attendance',
        'access_hc_obf',
        'access_hc_employee_requests',
        'access_hc_employee_relations',
        'access_hc_memos',
        'access_hc_training',
        'access_hc_performance',
        'access_hc_awards',
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
                ->update([
                    'access_human_capital' => true,
                    ...array_fill_keys($this->columns, true),
                ]);

            DB::table('role_permissions')
                ->where('role', 'Employee')
                ->update([
                    'access_human_capital' => true,
                    'access_hc_employee_profile' => true,
                    'access_hc_attendance' => true,
                    'access_hc_obf' => true,
                    'access_hc_employee_requests' => true,
                    'access_hc_employee_relations' => true,
                    'access_hc_memos' => true,
                    'access_hc_training' => true,
                    'access_hc_performance' => true,
                    'access_hc_awards' => true,
                ]);
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

            if (! $existingColumns) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
