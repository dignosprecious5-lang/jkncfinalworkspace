<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['company_id', 'pipeline_stage', 'stage_entered_at'] as $column) {
            if (Schema::hasColumn('deals', $column)) {
                throw new RuntimeException("Unexpected existing deals.{$column}; inspect schema before continuing.");
            }
        }

        Schema::table('deals', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('account_id')->constrained('companies')->nullOnDelete();
            $table->string('pipeline_stage')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->index(['pipeline_stage', 'stage_entered_at'], 'deals_pipeline_stage_entered_index');
        });

        if (Schema::hasTable('deal_stages') && Schema::hasColumn('deals', 'stage_id') && Schema::hasColumn('deal_stages', 'name')) {
            DB::table('deals')
                ->join('deal_stages', 'deals.stage_id', '=', 'deal_stages.id')
                ->whereNull('deals.pipeline_stage')
                ->update(['deals.pipeline_stage' => DB::raw('deal_stages.name')]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: Deal company and stage state must not be removed.');
    }
};
