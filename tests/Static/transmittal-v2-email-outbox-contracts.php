<?php

declare(strict_types=1);

// Source-only contract checks. No Composer/Laravel bootstrap, database, storage, queue, or network.
$root = dirname(__DIR__, 2);
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    $checks++;
};
$read = static function (string $relative) use ($root, $check): string {
    $contents = file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
    $check(is_string($contents), 'source exists: '.$relative);
    return $contents;
};

require_once $root.'/app/Enums/TransmittalEmailDispatchState.php';
require_once $root.'/app/Services/TransmittalV2/TransmittalEmailDispatchIdentity.php';
require_once $root.'/app/Services/TransmittalV2/TransmittalProviderEventContract.php';

use App\Enums\TransmittalEmailDispatchState as State;
use App\Services\TransmittalV2\TransmittalEmailDispatchIdentity as Identity;
use App\Services\TransmittalV2\TransmittalProviderEventContract as ProviderEvent;

$check(array_map(static fn (State $state): string => $state->value, State::cases()) === [
    'pending', 'processing', 'accepted', 'delivered', 'failed', 'unknown',
], 'Dispatch state vocabulary matches the approved conservative set.');
$check(State::Pending->canTransitionTo(State::Processing), 'Pending can be claimed as processing.');
$check(State::Processing->canTransitionTo(State::Accepted), 'Processing can record provider acceptance.');
$check(State::Processing->canTransitionTo(State::Failed), 'Processing can record a definitive terminal failure.');
$check(State::Processing->canTransitionTo(State::Unknown), 'Processing can record an uncertain provider outcome.');
$check(! State::Processing->canTransitionTo(State::Delivered), 'Provider submission alone cannot establish delivery.');
$check(! State::Accepted->canTransitionTo(State::Delivered), 'Delivery transition requires separately verified evidence.');
$check(State::Accepted->canTransitionTo(State::Delivered, true), 'Verified provider delivery evidence is required for accepted-to-delivered.');
foreach ([State::Unknown, State::Failed, State::Delivered] as $terminal) {
    foreach (State::cases() as $next) {
        $check(! $terminal->canTransitionTo($next, true), $terminal->value.' cannot be changed by the ordinary state transition contract.');
    }
}

$versionA = str_repeat('a', 64);
$versionB = str_repeat('b', 64);
$key = Identity::fingerprint(41, 12, 'acknowledgment_request', $versionA);
$check($key === Identity::fingerprint('41', '12', 'acknowledgment_request', $versionA), 'Dispatch identity is deterministic for equivalent identifiers.');
$dispatchUuid = Identity::uuidForFingerprint($key);
$check($dispatchUuid === Identity::uuidForFingerprint($key) && preg_match('/\A[0-9a-f-]{36}\z/i', $dispatchUuid) === 1, 'Dispatch UUID is stable and UUID-shaped for the same immutable identity.');
$check($key !== Identity::fingerprint(41, 12, 'acknowledgment_request', $versionB)
    && $dispatchUuid !== Identity::uuidForFingerprint(Identity::fingerprint(41, 12, 'acknowledgment_request', $versionB)), 'A new immutable transmission version receives a distinct identity.');
$check($key !== Identity::fingerprint(41, 13, 'acknowledgment_request', $versionA), 'A different recipient receives a distinct dedupe fingerprint.');

$event = new ProviderEvent(
    '123e4567-e89b-12d3-a456-426614174000',
    '<message@example.test>',
    'delivered',
    new DateTimeImmutable('2026-11-12T00:00:00+00:00'),
    str_repeat('a', 64)
);
$eventFields = array_keys(get_object_vars($event));
$check($eventFields === ['dispatchUuid', 'providerMessageId', 'event', 'occurredAt', 'eventFingerprint'], 'Provider event contract exposes only normalized non-token fields.');
$check(! property_exists($event, 'token') && ! property_exists($event, 'email') && ! property_exists($event, 'payload'), 'Provider event contract has no bearer token, recipient address, or raw payload.');

