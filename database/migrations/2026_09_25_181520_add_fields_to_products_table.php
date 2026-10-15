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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'service_area')) {
                $table->string('service_area')->nullable();
            }
            if (!Schema::hasColumn('products', 'pricing_type')) {
                $table->string('pricing_type')->nullable();
            }
            if (!Schema::hasColumn('products', 'tax_treatment')) {
                $table->string('tax_treatment')->nullable();
            }
            if (!Schema::hasColumn('products', 'inventory_type')) {
                $table->string('inventory_type')->nullable();
            }
            if (!Schema::hasColumn('products', 'inventory_stock')) {
                $table->integer('inventory_stock')->default(0);
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'service_area',
                'pricing_type',
                'tax_treatment',
                'inventory_type',
                'inventory_stock',
                'description'
            ]);
        });
    }
};