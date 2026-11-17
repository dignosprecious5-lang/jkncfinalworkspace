<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deal_stage_histories')) {
            throw new RuntimeException('Unexpected existing deal_stage_histories table; inspect schema before continuing.');
        }

        Schema::create('deal_stage_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->restrictOnDelete();
            $table->string('stage');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('duration_formatted')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['deal_id', 'stage'], 'deal_stage_histories_deal_stage_index');
            $table->index(['deal_id', 'started_at'], 'deal_stage_histories_deal_started_index');
            $table->index('ended_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: Deal stage history must not be dropped.');
    }
};
