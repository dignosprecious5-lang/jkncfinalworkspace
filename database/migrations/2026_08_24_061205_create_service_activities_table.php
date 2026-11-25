<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('service_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_version_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('service_activities')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sequence')->default(1);
            $table->boolean('is_mandatory')->default(true);
            $table->decimal('expected_working_hours', 8, 2)->default(0.00);
            $table->integer('expected_days')->default(0);
            $table->boolean('is_billable')->default(true);
            $table->boolean('include_in_report')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('service_activities');
    }
};