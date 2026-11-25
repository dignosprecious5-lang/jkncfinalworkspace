<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('deal_proposals') && ! Schema::hasColumn('deal_proposals', 'catalog_snapshot')) {
            Schema::table('deal_proposals', function (Blueprint $table) {
                $table->json('catalog_snapshot')->nullable();
            });
        }

        if (Schema::hasTable('product_terms')) {
            Schema::table('product_terms', function (Blueprint $table) {
                if (! Schema::hasColumn('product_terms', 'scope_service_area')) {
                    $table->text('scope_service_area')->nullable();
                }
                if (! Schema::hasColumn('product_terms', 'scope_category')) {
                    $table->text('scope_category')->nullable();
                }
            });

            if (Schema::hasTable('products')) {
                DB::table('product_terms')
                    ->whereNull('scope_service_area')
                    ->whereNull('scope_category')
                    ->orderBy('id')
                    ->chunkById(200, function ($terms) {
                        foreach ($terms as $term) {
                            $product = DB::table('products')->find($term->product_id);
                            if ($product) {
                                DB::table('product_terms')->where('id', $term->id)->update([
                                    'scope_service_area' => $product->service_area ?? null,
                                    'scope_category' => $product->category ?? null,
                                ]);
                            }
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_terms')) {
            $drop = array_values(array_filter(
                ['scope_service_area', 'scope_category'],
                fn (string $col) => Schema::hasColumn('product_terms', $col)
            ));
            if ($drop !== []) {
                Schema::table('product_terms', fn (Blueprint $table) => $table->dropColumn($drop));
            }
        }
        if (Schema::hasTable('deal_proposals') && Schema::hasColumn('deal_proposals', 'catalog_snapshot')) {
            Schema::table('deal_proposals', fn (Blueprint $table) => $table->dropColumn('catalog_snapshot'));
        }
    }
};
