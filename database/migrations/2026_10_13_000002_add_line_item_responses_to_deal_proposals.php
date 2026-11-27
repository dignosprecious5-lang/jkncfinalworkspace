<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_proposals', function (Blueprint $table): void {
            $table->json('line_items_snapshot')->nullable();
        });

        Schema::create('deal_proposal_item_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_proposal_id')->constrained('deal_proposals')->cascadeOnDelete();
            $table->string('line_item_id');
            $table->string('line_item_name');
            $table->string('decision', 20);
            $table->text('comment')->nullable();
            $table->string('client_name')->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();

            $table->index(['deal_proposal_id', 'line_item_id', 'responded_at', 'id'], 'proposal_item_response_latest_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_proposal_item_responses');

        Schema::table('deal_proposals', function (Blueprint $table): void {
            $table->dropColumn('line_items_snapshot');
        });
    }
};
