<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->foreignId('deal_id')->nullable()->constrained('deals')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('engagement_type')->nullable();
            $table->string('status')->default('Start');
            $table->string('current_phase')->default('Start');
            $table->string('current_step')->default('Start Checklist');
            $table->date('planned_start_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('client_preferred_completion_date')->nullable();
            $table->string('assigned_project_manager')->nullable();
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('client_name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('service_area')->nullable();
            $table->string('services')->nullable();
            $table->string('products')->nullable();
            $table->decimal('deal_value', 12, 2)->nullable();
            $table->text('scope_summary')->nullable();
            $table->string('client_confirmation_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
