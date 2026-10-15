<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit_measure')->nullable()->after('inventory_type');
            $table->string('stock_tracking')->nullable()->after('unit_measure');

            $table->unsignedInteger('reorder_level')->default(0)->after('inventory_stock');
            $table->unsignedInteger('minimum_stock')->default(0)->after('reorder_level');
            $table->unsignedInteger('maximum_stock')->default(0)->after('minimum_stock');

            $table->string('warehouse_location')->nullable()->after('maximum_stock');
            $table->string('storage_location')->nullable()->after('warehouse_location');
            $table->string('stock_status')->nullable()->after('storage_location');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'unit_measure',
                'stock_tracking',
                'reorder_level',
                'minimum_stock',
                'maximum_stock',
                'warehouse_location',
                'storage_location',
                'stock_status',
            ]);
        });
    }
};