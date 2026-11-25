<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_requirements', function (Blueprint $table) {
            $table->id();

            $table->string('requirement_name');
            $table->string('client_type')->nullable();

            $table->boolean('is_mandatory')->default(false);

            $table->string('source')->nullable();
            $table->string('file_required')->nullable();

            $table->text('instructions')->nullable();

            $table->string('validity_expiration')->nullable();
            $table->string('document_name')->nullable();

            $table->text('description')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_requirements');
    }
};