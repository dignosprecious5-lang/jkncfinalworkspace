<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('deal_name');
            $table->string('stage')->default('Inquiry');
            $table->string('pipeline_stage')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->string('service_area')->nullable();
            $table->string('services')->nullable();
            $table->string('products')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->string('engagement_type')->nullable();
            $table->string('requirements_status')->nullable();
            $table->text('required_actions')->nullable();
            $table->decimal('estimated_professional_fee', 12, 2)->nullable();
            $table->decimal('estimated_government_fees', 12, 2)->nullable();
            $table->decimal('estimated_service_support_fee', 12, 2)->nullable();
            $table->decimal('total_estimated_engagement_value', 12, 2)->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('payment_terms_other')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->string('estimated_duration')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->date('client_preferred_completion_date')->nullable();
            $table->date('confirmed_delivery_date')->nullable();
            $table->text('timeline_notes')->nullable();
            $table->string('service_complexity')->nullable();
            $table->string('support_required')->nullable();
            $table->text('complexity_notes')->nullable();
            $table->string('proposal_decision')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('service_department_unit')->nullable();
            $table->text('consultant_notes')->nullable();
            $table->text('associate_notes')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('salutation')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
