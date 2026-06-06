<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'create_user_account',
        'edit_user_account',
        'disable_enable_user_account',
        'reset_user_password',
        'delete_user_account',
    ];

    public function up(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (!Schema::hasColumn('user_permissions', $column)) {
                    $table->boolean($column)->default(false)->after('manage_users');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('user_permissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
