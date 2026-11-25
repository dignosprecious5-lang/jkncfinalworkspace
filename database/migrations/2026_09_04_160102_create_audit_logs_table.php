<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_audit_logs')) {
            Schema::create('product_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();
                $table->string('user_name')->nullable();
                $table->string('action')->nullable();
                $table->string('title')->nullable();
                $table->text('details')->nullable();
                $table->text('description')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive to preserve audit history.
    }
};
