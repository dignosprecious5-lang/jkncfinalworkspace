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
        Schema::table('deals', function (Blueprint $table) {
            // Stage 1: Inquiry
            if (!Schema::hasColumn('deals', 'inquiry_source')) {
                $table->string('inquiry_source')->nullable()->after('pipeline_stage');
            }
            if (!Schema::hasColumn('deals', 'inquiry_date')) {
                $table->date('inquiry_date')->nullable()->after('inquiry_source');
            }
            if (!Schema::hasColumn('deals', 'inquiry_details')) {
                $table->text('inquiry_details')->nullable()->after('inquiry_date');
            }

            // Stage 2: Qualification
            if (!Schema::hasColumn('deals', 'qualification_result')) {
                $table->string('qualification_result')->nullable()->after('inquiry_details');
            }
            if (!Schema::hasColumn('deals', 'client_need')) {
                $table->text('client_need')->nullable()->after('qualification_result');
            }
            if (!Schema::hasColumn('deals', 'decision_maker')) {
                $table->string('decision_maker')->nullable()->after('client_need');
            }
            if (!Schema::hasColumn('deals', 'qualification_notes')) {
                $table->text('qualification_notes')->nullable()->after('decision_maker');
            }

            // Stage 3: Consultation
            if (!Schema::hasColumn('deals', 'consultation_date')) {
                $table->date('consultation_date')->nullable()->after('qualification_notes');
            }
            if (!Schema::hasColumn('deals', 'consultation_type')) {
                $table->string('consultation_type')->nullable()->after('consultation_date');
            }
            if (!Schema::hasColumn('deals', 'requirements_confirmed')) {
                $table->string('requirements_confirmed')->nullable()->after('consultation_type');
            }

            // Stage 4: Proposal
            if (!Schema::hasColumn('deals', 'proposal_number')) {
                $table->string('proposal_number')->nullable()->after('requirements_confirmed');
            }
            if (!Schema::hasColumn('deals', 'proposal_date')) {
                $table->date('proposal_date')->nullable()->after('proposal_number');
            }
            if (!Schema::hasColumn('deals', 'proposal_value')) {
                $table->decimal('proposal_value', 15, 2)->nullable()->after('proposal_date');
            }
            if (!Schema::hasColumn('deals', 'proposal_valid_until')) {
                $table->date('proposal_valid_until')->nullable()->after('proposal_value');
            }
            if (!Schema::hasColumn('deals', 'proposal_status')) {
                $table->string('proposal_status')->nullable()->after('proposal_valid_until');
            }
            if (!Schema::hasColumn('deals', 'proposal_notes')) {
                $table->text('proposal_notes')->nullable()->after('proposal_status');
            }

            // Stage 5: Negotiation
            if (!Schema::hasColumn('deals', 'negotiation_status')) {
                $table->string('negotiation_status')->nullable()->after('proposal_notes');
            }
            if (!Schema::hasColumn('deals', 'final_deal_value')) {
                $table->decimal('final_deal_value', 15, 2)->nullable()->after('negotiation_status');
            }
            if (!Schema::hasColumn('deals', 'pricing_model')) {
                $table->string('pricing_model')->nullable()->after('final_deal_value');
            }
            if (!Schema::hasColumn('deals', 'negotiation_notes')) {
                $table->text('negotiation_notes')->nullable()->after('pricing_model');
            }

            // Stage 6: Payment
            if (!Schema::hasColumn('deals', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('negotiation_notes');
            }
            if (!Schema::hasColumn('deals', 'payment_amount')) {
                $table->decimal('payment_amount', 15, 2)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('deals', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('payment_amount');
            }
            if (!Schema::hasColumn('deals', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('payment_date');
            }
            if (!Schema::hasColumn('deals', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }

            // Stage 7: Activation
            if (!Schema::hasColumn('deals', 'activation_date')) {
                $table->date('activation_date')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('deals', 'assigned_team')) {
                $table->string('assigned_team')->nullable()->after('activation_date');
            }
            if (!Schema::hasColumn('deals', 'assigned_person')) {
                $table->string('assigned_person')->nullable()->after('assigned_team');
            }
            if (!Schema::hasColumn('deals', 'service_start_date')) {
                $table->date('service_start_date')->nullable()->after('assigned_person');
            }
            if (!Schema::hasColumn('deals', 'activation_notes')) {
                $table->text('activation_notes')->nullable()->after('service_start_date');
            }

            // Stage 8: Final Outcome (Closed Won / Closed Lost)
            if (!Schema::hasColumn('deals', 'closed_won_date')) {
                $table->date('closed_won_date')->nullable()->after('activation_notes');
            }
            if (!Schema::hasColumn('deals', 'closed_lost_date')) {
                $table->date('closed_lost_date')->nullable()->after('closed_won_date');
            }
            if (!Schema::hasColumn('deals', 'lost_reason')) {
                $table->string('lost_reason')->nullable()->after('closed_lost_date');
            }
            if (!Schema::hasColumn('deals', 'closing_notes')) {
                $table->text('closing_notes')->nullable()->after('lost_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $columns = [
                'inquiry_source', 'inquiry_date', 'inquiry_details',
                'qualification_result', 'client_need', 'decision_maker', 'qualification_notes',
                'consultation_date', 'consultation_type', 'requirements_confirmed',
                'proposal_number', 'proposal_date', 'proposal_value', 'proposal_valid_until', 'proposal_status', 'proposal_notes',
                'negotiation_status', 'final_deal_value', 'pricing_model', 'negotiation_notes',
                'payment_method', 'payment_amount', 'payment_date', 'payment_status', 'payment_reference',
                'activation_date', 'assigned_team', 'assigned_person', 'service_start_date', 'activation_notes',
                'closed_won_date', 'closed_lost_date', 'lost_reason', 'closing_notes'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
