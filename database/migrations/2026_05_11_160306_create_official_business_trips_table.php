<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_business_trips', function (Blueprint $table) {
            $table->id();

            $table->string('ob_reference_no')->nullable();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // Personnel details
            $table->string('employee_code')->nullable();
            $table->string('employee_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('immediate_superior')->nullable();
            $table->string('superior_email')->nullable();

            // A. Travel information
            $table->string('destination');
            $table->text('additional_stops')->nullable();
            $table->text('purpose');
            $table->json('purpose_options')->nullable();
            $table->string('purpose_other')->nullable();
            $table->string('trip_type')->nullable();
            $table->date('date_from');
            $table->date('date_to')->nullable();
            $table->time('departure_time')->nullable();
            $table->time('return_time')->nullable();

            // B. Personnel / team
            $table->string('nature_of_travel')->nullable();
            $table->json('team_members')->nullable();

            // C. Client and billability
            $table->boolean('is_client_travel')->default(false);
            $table->string('client_type')->nullable();
            $table->string('client_id_no')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_contract_number')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('travel_billability')->nullable();

            // D. Client payment details
            $table->string('client_payment_status')->nullable();
            $table->json('client_payment_items')->nullable();
            $table->string('client_payment_other')->nullable();
            $table->decimal('amount_client_will_pay', 12, 2)->default(0);

            // E. Travel credits
            $table->json('travel_credit_details')->nullable();

            // F. Mode of transportation
            $table->string('transportation_mode')->nullable();
            $table->json('transportation_details')->nullable();
            $table->decimal('estimated_expenses', 12, 2)->default(0);

            // G. Attachments
            $table->json('attachment_paths')->nullable();
            $table->json('attachment_types')->nullable();
            $table->string('attachment_other')->nullable();

            // General
            $table->text('remarks')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_business_trips');
    }
};