<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('deal_stage_histories')) {
            Schema::create('deal_stage_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
                $table->string('stage')->index();
                $table->timestamp('started_at')->useCurrent()->index();
                $table->timestamp('ended_at')->nullable()->index();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('duration_formatted')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['deal_id', 'stage']);
                $table->index(['deal_id', 'started_at']);
            });
        }

        if (!Schema::hasColumn('deals', 'stage_entered_at')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->timestamp('stage_entered_at')->nullable()->after('pipeline_stage');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('deals', 'stage_entered_at')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropColumn('stage_entered_at');
            });
        }
        Schema::dropIfExists('deal_stage_histories');
    }
};
