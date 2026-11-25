<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_versions', 'escalation_threshold_days')) {
                $table->integer('escalation_threshold_days')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'escalation_target_role')) {
                $table->string('escalation_target_role')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'internal_reminder_days')) {
                $table->integer('internal_reminder_days')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'client_reminder_cadence')) {
                $table->string('client_reminder_cadence')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'notification_channel')) {
                $table->string('notification_channel')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'default_assignment_rule')) {
                $table->string('default_assignment_rule')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'base_reference_date')) {
                $table->string('base_reference_date')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'requirement_gate_rule')) {
                $table->string('requirement_gate_rule')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'instantiation_mode')) {
                $table->string('instantiation_mode')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'instantiation_lead_days')) {
                $table->integer('instantiation_lead_days')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'auto_carryover')) {
                $table->boolean('auto_carryover')->default(true);
            }
            if (!Schema::hasColumn('service_versions', 'pricing_model')) {
                $table->string('pricing_model')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'tax_treatment')) {
                $table->string('tax_treatment')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'payment_structure')) {
                $table->string('payment_structure')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'currency')) {
                $table->string('currency')->default('PHP');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn([
                'escalation_threshold_days',
                'escalation_target_role',
                'internal_reminder_days',
                'client_reminder_cadence',
                'notification_channel',
                'default_assignment_rule',
                'base_reference_date',
                'requirement_gate_rule',
                'instantiation_mode',
                'instantiation_lead_days',
                'auto_carryover',
                'pricing_model',
                'tax_treatment',
                'payment_structure',
                'currency',
            ]);
        });
    }
};