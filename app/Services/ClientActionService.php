<?php

namespace App\Services;

use App\Models\ClientActionRequest;
use App\Models\Deal;
use App\Models\DealHistory;
use App\Models\Proposal;
use App\Models\StartRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClientActionService
{
    protected DealHistoryService $dealHistoryService;

    public function __construct(DealHistoryService $dealHistoryService)
    {
        $this->dealHistoryService = $dealHistoryService;
    }

    /**
     * Generate next sequential action code: CA-YYYY-XXX
     */
    public function generateActionCode(): string
    {
        $year = date('Y');
        $lastRecord = ClientActionRequest::where('action_code', 'like', "CA-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord && preg_match('/CA-\d{4}-(\d+)/', $lastRecord->action_code, $matches)) {
            $nextNum = (int) $matches[1] + 1;
        } else {
            $count = ClientActionRequest::whereYear('created_at', $year)->count();
            $nextNum = $count + 1;
        }

        return sprintf('CA-%s-%03d', $year, $nextNum);
    }

    /**
     * Create a new Client Action Request.
     */
    public function createActionRequest(array $data): ClientActionRequest
    {
        $actionCode = $data['action_code'] ?? $this->generateActionCode();
        $secureToken = Str::random(40);
        $user = Auth::user();
        $userId = $data['created_by_user_id'] ?? ($user ? $user->id : null);
        $userName = $user ? $user->name : 'Staff User';

        $actionType = $data['action_type'] ?? ClientActionRequest::ACTION_REVIEW;
        $documentType = $data['document_type'] ?? 'General Document';
        $documentVersion = $data['document_version'] ?? 'V1';
        $documentReference = $data['document_reference'] ?? ($data['document_ref'] ?? null);
        $documentTitle = $data['document_title'] ?? ($data['document_name'] ?? "{$documentType} {$documentVersion}");

        $dealId = $data['deal_id'] ?? null;
        $deal = $dealId ? Deal::find($dealId) : null;

        $clientName = $data['client_name'] ?? ($deal?->primary_contact_name ?: ($deal?->first_name ? trim("{$deal->first_name} {$deal->last_name}") : ($deal?->company ?: 'Valued Client')));
        $clientEmail = $data['client_email'] ?? ($deal?->email ?: null);
        $clientPhone = $data['client_phone'] ?? ($deal?->mobile_number ?: null);
        $accountId = $data['account_id'] ?? ($deal?->account_id ?: null);
        $contactId = $data['contact_id'] ?? null;

        $title = $data['title'] ?? "{$actionType} {$documentTitle}";
        $instructions = $data['instructions'] ?? "Please review the {$documentTitle} and submit your response.";
        $status = $data['status'] ?? ClientActionRequest::STATUS_PENDING;
        $requiresOtp = (bool) ($data['requires_otp'] ?? in_array($actionType, [ClientActionRequest::ACTION_SIGN, ClientActionRequest::ACTION_APPROVE], true));

        $clientAction = ClientActionRequest::create([
            'action_code' => $actionCode,
            'deal_id' => $dealId,
            'actionable_type' => $data['actionable_type'] ?? ($deal ? get_class($deal) : null),
            'actionable_id' => $data['actionable_id'] ?? ($deal ? $deal->id : null),
            'document_type' => $documentType,
            'document_reference' => $documentReference,
            'document_version' => $documentVersion,
            'document_title' => $documentTitle,
            'document_url' => $data['document_url'] ?? null,
            'account_id' => $accountId,
            'contact_id' => $contactId,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_phone' => $clientPhone,
            'action_type' => $actionType,
            'title' => $title,
            'instructions' => $instructions,
            'status' => $status,
            'secure_token' => $secureToken,
            'requires_otp' => $requiresOtp,
            'due_date' => isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : now()->addDays(30),
            'created_by_user_id' => $userId,
            'audit_trail' => [],
        ]);

        // Record creation in audit trail
        $clientAction->recordAuditEntry(
            'Created',
            "Client Action Request {$actionCode} created for {$clientName} ({$actionType} {$documentTitle} - Version {$documentVersion}).",
            $userId,
            $userName,
            [
                'document_type' => $documentType,
                'document_version' => $documentVersion,
                'action_type' => $actionType,
                'requires_otp' => $requiresOtp,
            ]
        );

        // Record in DealHistory if attached to deal
        if ($deal) {
            $this->dealHistoryService->logActivity(
                $deal,
                'client_action_created',
                "Created Client Action Request {$actionCode}: \"{$title}\" for {$clientName} ({$documentTitle} {$documentVersion}).",
                [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'client_action_id' => $clientAction->id,
                    'action_code' => $actionCode,
                    'document_name' => $documentTitle,
                    'document_version' => $documentVersion,
                    'action_type' => $actionType,
                ]
            );
        }

        return $clientAction;
    }

    /**
     * Dispatch / Send Client Action Request.
     */
    public function dispatchAction(ClientActionRequest $request, array $options = []): ClientActionRequest
    {
        $user = Auth::user();
        $userId = $options['user_id'] ?? ($user ? $user->id : null);
        $userName = $options['user_name'] ?? ($user ? $user->name : 'Staff User');

        $request->status = ClientActionRequest::STATUS_AWAITING_CLIENT;
        $request->sent_at = now();
        $request->save();

        $request->recordAuditEntry(
            'Sent / Dispatched',
            "Client Action Request {$request->action_code} dispatched to {$request->client_name} ({$request->client_email}).",
            $userId,
            $userName,
            [
                'sent_at' => $request->sent_at->toIso8601String(),
                'secure_url' => $request->secure_url,
            ]
        );

        if ($request->deal) {
            $this->dealHistoryService->logActivity(
                $request->deal,
                'client_action_sent',
                "Dispatched Client Action {$request->action_code} ({$request->title}) to {$request->client_name}.",
                [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'client_action_id' => $request->id,
                    'action_code' => $request->action_code,
                ]
            );
        }

        return $request;
    }

    /**
     * CHANNEL A: Record Portal Response
     */
    public function recordPortalResponse(
        ClientActionRequest $request,
        string $decision,
        ?string $notes = null,
        array $responseData = []
    ): ClientActionRequest {
        $now = now();
        $finalStatus = $this->determineStatusFromDecision($request->action_type, $decision);

        $request->update([
            'status' => $finalStatus,
            'response_channel' => ClientActionRequest::CHANNEL_PORTAL,
            'response_decision' => $decision,
            'response_notes' => $notes,
            'response_data' => $responseData,
            'evidence_type' => 'Client Portal Response Log',
            'evidence_notes' => "Authenticated client response submitted via Client Portal.",
            'evidence_metadata' => array_merge($responseData, [
                'channel' => ClientActionRequest::CHANNEL_PORTAL,
                'client_name' => $request->client_name,
                'client_email' => $request->client_email,
                'responded_at' => $now->toIso8601String(),
            ]),
            'responded_at' => $now,
            'completed_at' => $now,
            'verification_state' => 'Verified (Portal Auth)',
        ]);

        $request->recordAuditEntry(
            'Portal Response Submitted',
            "Client {$request->client_name} submitted decision \"{$decision}\" via Client Portal.",
            null,
            $request->client_name,
            [
                'channel' => ClientActionRequest::CHANNEL_PORTAL,
                'decision' => $decision,
                'notes' => $notes,
            ]
        );

        $this->syncDownstreamBusinessRecord($request, $decision, ClientActionRequest::CHANNEL_PORTAL);

        return $request;
    }

    /**
     * CHANNEL B: Record Secure No-Login Link Response
     */
    public function recordSecureLinkResponse(
        ClientActionRequest $request,
        string $decision,
        ?string $notes = null,
        array $responseData = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): ClientActionRequest {
        $now = now();
        $finalStatus = $this->determineStatusFromDecision($request->action_type, $decision);

        $request->update([
            'status' => $finalStatus,
            'response_channel' => ClientActionRequest::CHANNEL_SECURE_LINK,
            'response_decision' => $decision,
            'response_notes' => $notes,
            'response_data' => $responseData,
            'evidence_type' => 'Secure Link Direct Response',
            'evidence_notes' => "Client completed action via authorized secure no-login link (Token: {$request->secure_token}).",
            'evidence_metadata' => array_merge($responseData, [
                'channel' => ClientActionRequest::CHANNEL_SECURE_LINK,
                'ip_address' => $ip ?? request()->ip(),
                'user_agent' => $userAgent ?? request()->userAgent(),
                'responded_at' => $now->toIso8601String(),
            ]),
            'responded_at' => $now,
            'completed_at' => $now,
            'verification_state' => 'Verified (Secure Link)',
        ]);

        $request->recordAuditEntry(
            'Secure Link Response Submitted',
            "Client {$request->client_name} submitted decision \"{$decision}\" via Secure No-Login Link.",
            null,
            $request->client_name,
            [
                'channel' => ClientActionRequest::CHANNEL_SECURE_LINK,
                'decision' => $decision,
                'ip' => $ip ?? request()->ip(),
            ]
        );

        $this->syncDownstreamBusinessRecord($request, $decision, ClientActionRequest::CHANNEL_SECURE_LINK);

        return $request;
    }

    /**
     * CHANNEL C: Record External Email Response
     * 
     * Requirement: Staff captures email proof, records decision, preserves email evidence,
     * and explicit audit record says "Recorded from email evidence".
     */
    public function recordEmailResponse(
        ClientActionRequest $request,
        string $decision,
        string $evidenceNotes,
        ?string $evidenceFilePath = null,
        ?string $evidenceFileName = null,
        ?int $recorderId = null,
        ?string $recorderName = null
    ): ClientActionRequest {
        $now = now();
        $user = Auth::user();
        $actorId = $recorderId ?? ($user ? $user->id : null);
        $actorName = $recorderName ?? ($user ? $user->name : 'Staff User');
        $finalStatus = $this->determineStatusFromDecision($request->action_type, $decision);

        $request->update([
            'status' => $finalStatus,
            'response_channel' => ClientActionRequest::CHANNEL_EMAIL,
            'response_decision' => $decision,
            'response_notes' => $evidenceNotes,
            'evidence_type' => 'Client Email Evidence',
            'evidence_notes' => $evidenceNotes,
            'evidence_file_path' => $evidenceFilePath,
            'evidence_file_name' => $evidenceFileName,
            'evidence_metadata' => [
                'channel' => ClientActionRequest::CHANNEL_EMAIL,
                'recorded_from' => 'Email Evidence',
                'recorded_by_id' => $actorId,
                'recorded_by_name' => $actorName,
                'recorded_at' => $now->toIso8601String(),
                'evidence_summary' => $evidenceNotes,
            ],
            'recorded_by_user_id' => $actorId,
            'recorded_by_name' => $actorName,
            'responded_at' => $now,
            'completed_at' => $now,
            'verification_state' => 'Verified (Staff Recorded Email)',
        ]);

        // Explicit Audit Entry specifying decision recorded from email evidence
        $request->recordAuditEntry(
            'Recorded from Email Evidence',
            "Decision \"{$decision}\" was RECORDED FROM EMAIL EVIDENCE by staff user {$actorName}. Email summary: {$evidenceNotes}",
            $actorId,
            $actorName,
            [
                'channel' => ClientActionRequest::CHANNEL_EMAIL,
                'source' => 'Email Evidence',
                'decision' => $decision,
                'file' => $evidenceFileName,
            ]
        );

        $this->syncDownstreamBusinessRecord($request, $decision, ClientActionRequest::CHANNEL_EMAIL, [
            'recorder_id' => $actorId,
            'recorder_name' => $actorName,
            'from_source' => 'email_evidence',
        ]);

        return $request;
    }

    /**
     * CHANNEL D: Record Manual / Wet Signature
     * 
     * Requirement: Physical signed document uploaded. Records signatory, date shown on document,
     * uploader, timestamp, verification state. Explicitly NOT labeled as digital signature.
     */
    public function recordManualSignature(
        ClientActionRequest $request,
        string $signatoryName,
        string $documentDate,
        ?string $filePath = null,
        ?string $fileName = null,
        ?string $notes = null,
        ?int $recorderId = null,
        ?string $recorderName = null,
        string $verificationState = 'Verified (Physical Copy)'
    ): ClientActionRequest {
        $now = now();
        $user = Auth::user();
        $actorId = $recorderId ?? ($user ? $user->id : null);
        $actorName = $recorderName ?? ($user ? $user->name : 'Staff User');

        $request->update([
            'status' => ClientActionRequest::STATUS_SIGNED,
            'response_channel' => ClientActionRequest::CHANNEL_MANUAL_SIGNATURE,
            'response_decision' => 'Signed',
            'signatory_name' => $signatoryName,
            'document_signed_date' => Carbon::parse($documentDate),
            'response_notes' => $notes,
            'evidence_type' => 'Physical / Wet Signed Document',
            'evidence_notes' => "Physical signed document uploaded. Signatory: {$signatoryName}, Document Date: {$documentDate}.",
            'evidence_file_path' => $filePath,
            'evidence_file_name' => $fileName,
            'evidence_metadata' => [
                'channel' => ClientActionRequest::CHANNEL_MANUAL_SIGNATURE,
                'method' => 'Physical / Wet Signature (Manual Evidence)',
                'is_native_digital_signature' => false,
                'signatory' => $signatoryName,
                'document_date' => $documentDate,
                'uploader_id' => $actorId,
                'uploader_name' => $actorName,
                'recorded_at' => $now->toIso8601String(),
            ],
            'recorded_by_user_id' => $actorId,
            'recorded_by_name' => $actorName,
            'responded_at' => $now,
            'completed_at' => $now,
            'verification_state' => $verificationState,
        ]);

        $request->recordAuditEntry(
            'Physical / Wet Signature Recorded',
            "Uploaded physical signed copy for {$request->document_title} ({$request->document_version}). Signatory: {$signatoryName}, Document Date: {$documentDate}. Recorded by {$actorName}. [Manual / Wet Signature - NOT an ORDO native digital signature]",
            $actorId,
            $actorName,
            [
                'channel' => ClientActionRequest::CHANNEL_MANUAL_SIGNATURE,
                'signatory' => $signatoryName,
                'document_date' => $documentDate,
                'file' => $fileName,
            ]
        );

        $this->syncDownstreamBusinessRecord($request, 'Signed', ClientActionRequest::CHANNEL_MANUAL_SIGNATURE, [
            'recorder_id' => $actorId,
            'recorder_name' => $actorName,
            'signatory' => $signatoryName,
        ]);

        return $request;
    }

    /**
     * CHANNEL E: Record External Signing / In-Person
     * 
     * Requirement: Signing occurred outside ORDO (e.g. In-person or external e-sign).
     * Recorded as "Externally Completed / Manual Evidence". Does NOT claim ORDO digital signature.
     */
    public function recordExternalSigning(
        ClientActionRequest $request,
        string $externalMethod,
        ?string $signatoryName = null,
        ?string $documentDate = null,
        ?string $filePath = null,
        ?string $fileName = null,
        ?string $notes = null,
        ?int $recorderId = null,
        ?string $recorderName = null,
        string $verificationState = 'Verified (External Proof)'
    ): ClientActionRequest {
        $now = now();
        $user = Auth::user();
        $actorId = $recorderId ?? ($user ? $user->id : null);
        $actorName = $recorderName ?? ($user ? $user->name : 'Staff User');
        $docDateParsed = $documentDate ? Carbon::parse($documentDate) : now();

        $request->update([
            'status' => ClientActionRequest::STATUS_COMPLETED,
            'response_channel' => ClientActionRequest::CHANNEL_EXTERNAL_SIGNING,
            'response_decision' => 'Signed / Executed Externally',
            'signatory_name' => $signatoryName ?: $request->client_name,
            'document_signed_date' => $docDateParsed,
            'response_notes' => $notes,
            'evidence_type' => 'External Signing Evidence',
            'evidence_notes' => "Executed outside ORDO via {$externalMethod}. Returned signed evidence recorded.",
            'evidence_file_path' => $filePath,
            'evidence_file_name' => $fileName,
            'evidence_metadata' => [
                'channel' => ClientActionRequest::CHANNEL_EXTERNAL_SIGNING,
                'status_label' => 'Externally Completed / Manual Evidence',
                'external_method' => $externalMethod,
                'is_native_digital_signature' => false,
                'signatory' => $signatoryName ?: $request->client_name,
                'document_date' => $documentDate,
                'recorder_id' => $actorId,
                'recorder_name' => $actorName,
                'recorded_at' => $now->toIso8601String(),
            ],
            'recorded_by_user_id' => $actorId,
            'recorded_by_name' => $actorName,
            'responded_at' => $now,
            'completed_at' => $now,
            'verification_state' => $verificationState,
        ]);

        $request->recordAuditEntry(
            'Externally Completed / Manual Evidence',
            "Signing completed outside ORDO via \"{$externalMethod}\". Signatory: " . ($signatoryName ?: $request->client_name) . ". Recorded by {$actorName}. [Externally Completed / Manual Evidence - Signing executed outside ORDO]",
            $actorId,
            $actorName,
            [
                'channel' => ClientActionRequest::CHANNEL_EXTERNAL_SIGNING,
                'external_method' => $externalMethod,
                'signatory' => $signatoryName,
                'document_date' => $documentDate,
                'file' => $fileName,
            ]
        );

        $this->syncDownstreamBusinessRecord($request, 'Completed', ClientActionRequest::CHANNEL_EXTERNAL_SIGNING, [
            'recorder_id' => $actorId,
            'recorder_name' => $actorName,
            'external_method' => $externalMethod,
        ]);

        return $request;
    }

    /**
     * Verify Client Action Evidence
     */
    public function verifyAction(
        ClientActionRequest $request,
        int $verifierId,
        string $verifierName,
        string $state = 'Verified',
        ?string $notes = null
    ): ClientActionRequest {
        $request->update([
            'verification_state' => $state,
            'verified_by_user_id' => $verifierId,
        ]);

        $request->recordAuditEntry(
            'Evidence Verification',
            "Evidence verification updated to \"{$state}\" by {$verifierName}." . ($notes ? " Notes: {$notes}" : ''),
            $verifierId,
            $verifierName,
            ['verification_state' => $state, 'notes' => $notes]
        );

        if ($request->deal) {
            $this->dealHistoryService->logActivity(
                $request->deal,
                'client_action_verified',
                "Verified evidence for Client Action {$request->action_code} ({$request->document_title}): {$state}.",
                [
                    'user_id' => $verifierId,
                    'user_name' => $verifierName,
                    'client_action_id' => $request->id,
                    'verification_state' => $state,
                ]
            );
        }

        return $request;
    }

    /**
     * Generate 6-digit OTP code for sensitive actions.
     */
    public function generateOtp(ClientActionRequest $request): string
    {
        $otp = sprintf('%06d', random_int(100000, 999999));
        $request->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        $request->recordAuditEntry(
            'OTP Generated',
            "Verification OTP generated for sensitive action verification.",
            null,
            'System'
        );

        return $otp;
    }

    /**
     * Verify input OTP.
     */
    public function verifyOtp(ClientActionRequest $request, string $inputCode): bool
    {
        if (!$request->requires_otp) {
            return true;
        }

        if (!$request->otp_code || !$request->otp_expires_at) {
            return false;
        }

        if (now()->isAfter($request->otp_expires_at)) {
            return false;
        }

        return trim($inputCode) === trim($request->otp_code);
    }

    /**
     * Map decision string to standard status.
     */
    protected function determineStatusFromDecision(string $actionType, string $decision): string
    {
        $decLower = strtolower(trim($decision));

        if (in_array($decLower, ['approve', 'approved', 'accept', 'accepted', 'yes', 'confirm', 'confirmed'])) {
            return match ($actionType) {
                ClientActionRequest::ACTION_APPROVE => ClientActionRequest::STATUS_APPROVED,
                ClientActionRequest::ACTION_ACCEPT => ClientActionRequest::STATUS_ACCEPTED,
                ClientActionRequest::ACTION_SIGN => ClientActionRequest::STATUS_SIGNED,
                ClientActionRequest::ACTION_ACKNOWLEDGE => ClientActionRequest::STATUS_ACKNOWLEDGED,
                ClientActionRequest::ACTION_UPLOAD => ClientActionRequest::STATUS_UPLOADED,
                default => ClientActionRequest::STATUS_COMPLETED,
            };
        }

        if (in_array($decLower, ['decline', 'declined', 'reject', 'rejected', 'cancel', 'cancelled'])) {
            return ClientActionRequest::STATUS_DECLINED;
        }

        if (in_array($decLower, ['acknowledge', 'acknowledged'])) {
            return ClientActionRequest::STATUS_ACKNOWLEDGED;
        }

        if (in_array($decLower, ['signed', 'sign'])) {
            return ClientActionRequest::STATUS_SIGNED;
        }

        if (in_array($decLower, ['uploaded', 'upload'])) {
            return ClientActionRequest::STATUS_UPLOADED;
        }

        return ClientActionRequest::STATUS_COMPLETED;
    }

    /**
     * Sync downstream records upon Client Action decision (Deal, Proposal, StartRecord, etc.)
     */
    protected function syncDownstreamBusinessRecord(
        ClientActionRequest $request,
        string $decision,
        string $channel,
        array $extraMeta = []
    ): void {
        $deal = $request->deal;
        $isPositive = in_array(strtolower($decision), ['approve', 'approved', 'accept', 'accepted', 'signed', 'completed', 'acknowledged']);

        // 1. Sync Proposal
        if ($request->document_type === 'Proposal' || str_contains(strtolower($request->document_title), 'proposal')) {
            if ($deal) {
                $proposalDecision = $isPositive ? 'Accepted' : 'Declined';
                $deal->proposal_decision = $proposalDecision;
                $deal->saveQuietly();

                if ($deal->proposal) {
                    $deal->proposal->status = $proposalDecision;
                    $deal->proposal->saveQuietly();
                }

                $activityType = $isPositive ? DealHistory::TYPE_PROPOSAL_ACCEPTED : DealHistory::TYPE_PROPOSAL_REJECTED;
                $sourceNotice = ($channel === ClientActionRequest::CHANNEL_EMAIL)
                    ? ' [Decision recorded from email evidence]'
                    : ($channel === ClientActionRequest::CHANNEL_MANUAL_SIGNATURE ? ' [Physical / Wet Signature]' : " [Via {$channel}]");

                $this->dealHistoryService->logActivity(
                    $deal,
                    $activityType,
                    "Proposal {$request->document_version} was {$proposalDecision} by client {$request->client_name} (Action {$request->action_code}){$sourceNotice}.",
                    array_merge([
                        'user_id' => $extraMeta['recorder_id'] ?? auth()->id(),
                        'user_name' => $extraMeta['recorder_name'] ?? (auth()->user()?->name ?? $request->client_name),
                        'client_action_id' => $request->id,
                        'action_code' => $request->action_code,
                        'channel' => $channel,
                        'document_version' => $request->document_version,
                        'document_name' => $request->document_title,
                    ], $extraMeta)
                );
            }
        }

        // 2. Sync START Service Memo
        if ($request->document_type === 'START Service Memo' || str_contains(strtolower($request->document_title), 'service memo')) {
            if ($request->actionable_type === StartRecord::class && $request->actionable_id) {
                $startRecord = StartRecord::find($request->actionable_id);
                if ($startRecord) {
                    $startRecord->memo_status = $isPositive ? 'Acknowledged' : 'Declined';
                    $startRecord->saveQuietly();
                }
            }

            if ($deal) {
                $this->dealHistoryService->logActivity(
                    $deal,
                    'service_memo_client_action',
                    "START Service Memo ({$request->document_version}) was {$decision} by {$request->client_name} via {$channel} (Action {$request->action_code}).",
                    [
                        'user_id' => $extraMeta['recorder_id'] ?? auth()->id(),
                        'client_action_id' => $request->id,
                        'action_code' => $request->action_code,
                        'channel' => $channel,
                    ]
                );
            }
        }

        // 3. Generic Deal Action logging
        if ($deal && $request->document_type !== 'Proposal' && !str_contains(strtolower($request->document_title), 'service memo')) {
            $this->dealHistoryService->logActivity(
                $deal,
                'client_action_completed',
                "Client Action {$request->action_code} (\"{$request->title}\") completed: \"{$decision}\" via {$channel}.",
                [
                    'user_id' => $extraMeta['recorder_id'] ?? auth()->id(),
                    'client_action_id' => $request->id,
                    'action_code' => $request->action_code,
                    'channel' => $channel,
                    'document_name' => $request->document_title,
                    'document_version' => $request->document_version,
                ]
            );
        }
    }
}
