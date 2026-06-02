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
                $table->boolean('is_active')->default(true)->after('password');
            }

            if (!Schema::hasColumn('users', 'disabled_at')) {
                $table->timestamp('disabled_at')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('users', 'disabled_by')) {
                $table->unsignedBigInteger('disabled_by')->nullable()->after('disabled_at');
            }

            if (!Schema::hasColumn('users', 'disabled_reason')) {
                $table->text('disabled_reason')->nullable()->after('disabled_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['disabled_reason', 'disabled_by', 'disabled_at', 'is_active'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
