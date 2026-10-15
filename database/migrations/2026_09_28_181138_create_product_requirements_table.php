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
        Schema::create('product_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('requirement_type')->nullable();

            $table->string('name');

            $table->text('description')->nullable();

            $table->boolean('is_mandatory')
                ->default(true);

            $table->unsignedInteger('sequence')
                ->default(1);

            $table->string('status')
                ->default('Active');

            $table->timestamps();

            $table->index([
                'product_id',
                'sequence',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_requirements');
    }
};