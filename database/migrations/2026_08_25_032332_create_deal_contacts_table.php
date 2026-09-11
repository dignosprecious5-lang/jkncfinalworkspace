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
        Schema::create('deals', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Basic Deal Information
            |--------------------------------------------------------------------------
            */
            $table->id();

            $table->string('deal_code')->nullable()->unique();
            $table->string('deal_title')->nullable();

            $table->string('customer_type')->nullable();
            $table->string('commercial_stage')->nullable();
            $table->string('client_search')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Owner / Creator
            |--------------------------------------------------------------------------
            */
            $table->string('owner_name')->nullable();
            $table->string('created_by')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */
            $table->string('salutation')->nullable();
            $table->string('sex')->nullable();

            $table->string('first_name')->nullable();
            $table->string('middle_initial')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name_extension')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();

            $table->text('address')->nullable();

            $table->string('company')->nullable();
            $table->text('company_address')->nullable();
            $table->string('position')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact / Company References
            |--------------------------------------------------------------------------
            */
            $table->string('primary_contact_name')->nullable();
            $table->string('company_name')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Service Identification
            |--------------------------------------------------------------------------
            |
            | Stored as JSON because the form allows multiple selections.
            |
            */
            $table->json('service_areas')->nullable();
            $table->json('services_products')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Scope & Engagement
            |--------------------------------------------------------------------------
            */
            $table->text('scope_of_work')->nullable();

            $table->string('engagement_type')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Client Requirements
            |--------------------------------------------------------------------------
            |
            | Example:
            | {
            |   "client_contact_form": "provided",
            |   "deal_form": "pending"
            | }
            |
            */
            $table->json('client_requirements')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Required Actions
            |--------------------------------------------------------------------------
            */
            $table->json('required_actions')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Pricing / Fees
            |--------------------------------------------------------------------------
            */
            $table->decimal('est_professional_fee', 15, 2)->nullable();
            $table->decimal('est_government_fee', 15, 2)->nullable();
            $table->decimal('est_service_support_fee', 15, 2)->nullable();

            $table->decimal('total_service_fee', 15, 2)->nullable();
            $table->decimal('total_product_fee', 15, 2)->nullable();

            $table->decimal('discount', 15, 2)->nullable();

            $table->decimal(
                'total_estimated_engagement_value',
                15,
                2
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Pricing Guides / Other Fees
            |--------------------------------------------------------------------------
            */
            $table->json('services_pricing_guide')->nullable();
            $table->json('products_pricing_guide')->nullable();

            $table->json('other_fees')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Payment Terms
            |--------------------------------------------------------------------------
            */
            $table->string('payment_terms')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estimated Timeline
            |--------------------------------------------------------------------------
            */
            $table->date('planned_start_date')->nullable();

            $table->unsignedInteger('estimated_duration_days')->nullable();

            $table->date('estimated_completion_date')->nullable();
            $table->date('client_preferred_completion_date')->nullable();
            $table->date('confirmed_delivery_date')->nullable();

            $table->text('timeline_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Service Complexity Assessment
            |--------------------------------------------------------------------------
            */
            $table->string('service_complexity')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Professional Support Required
            |--------------------------------------------------------------------------
            */
            $table->json('professional_support_required')->nullable();

            $table->text('complexity_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Proposal Decision
            |--------------------------------------------------------------------------
            */
            $table->string('proposal_decision')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Internal Assignment
            |--------------------------------------------------------------------------
            */
            $table->string('assigned_consultant')->nullable();
            $table->string('assigned_associate')->nullable();
            $table->string('service_department')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
            $table->text('consultant_notes')->nullable();
            $table->text('associate_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Internal Approval
            |--------------------------------------------------------------------------
            */
            $table->string('prepared_by')->nullable();
            $table->string('reviewed_by')->nullable();

            $table->string('approval_name')->nullable();
            $table->date('approval_date')->nullable();

            $table->string('client_fullname_signature')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Sales / Assignment
            |--------------------------------------------------------------------------
            */
            $table->string('referred_by')->nullable();
            $table->string('sales_marketing')->nullable();

            $table->string('lead_consultant')->nullable();
            $table->string('lead_associate')->nullable();

            $table->string('finance')->nullable();

            $table->string('president')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Existing / Kanban Fields
            |--------------------------------------------------------------------------
            */
            $table->date('expected_close')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};