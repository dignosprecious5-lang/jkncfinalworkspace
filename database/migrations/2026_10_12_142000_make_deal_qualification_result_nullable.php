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
        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'qualification_result')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->string('qualification_result')->nullable()->default(null)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'qualification_result')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->string('qualification_result')->default('qualified')->change();
            });
        }
    }
};
