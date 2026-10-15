<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. DEALS TABLE
        if (!Schema::hasTable('deals')) {
            Schema::create('deals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->decimal('discount', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        // 2. PROPOSALS TABLE
        if (!Schema::hasTable('proposals')) {
            Schema::create('proposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('status')->default('pending');
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        // 3. ENGAGEMENTS TABLE
        if (!Schema::hasTable('engagements')) {
            Schema::create('engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 4. BILLINGS TABLE
        if (!Schema::hasTable('billings')) {
            Schema::create('billings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        // 5. COLLECTIONS TABLE
        if (!Schema::hasTable('collections')) {
            Schema::create('collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
        Schema::dropIfExists('billings');
        Schema::dropIfExists('engagements');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('deals');
    }
};