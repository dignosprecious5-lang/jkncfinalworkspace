<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('service_area');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->enum('status', ['incomplete', 'draft', 'for_approval', 'active', 'inactive', 'archived', 'superseded'])->default('incomplete');
            $table->enum('engagement_behavior', ['project', 'regular', 'both']);
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('services');
    }
};