<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'key')) {
                $table->string('key')->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('settings', 'value')) {
                $table->longText('value')->nullable()->after('key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'value')) {
                $table->dropColumn('value');
            }

            if (Schema::hasColumn('settings', 'key')) {
                $table->dropUnique(['key']);
                $table->dropColumn('key');
            }
        });
    }
};
