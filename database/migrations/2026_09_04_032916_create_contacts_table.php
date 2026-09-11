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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Contact Identity
            |--------------------------------------------------------------------------
            */

            $table->string('contact_code')->unique();

            $table->string('contact_type')->default('Individual');
            // Individual / Company Primary Contact

            /*
            |--------------------------------------------------------------------------
            | Personal Information
            |--------------------------------------------------------------------------
            */

            $table->string('salutation')->nullable();

            $table->string('first_name');

            $table->string('middle_name')->nullable();

            $table->string('last_name');

            $table->string('name_extension')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('sex')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */

            $table->string('email')->nullable();

            $table->string('mobile_number')->nullable();

            $table->text('address')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Company Relationship
            |--------------------------------------------------------------------------
            |
            | For a Business Account, this Contact can be the Primary Contact
            | of the selected Company master record.
            |
            */

            $table->unsignedBigInteger('company_id')->nullable();

            $table->string('position')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
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

            $table->index('contact_type');

            $table->index('company_id');

            $table->index('status');

            $table->index([
                'first_name',
                'last_name',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};