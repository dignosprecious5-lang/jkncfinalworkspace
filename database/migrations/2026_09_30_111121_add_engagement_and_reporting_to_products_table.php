<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Step 7: Engagement Fields
            $table->string('payment_structure')->nullable();
            $table->string('payment_structure_custom')->nullable();
            $table->string('instantiation_execution_mode')->nullable();
            $table->string('recurrence_frequency')->nullable();
            $table->string('recurrence_frequency_custom')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->string('billing_frequency_custom')->nullable();
            $table->string('reporting_frequency')->nullable();
            $table->string('reporting_frequency_custom')->nullable();
            $table->boolean('auto_carryover')->default(true);

            // Step 8: Reporting Fields
            $table->string('project_reporting_type')->nullable();
            $table->json('report_scope')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'payment_structure',
                'payment_structure_custom',
                'instantiation_execution_mode',
                'recurrence_frequency',
                'recurrence_frequency_custom',
                'billing_frequency',
                'billing_frequency_custom',
                'reporting_frequency',
                'reporting_frequency_custom',
                'auto_carryover',
                'project_reporting_type',
                'report_scope',
            ]);
        });
    }
};