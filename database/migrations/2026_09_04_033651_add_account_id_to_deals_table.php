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
        Schema::table('deals', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | R1 — Account Required
            |--------------------------------------------------------------------------
            |
            | Every Deal must belong to an existing Account Party.
            |
            */

            $table->unsignedBigInteger('account_id')
                ->nullable()
                ->after('id');

            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};