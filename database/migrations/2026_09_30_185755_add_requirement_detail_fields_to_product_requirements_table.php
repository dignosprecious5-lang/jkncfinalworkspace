```php
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
        Schema::table('product_requirements', function (Blueprint $table) {

            if (!Schema::hasColumn('product_requirements', 'client_type')) {
                $table->string('client_type')
                    ->nullable()
                    ->after('description');
            }

            if (!Schema::hasColumn('product_requirements', 'source')) {
                $table->string('source')
                    ->nullable()
                    ->after('client_type');
            }

            if (!Schema::hasColumn('product_requirements', 'file_required')) {
                $table->boolean('file_required')
                    ->default(false)
                    ->after('source');
            }

            if (!Schema::hasColumn('product_requirements', 'validity_expiration')) {
                $table->string('validity_expiration')
                    ->nullable()
                    ->after('file_required');
            }

            if (!Schema::hasColumn('product_requirements', 'instructions')) {
                $table->text('instructions')
                    ->nullable()
                    ->after('validity_expiration');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_requirements', function (Blueprint $table) {

            $columns = [];

            foreach ([
                'client_type',
                'source',
                'file_required',
                'validity_expiration',
                'instructions',
            ] as $column) {

                if (Schema::hasColumn('product_requirements', $column)) {
                    $columns[] = $column;
                }

            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }

        });
    }
};