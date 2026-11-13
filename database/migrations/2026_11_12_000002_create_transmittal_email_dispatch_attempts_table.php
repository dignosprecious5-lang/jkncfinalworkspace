<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Release-candidate migration.
 *
 * Deployment gate: verify target schema and migration history before execution.
 * Event UUID and per-attempt event identity constraints prevent
 * duplicate ledger events. A restrictive foreign key ties every append-only
 * attempt event to its dispatch. A submission start and outcome are separate
 * rows. No token, email, payload, or attachment content.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transmittal_email_dispatches')) {
            throw new RuntimeException('The dispatch table prerequisite is absent.');
        }

        if (Schema::hasTable('transmittal_email_dispatch_attempts')) {
            throw new RuntimeException('Attempt table exists; reconcile schema and migration history instead of recreating it.');
        }

        Schema::create('transmittal_email_dispatch_attempts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('event_uuid')->unique('transmittal_email_attempt_event_uuid_unique');
            $table->uuid('attempt_uuid');
            $table->unsignedSmallInteger('sequence_no');
            $table->unsignedBigInteger('dispatch_id');
            $table->string('event_type', 32);
            $table->char('request_fingerprint', 64)->nullable();
            $table->unsignedSmallInteger('provider_http_status')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('sanitized_error_code', 80)->nullable();
            $table->timestamp('occurred_at');
            $table->index('dispatch_id', 'transmittal_email_attempt_dispatch_idx');
            $table->foreign('dispatch_id', 'transmittal_email_attempt_dispatch_fk')
                ->references('id')->on('transmittal_email_dispatches')->restrictOnDelete();
            $table->unique(
                ['attempt_uuid', 'sequence_no', 'event_type'],
                'transmittal_email_attempt_event_identity_unique'
            );
        });
    }

    public function down(): void
    {
        throw new LogicException('Attempt evidence is append-only and retained; rollback requires separate approval.');
    }
};
