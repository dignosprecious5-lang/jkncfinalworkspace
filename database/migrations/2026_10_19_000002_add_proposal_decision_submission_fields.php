<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_proposals', function (Blueprint $table): void {
            $table->unsignedBigInteger('handler_user_id')->nullable()->index();
            $table->string('handler_name')->nullable();
            $table->string('handler_email')->nullable();
            $table->string('client_decision_status', 40)->nullable();
            $table->timestamp('client_submitted_at')->nullable();
            $table->string('client_submitted_email')->nullable();
            $table->json('decision_summary_snapshot')->nullable();
            $table->timestamp('decision_notified_at')->nullable();
            $table->text('decision_notification_error')->nullable();
            $table->unique('client_access_token', 'proposal_client_access_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('deal_proposals', function (Blueprint $table): void {
            $table->dropUnique('proposal_client_access_token_unique');
            $table->dropIndex(['handler_user_id']);
            $table->dropColumn(['handler_user_id', 'handler_name', 'handler_email', 'client_decision_status', 'client_submitted_at', 'client_submitted_email', 'decision_summary_snapshot', 'decision_notified_at', 'decision_notification_error']);
        });
    }
};
