<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_improvement_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('employee_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->date('start_date');
            $table->date('target_completion_date');

            // PIP Details - JSON for multiple improvement areas
            $table->json('improvement_areas')->nullable(); // Array of {area, required_action, target_date, responsible_person}

            // Status and notes
            $table->string('status')->default('Ongoing'); // Ongoing, Completed, Discontinued, Escalated
            $table->text('notes')->nullable();
            $table->text('employee_comments')->nullable();

            // Signatures
            $table->string('initiated_by_name')->nullable();
            $table->string('initiated_by_position')->nullable();
            $table->date('initiated_by_date')->nullable();

            $table->string('reviewed_by_name')->nullable();
            $table->string('reviewed_by_position')->nullable();
            $table->date('reviewed_by_date')->nullable();

            $table->string('employee_acknowledgment_date')->nullable();

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_improvement_plans');
    }
};
