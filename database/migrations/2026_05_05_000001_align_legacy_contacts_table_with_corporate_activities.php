<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contacts')) {
            return;
        }

        $this->addMissingColumn('business_date', fn (Blueprint $table) => $table->date('business_date')->nullable());
        $this->addMissingColumn('intake_date', fn (Blueprint $table) => $table->date('intake_date')->nullable());
        $this->addMissingColumn('customer_type', fn (Blueprint $table) => $table->string('customer_type')->nullable());
        $this->addMissingColumn('client_status', fn (Blueprint $table) => $table->string('client_status')->nullable());
        $this->addMissingColumn('salutation', fn (Blueprint $table) => $table->string('salutation')->nullable());
        $this->addMissingColumn('first_name', fn (Blueprint $table) => $table->string('first_name')->nullable());
        $this->addMissingColumn('middle_initial', fn (Blueprint $table) => $table->string('middle_initial')->nullable());
        $this->addMissingColumn('middle_name', fn (Blueprint $table) => $table->string('middle_name')->nullable());
        $this->addMissingColumn('last_name', fn (Blueprint $table) => $table->string('last_name')->nullable());
        $this->addMissingColumn('name_extension', fn (Blueprint $table) => $table->string('name_extension')->nullable());
        $this->addMissingColumn('sex', fn (Blueprint $table) => $table->string('sex')->nullable());
        $this->addMissingColumn('date_of_birth', fn (Blueprint $table) => $table->date('date_of_birth')->nullable());
        $this->addMissingColumn('company_name', fn (Blueprint $table) => $table->string('company_name')->nullable());
        $this->addMissingColumn('company_address', fn (Blueprint $table) => $table->text('company_address')->nullable());
        $this->addMissingColumn('contact_address', fn (Blueprint $table) => $table->text('contact_address')->nullable());
        $this->addMissingColumn('position', fn (Blueprint $table) => $table->string('position')->nullable());
        $this->addMissingColumn('business_type_organization', fn (Blueprint $table) => $table->string('business_type_organization')->nullable());
        $this->addMissingColumn('organization_type', fn (Blueprint $table) => $table->string('organization_type')->nullable());
        $this->addMissingColumn('organization_type_other', fn (Blueprint $table) => $table->string('organization_type_other')->nullable());
        $this->addMissingColumn('nature_of_business', fn (Blueprint $table) => $table->string('nature_of_business')->nullable());
        $this->addMissingColumn('capitalization_amount', fn (Blueprint $table) => $table->decimal('capitalization_amount', 14, 2)->nullable());
        $this->addMissingColumn('ownership_structure', fn (Blueprint $table) => $table->string('ownership_structure')->nullable());
        $this->addMissingColumn('previous_year_revenue', fn (Blueprint $table) => $table->decimal('previous_year_revenue', 14, 2)->nullable());
        $this->addMissingColumn('years_operating', fn (Blueprint $table) => $table->string('years_operating')->nullable());
        $this->addMissingColumn('projected_current_year_revenue', fn (Blueprint $table) => $table->decimal('projected_current_year_revenue', 14, 2)->nullable());
        $this->addMissingColumn('ownership_flag', fn (Blueprint $table) => $table->string('ownership_flag')->nullable());
        $this->addMissingColumn('foreign_business_nature', fn (Blueprint $table) => $table->text('foreign_business_nature')->nullable());
        $this->addMissingColumn('service_inquiry_types', fn (Blueprint $table) => $table->json('service_inquiry_types')->nullable());
        $this->addMissingColumn('service_inquiry_other', fn (Blueprint $table) => $table->string('service_inquiry_other')->nullable());
        $this->addMissingColumn('service_inquiry_type', fn (Blueprint $table) => $table->string('service_inquiry_type')->nullable());
        $this->addMissingColumn('inquiry', fn (Blueprint $table) => $table->text('inquiry')->nullable());
        $this->addMissingColumn('jknc_notes', fn (Blueprint $table) => $table->text('jknc_notes')->nullable());
        $this->addMissingColumn('sales_marketing', fn (Blueprint $table) => $table->text('sales_marketing')->nullable());
        $this->addMissingColumn('consultant_lead', fn (Blueprint $table) => $table->string('consultant_lead')->nullable());
        $this->addMissingColumn('lead_associate', fn (Blueprint $table) => $table->string('lead_associate')->nullable());
        $this->addMissingColumn('recommendation_options', fn (Blueprint $table) => $table->json('recommendation_options')->nullable());
        $this->addMissingColumn('recommendation_other', fn (Blueprint $table) => $table->string('recommendation_other')->nullable());
        $this->addMissingColumn('lead_source_channels', fn (Blueprint $table) => $table->json('lead_source_channels')->nullable());
        $this->addMissingColumn('lead_source_other', fn (Blueprint $table) => $table->string('lead_source_other')->nullable());
        $this->addMissingColumn('referred_by', fn (Blueprint $table) => $table->string('referred_by')->nullable());
        $this->addMissingColumn('lead_stage', fn (Blueprint $table) => $table->string('lead_stage')->nullable());
        $this->addMissingColumn('recommendation', fn (Blueprint $table) => $table->text('recommendation')->nullable());
        $this->addMissingColumn('phone', fn (Blueprint $table) => $table->string('phone')->nullable());
        $this->addMissingColumn('kyc_status', fn (Blueprint $table) => $table->string('kyc_status')->default('Not Submitted'));
        $this->addMissingColumn('cif_status', fn (Blueprint $table) => $table->string('cif_status')->default('pending'));
        $this->addMissingColumn('cif_submitted_at', fn (Blueprint $table) => $table->timestamp('cif_submitted_at')->nullable());
        $this->addMissingColumn('cif_reviewed_at', fn (Blueprint $table) => $table->timestamp('cif_reviewed_at')->nullable());
        $this->addMissingColumn('cif_reviewed_by', fn (Blueprint $table) => $table->foreignId('cif_reviewed_by')->nullable());
        $this->addMissingColumn('cif_rejection_reason', fn (Blueprint $table) => $table->text('cif_rejection_reason')->nullable());
        $this->addMissingColumn('owner_name', fn (Blueprint $table) => $table->string('owner_name')->nullable());
        $this->addMissingColumn('last_activity_at', fn (Blueprint $table) => $table->timestamp('last_activity_at')->nullable());
        $this->addMissingColumn('lead_source', fn (Blueprint $table) => $table->string('lead_source')->nullable());
        $this->addMissingColumn('description', fn (Blueprint $table) => $table->text('description')->nullable());
        $this->addMissingColumn('cif_no', fn (Blueprint $table) => $table->string('cif_no')->nullable());
        $this->addMissingColumn('tin', fn (Blueprint $table) => $table->string('tin')->nullable());
        $this->addMissingColumn('created_by', fn (Blueprint $table) => $table->foreignId('created_by')->nullable());
        $this->addMissingColumn('cif_access_token', fn (Blueprint $table) => $table->string('cif_access_token')->nullable());
        $this->addMissingColumn('cif_access_expires_at', fn (Blueprint $table) => $table->timestamp('cif_access_expires_at')->nullable());
        $this->addMissingColumn('cif_form_sent_to_email', fn (Blueprint $table) => $table->string('cif_form_sent_to_email')->nullable());
        $this->addMissingColumn('cif_form_sent_at', fn (Blueprint $table) => $table->timestamp('cif_form_sent_at')->nullable());
        $this->addMissingColumn('specimen_access_token', fn (Blueprint $table) => $table->string('specimen_access_token')->nullable());
        $this->addMissingColumn('specimen_access_expires_at', fn (Blueprint $table) => $table->timestamp('specimen_access_expires_at')->nullable());
        $this->addMissingColumn('specimen_form_sent_to_email', fn (Blueprint $table) => $table->string('specimen_form_sent_to_email')->nullable());
        $this->addMissingColumn('specimen_form_sent_at', fn (Blueprint $table) => $table->timestamp('specimen_form_sent_at')->nullable());

        if (Schema::hasColumn('contacts', 'name')) {
            DB::statement("
                UPDATE contacts
                SET
                    first_name = COALESCE(NULLIF(first_name, ''), SUBSTRING_INDEX(TRIM(name), ' ', 1)),
                    last_name = COALESCE(NULLIF(last_name, ''), NULLIF(TRIM(SUBSTRING(TRIM(name), LENGTH(SUBSTRING_INDEX(TRIM(name), ' ', 1)) + 1)), '')),
                    contact_address = COALESCE(NULLIF(contact_address, ''), address),
                    tin = COALESCE(NULLIF(tin, ''), tax_id)
                WHERE name IS NOT NULL AND TRIM(name) <> ''
            ");
        }
    }

    public function down(): void
    {
        // This migration repairs legacy local schemas. Keeping the columns is safer than
        // removing potentially populated application data on rollback.
    }

    private function addMissingColumn(string $column, callable $definition): void
    {
        if (Schema::hasColumn('contacts', $column)) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
};
