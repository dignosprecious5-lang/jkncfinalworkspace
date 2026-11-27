<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasColumn('deals', 'assigned_user_id')) {
                $col = $table->foreignId('assigned_user_id')->nullable();
                if (Schema::hasColumn('deals', 'owner_name')) {
                    $col->after('owner_name');
                }
                $col->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deals', 'responsible_user_id')) {
                $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deals', 'reviewer_id')) {
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deals', 'approver_id')) {
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deals', 'assigned_user_ids')) {
                $table->json('assigned_user_ids')->nullable();
            }
        });

        Schema::table('operational_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('operational_tasks', 'assigned_by')) {
                $col = $table->foreignId('assigned_by')->nullable();
                if (Schema::hasColumn('operational_tasks', 'assigned_to')) {
                    $col->after('assigned_to');
                }
                $col->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('operational_tasks', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'assigned_user_id')) {
                $table->dropForeign(['assigned_user_id']);
            }
            if (Schema::hasColumn('deals', 'responsible_user_id')) {
                $table->dropForeign(['responsible_user_id']);
            }
            if (Schema::hasColumn('deals', 'reviewer_id')) {
                $table->dropForeign(['reviewer_id']);
            }
            if (Schema::hasColumn('deals', 'approver_id')) {
                $table->dropForeign(['approver_id']);
            }
            $colsToDrop = array_filter(['assigned_user_id', 'responsible_user_id', 'reviewer_id', 'approver_id', 'assigned_user_ids'], function ($col) {
                return Schema::hasColumn('deals', $col);
            });
            if (! empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });

        Schema::table('operational_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('operational_tasks', 'assigned_by')) {
                $table->dropForeign(['assigned_by']);
                $table->dropColumn('assigned_by');
            }
            if (Schema::hasColumn('operational_tasks', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });
    }
};
