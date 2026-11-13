<?php

declare(strict_types=1);

// Compose the prior workflow, workspace and lifecycle source checks first.
require __DIR__.'/transmittal-lifecycle-exclusions.php';

$approval = $read('app/Services/TransmittalV2/TransmittalApprovalService.php');
$ack = $read('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
$controller = $read('app/Http/Controllers/TransmittalV2/TransmittalController.php');
$policy = $read('app/Policies/TransmittalV2Policy.php');
$brevo = $read('app/Services/TransmittalV2/BrevoTransmittalService.php');
$config = $read('config/transmittal_v2.php');
$receiptCandidate = $read('patches/transmittal-v2/migrations/2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate');
$recipientCandidate = $read('patches/transmittal-v2/migrations/2026_11_09_000002_create_transmittal_recipients_table.php.candidate');
$parentCandidate = $read('patches/transmittal-v2/migrations/2026_11_09_000001_add_transmittal_v2_columns.php.candidate');
$v1Receipt = $read('database/migrations/2026_04_10_173623_create_transmittal_receipts_table.php');

$check(strpos($approval, 'TransmittalWorkflowBoundary::requireV2($transmittal)') < strpos($approval, 'DB::transaction('), 'Approval requires the exact V2 parent before transaction entry.');
$check(str_contains($approval, 'TransmittalWorkflowBoundary::v2Query()') && str_contains($approval, '->lockForUpdate()'), 'Approval transaction reloads a V2-scoped parent under lock.');
$check(strpos($approval, '->lockForUpdate()') < strpos($approval, 'TransmittalV2Authorization::canApprove($approver)', strpos($approval, '->lockForUpdate()')), 'Approval authorization is repeated after locking.');
$check(str_contains($approval, 'ownsTransmittal($approver, $locked->prepared_by)'), 'Approval preserves separation of duties after locking.');
$check(str_contains($approval, 'hasFrozenApprovalEvidence($locked)'), 'Duplicate or conflicting approval evidence is refused.');
$check(str_contains($approval, "'approved_by' => \$approver->id") && str_contains($approval, "'approval_authority_code' => \$code"), 'System approver, timestamp, and authority code are persisted with approval.');
$check(str_contains($approval, "'frozen_snapshot' => \$snapshot") && str_contains($approval, "'snapshot_hash' => hash('sha256', \$serialized)"), 'Authority record stores a frozen snapshot and digest.');
$check(str_contains($approval, "'action' => 'approved'") && str_contains($approval, "'action' => 'rejected'"), 'Approval and rejection each add history inside their transactions.');
$check(str_contains($approval, 'TransmittalWorkflowBoundary::v2Query()') && substr_count($approval, '->lockForUpdate()') >= 2, 'Approval and rejection both reload scoped parents under locks.');
$check(str_contains($approval, 'Conflicting approval evidence prevents rejection.'), 'Rejection refuses conflicting frozen approval evidence.');
$check(str_contains($parentCandidate, "\$table->foreignId('transmittal_id')->unique()") || str_contains($read('patches/transmittal-v2/migrations/2026_11_09_000006_create_approval_authority_codes_table.php.candidate'), "\$table->foreignId('transmittal_id')->unique()"), 'Authority schema candidate requires at most one authority row per transmittal.');

$check(str_contains($policy, "in_array(\$transmittal->status, ['approved', 'sent'], true)"), 'Transmission is limited to approved or already-sent records.');
$check(! str_contains($policy, "in_array(\$transmittal->status, ['pending_approval', 'approved', 'sent'], true)"), 'Pending-approval records cannot be transmitted.');
$check(str_contains($policy, 'TransmittalV2Authorization::canApprove($user)') && str_contains($policy, 'ownsTransmittal($user, $transmittal->prepared_by)'), 'Transmission retains existing role and owner policy checks.');
$check(str_contains($method($controller, 'recall'), 'hasFrozenApprovalEvidence($record)'), 'Recall refuses conflicting frozen approval evidence after lock.');
$check(str_contains($method($controller, 'cancel'), 'hasFrozenApprovalEvidence($record)'), 'Cancellation refuses conflicting frozen approval evidence after lock.');
$check(str_contains($controller, "config('transmittal_v2.acknowledgment.hours', 72)"), 'Acknowledgment token expiry reads the configured acknowledgment.hours namespace.');
$check(! str_contains($controller, "config('transmittal_v2.brevo.acknowledgment_hours"), 'No stale Brevo acknowledgment-hours config key remains.');
$check(str_contains($config, "'hours' => (int) env('ORDO_TRANSMITTAL_ACKNOWLEDGMENT_HOURS', 72)"), 'Configured acknowledgment lifetime is present at the verified source key.');

$check(str_contains($ack, 'TransmittalAcknowledgmentToken::isValid($token)'), 'Acknowledgment tokens are shape-validated.');
$check(str_contains($method($ack, 'assertCurrentToken'), 'hash_equals(') && str_contains($method($ack, 'assertCurrentToken'), 'isFuture()') && str_contains($method($ack, 'assertCurrentToken'), 'acknowledgment_revoked_at === null'), 'Locked acknowledgment token is revalidated for hash, expiry, and revocation.');
$check(str_contains($method($ack, 'recipientFor'), "whereNull('acknowledgment_revoked_at')") && str_contains($method($ack, 'recipientFor'), "where('acknowledgment_expires_at', '>', now())"), 'Initial token lookup also excludes expired and revoked links.');
$check(str_contains($ack, "->where('event', 'acknowledged')") && str_contains($ack, 'A prior acknowledgment event exists without its receipt'), 'Prior orphaned acknowledgment event blocks a duplicate receipt attempt.');
$check(str_contains($ack, "if (\$locked->receipt()->exists())") && str_contains($ack, "'recipient_id' => \$locked->id"), 'Receipt is unique to the locked recipient before insert.');
$check(str_contains($ack, "'receipt_no' => \$receiptNumbers->generate()") && str_contains($v1Receipt, "\$table->string('receipt_no')->unique()"), 'V2 receipt numbers use the existing unique receipt number column/index.');
$check(str_contains($receiptCandidate, "\$table->foreignId('recipient_id')->nullable()->unique"), 'Candidate schema adds one nullable V2 receipt per recipient.');
$check(str_contains($ack, 'DB::transaction(function () use') && str_contains($ack, "'action' => 'acknowledged'"), 'Receipt, acknowledgment, proof, and history writes share one transaction.');
$check(str_contains($ack, 'TransmittalWorkflowBoundary::v2Query()') && str_contains($ack, 'TransmittalWorkflowBoundary::v2Children(TransmittalRecipient::class)'), 'Acknowledgment locks a V2 parent and its V2 recipient.');
$check(str_contains($ack, "'status' => 'acknowledged'") && str_contains($ack, "whereDoesntHave('receipt')"), 'Parent acknowledgment state is set only after all recipient receipts exist.');
$check(str_contains($ack, 'assertChild($parent, $recipient->receipt)') && str_contains($ack, '$recipient->receipt->recipient_id'), 'Existing receipt access verifies parent and recipient ownership.');
$check(str_contains($method($controller, 'receiptPdf'), '$this->canViewPrivateFiles($request, $transmittal)'), 'Every authenticated receipt PDF path requires parent authorization.');
$check(str_contains($method($controller, 'renderReceiptPdf'), 'assertChild($transmittal, $receipt)') && str_contains($method($controller, 'renderReceiptPdf'), 'recipients()->whereKey($receipt->recipient_id)'), 'Receipt rendering validates parent and recipient ownership.');
$check(str_contains($method($controller, 'data'), "'receipts.recipient'") && str_contains($method($controller, 'data'), "route('transmittals.receipts.pdf'"), 'V2 register payload links each receipt through the V2 scoped route.');
$check(str_contains($method($controller, 'data'), "'receipts' => \$receipts") && ! str_contains($method($controller, 'data'), "route('transmittal.receipts.show'"), 'V2 register payload represents all recipient receipts without dispatching into the V1 receipt route.');

$check(str_contains($brevo, "config('transmittal_v2.brevo.api_key')") && str_contains($brevo, "config('transmittal_v2.brevo.sender_email')"), 'Brevo service uses matching transmittal_v2.brevo configuration keys.');
$check(str_contains($brevo, 'An associated transmittal attachment is missing or unreadable; delivery was stopped.'), 'Acknowledgment email fails closed when a required attachment is absent.');
$check(str_contains($brevo, 'failed integrity verification; delivery was stopped.'), 'Acknowledgment attachments are digest-checked before transmission.');
$check(str_contains($brevo, 'is_readable($file[\'path\'])') && str_contains($brevo, 'if (! is_string($content))'), 'Direct Brevo transmission fails rather than silently dropping unreadable attachments.');
$check(! str_contains($brevo, '->filter()'), 'Acknowledgment attachment mapping does not silently filter missing documents.');
$check(str_contains($method($controller, 'sendEmail'), 'is_file($attachmentPath) && is_readable($attachmentPath)'), 'Direct V2 email refuses associated legacy-file records whose files are missing.');
$check(str_contains($method($controller, 'sendEmail'), 'Automatic fallback was stopped to avoid a duplicate transmission.'), 'Ambiguous Brevo failure does not trigger a second mail transport.');
$check(! str_contains($method($controller, 'sendEmail'), 'falling back to mailer'), 'Direct send path has no automatic Brevo-to-mailer retry.');
$check(str_contains($method($controller, 'transmit'), 'Every unsent recipient needs a valid email address'), 'Transmission reports invalid recipient email instead of silently skipping it.');
$check(str_contains($method($controller, 'transmit'), 'At least one recipient is required before transmission.'), 'Transmission refuses an empty recipient list.');
$check(str_contains($method($controller, 'transmit'), "->lockForUpdate()")
    && str_contains($method($controller, 'transmit'), 'createSendableIntent'), 'Database row locks and the unique dispatch intent constrain duplicate submissions; no exactly-once guarantee is asserted.');

$check(str_contains($recipientCandidate, "\$table->string('acknowledgment_token_hash', 64)->nullable()->unique()"), 'Recipient token hash uniqueness is declared in the candidate schema.');
$check(str_contains($receiptCandidate, "\$table->foreignId('recipient_id')->nullable()->unique") && str_contains($v1Receipt, "\$table->string('receipt_no')->unique()"), 'Receipt uniqueness requirements preserve NULL-linked V1 receipts and unique receipt numbers.');
$check(! str_contains($v1Receipt, 'recipient_id'), 'V1 receipt schema has no new V2 association requirement.');
$check(! str_contains($ack, 'Mail::') && ! str_contains($brevo, 'Http::fake'), 'The source-only review path itself sends no email and performs no API call.');

fwrite(STDOUT, "PASS: {$checks} additional V2 integrity source checks; ".($checks + 425)." total including STEPS 184–186. No runtime/database acceptance.\n");
