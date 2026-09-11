<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            // 1. Tatanggalin muna ang column para mabura ang lumang CHECK constraint
            $table->dropColumn('client_type');
        });

        Schema::table('service_requirements', function (Blueprint $table) {
            // 2. Muling lilikhain bilang plain string
            $table->string('client_type')->default('All')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            $table->dropColumn('client_type');
        });
    }
};