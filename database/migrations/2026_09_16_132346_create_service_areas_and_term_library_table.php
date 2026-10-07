<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Gumawa ng service_areas table
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Magdagdag ng service_area_id sa existing services table
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('service_area_id')->nullable()->constrained('service_areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['service_area_id']);
            $table->dropColumn('service_area_id');
        });

        Schema::dropIfExists('service_areas');
    }
};