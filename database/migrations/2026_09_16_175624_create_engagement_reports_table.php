<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('engagement_reports')) {
            Schema::create('engagement_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('engagement_id')->constrained('engagements')->onDelete('cascade');
                $table->foreignId('engagement_period_id')->constrained('engagement_periods')->onDelete('cascade');
                $table->string('report_type')->default('Regular Progress Report');
                $table->string('reporting_period')->nullable();
                $table->string('preparation_status')->default('prepared');
                $table->timestamp('prepared_at')->nullable();
                $table->timestamps();

                // Unique constraint para hindi maulit ang pag-prepare ng parehong report para sa parehong engagement period at report type
                $table->unique(['engagement_period_id', 'report_type'], 'engagement_period_report_unique_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_reports');
    }
};