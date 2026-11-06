<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = ['manage_deal_stages', 'manage_deals_finance_settings'];

    public function up(): void
    {
        foreach (['role_permissions', 'user_permissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (self::PERMISSIONS as $permission) {
                    if (! Schema::hasColumn($tableName, $permission)) {
                        $table->boolean($permission)->default(false);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['role_permissions', 'user_permissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach (self::PERMISSIONS as $permission) {
                if (Schema::hasColumn($tableName, $permission) && DB::table($tableName)->where($permission, true)->exists()) {
                    throw new RuntimeException("{$tableName}.{$permission} contains grants and cannot be removed safely.");
                }
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (self::PERMISSIONS as $permission) {
                    if (Schema::hasColumn($tableName, $permission)) {
                        $table->dropColumn($permission);
                    }
                }
            });
        }
    }
};
