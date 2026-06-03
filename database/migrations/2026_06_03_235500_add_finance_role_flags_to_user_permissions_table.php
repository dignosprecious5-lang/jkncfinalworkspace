<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_permissions')) {
            return;
        }

        Schema::table('user_permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('user_permissions', 'finance_treasurer')) {
                $table->boolean('finance_treasurer')->default(false)->after('approve_finance');
            }

            if (! Schema::hasColumn('user_permissions', 'finance_president')) {
                $table->boolean('finance_president')->default(false)->after('finance_treasurer');
            }

            if (! Schema::hasColumn('user_permissions', 'finance_approver')) {
                $table->boolean('finance_approver')->default(false)->after('finance_president');
            }
        });

        if (Schema::hasColumn('user_permissions', 'approve_finance') && Schema::hasColumn('user_permissions', 'finance_approver')) {
            DB::table('user_permissions')
                ->where('approve_finance', true)
                ->update(['finance_approver' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_permissions')) {
            return;
        }

        Schema::table('user_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('user_permissions', 'finance_approver')) {
                $table->dropColumn('finance_approver');
            }

            if (Schema::hasColumn('user_permissions', 'finance_president')) {
                $table->dropColumn('finance_president');
            }

            if (Schema::hasColumn('user_permissions', 'finance_treasurer')) {
                $table->dropColumn('finance_treasurer');
            }
        });
    }
};
