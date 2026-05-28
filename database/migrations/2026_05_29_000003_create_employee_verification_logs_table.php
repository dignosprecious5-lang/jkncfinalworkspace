<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('verification_reference')->unique();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_code')->nullable();
            $table->string('employee_name');
            $table->string('purpose');
            $table->string('requestor_name');
            $table->string('requestor_company')->nullable();
            $table->string('requestor_email');
            $table->string('requestor_contact');
            $table->string('requestor_position')->nullable();
            $table->text('notes')->nullable();
            $table->string('result_status');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_verification_logs');
    }
};
