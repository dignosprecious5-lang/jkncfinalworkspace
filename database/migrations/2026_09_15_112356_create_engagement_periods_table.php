<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('engagements')) {
            Schema::create('engagements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_version_id')->nullable();
                $table->string('engagement_type')->default('regular');
                $table->string('status')->default('active');
                $table->date('start_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('engagement_periods')) {
            Schema::create('engagement_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('engagement_id')->constrained('engagements')->onDelete('cascade');
                $table->foreignId('service_version_id')->constrained('service_versions')->onDelete('cascade');
                $table->string('period_name');
                $table->date('period_start_date');
                $table->date('period_end_date');
                $table->string('status')->default('pending');
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_periods');
    }
};
