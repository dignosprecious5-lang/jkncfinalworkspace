<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_proposal_versions', function (Blueprint $table): void {
            $table->string('issuance_request_key', 80)->nullable();
            $table->char('issuance_request_fingerprint', 64)->nullable();
            $table->unique('issuance_request_key', 'deal_proposal_versions_issuance_request_key_unique');
        });

        Schema::table('deal_proposal_approval_events', function (Blueprint $table): void {
            $table->string('idempotency_key', 80)->nullable()->unique();
        });

        Schema::create('deal_proposal_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_proposal_version_id')
                ->unique()
                ->constrained('deal_proposal_versions')
                ->restrictOnDelete();
            $table->string('idempotency_key', 100)->unique();
            $table->string('recipient_email');
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('last_error_code', 80)->nullable();
            $table->string('last_error_message', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at'], 'deal_proposal_deliveries_due_index');
            $table->index(['status', 'lease_expires_at'], 'deal_proposal_deliveries_lease_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_proposal_deliveries');

        Schema::table('deal_proposal_approval_events', function (Blueprint $table): void {
            $table->dropUnique('deal_proposal_approval_events_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('deal_proposal_versions', function (Blueprint $table): void {
            $table->dropUnique('deal_proposal_versions_issuance_request_key_unique');
            $table->dropColumn(['issuance_request_key', 'issuance_request_fingerprint']);
        });
    }
};
