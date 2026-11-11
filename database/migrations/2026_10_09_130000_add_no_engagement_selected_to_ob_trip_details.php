<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_business_trip_details', function (Blueprint $table): void {
            $table->boolean('no_engagement_selected')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('official_business_trip_details', function (Blueprint $table): void {
            $table->dropColumn('no_engagement_selected');
        });
    }
};