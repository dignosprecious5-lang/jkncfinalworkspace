<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable()->unique();
                $table->string('product_type')->default('Service');
                $table->string('category')->nullable();
                $table->decimal('price', 15, 2)->default(0.00);
                $table->integer('inventory_stock')->default(0);
                $table->string('status')->default('active');
                $table->string('service_area')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('products', 'inventory_stock')) {
                $table->integer('inventory_stock')->default(0);
            }
            if (! Schema::hasColumn('products', 'service_area')) {
                $table->text('service_area')->nullable();
            }
        });

        // Safely backfill V2 columns from legacy production columns without deleting legacy data.
        if (Schema::hasColumn('products', 'product_name')) {
            DB::table('products')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];
                    if (empty($row->name) && ! empty($row->product_name)) {
                        $updates['name'] = $row->product_name;
                    }
                    if (($row->inventory_stock === null || (int) $row->inventory_stock === 0) && isset($row->stock_qty) && $row->stock_qty !== null) {
                        $updates['inventory_stock'] = (int) $row->stock_qty;
                    }
                    if (empty($row->service_area) && ! empty($row->product_area)) {
                        $decoded = is_string($row->product_area) ? json_decode($row->product_area, true) : $row->product_area;
                        if (is_array($decoded)) {
                            $first = collect($decoded)->filter(fn ($v) => is_string($v) && trim($v) !== '')->first();
                            if ($first) {
                                $updates['service_area'] = trim($first);
                            }
                        } elseif (is_string($row->product_area)) {
                            $updates['service_area'] = trim($row->product_area);
                        }
                    }
                    if ($updates !== []) {
                        DB::table('products')->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: preserve pre-existing production products table and data.
    }
};
