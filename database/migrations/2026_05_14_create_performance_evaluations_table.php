<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('employee_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->date('evaluation_period_start')->nullable();
            $table->date('evaluation_period_end')->nullable();
            $table->date('evaluation_date');

            // Performance Criteria (12 fields with ratings)
            $table->integer('quality_of_work')->nullable();
            $table->integer('timeliness_compliance')->nullable();
            $table->integer('productivity_output')->nullable();
            $table->integer('attendance_punctuality')->nullable();
            $table->integer('policy_compliance')->nullable();
            $table->integer('task_ownership')->nullable();
            $table->integer('communication_coordination')->nullable();
            $table->integer('teamwork_conduct')->nullable();
            $table->integer('initiative_problem_solving')->nullable();
            $table->integer('adaptability_learning')->nullable();
            $table->integer('client_support')->nullable();
            $table->integer('care_of_resources')->nullable();

            // Remarks for each criteria
            $table->text('quality_of_work_remarks')->nullable();
            $table->text('timeliness_compliance_remarks')->nullable();
            $table->text('productivity_output_remarks')->nullable();
            $table->text('attendance_punctuality_remarks')->nullable();
            $table->text('policy_compliance_remarks')->nullable();
            $table->text('task_ownership_remarks')->nullable();
            $table->text('communication_coordination_remarks')->nullable();
            $table->text('teamwork_conduct_remarks')->nullable();
            $table->text('initiative_problem_solving_remarks')->nullable();
            $table->text('adaptability_learning_remarks')->nullable();
            $table->text('client_support_remarks')->nullable();
            $table->text('care_of_resources_remarks')->nullable();

            // Summary scores
            $table->integer('total_score')->nullable();
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->string('overall_performance_rating')->nullable(); // Excellent, Very Good, Satisfactory, Needs Improvement, Unsatisfactory

            // Key sections
            $table->text('key_strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('employee_comments')->nullable();

            // Supervisor recommendations
            $table->json('supervisor_recommendations')->nullable(); // Array of selected recommendations

            // Signatures
            $table->string('evaluated_by_name')->nullable();
            $table->string('evaluated_by_position')->nullable();
            $table->date('evaluated_by_date')->nullable();

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
        Schema::dropIfExists('performance_evaluations');
    }
};
