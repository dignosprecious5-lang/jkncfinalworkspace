<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['transmittals', 'users', 'user_permissions', 'role_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required assignment-security table [{$table}] is absent.");
            }
        }

        Schema::table('transmittals', function (Blueprint $table): void {
            foreach (['assigned_reviewer_id', 'v1_preparer_user_id', 'v1_assigned_reviewer_id'] as $column) {
                if (! Schema::hasColumn('transmittals', $column)) {
                    $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
                }
            }
        });

        foreach (['user_permissions', 'role_permissions'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'transmittal_v2_review')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->boolean('transmittal_v2_review')->default(false);
                });
            }
        }

        if (Schema::hasTable('transmittal_v1_assignment_histories')) {
            throw new RuntimeException('V1 assignment history table already exists; reconcile it before applying this migration.');
        }

        Schema::create('transmittal_v1_assignment_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained('transmittals')->restrictOnDelete();
            $table->string('assignment_type', 24);
            $table->string('action', 24);
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            // Keep historical IDs as values so deleting a user cannot erase the assignment trail.
            $table->unsignedBigInteger('previous_assignee_id')->nullable();
            $table->unsignedBigInteger('new_assignee_id')->nullable();
            $table->text('reason');
            $table->timestamp('created_at');
            $table->index(['transmittal_id', 'created_at'], 'transmittal_v1_assign_history_record_idx');
        });
    }

    public function down(): void
    {
        // Assignment and audit data may be populated after activation; never drop it automatically.
    }
};
