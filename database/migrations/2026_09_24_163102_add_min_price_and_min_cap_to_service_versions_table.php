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
            $table->decimal('min_price', 10, 2)->nullable()->after('tax_treatment');
            $table->decimal('min_cap', 10, 2)->nullable()->after('min_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'min_cap']);
        });
    }
};