<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('employee_name');
            $table->string('request_type');

            // Common / optional fields for all request forms
            $table->string('department')->nullable();
            $table->date('request_date')->nullable();

            // Overtime
            $table->date('overtime_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('total_hours', 8, 2)->nullable();

            // Leave
            $table->string('leave_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('number_of_days', 8, 2)->nullable();
            $table->string('with_pay')->nullable();

            // Attendance Correction
            $table->date('attendance_date')->nullable();
            $table->string('correction_type')->nullable();
            $table->time('correct_time')->nullable();

            // Undertime / Absence
            $table->string('absence_type')->nullable();
            $table->time('time_affected')->nullable();

            // COE
            $table->string('purpose')->nullable();
            $table->date('date_needed')->nullable();
            $table->integer('number_of_copies')->nullable();

            // Shared
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();

            $table->string('status')->default('Pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_requests');
    }
};