$dispatchModel = $read('app/Models/TransmittalEmailDispatch.php');
$attemptModel = $read('app/Models/TransmittalEmailDispatchAttempt.php');
$intentService = $read('app/Services/TransmittalV2/TransmittalEmailDispatchService.php');
$attemptService = $read('app/Services/TransmittalV2/TransmittalEmailDispatchAttemptRecorder.php');
$eventContract = $read('app/Services/TransmittalV2/TransmittalProviderEventContract.php');
$dispatchCandidate = $read('patches/transmittal-v2/migrations/2026_11_12_000001_create_transmittal_email_dispatches_table.php.candidate');
$attemptCandidate = $read('patches/transmittal-v2/migrations/2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php.candidate');
$eventCandidate = $read('patches/transmittal-v2/migrations/2026_11_12_000003_create_transmittal_provider_events_table.php.candidate');
$controller = $read('app/Http/Controllers/TransmittalV2/TransmittalController.php');

$check(str_contains($dispatchModel, 'RequiresTransmittalV2Parent') && str_contains($dispatchModel, "getAttribute('workflow_version') !== 'v2'"), 'Dispatch creation is restricted to V2 parent association.');
$check(str_contains($intentService, 'TransmittalWorkflowBoundary::requireV2') && str_contains($intentService, 'TransmittalWorkflowBoundary::assertChild'), 'Intent service checks V2 parent and recipient ownership.');
$check(str_contains($intentService, 'TransmittalV2Authorization::canApprove') && str_contains($intentService, 'TransmittalV2Authorization::ownsTransmittal'), 'Intent service preserves existing transmit role/owner restrictions.');
$check(str_contains($intentService, 'dedupe_fingerprint') && str_contains($intentService, 'payload_fingerprint'), 'Intent service identifies duplicate version and payload conflicts.');
$check(str_contains($intentService, 'isUniqueConstraintViolation') && str_contains($intentService, 'dedupe_fingerprint'), 'Intent service reconciles a unique-key race by immutable dispatch identity.');
$check(str_contains($attemptService, 'DB::transaction(') && str_contains($attemptService, 'lockForUpdate()'), 'Attempt evidence and outcome projection use one locked transaction.');
$check(str_contains($attemptService, 'accepted_at') && str_contains($attemptService, 'failed_at') && str_contains($attemptService, 'unknown_at'), 'Provider outcomes project acceptance, rejection and uncertainty separately.');
$check(str_contains($attemptService, 'uuidForFingerprint') && str_contains($attemptService, 'Attempt event identity already has different immutable evidence.'), 'Attempt event identity is deterministic and same-event duplicates cannot silently change evidence.');
$check(str_contains($attemptService, 'beginSubmission') && str_contains($attemptService, 'Only a pending V2 dispatch can be claimed'), 'Pending-to-processing claim and initial attempt evidence are committed atomically; failed or uncertain dispatches cannot be auto-claimed.');
$check(str_contains($attemptService, '$eventType->value') && str_contains($attemptService, 'hash(\'sha256\', $attemptUuid'), 'Attempt start and provider outcome use distinct stable event identifiers for one attempt sequence.');
$check(str_contains($attemptModel, 'static::updating') && str_contains($attemptModel, 'static::deleting'), 'Attempt evidence cannot be edited or deleted through the model.');
$check(str_contains($dispatchModel, 'static::deleting') && str_contains($dispatchModel, 'canTransitionTo($to,'), 'Dispatch evidence is retained and state changes use the guarded transition matrix.');
$check(str_contains($eventContract, 'Non-persistent') && str_contains($eventContract, 'cannot transition a dispatch'), 'Provider event value is not treated as authenticated or persisted evidence.');

foreach ([$dispatchModel, $attemptModel, $intentService, $attemptService, $eventContract] as $source) {
    $check(! str_contains($source, 'acknowledgment_token') && ! str_contains($source, "'token'"), 'New foundation source does not persist/log bearer tokens.');
    $check(! str_contains($source, 'Http::') && ! str_contains($source, 'BrevoTransmittalService') && ! str_contains($source, 'ShouldQueue'), 'Foundation source has no provider transport or active queue job.');
    $check(! str_contains($source, 'TransmittalItem') && ! str_contains($source, 'TransmittalReceipt'), 'Foundation source has no legacy V1 item or receipt model dependency.');
}
$check(! str_contains($intentService, 'logger(') && ! str_contains($attemptService, 'logger('), 'Foundation services do not log email or token data.');

