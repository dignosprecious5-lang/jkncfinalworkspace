<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_custom_fields')) {
            Schema::create('service_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')
                    ->nullable()
                    ->constrained('services')
                    ->cascadeOnDelete();
                $table->string('field_type');
                $table->string('field_label');
                $table->string('field_api_name')->nullable();
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['service_id', 'field_api_name'],
                    'service_custom_fields_service_api_unique'
                );
            });

            return;
        }

        Schema::table('service_custom_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('service_custom_fields', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable();
            }
            if (! Schema::hasColumn('service_custom_fields', 'field_label')) {
                $table->string('field_label')->nullable();
            }
            if (! Schema::hasColumn('service_custom_fields', 'field_api_name')) {
                $table->string('field_api_name')->nullable();
            }
            if (! Schema::hasColumn('service_custom_fields', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (Schema::hasColumn('service_custom_fields', 'field_name')) {
            DB::table('service_custom_fields')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $updates = [];
                    if (empty($row->field_label) && ! empty($row->field_name)) {
                        $updates['field_label'] = $row->field_name;
                    }
                    if (empty($row->field_api_name) && ! empty($row->field_key)) {
                        $updates['field_api_name'] = $row->field_key;
                    }
                    if ($updates !== []) {
                        DB::table('service_custom_fields')->where('id', $row->id)->update($updates);
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
