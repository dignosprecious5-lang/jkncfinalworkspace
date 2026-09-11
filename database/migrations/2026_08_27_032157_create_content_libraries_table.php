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
        Schema::create('content_libraries', function (Blueprint $table) {
            $table->id();
            // Section 11 Hierarchy Scope: 'global', 'service_area', 'category', 'service'
            $table->string('scope_level')->default('global');
            $table->string('scope_value')->nullable(); // Target value or service_id
            
            // Content Types: 'terms_and_conditions', 'confidentiality', 'exclusions', 'payment_clause', etc.
            $table->string('content_type'); 
            $table->string('title');
            $table->text('body_text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_libraries');
    }
};