foreach ([$dispatchCandidate, $attemptCandidate] as $candidate) {
    $check(str_contains($candidate, 'NON-EXECUTABLE') && str_contains($candidate, 'target database engine/version and migration history remain'), 'Candidate remains non-executable pending target engine/version and migration-history verification.');
    $check(str_contains($candidate, 'foreign(') && str_contains($candidate, 'restrictOnDelete()'), 'Outbox dispatch/attempt links are restrictive after isolated schema rehearsal.');
    $check(str_contains($candidate, 'function down') && str_contains($candidate, 'throw new LogicException'), 'Candidate rollback refuses to drop outbox evidence.');
    $check(! preg_match('/Schema::table\s*\(\s*[\'\"]transmittal_(?:items|receipts)/i', $candidate), 'Candidate does not alter V1 item or receipt tables.');
}
    $check(str_contains($dispatchCandidate, "foreign(['workflow_version', 'transmittal_id']")
        && str_contains($dispatchCandidate, "foreign(['recipient_id', 'transmittal_id']")
        && str_contains($attemptCandidate, "foreign('dispatch_id'")
        && str_contains($eventCandidate, "foreign('dispatch_uuid'"), 'Dispatches require the exact V2 parent and same-parent recipient; attempts and provider events require an existing dispatch.');
$check(str_contains($dispatchCandidate, "unique('transmittal_email_dispatch_uuid_unique')")
    && str_contains($dispatchCandidate, "unique('transmittal_email_dispatch_dedupe_unique')"), 'Dispatch UUID and immutable dedupe fingerprint have database uniqueness constraints.');
$check(str_contains($attemptCandidate, "unique('transmittal_email_attempt_event_uuid_unique')")
    && str_contains($attemptCandidate, "'transmittal_email_attempt_event_identity_unique'"), 'Attempt event UUID and stable attempt-event identity have database uniqueness constraints.');
$check(! str_contains($dispatchCandidate, 'acknowledgment_token') && ! str_contains($dispatchCandidate, 'html_body'), 'Dispatch schema candidate omits bearer token and rendered body.');
$check(! str_contains($attemptCandidate, 'acknowledgment_token') && ! str_contains($attemptCandidate, 'html_body'), 'Attempt schema candidate omits bearer token and rendered body.');
$releaseMigrations = [
    '2026_11_09_000001_add_transmittal_v2_columns.php',
    '2026_11_09_000002_create_transmittal_recipients_table.php',
    '2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php',
    '2026_11_09_000004_create_transmittal_documents_table.php',
    '2026_11_09_000005_create_proof_of_transmissions_table.php',
    '2026_11_09_000006_create_approval_authority_codes_table.php',
    '2026_11_09_000007_create_transmittal_histories_table.php',
    '2026_11_09_000008_create_external_acknowledgments_table.php',
    '2026_11_09_000009_create_transmittal_upload_sessions_table.php',
    '2026_11_09_000010_create_transmittal_workspace_items_table.php',
    '2026_11_11_000001_add_workflow_version_to_transmittals_table.php',
    '2026_11_12_000001_create_transmittal_email_dispatches_table.php',
    '2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php',
    '2026_11_12_000003_create_transmittal_provider_events_table.php',
];
foreach ($releaseMigrations as $migration) {
    $check(file_exists($root.'/database/migrations/'.$migration), 'Approved release migration is integrated: '. $migration);
}
$check(! file_exists($root.'/database/migrations/2026_11_04_142800_sync_transmittal_v1_and_v2_columns.php'), 'Unsafe historical synchronization migration remains excluded.');
$dispatchExecutable = $read('database/migrations/2026_11_12_000001_create_transmittal_email_dispatches_table.php');
$attemptExecutable = $read('database/migrations/2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php');
$eventExecutable = $read('database/migrations/2026_11_12_000003_create_transmittal_provider_events_table.php');
$check(str_contains($dispatchExecutable, "foreign(['workflow_version', 'transmittal_id']")
    && str_contains($dispatchExecutable, "foreign(['recipient_id', 'transmittal_id']")
    && str_contains($attemptExecutable, "foreign('dispatch_id'")
    && str_contains($eventExecutable, "foreign('dispatch_uuid'")
    && ! str_contains($dispatchExecutable, 'NON-EXECUTABLE'), 'Executable outbox migrations retain the reviewed parent, recipient, attempt and provider-event constraints.');

