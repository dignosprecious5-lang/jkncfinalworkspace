
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_custom_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->string('field_type');
            $table->string('field_label');
            $table->string('field_api_name');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['service_id', 'field_api_name'],
                'service_custom_fields_service_api_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_custom_fields');
    }
};
