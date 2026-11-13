<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransmittalEmailDispatchAttemptEvent;
use App\Enums\TransmittalEmailDispatchState;
use App\Models\Transmittal;
use App\Models\TransmittalEmailDispatch;
use App\Models\TransmittalRecipient;
use App\Models\User;
use App\Services\TransmittalV2\TransmittalEmailDispatchAttemptRecorder;
use App\Services\TransmittalV2\TransmittalEmailDispatchService;
use App\Services\TransmittalV2\TransmittalDispatchPayloadCipher;
use App\Services\TransmittalV2\MockTransmittalDispatchProvider;
use App\Services\TransmittalV2\BrevoTransmittalDispatchProvider;
use App\Services\TransmittalV2\BrevoTransmittalService;
use App\Services\TransmittalV2\TransmittalV2DispatchWorker;
use App\Services\TransmittalV2\TransmittalDispatchRecovery;
use App\Services\TransmittalV2\TransmittalDispatchResult;
use App\Services\TransmittalV2\TransmittalProviderEventProcessor;
use App\Services\TransmittalV2\TransmittalBrevoEventProcessor;
use App\Services\TransmittalV2\TransmittalDispatchProviderAvailability;
use App\Services\TransmittalV2\TransmittalEmailDispatchResender;
use App\Services\TransmittalV2\TransmittalManualResendAuthorization;
use App\Jobs\TransmittalV2\DispatchTransmittalEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class TransmittalEmailDispatchDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('workflow_version', 8)->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->json('approved_snapshot')->nullable();
            $table->string('status')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('subject')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_recipients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transmittal_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->char('acknowledgment_token_hash', 64)->nullable();
            $table->timestamp('acknowledgment_expires_at')->nullable();
            $table->timestamp('acknowledgment_revoked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_documents', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->string('category')->nullable();
            $table->string('original_name')->nullable(); $table->string('storage_path'); $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('sha256', 64); $table->timestamps();
        });
        Schema::create('transmittal_workspace_items', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedInteger('position')->default(0); $table->timestamps();
        });
        Schema::create('transmittal_attachments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->string('file_path')->nullable();
            $table->string('storage_path')->nullable(); $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable(); $table->unsignedBigInteger('size')->nullable(); $table->timestamps();
        });
        Schema::create('proof_of_transmissions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('recipient_id')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable(); $table->string('channel'); $table->string('status');
            $table->string('provider_message_id')->nullable(); $table->string('transaction_reference')->nullable();
            $table->timestamp('transmitted_at')->nullable(); $table->timestamp('acknowledged_at')->nullable();
            $table->json('provider_metadata')->nullable(); $table->timestamps();
        });
        Schema::create('transmittal_receipts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->string('receipt_no')->nullable();
            $table->date('receipt_date')->nullable(); $table->unsignedBigInteger('recipient_id')->nullable()->unique();
            $table->string('signed_name')->nullable(); $table->string('received_by')->nullable();
            $table->text('comments')->nullable(); $table->string('receipt_code')->nullable();
            $table->json('snapshot')->nullable(); $table->timestamp('received_at')->nullable(); $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable(); $table->timestamps();
        });
        Schema::create('external_acknowledgments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('receipt_id')->nullable(); $table->string('event'); $table->json('details')->nullable();
            $table->timestamp('occurred_at');
        });
        Schema::create('transmittal_histories', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('transmittal_id'); $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action'); $table->text('description'); $table->json('metadata')->nullable(); $table->timestamp('created_at');
        });
        Schema::create('transmittal_email_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('dispatch_uuid')->unique();
            $table->unsignedBigInteger('transmittal_id');
            $table->unsignedBigInteger('recipient_id');
            $table->string('workflow_version', 8);
            $table->char('transmission_version', 64);
            $table->char('dedupe_fingerprint', 64)->unique();
            $table->char('payload_fingerprint', 64);
            $table->char('attachment_manifest_fingerprint', 64);
            $table->longText('encrypted_payload')->nullable();
            $table->longText('wrapped_payload_key')->nullable();
            $table->string('payload_key_id', 80)->nullable();
            $table->string('state', 24);
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 80)->nullable();
            $table->unsignedBigInteger('resend_of_dispatch_id')->nullable();
            $table->uuid('manual_resend_request_uuid')->nullable()->unique();
            $table->text('manual_resend_reason')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('unknown_at')->nullable();
            $table->timestamps();
        });
        Schema::create('transmittal_email_dispatch_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->uuid('attempt_uuid');
            $table->unsignedSmallInteger('sequence_no');
            $table->unsignedBigInteger('dispatch_id');
            $table->string('event_type', 32);
            $table->char('request_fingerprint', 64)->nullable();
            $table->unsignedSmallInteger('provider_http_status')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('sanitized_error_code', 80)->nullable();
            $table->timestamp('occurred_at');
            $table->unique(['attempt_uuid', 'sequence_no', 'event_type']);
        });
        Schema::create('transmittal_provider_events', function (Blueprint $table): void {
            $table->id(); $table->string('provider', 40); $table->string('provider_event_id', 191);
            $table->uuid('dispatch_uuid'); $table->string('provider_message_id')->nullable();
            $table->string('normalized_type', 32); $table->timestamp('provider_occurred_at')->nullable();
            $table->timestamp('received_at'); $table->char('body_fingerprint', 64); $table->char('signature_fingerprint', 64);
            $table->unique(['provider', 'provider_event_id']);
        });
    }

    public function test_intent_is_v2_owned_stable_and_deduplicated_sequentially(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $service = new TransmittalEmailDispatchService;
        $fingerprints = [str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)];

        $first = $service->createPendingIntent($parent, $recipient, $actor, ...$fingerprints);
        $again = $service->createPendingIntent($parent, $recipient, $actor, ...$fingerprints);

        self::assertSame($first->getKey(), $again->getKey());
        self::assertSame($first->dispatch_uuid, $again->dispatch_uuid);
        self::assertSame('v2', $first->workflow_version);
        self::assertSame(TransmittalEmailDispatchState::Pending, $first->state);
        self::assertSame(1, DB::table('transmittal_email_dispatches')->count());
    }

    public function test_database_unique_keys_reject_duplicate_dispatch_and_attempt_events(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent(
            $parent, $recipient, $actor, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)
        );
        try {
            DB::table('transmittal_email_dispatches')->insert([
                'dispatch_uuid' => $dispatch->dispatch_uuid,
                'transmittal_id' => $parent->id,
                'recipient_id' => $recipient->id,
                'workflow_version' => 'v2',
                'transmission_version' => str_repeat('a', 64),
                'dedupe_fingerprint' => $dispatch->dedupe_fingerprint,
                'payload_fingerprint' => str_repeat('b', 64),
                'attachment_manifest_fingerprint' => str_repeat('c', 64),
                'state' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            self::fail('The database must reject duplicate dispatch UUID and fingerprint values.');
        } catch (QueryException) {
            self::assertSame(1, DB::table('transmittal_email_dispatches')->count());
        }

        $attemptUuid = '123e4567-e89b-42d3-a456-426614174010';
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $event = $recorder->beginSubmission($dispatch, $attemptUuid, 1);
        try {
            DB::table('transmittal_email_dispatch_attempts')->insert([
                'event_uuid' => $event->event_uuid,
                'attempt_uuid' => $attemptUuid,
                'sequence_no' => 1,
                'dispatch_id' => $dispatch->id,
                'event_type' => 'submission_started',
                'occurred_at' => now(),
            ]);
            self::fail('The database must reject duplicate attempt event identity.');
        } catch (QueryException) {
            self::assertSame(1, DB::table('transmittal_email_dispatch_attempts')->count());
        }
    }

    public function test_begin_submission_claim_and_first_event_are_atomic_and_idempotent(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent(
            $parent, $recipient, $actor, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)
        );
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $attemptUuid = '123e4567-e89b-42d3-a456-426614174011';
        $started = $recorder->beginSubmission($dispatch, $attemptUuid, 1, str_repeat('d', 64));

        self::assertSame(TransmittalEmailDispatchState::Processing, $dispatch->fresh()->state);
        self::assertSame(1, DB::table('transmittal_email_dispatch_attempts')->count());
        self::assertSame($started->getKey(), $recorder->beginSubmission($dispatch, $attemptUuid, 1, str_repeat('d', 64))->getKey());

        $accepted = $recorder->append(
            $dispatch, $attemptUuid, 1, TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
            str_repeat('d', 64), 202, '<synthetic-provider-id>'
        );
        self::assertNotSame($started->event_uuid, $accepted->event_uuid);
        self::assertSame($accepted->getKey(), $recorder->append(
            $dispatch, $attemptUuid, 1, TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
            str_repeat('d', 64), 202, '<synthetic-provider-id>'
        )->getKey());
        self::assertSame(2, DB::table('transmittal_email_dispatch_attempts')->count());
        self::assertSame(TransmittalEmailDispatchState::Accepted, $dispatch->fresh()->state);
    }

    public function test_intent_rejects_non_v2_parent_and_foreign_recipient(): void
    {
        [$legacyParent, $legacyRecipient, $actor] = $this->records(null);
        $service = new TransmittalEmailDispatchService;
        $fingerprints = [str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)];

        try {
            $service->createPendingIntent($legacyParent, $legacyRecipient, $actor, ...$fingerprints);
            self::fail('Historical NULL parent must not create a V2 dispatch.');
        } catch (NotFoundHttpException) {
            self::assertSame(0, DB::table('transmittal_email_dispatches')->count());
        }

        [$v2Parent, , $v2Actor] = $this->records('v2');
        try {
            $service->createPendingIntent($v2Parent, $legacyRecipient, $v2Actor, ...$fingerprints);
            self::fail('Recipient belonging to another workflow parent must be rejected.');
        } catch (NotFoundHttpException) {
            self::assertSame(0, DB::table('transmittal_email_dispatches')->count());
        }
    }

    public function test_conflicting_immutable_payload_is_rejected_for_same_dispatch_identity(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $service = new TransmittalEmailDispatchService;
        $service->createPendingIntent($parent, $recipient, $actor,
            str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64));

        $this->expectException(LogicException::class);
        $service->createPendingIntent($parent, $recipient, $actor,
            str_repeat('a', 64), str_repeat('d', 64), str_repeat('c', 64));
    }

    public function test_sent_recipient_cannot_receive_a_new_dispatch_generation(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $service = new TransmittalEmailDispatchService;
        $original = [str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)];
        $dispatch = $service->createPendingIntent($parent, $recipient, $actor, ...$original);
        DB::table('transmittal_recipients')->where('id', $recipient->id)->update(['sent_at' => now()]);

        self::assertSame($dispatch->getKey(), $service->createPendingIntent($parent, $recipient, $actor, ...$original)->getKey());
        $this->expectException(LogicException::class);
        $service->createPendingIntent($parent, $recipient, $actor,
            str_repeat('e', 64), str_repeat('f', 64), str_repeat('1', 64));
    }

    public function test_unknown_outcome_is_atomic_terminal_and_attempt_evidence_is_append_only(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent(
            $parent, $recipient, $actor, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)
        );
        $attemptId = '123e4567-e89b-42d3-a456-426614174000';
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $event = $recorder->beginSubmission($dispatch, $attemptId, 1, str_repeat('d', 64));

        self::assertSame($event->getKey(), $recorder->beginSubmission($dispatch, $attemptId, 1, str_repeat('d', 64))->getKey());

        $unknown = $recorder->append($dispatch, $attemptId, 1,
            TransmittalEmailDispatchAttemptEvent::OutcomeUnknown, null, null, null, 'provider_timeout');
        self::assertSame(TransmittalEmailDispatchState::Unknown, $dispatch->fresh()->state);
        self::assertSame($unknown->getKey(), $recorder->append($dispatch, $attemptId, 1,
            TransmittalEmailDispatchAttemptEvent::OutcomeUnknown, null, null, null, 'provider_timeout')->getKey());
        try {
            $recorder->append($dispatch, '123e4567-e89b-42d3-a456-426614174002', 3,
                TransmittalEmailDispatchAttemptEvent::SubmissionStarted);
            self::fail('Unknown dispatch must not append a new submission attempt.');
        } catch (LogicException) {
            self::assertSame(2, DB::table('transmittal_email_dispatch_attempts')->count());
        }

        $event->sanitized_error_code = 'overwritten';
        try {
            $event->save();
            self::fail('Attempt evidence must not be updated.');
        } catch (LogicException) {
            self::assertSame(null, DB::table('transmittal_email_dispatch_attempts')->where('id', $event->id)->value('sanitized_error_code'));
        }
        self::assertSame(TransmittalEmailDispatchState::Unknown, $dispatch->fresh()->state);
    }

    public function test_provider_acceptance_does_not_persist_as_delivery_without_verified_projection(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent(
            $parent, $recipient, $actor, str_repeat('a', 64), str_repeat('b', 64), str_repeat('c', 64)
        );
        (new TransmittalEmailDispatchAttemptRecorder)->beginSubmission(
            $dispatch, '123e4567-e89b-42d3-a456-426614174003', 1
        );
        $accepted = (new TransmittalEmailDispatchAttemptRecorder)->append(
            $dispatch, '123e4567-e89b-42d3-a456-426614174003', 1,
            TransmittalEmailDispatchAttemptEvent::ProviderAccepted, null, 202, '<synthetic-provider-id>'
        );
        self::assertSame('provider_accepted', $accepted->event_type->value);
        self::assertSame(TransmittalEmailDispatchState::Accepted, $dispatch->fresh()->state);
        self::assertNotNull($dispatch->fresh()->accepted_at);

        $dispatch->refresh();
        $dispatch->state = TransmittalEmailDispatchState::Delivered;
        $this->expectException(LogicException::class);
        $dispatch->save();
    }

    public function test_dispatch_payload_is_encrypted_and_missing_key_fails_closed(): void
    {
        $key = 'base64:'.base64_encode(str_repeat('K', 32));
        config(['transmittal_v2.dispatch.current_key_id' => 'test-v1', 'transmittal_v2.dispatch.keyring' => ['test-v1' => $key]]);
        $cipher = new TransmittalDispatchPayloadCipher;
        $payload = ['to' => ['email' => 'secret@example.invalid'], 'subject' => 'Sensitive', 'html' => 'private-token-value'];
        $envelope = $cipher->encrypt($payload);

        self::assertStringNotContainsString('secret@example.invalid', $envelope['ciphertext']);
        self::assertSame($payload, $cipher->decrypt($envelope['ciphertext'], $envelope['wrapped_key'], $envelope['key_id']));
        config(['transmittal_v2.dispatch.keyring' => []]);
        $this->expectException(\RuntimeException::class);
        $cipher->decrypt($envelope['ciphertext'], $envelope['wrapped_key'], $envelope['key_id']);
    }

    public function test_mock_worker_commits_acceptance_and_audit_without_claiming_delivery(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $recipient->forceFill(['email' => 'recipient@example.invalid', 'acknowledgment_token_hash' => hash('sha256', 'synthetic-token')])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock', 'transmittal_v2.dispatch.current_key_id' => 'test-v1',
            'transmittal_v2.dispatch.keyring' => ['test-v1' => 'base64:'.base64_encode(str_repeat('W', 32))]]);
        $cipher = new TransmittalDispatchPayloadCipher;
        $service = new TransmittalEmailDispatchService;
        $payload = ['to' => ['email' => $recipient->email], 'subject' => 'Synthetic', 'html' => 'Private body', 'attachments' => []];
        $dispatch = $service->createSendableIntent($parent, $recipient, $actor, hash('sha256', 'approved-transmission'), $payload, $cipher);
        $provider = new MockTransmittalDispatchProvider;
        $worker = new TransmittalV2DispatchWorker($provider, $cipher, new TransmittalEmailDispatchAttemptRecorder);

        $result = $worker->handle((int) $dispatch->getKey());
        self::assertSame(TransmittalEmailDispatchState::Accepted, $result->state);
        self::assertNull($result->delivered_at);
        self::assertSame(1, count($provider->submissions()));
        self::assertSame(hash('sha256', 'recipient@example.invalid'), $provider->submissions()[0]['recipient_fingerprint']);
        self::assertArrayNotHasKey('payload', $provider->submissions()[0]);
        self::assertNotNull($recipient->fresh()->sent_at);
        self::assertSame(1, DB::table('proof_of_transmissions')->count());
        self::assertSame('email_dispatch_accepted', DB::table('transmittal_histories')->value('action'));
    }

    public function test_http_transmit_creates_encrypted_outbox_and_queues_mock_job_without_external_send(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $parent->forceFill(['tracking_number' => 'TRN-TEST-01', 'subject' => 'Synthetic approval'])->save();
        $recipient->forceFill(['email' => 'recipient@example.invalid', 'name' => 'Synthetic recipient'])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock', 'transmittal_v2.dispatch.current_key_id' => 'http-test',
            'transmittal_v2.dispatch.keyring' => ['http-test' => 'base64:'.base64_encode(str_repeat('H', 32))]]);
        Queue::fake();
        Http::preventStrayRequests();

        $this->actingAs($actor)->postJson(route('transmittals.transmit', $parent))->assertAccepted();
        $dispatch = TransmittalEmailDispatch::query()->firstOrFail();
        self::assertSame(TransmittalEmailDispatchState::Pending, $dispatch->state);
        self::assertNotNull($dispatch->encrypted_payload);
        self::assertStringNotContainsString('recipient@example.invalid', $dispatch->encrypted_payload);
        self::assertNotNull($recipient->fresh()->acknowledgment_token_hash);
        // The isolated test harness keeps its outer transaction open; after-commit dispatch must not run early.
        Queue::assertNothingPushed();
        Http::assertNothingSent();

        $mock = new MockTransmittalDispatchProvider;
        $worker = new TransmittalV2DispatchWorker($mock, new TransmittalDispatchPayloadCipher,
            new TransmittalEmailDispatchAttemptRecorder);
        $accepted = $worker->handle((int) $dispatch->id);
        self::assertSame(TransmittalEmailDispatchState::Accepted, $accepted->state);
        self::assertSame('sent', $parent->fresh()->status);
        self::assertSame(1, DB::table('proof_of_transmissions')->where('transmittal_id', $parent->id)->count());
        self::assertCount(1, $mock->submissions());
        Http::assertNothingSent();

        $payload = (new TransmittalDispatchPayloadCipher)->decrypt(
            $dispatch->fresh()->encrypted_payload, $dispatch->fresh()->wrapped_payload_key, $dispatch->fresh()->payload_key_id
        );
        $token = $payload['acknowledgment_token'];
        $this->post(route('transmittal-v2.acknowledgments.store', ['token' => $token]), [
            'signed_name' => 'Synthetic Recipient', 'confirmation_check' => '1', 'comments' => 'Received for local acceptance.',
        ])->assertRedirect();
        self::assertSame('acknowledged', $parent->fresh()->status);
        self::assertSame(1, DB::table('transmittal_receipts')->where('recipient_id', $recipient->id)->count());
        $receipt = DB::table('transmittal_receipts')->where('recipient_id', $recipient->id)->first();
        $this->get(route('transmittal-v2.acknowledgments.receipts.pdf', ['token' => $token, 'receipt' => $receipt->id]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_brevo_outbox_adapter_reuses_existing_transport_and_tracks_acceptance_only(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $token = str_repeat('b', 64);
        $recipient->forceFill(['email' => 'brevo-recipient@example.invalid', 'name' => 'Synthetic Recipient',
            'acknowledgment_token_hash' => hash('sha256', $token)])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'brevo', 'transmittal_v2.brevo.outbox_enabled' => true,
            'transmittal_v2.brevo.api_key' => 'synthetic-brevo-key',
            'transmittal_v2.brevo.sender_email' => 'sender@example.invalid',
            'transmittal_v2.brevo.sender_name' => 'Synthetic ORDO',
            'transmittal_v2.dispatch.current_key_id' => 'brevo-test',
            'transmittal_v2.dispatch.keyring' => ['brevo-test' => 'base64:'.base64_encode(str_repeat('B', 32))]]);
        $http = $this->fakeBrevoHttp(['messageId' => '<synthetic-message@brevo.test>'], 201);

        $cipher = new TransmittalDispatchPayloadCipher;
        $dispatch = (new TransmittalEmailDispatchService)->createSendableIntent($parent, $recipient, $actor,
            hash('sha256', 'brevo-immutable-version'), [
                'to' => ['email' => $recipient->email, 'name' => $recipient->name],
                'subject' => 'Synthetic transmittal', 'html' => '<p>Synthetic body</p>',
                'acknowledgment_token' => $token, 'attachments' => [],
            ], $cipher);
        self::assertTrue(TransmittalDispatchProviderAvailability::isEnabled());
        $worker = new TransmittalV2DispatchWorker(new BrevoTransmittalDispatchProvider(new BrevoTransmittalService),
            $cipher, new TransmittalEmailDispatchAttemptRecorder);

        $accepted = $worker->handle((int) $dispatch->id);

        self::assertSame(TransmittalEmailDispatchState::Accepted, $accepted->state);
        self::assertSame('<synthetic-message@brevo.test>', $accepted->provider_message_id);
        self::assertNull($accepted->delivered_at);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'synthetic-brevo-key')
            && ($request->data()['tags'] ?? null) === ['ordo-v2-'.$dispatch->dispatch_uuid]
            && ($request->data()['headers']['X-Entity-Ref-ID'] ?? null) === $dispatch->dispatch_uuid
            && ($request->data()['to'][0]['email'] ?? null) === 'brevo-recipient@example.invalid');
        Http::assertSentCount(1);
    }

    public function test_brevo_rate_limit_response_is_unknown_and_never_automatically_retried(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $recipient->forceFill(['email' => 'brevo-recipient@example.invalid',
            'acknowledgment_token_hash' => hash('sha256', 'synthetic-bearer')])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'brevo', 'transmittal_v2.brevo.outbox_enabled' => true,
            'transmittal_v2.brevo.api_key' => 'synthetic-brevo-key',
            'transmittal_v2.brevo.sender_email' => 'sender@example.invalid',
            'transmittal_v2.dispatch.current_key_id' => 'brevo-rate-limit-test',
            'transmittal_v2.dispatch.keyring' => ['brevo-rate-limit-test' => 'base64:'.base64_encode(str_repeat('R', 32))]]);
        $http = $this->fakeBrevoHttp(['code' => 'rate_limit'], 429);
        $cipher = new TransmittalDispatchPayloadCipher;
        $dispatch = (new TransmittalEmailDispatchService)->createSendableIntent($parent, $recipient, $actor,
            hash('sha256', 'brevo-rate-limit-version'), [
                'to' => ['email' => $recipient->email], 'subject' => 'Synthetic', 'html' => '<p>Body</p>',
                'attachments' => [],
            ], $cipher);
        $worker = new TransmittalV2DispatchWorker(new BrevoTransmittalDispatchProvider(new BrevoTransmittalService),
            $cipher, new TransmittalEmailDispatchAttemptRecorder);

        self::assertSame(TransmittalEmailDispatchState::Unknown, $worker->handle((int) $dispatch->id)->state);
        self::assertSame(TransmittalEmailDispatchState::Unknown, $worker->handle((int) $dispatch->id)->state);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'synthetic-brevo-key'));
        Http::assertSentCount(1);
    }

    public function test_brevo_bearer_webhook_correlates_by_tag_and_replay_is_idempotent(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent($parent, $recipient, $actor,
            str_repeat('7', 64), str_repeat('8', 64), str_repeat('9', 64));
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $attemptUuid = '123e4567-e89b-42d3-a456-426614174095';
        $recorder->beginSubmission($dispatch, $attemptUuid, 1);
        $recorder->append($dispatch, $attemptUuid, 1, TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
            null, 201, '<synthetic-message@brevo.test>');
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'brevo', 'transmittal_v2.brevo.outbox_enabled' => true,
            'transmittal_v2.dispatch.event_secret' => str_repeat('W', 40)]);
        $body = json_encode(['event' => 'delivered', 'id' => 14, 'email' => 'private@example.invalid',
            'message-id' => '<synthetic-message@brevo.test>', 'ts_event' => time(),
            'tags' => ['ordo-v2-'.$dispatch->dispatch_uuid]], JSON_THROW_ON_ERROR);
        $uri = route('transmittal-v2.provider-events.store', ['provider' => 'brevo'], false);
        $headers = ['HTTP_AUTHORIZATION' => 'Bearer '.str_repeat('W', 40), 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', $uri, [], [], [], $headers, $body)->assertAccepted()->assertJson(['accepted' => true]);
        $this->call('POST', $uri, [], [], [], $headers, $body)->assertOk()->assertJson(['accepted' => false]);
        self::assertSame(TransmittalEmailDispatchState::Delivered, $dispatch->fresh()->state);
        self::assertSame(1, DB::table('transmittal_provider_events')->where('provider', 'brevo')->count());
        $stored = (array) DB::table('transmittal_provider_events')->where('provider', 'brevo')->first();
        self::assertArrayNotHasKey('raw_body', $stored);
        self::assertArrayNotHasKey('email', $stored);
        self::assertArrayNotHasKey('bearer_token', $stored);

        $headers['HTTP_AUTHORIZATION'] = 'Bearer invalid-token';
        $this->call('POST', $uri, [], [], [], $headers, $body)->assertForbidden();
        self::assertSame(1, DB::table('transmittal_provider_events')->where('provider', 'brevo')->count());
    }

    public function test_brevo_documented_event_names_have_conservative_state_projection(): void
    {
        config(['transmittal_v2.dispatch.event_secret' => str_repeat('E', 40)]);
        $cases = [
            'hard_bounce' => TransmittalEmailDispatchState::Failed,
            'invalid_email' => TransmittalEmailDispatchState::Failed,
            'blocked' => TransmittalEmailDispatchState::Failed,
            'soft_bounce' => TransmittalEmailDispatchState::Accepted,
            'deferred' => TransmittalEmailDispatchState::Accepted,
        ];

        foreach ($cases as $eventName => $expectedState) {
            [$parent, $recipient, $actor] = $this->records('v2');
            $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent($parent, $recipient, $actor,
                hash('sha256', 'brevo-event-'.$eventName), hash('sha256', 'payload-'.$eventName), hash('sha256', 'attachments-'.$eventName));
            $attempts = new TransmittalEmailDispatchAttemptRecorder;
            $attemptUuid = (string) \Illuminate\Support\Str::uuid();
            $attempts->beginSubmission($dispatch, $attemptUuid, 1);
            $messageId = '<synthetic-'.$eventName.'@brevo.test>';
            $attempts->append($dispatch, $attemptUuid, 1, TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
                null, 201, $messageId);
            $body = json_encode(['event' => $eventName, 'email' => $recipient->email, 'id' => 17,
                'message-id' => $messageId, 'ts_event' => time(), 'tags' => ['ordo-v2-'.$dispatch->dispatch_uuid]], JSON_THROW_ON_ERROR);

            self::assertTrue((new TransmittalBrevoEventProcessor)->process($body, str_repeat('E', 40)));
            self::assertSame($expectedState, $dispatch->fresh()->state, $eventName.' projects only the approved state result.');
        }
        self::assertSame(5, DB::table('transmittal_provider_events')->where('provider', 'brevo')->count());
    }

    public function test_unknown_provider_outcome_is_never_automatically_retried(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $recipient->forceFill(['email' => 'recipient@example.invalid', 'acknowledgment_token_hash' => hash('sha256', 'synthetic-token')])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock', 'transmittal_v2.dispatch.current_key_id' => 'test-v1',
            'transmittal_v2.dispatch.keyring' => ['test-v1' => 'base64:'.base64_encode(str_repeat('Y', 32))]]);
        $cipher = new TransmittalDispatchPayloadCipher;
        $dispatch = (new TransmittalEmailDispatchService)->createSendableIntent($parent, $recipient, $actor,
            hash('sha256', 'version'), ['to' => ['email' => $recipient->email], 'subject' => 'Test', 'html' => 'Body', 'attachments' => []], $cipher);
        $provider = new MockTransmittalDispatchProvider(TransmittalDispatchResult::unknown('timeout'));
        $worker = new TransmittalV2DispatchWorker($provider, $cipher, new TransmittalEmailDispatchAttemptRecorder);
        self::assertSame(TransmittalEmailDispatchState::Unknown, $worker->handle((int) $dispatch->id)->state);
        self::assertSame(TransmittalEmailDispatchState::Unknown, $worker->handle((int) $dispatch->id)->state);
        self::assertCount(1, $provider->submissions());
    }

    public function test_worker_requires_owned_hash_verified_private_attachment_references(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $recipient->forceFill(['email' => 'recipient@example.invalid', 'acknowledgment_token_hash' => hash('sha256', 'token')])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock', 'transmittal_v2.dispatch.current_key_id' => 'test-v1',
            'transmittal_v2.dispatch.keyring' => ['test-v1' => 'base64:'.base64_encode(str_repeat('Q', 32))]]);
        Storage::fake('local');
        $path = 'transmittals/'.$parent->id.'/proof.pdf';
        $bytes = "%PDF-1.4\nprivate synthetic attachment\n";
        Storage::disk('local')->put($path, $bytes);
        \App\Models\TransmittalDocument::query()->create([
            'transmittal_id' => $parent->id, 'category' => 'attachment', 'original_name' => 'proof.pdf',
            'storage_path' => $path, 'mime_type' => 'application/pdf', 'size_bytes' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
        ]);
        $cipher = new TransmittalDispatchPayloadCipher;
        $payload = ['to' => ['email' => $recipient->email], 'subject' => 'Attachment', 'html' => 'Body',
            'attachments' => [['disk' => 'local', 'path' => $path, 'sha256' => hash('sha256', $bytes), 'name' => 'proof.pdf']]];
        $dispatch = (new TransmittalEmailDispatchService)->createSendableIntent($parent, $recipient, $actor,
            hash('sha256', 'attachment-version'), $payload, $cipher);
        $provider = new MockTransmittalDispatchProvider;
        $worker = new TransmittalV2DispatchWorker($provider, $cipher, new TransmittalEmailDispatchAttemptRecorder);

        self::assertSame(TransmittalEmailDispatchState::Accepted, $worker->handle((int) $dispatch->id)->state);
        self::assertSame(hash('sha256', 'proof.pdf'), $provider->submissions()[0]['attachment_evidence'][0]['name_fingerprint']);
        self::assertSame(hash('sha256', $bytes), $provider->submissions()[0]['attachment_evidence'][0]['sha256']);
        self::assertSame(strlen($bytes), $provider->submissions()[0]['attachment_evidence'][0]['size_bytes']);
        self::assertArrayNotHasKey('payload', $provider->submissions()[0]);
    }

    public function test_authenticated_provider_event_is_replay_safe_and_projects_delivery(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent($parent, $recipient, $actor,
            str_repeat('1', 64), str_repeat('2', 64), str_repeat('3', 64));
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $attemptUuid = '123e4567-e89b-42d3-a456-426614174099';
        $recorder->beginSubmission($dispatch, $attemptUuid, 1);
        $recorder->append($dispatch, $attemptUuid, 1, TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
            null, 202, 'mock-message-1');
        config(['transmittal_v2.dispatch.event_secret' => str_repeat('s', 32)]);
        $timestamp = (string) time();
        $body = json_encode(['event_id' => 'event-1', 'dispatch_uuid' => $dispatch->dispatch_uuid,
            'message_id' => 'mock-message-1', 'event_type' => 'delivered', 'occurred_at' => time()], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('s', 32));
        $processor = new TransmittalProviderEventProcessor;

        self::assertTrue($processor->process($body, $timestamp, $signature));
        self::assertFalse($processor->process($body, $timestamp, $signature));
        self::assertSame(TransmittalEmailDispatchState::Delivered, $dispatch->fresh()->state);
        self::assertSame(1, DB::table('transmittal_provider_events')->count());
        $this->expectException(\RuntimeException::class);
        $processor->process($body, $timestamp, str_repeat('0', 64));
    }

    public function test_provider_event_http_endpoint_requires_hmac_and_uses_replay_safe_processor(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent($parent, $recipient, $actor,
            str_repeat('7', 64), str_repeat('8', 64), str_repeat('9', 64));
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $attemptId = '123e4567-e89b-42d3-a456-426614174095';
        $recorder->beginSubmission($dispatch, $attemptId, 1);
        $recorder->append($dispatch, $attemptId, 1, TransmittalEmailDispatchAttemptEvent::ProviderAccepted,
            null, 202, 'mock-message-http');
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock', 'transmittal_v2.dispatch.event_secret' => str_repeat('e', 40)]);
        $timestamp = (string) time();
        $body = json_encode(['event_id' => 'http-event-1', 'dispatch_uuid' => $dispatch->dispatch_uuid,
            'message_id' => 'mock-message-http', 'event_type' => 'delivered'], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('e', 40));
        $uri = route('transmittal-v2.provider-events.store', ['provider' => 'mock'], false);
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_ORDO_EVENT_TIMESTAMP' => $timestamp,
            'HTTP_X_ORDO_EVENT_SIGNATURE' => $signature];

        $this->call('POST', $uri, [], [], [], $headers, $body)->assertAccepted();
        $this->call('POST', $uri, [], [], [], $headers, $body)->assertOk();
        self::assertSame(TransmittalEmailDispatchState::Delivered, $dispatch->fresh()->state);
        self::assertSame(1, DB::table('transmittal_provider_events')->count());
    }

    public function test_confirmed_safe_provider_rejection_uses_bounded_delayed_retry(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $recipient->forceFill(['email' => 'recipient@example.invalid', 'acknowledgment_token_hash' => hash('sha256', 'token')])->save();
        config(['transmittal_v2.enabled' => true, 'transmittal_v2.dispatch.enabled' => true,
            'transmittal_v2.dispatch.provider' => 'mock', 'transmittal_v2.dispatch.current_key_id' => 'test-v1',
            'transmittal_v2.dispatch.keyring' => ['test-v1' => 'base64:'.base64_encode(str_repeat('Z', 32))],
            'transmittal_v2.dispatch.max_attempts' => 2]);
        $cipher = new TransmittalDispatchPayloadCipher;
        $dispatch = (new TransmittalEmailDispatchService)->createSendableIntent($parent, $recipient, $actor,
            hash('sha256', 'safe-retry'), ['to' => ['email' => $recipient->email], 'subject' => 'Test', 'html' => 'Body', 'attachments' => []], $cipher);
        $firstProvider = new MockTransmittalDispatchProvider(TransmittalDispatchResult::rejected('rate_limited', 429, true));
        $worker = new TransmittalV2DispatchWorker($firstProvider, $cipher, new TransmittalEmailDispatchAttemptRecorder);
        $retry = $worker->handle((int) $dispatch->id);
        self::assertSame(TransmittalEmailDispatchState::Pending, $retry->state);
        self::assertTrue($retry->available_at->isFuture());
        self::assertSame(1, $retry->attempt_count);
        self::assertCount(1, $firstProvider->submissions());

        DB::table('transmittal_email_dispatches')->where('id', $dispatch->id)->update(['available_at' => now()->subSecond()]);
        $secondProvider = new MockTransmittalDispatchProvider(TransmittalDispatchResult::rejected('rate_limited', 429, true));
        $final = (new TransmittalV2DispatchWorker($secondProvider, $cipher, new TransmittalEmailDispatchAttemptRecorder))
            ->handle((int) $dispatch->id);
        self::assertSame(TransmittalEmailDispatchState::Failed, $final->state);
        self::assertSame(2, $final->attempt_count);
        self::assertCount(1, $secondProvider->submissions());
        self::assertSame(TransmittalEmailDispatchState::Failed,
            (new TransmittalV2DispatchWorker(new MockTransmittalDispatchProvider, $cipher, new TransmittalEmailDispatchAttemptRecorder))
                ->handle((int) $dispatch->id)->state);
    }

    public function test_expired_worker_attempt_becomes_unknown_without_resubmission(): void
    {
        [$parent, $recipient, $actor] = $this->records('v2');
        $dispatch = (new TransmittalEmailDispatchService)->createPendingIntent($parent, $recipient, $actor,
            str_repeat('4', 64), str_repeat('5', 64), str_repeat('6', 64));
        $attemptId = '123e4567-e89b-42d3-a456-426614174098';
        (new TransmittalEmailDispatchAttemptRecorder)->beginSubmission($dispatch, $attemptId, 1);
        DB::table('transmittal_email_dispatches')->where('id', $dispatch->id)->update(['locked_until' => now()->subMinute()]);

        self::assertSame(1, (new TransmittalDispatchRecovery)->markExpiredAsUnknown());
        self::assertSame(TransmittalEmailDispatchState::Unknown, $dispatch->fresh()->state);
        self::assertSame(0, (new TransmittalDispatchRecovery)->markExpiredAsUnknown());
        config(['transmittal_v2.dispatch.event_secret' => str_repeat('r', 32)]);
        $timestamp = (string) time();
        $body = json_encode(['event_id' => 'reconcile-1', 'dispatch_uuid' => $dispatch->dispatch_uuid,
            'message_id' => 'provider-reconciled-id', 'event_type' => 'delivered'], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, str_repeat('r', 32));
        self::assertTrue((new TransmittalProviderEventProcessor)->process($body, $timestamp, $signature));
        self::assertSame(TransmittalEmailDispatchState::Delivered, $dispatch->fresh()->state);
    }

    public function test_manual_resend_requires_designation_reason_and_retains_source_evidence(): void
    {
        [$parent, $recipient, $owner] = $this->records('v2');
        $oldToken = str_repeat('o', 64);
        $recipient->forceFill(['email' => 'recipient@example.invalid', 'acknowledgment_token_hash' => hash('sha256', $oldToken)])->save();
        config(['transmittal_v2.dispatch.current_key_id' => 'test-v1',
            'transmittal_v2.dispatch.keyring' => ['test-v1' => 'base64:'.base64_encode(str_repeat('A', 32))]]);
        $cipher = new TransmittalDispatchPayloadCipher;
        $source = (new TransmittalEmailDispatchService)->createSendableIntent($parent, $recipient, $owner,
            hash('sha256', 'resend-source'), ['to' => ['email' => $recipient->email], 'subject' => 'Test',
                'html' => 'Link https://example.invalid/acknowledgments/'.$oldToken, 'acknowledgment_token' => $oldToken,
                'attachments' => []], $cipher);
        $recorder = new TransmittalEmailDispatchAttemptRecorder;
        $attemptId = '123e4567-e89b-42d3-a456-426614174097';
        $recorder->beginSubmission($source, $attemptId, 1);
        $recorder->append($source, $attemptId, 1, TransmittalEmailDispatchAttemptEvent::OutcomeUnknown, null, null, null, 'timeout');
        $admin = User::query()->create(['role' => 'Admin']);
        $authorization = new TransmittalManualResendAuthorization;
        self::assertFalse($authorization->allows($owner));
        self::assertFalse($authorization->allows($admin));
        config(['transmittal_v2.dispatch.designated_admin_user_ids' => [(int) $admin->id]]);
        self::assertTrue($authorization->allows($admin));
        $resender = new TransmittalEmailDispatchResender($authorization, $cipher);
        $uuid = '123e4567-e89b-42d3-a456-426614174096';
        $resend = $resender->request($source, $admin, 'Operator reconciled and authorized resend.', $uuid);

        self::assertSame(TransmittalEmailDispatchState::Unknown, $source->fresh()->state);
        self::assertSame(TransmittalEmailDispatchState::Pending, $resend->state);
        self::assertSame($source->id, $resend->resend_of_dispatch_id);
        self::assertSame($uuid, $resend->manual_resend_request_uuid);
        self::assertNotSame(hash('sha256', $oldToken), $recipient->fresh()->acknowledgment_token_hash);
        $resentPayload = $cipher->decrypt($resend->encrypted_payload, $resend->wrapped_payload_key, $resend->payload_key_id);
        self::assertNotSame($oldToken, $resentPayload['acknowledgment_token']);
        self::assertStringContainsString($resentPayload['acknowledgment_token'], $resentPayload['html']);
        self::assertSame($resend->id, $resender->request($source, $admin, 'Operator reconciled and authorized resend.', $uuid)->id);
        self::assertSame(1, DB::table('transmittal_histories')->where('action', 'email_dispatch_manual_resend_requested')->count());
    }

    private function records(?string $workflow): array
    {
        $actorId = DB::table('users')->insertGetId(['role' => 'Employee']);
        $parentId = DB::table('transmittals')->insertGetId([
            'workflow_version' => $workflow,
            'prepared_by' => $actorId,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $recipientId = DB::table('transmittal_recipients')->insertGetId([
            'transmittal_id' => $parentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            Transmittal::findOrFail($parentId),
            TransmittalRecipient::findOrFail($recipientId),
            User::findOrFail($actorId),
        ];
    }

    private function fakeBrevoHttp(array $body, int $status): \Illuminate\Http\Client\Factory
    {
        $factory = new class extends \Illuminate\Http\Client\Factory
        {
            public function createPendingRequest()
            {
                $request = parent::createPendingRequest();
                $handler = new \ReflectionProperty(\Illuminate\Http\Client\PendingRequest::class, 'handler');
                $handler->setValue($request, new \GuzzleHttp\Handler\MockHandler);

                return $request;
            }
        };
        $factory->preventStrayRequests();
        self::assertTrue($factory->preventingStrayRequests());
        $factory->fake(function () use ($body, $status) {
            return \Illuminate\Http\Client\Factory::response($body, $status);
        });
        app()->instance(\Illuminate\Http\Client\Factory::class, $factory);
        Http::swap($factory);

        return $factory;
    }
}
