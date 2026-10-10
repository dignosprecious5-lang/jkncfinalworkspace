<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_reminders')) {
            Schema::create('task_reminders', function (Blueprint $table) {
                $table->id();

                $table->foreignId('operational_task_id')
                    ->constrained('operational_tasks')
                    ->cascadeOnDelete();

                $table->string('reminder_type');
                $table->date('target_due_date');

                $table->timestamps();

                $table->unique(
                    [
                        'operational_task_id',
                        'reminder_type',
                        'target_due_date',
                    ],
                    'task_reminder_unique_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};