$routes = json_decode($read('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$check(($routes['status'] ?? null) === 'inactive', 'V2 route manifest remains inactive.');
$transmitStart = strpos($controller, 'public function transmit(');
$uploadProofStart = strpos($controller, 'public function uploadProof(', $transmitStart);
$sendEmailStart = strpos($controller, 'public function sendEmail(');
$prefillStart = strpos($controller, 'private function buildProjectPrefill(', $sendEmailStart);
$transmitBody = is_int($transmitStart) && is_int($uploadProofStart)
    ? substr($controller, $transmitStart, $uploadProofStart - $transmitStart) : '';
$check(str_contains($transmitBody, 'createSendableIntent') && str_contains($transmitBody, '$cipher')
    && str_contains($transmitBody, 'TransmittalDispatchProviderAvailability::isEnabled()')
    && str_contains($transmitBody, 'DB::afterCommit'),
    'V2 transmit creates encrypted intent and queues only after a configured provider gate passes.');
$check(is_int($sendEmailStart) && is_int($prefillStart)
    && strpos(substr($controller, $sendEmailStart, $prefillStart - $sendEmailStart), 'abort(503') !== false,
    'Legacy V2 direct-send method remains fail-closed.');
$worker = $read('app/Services/TransmittalV2/TransmittalV2DispatchWorker.php');
$job = $read('app/Jobs/TransmittalV2/DispatchTransmittalEmail.php');
$eventProcessor = $read('app/Services/TransmittalV2/TransmittalProviderEventProcessor.php');
$eventController = $read('app/Http/Controllers/TransmittalV2/TransmittalProviderEventController.php');
$resender = $read('app/Services/TransmittalV2/TransmittalEmailDispatchResender.php');
$resendAuthorization = $read('app/Services/TransmittalV2/TransmittalManualResendAuthorization.php');
$cipher = $read('app/Services/TransmittalV2/TransmittalDispatchPayloadCipher.php');
$check(str_contains($worker, 'beginSubmission') && str_contains($worker, 'OutcomeUnknown')
    && str_contains($worker, 'confirmedSafeRetry') && str_contains($worker, 'validatePayloadAndAttachments'),
    'Worker uses atomic claims, safe bounded retry decisions, explicit unknown outcomes and attachment validation.');
$availability = $read('app/Services/TransmittalV2/TransmittalDispatchProviderAvailability.php');
$brevoAdapter = $read('app/Services/TransmittalV2/BrevoTransmittalDispatchProvider.php');
$brevoTransport = $read('app/Services/TransmittalV2/BrevoTransmittalService.php');
$brevoConfig = $read('config/transmittal_v2.php');
$authorizationProvider = $read('app/Providers/TransmittalV2AuthorizationServiceProvider.php');
$check(str_contains($availability, "'mock' => app()->environment(['local', 'testing'])")
    && str_contains($availability, "'brevo' => (bool) config('transmittal_v2.brevo.outbox_enabled', false)"),
    'Mock is local/testing-only and Brevo outbox use requires its V2-specific default-off gate.');
$check(str_contains($brevoAdapter, 'submitOutboxPayload')
    && str_contains($authorizationProvider, 'BrevoTransmittalDispatchProvider'),
    'The V2 adapter is registered only for the explicitly enabled existing Brevo transport.');
$check(str_contains($brevoTransport, 'https://api.brevo.com/v3/smtp/email')
    && str_contains($brevoTransport, "config('transmittal_v2.brevo.api_key')")
    && str_contains($brevoTransport, "config('transmittal_v2.brevo.sender_email')")
    && str_contains($brevoTransport, 'X-Entity-Ref-ID')
    && str_contains($brevoTransport, "'tags' =>") && str_contains($brevoTransport, '$referenceTag'),
    'V2 reuses the existing Brevo endpoint/config/client and uses documented correlation aids without claiming provider idempotency.');
$check(str_contains($brevoConfig, "env('ORDO_TRANSMITTAL_V2_BREVO_OUTBOX_ENABLED', false)"),
    'Brevo outbox activation defaults off and adds no duplicate credentials.');
$check(str_contains($worker, 'beginSubmission') && str_contains($worker, 'OutcomeUnknown')
    && str_contains($worker, 'confirmedSafeRetry') && str_contains($worker, 'validatePayloadAndAttachments'),
    'Worker preserves atomic claims, safe retries, unknown outcomes and attachment verification for either approved adapter.');
$check(str_contains($job, 'public int $tries = 1'), 'Queue job cannot auto-replay an uncertain provider submission.');
$check(str_contains($eventProcessor, 'hash_hmac') && str_contains($eventProcessor, 'provider_event_id')
    && str_contains($eventProcessor, 'body_fingerprint') && str_contains($eventProcessor, 'Unknown'),
    'Provider event processing authenticates, fingerprints and reconciles replay-safe evidence.');
$brevoEventProcessor = $read('app/Services/TransmittalV2/TransmittalBrevoEventProcessor.php');
$check(str_contains($eventController, "config('transmittal_v2.dispatch.provider') === 'brevo'")
    && str_contains($eventController, '$request->bearerToken()')
    && str_contains($eventController, 'hash_equals($expected, $token)'),
    'Provider event endpoint accepts Brevo only through the explicit provider gate and constant-time bearer comparison.');
$check(str_contains($brevoEventProcessor, "'delivered'") && str_contains($brevoEventProcessor, 'provider_message_id')
    && str_contains($brevoEventProcessor, 'body_fingerprint') && ! str_contains($brevoEventProcessor, 'raw_body'),
    'Brevo delivery events correlate to dispatch evidence and persist sanitized replay data only.');
$check(str_contains($brevoEventProcessor, "'hard_bounce'") && str_contains($brevoEventProcessor, "'soft_bounce'")
    && str_contains($brevoEventProcessor, "'invalid_email'"),
    'Brevo event parser accepts the documented snake_case bounce and invalid-email event names.');
$check(str_contains($resender, 'manual_resend_reason') && str_contains($resender, 'manual_resend_request_uuid')
    && str_contains($resender, 'resend_of_dispatch_id'), 'Manual resend keeps reason, request identity and source dispatch audit linkage.');
$check(str_contains($resendAuthorization, 'operations_manager_user_ids')
    && str_contains($resendAuthorization, 'designated_admin_user_ids'), 'Resend authority requires explicit protected user-ID designation.');
$check(str_contains($cipher, 'new Encrypter') && str_contains($cipher, 'random_bytes(32)')
    && str_contains($cipher, 'sending is stopped'), 'Payload uses envelope encryption and fails closed when key configuration is invalid.');
$check(str_contains($eventCandidate, 'provider_event_id') && str_contains($eventCandidate, 'body_fingerprint')
    && ! str_contains($eventCandidate, 'raw_body') && ! str_contains($eventCandidate, 'acknowledgment_token'),
    'Provider-event candidate retains only sanitized replay evidence.');
$check(($routes['transmission_activation_gate']['status'] ?? null) === 'blocked'
    && str_contains((string) ($routes['transmission_activation_gate']['reason'] ?? ''), 'Brevo'),
    'Non-mock transmission remains blocked pending Brevo configuration and staging verification.');

fwrite(STDOUT, "PASS: {$checks} V2 outbox foundation source-only checks. No database/concurrency/runtime acceptance.\n");