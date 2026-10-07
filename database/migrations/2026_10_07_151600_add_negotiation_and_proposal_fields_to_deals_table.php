<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (! Schema::hasColumn('deals', 'client_need')) {
                $table->text('client_need')->nullable();
            }
            if (! Schema::hasColumn('deals', 'decision_maker')) {
                $table->text('decision_maker')->nullable();
            }
            if (! Schema::hasColumn('deals', 'consultation_date')) {
                $table->date('consultation_date')->nullable();
            }
            if (! Schema::hasColumn('deals', 'consultation_type')) {
                $table->text('consultation_type')->nullable();
            }
            if (! Schema::hasColumn('deals', 'requirements_confirmed')) {
                $table->boolean('requirements_confirmed')->default(false);
            }
            if (! Schema::hasColumn('deals', 'proposal_number')) {
                $table->text('proposal_number')->nullable();
            }
            if (! Schema::hasColumn('deals', 'proposal_date')) {
                $table->date('proposal_date')->nullable();
            }
            if (! Schema::hasColumn('deals', 'proposal_value')) {
                $table->decimal('proposal_value', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('deals', 'proposal_valid_until')) {
                $table->date('proposal_valid_until')->nullable();
            }
            if (! Schema::hasColumn('deals', 'proposal_status')) {
                $table->text('proposal_status')->nullable();
            }
            if (! Schema::hasColumn('deals', 'proposal_notes')) {
                $table->text('proposal_notes')->nullable();
            }
            if (! Schema::hasColumn('deals', 'negotiation_status')) {
                $table->text('negotiation_status')->nullable();
            }
            if (! Schema::hasColumn('deals', 'final_deal_value')) {
                $table->decimal('final_deal_value', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('deals', 'pricing_model')) {
                $table->text('pricing_model')->nullable();
            }
            if (! Schema::hasColumn('deals', 'negotiation_notes')) {
                $table->text('negotiation_notes')->nullable();
            }
            if (! Schema::hasColumn('deals', 'payment_method')) {
                $table->text('payment_method')->nullable();
            }
            if (! Schema::hasColumn('deals', 'payment_amount')) {
                $table->decimal('payment_amount', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('deals', 'payment_date')) {
                $table->date('payment_date')->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
