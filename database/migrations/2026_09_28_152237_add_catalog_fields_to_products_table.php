<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('scope_of_work')->nullable()->after('what_product_is_not');
            $table->text('deliverables')->nullable()->after('scope_of_work');
            $table->text('client_responsibilities')->nullable()->after('deliverables');
            $table->text('exclusions')->nullable()->after('client_responsibilities');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'scope_of_work',
                'deliverables',
                'client_responsibilities',
                'exclusions',
            ]);
        });
    }
};