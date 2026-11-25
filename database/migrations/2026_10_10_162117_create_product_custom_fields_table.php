<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_custom_fields')) {
            Schema::create('product_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->cascadeOnDelete();
                $table->string('field_type');
                $table->string('field_label');
                $table->string('field_api_name')->nullable();
                $table->text('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['product_id', 'field_api_name'],
                    'product_custom_fields_product_api_unique'
                );
            });

            return;
        }

        Schema::table('product_custom_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('product_custom_fields', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable();
            }
            if (! Schema::hasColumn('product_custom_fields', 'field_label')) {
                $table->string('field_label')->nullable();
            }
            if (! Schema::hasColumn('product_custom_fields', 'field_api_name')) {
                $table->string('field_api_name')->nullable();
            }
            if (! Schema::hasColumn('product_custom_fields', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (Schema::hasColumn('product_custom_fields', 'field_name')) {
            DB::table('product_custom_fields')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];
                    if (empty($row->field_label) && ! empty($row->field_name)) {
                        $updates['field_label'] = $row->field_name;
                    }
                    if (empty($row->field_api_name) && ! empty($row->field_key)) {
                        $updates['field_api_name'] = $row->field_key;
                    }
                    if ($updates !== []) {
                        DB::table('product_custom_fields')->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally do not drop the existing table or its data.
    }
};
