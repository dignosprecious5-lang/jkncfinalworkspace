<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_versions', function (Blueprint $table) {
            $table->string('name')->nullable()->after('product_id');
            $table->string('sku')->nullable()->after('name');
            $table->string('service_area')->nullable()->after('sku');
            $table->string('category')->nullable()->after('service_area');
            $table->string('pricing_type')->nullable()->after('category');
            $table->string('tax_treatment')->nullable()->after('pricing_type');
        });
    }

    public function down(): void
    {
        Schema::table('product_versions', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'sku',
                'service_area',
                'category',
                'pricing_type',
                'tax_treatment',
            ]);
        });
    }
};