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
        Schema::table('service_versions', function (Blueprint $table) {
            // Commercials Tab Fields
            if (!Schema::hasColumn('service_versions', 'currency')) {
                $table->string('currency')->default('PHP')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'unit_rate')) {
                $table->decimal('unit_rate', 15, 2)->default(0)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'tax_treatment')) {
                $table->string('tax_treatment')->default('Exclusive')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'minimum_price')) {
                $table->decimal('minimum_price', 15, 2)->default(0)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'minimum_cap')) {
                $table->decimal('minimum_cap', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'maximum_cap')) {
                $table->decimal('maximum_cap', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'discount_allowed')) {
                $table->boolean('discount_allowed')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'max_discount_without_approval')) {
                $table->decimal('max_discount_without_approval', 5, 2)->default(0)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'cost_of_service')) {
                $table->decimal('cost_of_service', 15, 2)->default(0)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'target_margin')) {
                $table->decimal('target_margin', 5, 2)->default(0)->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'payment_terms_type')) {
                $table->string('payment_terms_type')->default('Full Advance')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'payment_terms_notes')) {
                $table->string('payment_terms_notes')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'reimbursables')) {
                $table->json('reimbursables')->nullable();
            }

            // Reporting & Automation Tab Fields
            if (!Schema::hasColumn('service_versions', 'project_reporting_type')) {
                $table->string('project_reporting_type')->default('Progress')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'report_content')) {
                $table->json('report_content')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'auto_create_recurring')) {
                $table->boolean('auto_create_recurring')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'auto_send_due_reminders')) {
                $table->boolean('auto_send_due_reminders')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'auto_escalate_overdue')) {
                $table->boolean('auto_escalate_overdue')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'auto_open_next_period')) {
                $table->boolean('auto_open_next_period')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'auto_trigger_report_prep')) {
                $table->boolean('auto_trigger_report_prep')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'auto_notify_incomplete_reqs')) {
                $table->boolean('auto_notify_incomplete_reqs')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn([
                'currency', 'unit_rate', 'tax_treatment', 'minimum_price',
                'minimum_cap', 'maximum_cap', 'discount_allowed',
                'max_discount_without_approval', 'cost_of_service', 'target_margin',
                'payment_terms_type', 'payment_terms_notes', 'reimbursables',
                'project_reporting_type', 'report_content', 'auto_create_recurring',
                'auto_send_due_reminders', 'auto_escalate_overdue', 'auto_open_next_period',
                'auto_trigger_report_prep', 'auto_notify_incomplete_reqs'
            ]);
        });
    }
};