<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addColumnIfMissing(Blueprint $table, string $tableName, string $column, callable $definition): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $definition($table);
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('correspondences')) {
            return;
        }

        Schema::table('correspondences', function (Blueprint $table) {
            $tableName = 'correspondences';

            $this->addColumnIfMissing($table, $tableName, 'management_approver_email', fn($table) => $table->string('management_approver_email')->nullable()->after('management_approver_name'));
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_email', fn($table) => $table->string('executive_approver_email')->nullable()->after('executive_approver_name'));
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('correspondences')) {
            return;
        }

        Schema::table('correspondences', function (Blueprint $table) {
            if (Schema::hasColumn('correspondences', 'management_approver_email')) {
                $table->dropColumn('management_approver_email');
            }

            if (Schema::hasColumn('correspondences', 'executive_approver_email')) {
                $table->dropColumn('executive_approver_email');
            }
        });
    }
};
