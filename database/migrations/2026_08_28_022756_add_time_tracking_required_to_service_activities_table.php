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
        if (Schema::hasTable('service_activities')) {
            Schema::table('service_activities', function (Blueprint $table) {
                if (!Schema::hasColumn('service_activities', 'time_tracking_required')) {
                    $table->boolean('time_tracking_required')->default(true)->after('is_billable');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_activities')) {
            Schema::table('service_activities', function (Blueprint $table) {
                if (Schema::hasColumn('service_activities', 'time_tracking_required')) {
                    $table->dropColumn('time_tracking_required');
                }
            });
        }
    }
};