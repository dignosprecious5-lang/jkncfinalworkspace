<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_escalations')) {
            Schema::create('task_escalations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('operational_task_id')->constrained('operational_tasks')->onDelete('cascade');
                $table->string('escalation_type')->default('overdue');
                $table->unsignedBigInteger('target_user_id')->nullable();
                $table->date('escalation_date');
                $table->timestamps();

                // Unique constraint para maiwasan ang duplicate escalations sa parehong araw
                $table->unique(['operational_task_id', 'escalation_type', 'escalation_date'], 'task_escalation_unique_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_escalations');
    }
};