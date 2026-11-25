<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_term_library', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // global, service_area, category
            $table->foreignId('service_area_id')->nullable()->constrained('service_areas')->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('title');
            $table->text('content');
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_term_library');
    }
};