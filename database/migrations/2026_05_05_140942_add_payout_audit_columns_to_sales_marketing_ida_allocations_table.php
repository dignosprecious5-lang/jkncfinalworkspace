<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_marketing_ida_allocations')) {
            return;
        }

        Schema::table('sales_marketing_ida_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_marketing_ida_allocations', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('sales_marketing_ida_allocations', 'requested_by')) {
                $table->foreignId('requested_by')->nullable()->after('requested_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('sales_marketing_ida_allocations', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('requested_by');
            }

            if (!Schema::hasColumn('sales_marketing_ida_allocations', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_marketing_ida_allocations')) {
            return;
        }

        Schema::table('sales_marketing_ida_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('sales_marketing_ida_allocations', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }

            if (Schema::hasColumn('sales_marketing_ida_allocations', 'paid_at')) {
                $table->dropColumn('paid_at');
            }

            if (Schema::hasColumn('sales_marketing_ida_allocations', 'requested_by')) {
                $table->dropConstrainedForeignId('requested_by');
            }

            if (Schema::hasColumn('sales_marketing_ida_allocations', 'requested_at')) {
                $table->dropColumn('requested_at');
            }
        });
    }
};