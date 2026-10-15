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

            // =====================================================
            // REQUIREMENT DETAILS
            // =====================================================

            $table->string('client_type')
                ->nullable()
                ->after('requirement_type');

            $table->string('source')
                ->nullable()
                ->after('client_type');

            $table->boolean('file_required')
                ->default(true)
                ->after('source');

            $table->string('validity_expiration')
                ->nullable()
                ->after('file_required');

            $table->text('instructions')
                ->nullable()
                ->after('validity_expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_requirements', function (Blueprint $table) {

            $table->dropColumn([
                'client_type',
                'source',
                'file_required',
                'validity_expiration',
                'instructions',
            ]);
        });
    }
};