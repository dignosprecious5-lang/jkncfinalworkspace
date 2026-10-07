<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_terms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_version_id')
                ->constrained('service_versions')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('content');

            $table->unsignedInteger('sort_order')->default(0);

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_terms');
    }
};