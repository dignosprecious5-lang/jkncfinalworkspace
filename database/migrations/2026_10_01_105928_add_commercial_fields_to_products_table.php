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
        Schema::table('products', function (Blueprint $table) {

            // =========================================================
            // STEP 6 — PRODUCT COMMERCIALS
            // =========================================================

            $table->text('payment_notes')
                ->nullable()
                ->after('payment_structure_custom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropColumn([
                'payment_notes',
            ]);

        });
    }
};