<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->string('record_custodian')->nullable();
            $table->date('date_recorded')->nullable();
            $table->date('date_signed')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'record_custodian',
                'date_recorded',
                'date_signed',
            ]);
        });
    }
};