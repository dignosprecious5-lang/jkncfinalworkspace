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
        Schema::table('engagement_periods', function (Blueprint $table) {
            $table->foreignId('service_version_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('engagement_periods', function (Blueprint $table) {
            $table->dropForeign(['service_version_id']);
            $table->dropColumn('service_version_id');
        });
    }
};