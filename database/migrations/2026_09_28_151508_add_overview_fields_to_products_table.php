<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('name');
            $table->string('expected_turnaround')->nullable()->after('short_name');
            $table->date('effective_date')->nullable()->after('expected_turnaround');

            $table->text('internal_description')->nullable()->after('description');
            $table->text('client_description')->nullable()->after('internal_description');
            $table->text('purpose')->nullable()->after('client_description');
            $table->text('when_to_use')->nullable()->after('purpose');
            $table->text('what_product_is_not')->nullable()->after('when_to_use');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'short_name',
                'expected_turnaround',
                'effective_date',
                'internal_description',
                'client_description',
                'purpose',
                'when_to_use',
                'what_product_is_not',
            ]);
        });
    }
};