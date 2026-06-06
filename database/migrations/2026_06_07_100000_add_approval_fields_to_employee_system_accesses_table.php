<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_system_accesses')) {
            return;
        }

        Schema::table('employee_system_accesses', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_system_accesses', 'approval_status')) {
                $table->string('approval_status')->default('Pending')->after('approved_by');
            }

            if (! Schema::hasColumn('employee_system_accesses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employee_system_accesses')) {
            return;
        }

        Schema::table('employee_system_accesses', function (Blueprint $table) {
            if (Schema::hasColumn('employee_system_accesses', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('employee_system_accesses', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }
};
