<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $myHcColumns = [
        'access_hc_my_hc',
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
                if (! Schema::hasColumn($tableName, 'access_hc_my_hc')) {
                    $table->boolean('access_hc_my_hc')->default(false);
                }
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        DB::table('role_permissions')
            ->whereIn('role', ['SuperAdmin', 'Admin'])
            ->update(array_fill_keys($this->myHcColumns, true));

        DB::table('role_permissions')
            ->where('role', 'Employee')
            ->update([
                'access_human_capital' => true,
                ...array_fill_keys($this->myHcColumns, false),
                'access_hc_employee_profile' => false,
            ]);
    }

    public function down(): void
    {
        foreach (['user_permissions', 'role_permissions'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'access_hc_my_hc')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('access_hc_my_hc');
            });
        }
    }
};
