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
        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->foreignId('engagement_period_id')
                  ->nullable()
                  ->constrained('engagement_periods')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('engagement_period_id');
        });
    }
};