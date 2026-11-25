<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->nullable();
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (! Schema::hasColumn('services', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('services', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('services', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
                if (! Schema::hasColumn('services', 'rejected_by')) {
                    $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('services', 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable();
                }
                if (! Schema::hasColumn('services', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
            });
        }

        if (Schema::hasTable('product_versions')) {
            Schema::table('product_versions', function (Blueprint $table) {
                if (! Schema::hasColumn('product_versions', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
                if (! Schema::hasColumn('product_versions', 'rejected_by')) {
                    $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('product_versions', 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable();
                }
                if (! Schema::hasColumn('product_versions', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally NEVER drop users.role or pre-existing services.created_by/approved_by/approved_at columns.
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (Schema::hasColumn('services', 'rejected_by')) {
                    try {
                        $table->dropConstrainedForeignId('rejected_by');
                    } catch (\Throwable) {
                        $table->dropColumn('rejected_by');
                    }
                }
                foreach (['rejected_at', 'rejection_reason'] as $column) {
                    if (Schema::hasColumn('services', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('product_versions')) {
            Schema::table('product_versions', function (Blueprint $table) {
                if (Schema::hasColumn('product_versions', 'rejected_by')) {
                    try {
                        $table->dropConstrainedForeignId('rejected_by');
                    } catch (\Throwable) {
                        $table->dropColumn('rejected_by');
                    }
                }
                foreach (['approved_at', 'rejected_at', 'rejection_reason'] as $column) {
                    if (Schema::hasColumn('product_versions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
