<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_marketing_idas')) {
            return;
        }

        Schema::table('sales_marketing_idas', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_marketing_idas', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('sales_marketing_idas', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('sales_marketing_idas', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('submitted_by');
            }

            if (!Schema::hasColumn('sales_marketing_idas', 'accepted_by')) {
                $table->foreignId('accepted_by')->nullable()->after('accepted_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('sales_marketing_idas', 'reverted_at')) {
                $table->timestamp('reverted_at')->nullable()->after('accepted_by');
            }

            if (!Schema::hasColumn('sales_marketing_idas', 'reverted_by')) {
                $table->foreignId('reverted_by')->nullable()->after('reverted_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_marketing_idas')) {
            return;
        }

        Schema::table('sales_marketing_idas', function (Blueprint $table) {
            if (Schema::hasColumn('sales_marketing_idas', 'reverted_by')) {
                $table->dropConstrainedForeignId('reverted_by');
            }

            if (Schema::hasColumn('sales_marketing_idas', 'reverted_at')) {
                $table->dropColumn('reverted_at');
            }

            if (Schema::hasColumn('sales_marketing_idas', 'accepted_by')) {
                $table->dropConstrainedForeignId('accepted_by');
            }

            if (Schema::hasColumn('sales_marketing_idas', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }

            if (Schema::hasColumn('sales_marketing_idas', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }

            if (Schema::hasColumn('sales_marketing_idas', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });
    }
};