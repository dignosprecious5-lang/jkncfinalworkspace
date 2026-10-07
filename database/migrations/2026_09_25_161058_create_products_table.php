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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('product_type')->default('Service');
            $table->string('category')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('inventory_stock')->default(0); // Dito nakalagay ang stock / inventory level
            $table->string('status')->default('active'); // active, pending, rejected
            $table->string('service_area')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};