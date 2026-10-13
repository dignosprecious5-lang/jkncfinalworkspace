<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->nullable();
            });
        }

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('services', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('services', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('services', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('services', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            if (!Schema::hasColumn('services', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('status')->default('incomplete')->change();
        });

        Schema::table('product_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('product_versions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('product_versions', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('product_versions', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            if (!Schema::hasColumn('product_versions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        DB::table('services')
            ->where('status', 'pending_approval')
            ->update(['status' => 'for_approval']);
        DB::table('services')
            ->where('status', 'rejected')
            ->update(['status' => 'inactive']);
        DB::table('services')
            ->where('status', 'approved')
            ->update(['status' => 'active']);

        Schema::table('services', function (Blueprint $table) {
            $table->enum('status', [
                'incomplete',
                'draft',
                'for_approval',
                'active',
                'inactive',
                'archived',
                'superseded',
            ])->default('incomplete')->change();

            foreach (['created_by', 'approved_by', 'rejected_by'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['approved_at', 'rejected_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('product_versions', function (Blueprint $table) {
            if (Schema::hasColumn('product_versions', 'rejected_by')) {
                $table->dropConstrainedForeignId('rejected_by');
            }
            foreach (['approved_at', 'rejected_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('product_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
