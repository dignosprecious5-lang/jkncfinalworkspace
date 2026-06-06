<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('users', 'temporary_password_expires_at')) {
                $table->timestamp('temporary_password_expires_at')->nullable()->after('must_change_password');
            }

            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('temporary_password_expires_at');
            }

            if (!Schema::hasColumn('users', 'disabled_at')) {
                $table->timestamp('disabled_at')->nullable()->after('password_changed_at');
            }

            if (!Schema::hasColumn('users', 'disabled_by')) {
                $table->foreignId('disabled_by')->nullable()->after('disabled_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'disabled_reason')) {
                $table->text('disabled_reason')->nullable()->after('disabled_by');
            }

            if (!Schema::hasColumn('users', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('disabled_reason');
            }

            if (!Schema::hasColumn('users', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'archived_reason')) {
                $table->text('archived_reason')->nullable()->after('archived_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'archived_reason',
                'archived_by',
                'archived_at',
                'disabled_reason',
                'disabled_by',
                'disabled_at',
                'password_changed_at',
                'temporary_password_expires_at',
                'must_change_password',
                'is_active',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if (in_array($column, ['disabled_by', 'archived_by'], true)) {
                        try {
                            $table->dropConstrainedForeignId($column);
                        } catch (\Throwable $e) {
                            $table->dropColumn($column);
                        }
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
