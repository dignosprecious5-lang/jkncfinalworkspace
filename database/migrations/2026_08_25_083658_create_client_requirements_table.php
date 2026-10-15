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
        Schema::create('client_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->string('document_name');
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->string('file_type_allowed')->default('pdf,jpg,png');
            $table->string('status')->default('pending'); // pending, submitted, approved, rejected
            $table->string('file_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_requirements');
    }
};