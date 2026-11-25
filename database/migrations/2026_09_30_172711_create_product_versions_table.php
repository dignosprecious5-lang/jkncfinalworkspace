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
        Schema::create('product_versions', function (Blueprint $table) {
            $table->id();

            // =========================================================
            // PRODUCT RELATIONSHIP
            // =========================================================
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // =========================================================
            // VERSION CONTROL
            // =========================================================
            $table->string('version_number')->default('V1.0');
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('draft');

            // =========================================================
            // VERSION OWNERSHIP / APPROVAL
            // =========================================================
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // =========================================================
            // PRODUCT OVERVIEW
            // =========================================================
            $table->string('short_name')->nullable();
            $table->string('expected_turnaround')->nullable();
            $table->date('effective_date')->nullable();
            $table->text('description')->nullable();
            $table->text('internal_description')->nullable();
            $table->text('client_description')->nullable();
            $table->text('purpose')->nullable();
            $table->text('when_to_use')->nullable();
            $table->text('what_product_is_not')->nullable();

            // =========================================================
            // PRODUCT CATALOG
            // =========================================================
            $table->text('scope_of_work')->nullable();
            $table->text('deliverables')->nullable();
            $table->text('client_responsibilities')->nullable();
            $table->text('exclusions')->nullable();

            // =========================================================
            // INVENTORY SPECIFICATIONS
            // =========================================================
            $table->string('inventory_type')->nullable();
            $table->integer('inventory_stock')->default(0);
            $table->string('unit_measure')->nullable();
            $table->string('stock_tracking')->nullable();
            $table->integer('reorder_level')->nullable();
            $table->integer('minimum_stock')->nullable();
            $table->integer('maximum_stock')->nullable();
            $table->string('warehouse_location')->nullable();
            $table->string('storage_location')->nullable();
            $table->string('stock_status')->nullable();

            // =========================================================
            // ENGAGEMENT & REPORTING
            // =========================================================
            $table->string('payment_structure')->nullable();
            $table->string('payment_structure_custom')->nullable();
            $table->string('instantiation_execution_mode')->nullable();
            $table->string('recurrence_frequency')->nullable();
            $table->string('recurrence_frequency_custom')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->string('billing_frequency_custom')->nullable();
            $table->string('reporting_frequency')->nullable();
            $table->string('reporting_frequency_custom')->nullable();
            $table->boolean('auto_carryover')->default(false);

            // =========================================================
            // REPORTING
            // =========================================================
            $table->string('project_reporting_type')->nullable();
            $table->json('report_scope')->nullable();

            // =========================================================
            // AUTOMATION
            // =========================================================
            $table->string('base_reference_date')->nullable();
            $table->integer('lead_time_generation')->nullable();
            $table->string('default_auto_assignment_rule')->nullable();
            $table->boolean('auto_carryover_automation')->default(false);
            $table->integer('internal_reminder_trigger')->nullable();
            $table->string('client_followup_cadence')->nullable();
            $table->string('notification_channel')->nullable();
            $table->integer('overdue_escalation_threshold')->nullable();
            $table->string('escalation_recipient_role')->nullable();
            $table->string('missing_requirements_gate_rule')->nullable();

            $table->timestamps();

            // =========================================================
            // VERSION INDEXES
            // =========================================================
            $table->index(['product_id', 'version_number']);
            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_versions');
    }
};