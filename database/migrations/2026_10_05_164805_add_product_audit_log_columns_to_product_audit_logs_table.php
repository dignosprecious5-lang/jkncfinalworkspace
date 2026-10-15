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
        Schema::table('product_audit_logs', function (Blueprint $table) {

$table->foreignId('product_id')
    ->nullable()
    ->after('id')
    ->constrained('products')
    ->nullOnDelete();

            $table->string('user_name')
                ->nullable()
                ->after('product_id');

            $table->string('action')
                ->nullable()
                ->after('user_name');

            $table->string('title')
                ->nullable()
                ->after('action');

            $table->text('details')
                ->nullable()
                ->after('title');

            $table->text('description')
                ->nullable()
                ->after('details');

            $table->text('message')
                ->nullable()
                ->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_audit_logs', function (Blueprint $table) {

            $table->dropForeign([
                'product_id',
            ]);

            $table->dropColumn([
                'product_id',
                'user_name',
                'action',
                'title',
                'details',
                'description',
                'message',
            ]);
        });
    }
};