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

        if (! Schema::hasTable('operational_tasks')) {
            Schema::create('operational_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('engagement_id')->nullable()->constrained('engagements')->nullOnDelete();
                $table->foreignId('parent_task_id')->nullable()->constrained('operational_tasks')->nullOnDelete();
                $table->foreignId('service_activity_id')->nullable()->constrained('service_activities')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedInteger('expected_days')->default(1);
                $table->unsignedInteger('expected_working_hours')->default(8);
                $table->boolean('is_mandatory')->default(true);
                $table->boolean('is_billable')->default(true);
                $table->string('status')->default('pending');
                $table->timestamps();

                $table->index(['status', 'due_date']);
                $table->index('engagement_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_tasks');
    }
};
