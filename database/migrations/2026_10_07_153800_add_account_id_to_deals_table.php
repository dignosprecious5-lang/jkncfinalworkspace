<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasColumn('deals', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('id');
                $table->index('account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'account_id')) {
                try {
                    $table->dropForeign(['account_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('account_id');
            }
        });
    }
};
