<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V2-only durable outbox migration.
 *
 * The restrictive foreign keys bind each dispatch to a V2 parent, the recipient\r\n * on that same parent, its requester, and any prior dispatch used for a resend.\r\n * Payloads are encrypted and retained without automatic deletion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittals')
            || ! Schema::hasColumn('transmittals', 'workflow_version')
            || ! Schema::hasTable('transmittal_recipients')
            || ! Schema::hasTable('users')) {
            throw new RuntimeException('The V2 parent classification and recipient prerequisites are absent.');
        }

        if (Schema::hasTable('transmittal_email_dispatches')) {
            throw new RuntimeException('Dispatch table exists; reconcile schema and migration history instead of recreating it.');
        }

        Schema::create('transmittal_email_dispatches', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('dispatch_uuid')->unique('transmittal_email_dispatch_uuid_unique');
            $table->unsignedBigInteger('transmittal_id');
            $table->unsignedBigInteger('recipient_id');
            $table->string('workflow_version', 2)->default('v2');
            $table->char('transmission_version', 64);
            $table->char('dedupe_fingerprint', 64)->unique('transmittal_email_dispatch_dedupe_unique');
            $table->char('payload_fingerprint', 64);
            $table->char('attachment_manifest_fingerprint', 64);
            $table->longText('encrypted_payload')->nullable();
            $table->longText('wrapped_payload_key')->nullable();
            $table->string('payload_key_id', 80)->nullable();
            $table->string('state', 24)->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('resend_of_dispatch_id')->nullable();
            $table->uuid('manual_resend_request_uuid')->nullable()->unique('transmittal_email_dispatch_resend_uuid_unique');
            $table->text('manual_resend_reason')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 80)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('unknown_at')->nullable();
            $table->index(['state', 'available_at'], 'transmittal_email_dispatch_claim_idx');
            $table->index('resend_of_dispatch_id', 'transmittal_email_dispatch_resend_idx');
            $table->foreign(['workflow_version', 'transmittal_id'], 'transmittal_email_dispatch_v2_parent_fk')
                ->references(['workflow_version', 'id'])->on('transmittals')->restrictOnDelete();
            $table->foreign(['recipient_id', 'transmittal_id'], 'transmittal_email_dispatch_recipient_parent_fk')
                ->references(['id', 'transmittal_id'])->on('transmittal_recipients')->restrictOnDelete();
            $table->foreign('requested_by', 'transmittal_email_dispatch_requested_by_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->foreign('resend_of_dispatch_id', 'transmittal_email_dispatch_resend_fk')
                ->references('id')->on('transmittal_email_dispatches')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new LogicException('Dispatch evidence is retained; table rollback requires a separate approved preservation plan.');
    }
};
