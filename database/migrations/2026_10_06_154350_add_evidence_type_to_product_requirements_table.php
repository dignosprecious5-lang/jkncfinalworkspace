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
        Schema::table('product_requirements', function (Blueprint $table) {
            $table->string('evidence_type')->nullable(); // <-- Idagdag ito
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_requirements', function (Blueprint $table) {
            $table->dropColumn('evidence_type'); // <-- Idagdag ito para sa rollback
        });
    }
};