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
        Schema::table('service_versions', function (Blueprint $table) {
            $table->unsignedInteger('internal_reminder_days')
                  ->nullable()
                  ->after('instantiation_lead_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn('internal_reminder_days');
        });
    }
};