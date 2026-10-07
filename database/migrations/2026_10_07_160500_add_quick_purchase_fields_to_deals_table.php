<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (!Schema::hasColumn('deals', 'deal_title')) {
                $table->string('deal_title')->nullable();
            }
            if (!Schema::hasColumn('deals', 'owner_name')) {
                $table->string('owner_name')->nullable();
            }
            if (!Schema::hasColumn('deals', 'sex')) {
                $table->string('sex')->nullable();
            }
            if (!Schema::hasColumn('deals', 'middle_initial')) {
                $table->string('middle_initial')->nullable();
            }
            if (!Schema::hasColumn('deals', 'name_extension')) {
                $table->string('name_extension')->nullable();
            }
            if (!Schema::hasColumn('deals', 'date_of_birth')) {
                $table->string('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('deals', 'mobile_number')) {
                $table->string('mobile_number')->nullable();
            }
            if (!Schema::hasColumn('deals', 'company')) {
                $table->string('company')->nullable();
            }
            if (!Schema::hasColumn('deals', 'primary_contact_name')) {
                $table->string('primary_contact_name')->nullable();
            }
            if (!Schema::hasColumn('deals', 'service_areas')) {
                $table->json('service_areas')->nullable();
            }
            if (!Schema::hasColumn('deals', 'services_products')) {
                $table->json('services_products')->nullable();
            }
            if (!Schema::hasColumn('deals', 'est_professional_fee')) {
                $table->decimal('est_professional_fee', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('deals', 'discount')) {
                $table->decimal('discount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('deals', 'amount')) {
                $table->decimal('amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('deals', 'commercial_stage')) {
                $table->string('commercial_stage')->nullable();
            }
            if (!Schema::hasColumn('deals', 'client_search')) {
                $table->text('client_search')->nullable();
            }
            if (!Schema::hasColumn('deals', 'pipeline_stage')) {
                $table->string('pipeline_stage')->nullable();
            }
            if (!Schema::hasColumn('deals', 'inquiry_source')) {
                $table->string('inquiry_source')->nullable();
            }
            if (!Schema::hasColumn('deals', 'inquiry_date')) {
                $table->date('inquiry_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'expected_close')) {
                $table->string('expected_close')->nullable();
            }
            if (!Schema::hasColumn('deals', 'approval_date')) {
                $table->date('approval_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'internal_date')) {
                $table->date('internal_date')->nullable();
            }
            if (!Schema::hasColumn('deals', 'record_custodian')) {
                $table->string('record_custodian')->nullable();
            }
            if (!Schema::hasColumn('deals', 'date_recorded')) {
                $table->date('date_recorded')->nullable();
            }
            if (!Schema::hasColumn('deals', 'date_signed')) {
                $table->date('date_signed')->nullable();
            }
            if (!Schema::hasColumn('deals', 'referred_by')) {
                $table->string('referred_by')->nullable();
            }
            if (!Schema::hasColumn('deals', 'sales_marketing')) {
                $table->string('sales_marketing')->nullable();
            }
            if (!Schema::hasColumn('deals', 'lead_consultant')) {
                $table->string('lead_consultant')->nullable();
            }
            if (!Schema::hasColumn('deals', 'lead_associate')) {
                $table->string('lead_associate')->nullable();
            }
            if (!Schema::hasColumn('deals', 'finance')) {
                $table->string('finance')->nullable();
            }
            if (!Schema::hasColumn('deals', 'president')) {
                $table->string('president')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
