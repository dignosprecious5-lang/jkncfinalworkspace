<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('assignment_type')->default('general');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('trainer')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('Pending');
            $table->text('remarks')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'training_id', 'assignment_type'], 'training_assignments_unique');
        });

        if (Schema::hasTable('onboarding_trainings')) {
            DB::table('onboarding_trainings')
                ->whereNotNull('employee_id')
                ->whereNotNull('training_id')
                ->orderBy('id')
                ->get()
                ->each(function ($item) {
                    DB::table('training_assignments')->updateOrInsert(
                        [
                            'employee_id' => $item->employee_id,
                            'training_id' => $item->training_id,
                            'assignment_type' => 'onboarding',
                        ],
                        [
                            'start_date' => $item->start_date,
                            'due_date' => $item->due_date,
                            'trainer' => $item->trainer,
                            'description' => $item->description,
                            'status' => $item->status ?: 'Pending',
                            'assigned_by' => $item->created_by,
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
