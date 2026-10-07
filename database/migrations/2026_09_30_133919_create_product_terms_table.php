<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_terms', function (Blueprint $table) {
            $table->id();

            // Product na pagmamay-ari ng Term
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Term / Agreement
            $table->string('title');
            $table->text('content');

            // Display order
            $table->unsignedInteger('sort_order')->default(0);

            // active / inactive
            $table->string('status')->default('active');

            // product_specific / global / service_area / category
            $table->string('scope')->default('product_specific');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_terms');
    }
};