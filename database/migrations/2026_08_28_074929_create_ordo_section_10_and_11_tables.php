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
        // 1. Engagements Table
        if (!Schema::hasTable('engagements')) {
            Schema::create('engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained();
                $table->foreignId('service_version_id')->constrained();
                $table->enum('type', ['project', 'regular']);
                $table->string('status')->default('active');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->json('service_snapshot');
                $table->timestamps();
            });
        }

        // 2. Engagement Recurrence Periods (Section 10.1 JIT Generation)
        if (!Schema::hasTable('engagement_periods')) {
            Schema::create('engagement_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('engagement_id')->constrained()->onDelete('cascade');
                $table->string('period_key'); // e.g., "2026-M08", "2026-Q3"
                $table->date('period_start');
                $table->date('period_end');
                $table->string('status')->default('open');
                $table->timestamps();
            });
        }

        // 3. Shared Content Library Items (Section 11 Terms Master)
        if (!Schema::hasTable('shared_content_items')) {
            Schema::create('shared_content_items', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('content_type');
                $table->enum('scope_level', ['global', 'service_area', 'category', 'service_specific']);
                $table->string('service_area')->nullable();
                $table->string('category')->nullable();
                $table->foreignId('service_id')->nullable()->constrained();
                $table->integer('version_number')->default(1);
                $table->boolean('is_active')->default(true);
                $table->text('content');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_content_items');
        Schema::dropIfExists('engagement_periods');
        Schema::dropIfExists('engagements');
    }
};