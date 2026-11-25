<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('service_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_version_id')->constrained()->onDelete('cascade');
            $table->string('requirement_name');
            $table->enum('client_type', ['all', 'individual', 'sole_proprietor', 'corporation', 'partnership', 'cooperative', 'other'])->default('all');
            $table->boolean('is_mandatory')->default(true);
            $table->string('source')->nullable();
            $table->boolean('file_required')->default(false);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('service_requirements');
    }
};