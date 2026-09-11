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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Account Identity
            |--------------------------------------------------------------------------
            */

            $table->string('account_code')->unique();

            $table->string('account_type');
            // Business or Individual

            $table->string('account_name');

            /*
            |--------------------------------------------------------------------------
            | Master Record References
            |--------------------------------------------------------------------------
            |
            | These reference the existing Company / Contact master records
            | once those master tables are established in ORDO Deals.
            |
            */

            $table->unsignedBigInteger('company_id')->nullable();

            $table->unsignedBigInteger('individual_contact_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            $table->string('status')->default('Active');

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('account_type');
            $table->index('status');
            $table->index('company_id');
            $table->index('individual_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};