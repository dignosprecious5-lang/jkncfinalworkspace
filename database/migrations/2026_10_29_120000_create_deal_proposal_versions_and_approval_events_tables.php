<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_proposal_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_proposal_id')->constrained('deal_proposals')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->unsignedSmallInteger('snapshot_schema_version');
            $table->json('resolved_proposal_data');
            $table->json('commercial_values');
            $table->json('source_metadata');
            $table->json('document_identifiers');
            $table->longText('rendered_html');
            $table->string('artifact_private_path')->unique();
            $table->char('artifact_sha256', 64);
            $table->char('issued_token_sha256', 64)->nullable()->unique();
            $table->string('issued_to_email')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('client_access_expires_at')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['deal_proposal_id', 'version_number'], 'deal_proposal_versions_number_unique');
            $table->index(['deal_proposal_id', 'issued_at']);
        });

        Schema::create('deal_proposal_approval_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deal_proposal_version_id')
                ->constrained('deal_proposal_versions')
                ->restrictOnDelete();
            $table->string('event_type', 40);
            $table->timestamp('occurred_at');
            $table->string('actor_type', 40)->nullable();
            $table->string('actor_name')->nullable();
            $table->text('note')->nullable();
            $table->json('event_metadata')->nullable();
            $table->timestamps();

            $table->index(['deal_proposal_version_id', 'occurred_at'], 'deal_proposal_approval_events_version_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_proposal_approval_events');
        Schema::dropIfExists('deal_proposal_versions');
    }
};
