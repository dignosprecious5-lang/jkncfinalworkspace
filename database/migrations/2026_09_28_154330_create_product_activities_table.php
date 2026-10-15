<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_activities', function (Blueprint $table) {
            $table->id();

            // Product relationship
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // 2-Level hierarchy
            $table->string('activity_level');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_activities')
                ->nullOnDelete();

            // Activity information
            $table->string('name');
            $table->text('description')->nullable();

            // Workflow settings
            $table->unsignedInteger('sequence')->default(1);

            $table->decimal('expected_days', 8, 2)
                ->default(1);

            $table->decimal('working_hours', 8, 2)
                ->default(1);

            $table->boolean('is_billable')
                ->default(true);

            $table->boolean('is_mandatory')
                ->default(true);

            $table->timestamps();

            // Helpful index
            $table->index([
                'product_id',
                'sequence',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_activities');
    }
};