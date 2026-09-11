<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            $table->text('conditional_rule')->nullable()->after('instructions');
        });
    }

    public function down(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            $table->dropColumn('conditional_rule');
        });
    }
};