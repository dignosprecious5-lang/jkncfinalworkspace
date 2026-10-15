
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_custom_fields')) {
            return;
        }

        Schema::create('product_custom_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
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
    }

    public function down(): void
    {
        // Intentionally do not drop the existing table or its data.
    }
};