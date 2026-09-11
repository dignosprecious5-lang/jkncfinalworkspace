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
            // Commercials & Engagement
            if (!Schema::hasColumn('service_versions', 'pricing_model')) {
                $table->string('pricing_model')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'payment_structure')) {
                $table->string('payment_structure')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'instantiation_mode')) {
                $table->string('instantiation_mode')->default('automatic');
            }
            if (!Schema::hasColumn('service_versions', 'recurrence_frequency')) {
                $table->string('recurrence_frequency')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'billing_frequency')) {
                $table->string('billing_frequency')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'instantiation_lead_days')) {
                $table->integer('instantiation_lead_days')->default(15);
            }
            if (!Schema::hasColumn('service_versions', 'auto_carryover')) {
                $table->boolean('auto_carryover')->default(true);
            }

            // Reporting
            if (!Schema::hasColumn('service_versions', 'reporting_frequency')) {
                $table->string('reporting_frequency')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'project_reporting')) {
                $table->string('project_reporting')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'report_content')) {
                $table->json('report_content')->nullable();
            }

            // Automation Rules Flags
            if (!Schema::hasColumn('service_versions', 'auto_create_activities')) {
                $table->boolean('auto_create_activities')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'send_due_reminders')) {
                $table->boolean('send_due_reminders')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'escalate_overdue')) {
                $table->boolean('escalate_overdue')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'open_next_period')) {
                $table->boolean('open_next_period')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'trigger_report_prep')) {
                $table->boolean('trigger_report_prep')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'notify_incomplete_reqs')) {
                $table->boolean('notify_incomplete_reqs')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                'pricing_model', 'payment_structure', 'instantiation_mode',
                'recurrence_frequency', 'billing_frequency', 'instantiation_lead_days',
                'auto_carryover', 'reporting_frequency', 'project_reporting',
                'report_content', 'auto_create_activities', 'send_due_reminders',
                'escalate_overdue', 'open_next_period', 'trigger_report_prep',
                'notify_incomplete_reqs'
            ], function ($column) {
                return Schema::hasColumn('service_versions', $column);
            });

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};