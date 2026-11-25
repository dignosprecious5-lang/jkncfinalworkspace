<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('base_reference_date')->nullable();
            $table->integer('lead_time_generation')->nullable();
            $table->string('default_auto_assignment_rule')->nullable();
            $table->boolean('auto_carryover_automation')->default(true);
            $table->integer('internal_reminder_trigger')->nullable();
            $table->string('client_followup_cadence')->nullable();
            $table->string('notification_channel')->nullable();
            $table->integer('overdue_escalation_threshold')->nullable();
            $table->string('escalation_recipient_role')->nullable();
            $table->string('missing_requirements_gate_rule')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'base_reference_date',
                'lead_time_generation',
                'default_auto_assignment_rule',
                'auto_carryover_automation',
                'internal_reminder_trigger',
                'client_followup_cadence',
                'notification_channel',
                'overdue_escalation_threshold',
                'escalation_recipient_role',
                'missing_requirements_gate_rule',
            ]);
        });
    }
};