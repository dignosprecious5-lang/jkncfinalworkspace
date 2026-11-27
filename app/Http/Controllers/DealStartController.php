<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Deal;
use App\Services\TargetedNotificationService;
use App\Http\Controllers\ProjectController;

class DealStartController extends Controller
{
    /**
     * Get all START batches for a deal.
     */
    public function index($dealId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        return response()->json([
            'success' => true,
            'batches' => collect($deal->start_batches ?? [])->map(function ($batch) use ($deal) {
                $batch = (array) $batch;
                $batch['status'] = $deal->resolveStartBatchTeamStatus($batch);
                return $batch;
            })->values(),
            'is_start_completed' => $deal->isStartCompleted(),
        ]);
    }

    /**
     * Get live START completion and engagement type status.
     */
    public function getStartStatus($dealId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        return response()->json([
            'success' => true,
            'is_start_completed' => $deal->isStartCompleted(),
            'has_project' => $deal->hasProjectEngagement(),
            'has_regular' => $deal->hasRegularEngagement(),
            'is_hybrid' => $deal->isHybridEngagement(),
            'pipeline_stage' => $deal->pipeline_stage,
            'start_status' => $deal->getResolvedStartStatus(),
            'outstanding_start_requirements' => $deal->getStartOutstandingRequirements(),
            'proposal_status' => $deal->getResolvedProposalStatus(),
            'commercial_status' => $deal->getResolvedCommercialStatus(),
            'casa_status' => $deal->getResolvedCasaStatus(),
            'payment_status' => $deal->getResolvedPaymentStatus(),
            'kyc_status' => $deal->getResolvedKycStatus(),
        ]);
    }

    /**
     * Save the client-specific KYC requirements for this Deal.
     * Requirement assignment is separate from upload/review status, which is
     * resolved from the existing CIF/BIF document workflow.
     */
    public function updateKycRequirements(Request $request, $dealId): JsonResponse
    {
        abort_unless($this->canConfigureKyc($request), 403, 'You are not authorized to configure client KYC requirements.');

        $deal = Deal::findOrFail($dealId);
        $validated = $request->validate([
            'organization_type' => ['required', 'string', 'max:100'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['required', 'string', 'max:255'],
            'custom_requirements' => ['nullable', 'array'],
            'custom_requirements.*' => ['nullable', 'string', 'max:255'],
        ]);

        $kyc = app(\App\Services\ProjectProvisioner::class)->resolveDealStartKyc($deal);
        $organization = (string) ($kyc['organization_type'] ?? 'unknown');
        abort_unless($validated['organization_type'] === $organization, 422, 'The client entity type changed. Refresh the page and try again.');

        $group = match ($organization) {
            'sole_proprietorship' => 'sole',
            'individual' => 'individual',
            default => 'juridical',
        };
        $availableRows = collect($kyc[$group] ?? [])->filter(fn ($row) => is_array($row));
        $availableByKey = $availableRows->keyBy(fn ($row) => mb_strtolower(trim((string) ($row['label'] ?? ''))));
        $requested = collect($validated['requirements'] ?? [])
            ->merge($validated['custom_requirements'] ?? [])
            ->map(fn ($label) => trim((string) $label))
            ->filter()
            ->unique(fn ($label) => mb_strtolower($label))
            ->values();

        foreach ($requested as $label) {
            $key = mb_strtolower($label);
            abort_unless($availableByKey->has($key) || collect($validated['custom_requirements'] ?? [])->contains(fn ($custom) => mb_strtolower(trim((string) $custom)) === $key), 422, 'A selected requirement is not available for this entity type.');
        }

        $standard = [];
        $custom = [];
        foreach ($requested as $label) {
            $row = $availableByKey->get(mb_strtolower($label));
            if (($row['custom'] ?? false) === true || ! $row) {
                $custom[] = $label;
            } else {
                $standard[] = $label;
            }
        }

        $current = (array) ($deal->client_kyc_requirements ?? []);
        $previous = [
            'organization_type' => $current['organization_type'] ?? null,
            $group => array_values(array_merge((array) ($current[$group] ?? []), (array) data_get($current, "custom.{$group}", []))),
        ];
        $history = (array) ($current['assignment_history'] ?? []);
        $history[] = [
            'updated_at' => now()->toIso8601String(),
            'updated_by_id' => $request->user()?->id,
            'updated_by' => $request->user()?->name,
            'organization_type' => $organization,
            'previous' => $previous,
            'requirements' => $requested->all(),
        ];

        $assignment = $current;
        $assignment['organization_type'] = $organization;
        $assignment['sole'] = $group === 'sole' ? $standard : [];
        $assignment['individual'] = $group === 'individual' ? $standard : [];
        $assignment['juridical'] = $group === 'juridical' ? $standard : [];
        $assignment['custom'] = ['sole' => [], 'individual' => [], 'juridical' => []];
        $assignment['custom'][$group] = array_values(array_unique($custom));
        $assignment['assignment_history'] = $history;

        $deal->client_kyc_requirements = $assignment;
        $deal->save();

        return response()->json([
            'success' => true,
            'message' => 'Client KYC requirements saved.',
            'requirements' => $requested->all(),
        ]);
    }

    private function canConfigureKyc(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && method_exists($user, 'hasPermission')
            && ($user->hasPermission('access_admin_dashboard') || $user->hasPermission('approve_townhall'));
    }

    /**
     * Create a new START batch for a deal.
     */
    public function store(Request $request, $dealId): JsonResponse
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $dealId): JsonResponse {
            $deal = Deal::query()->lockForUpdate()->findOrFail($dealId);
            $deal->proposal()->lockForUpdate()->first();

            app(\App\Services\DealPaymentEligibility::class)->assertBatchEligible($deal, (array) $request->input('activated_items', []));

            $validated = $request->validate([
                'start_code' => ['required', 'string', 'max:100'],
                'batch_name' => ['required', 'string', 'max:255'],
                'activated_items' => ['required', 'array', 'min:1'],
                'activated_items.*' => ['required', 'array'],
                'activated_items.*.id' => ['nullable', 'string', 'max:255'],
                'activated_items.*.name' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
                'assignments' => ['nullable', 'array'],
            ]);

            $batches = (array) ($deal->start_batches ?? []);
            $approvedItems = collect(app(\App\Services\DealPaymentEligibility::class)->items($deal));
            $validated['activated_items'] = collect($validated['activated_items'])->map(function ($selected) use ($approvedItems) {
                $item = isset($selected['id']) || isset($selected['item_id'])
                    ? $approvedItems->firstWhere('id', (string) ($selected['id'] ?? $selected['item_id']))
                    : $approvedItems->firstWhere('name', $selected['name'] ?? '');
                return ['id' => $item['id'], 'name' => $item['name'], 'division' => $item['route']];
            })->all();
            $newId = time();
            $openedDate = now()->format('M d, Y');
            $openedTime = now()->format('h:i A');
            $actor = auth()->user()?->name ?? 'System';

            $assignments = [];
            $assignedRecipients = [];

            if (!empty($validated['assignments'])) {
                foreach ($validated['assignments'] as $idx => $assign) {
                    $role = $assign['role'] ?? 'Team Member';
                    $assignee = $assign['assigned_to'] ?? ($assign['assignee'] ?? 'Unassigned');
                    $assignments[] = [
                        'id' => $newId + $idx + 1,
                        'role' => $role,
                        'assignee' => $assignee,
                        'status' => 'Pending',
                        'is_required' => (bool) ($assign['is_required'] ?? true),
                        'acknowledged_at' => null,
                        'notes' => $assign['notes'] ?? '',
                    ];
                    if ($assignee && $assignee !== 'Unassigned') {
                        $assignedRecipients[] = $assignee;
                    }
                }
            } else {
                $assignments = [
                    [
                        'id' => $newId + 1,
                        'role' => 'Lead Consultant',
                        'assignee' => $deal->assigned_consultant ?: $actor,
                        'status' => 'Pending',
                        'is_required' => true,
                        'acknowledged_at' => null,
                        'notes' => '',
                    ],
                    [
                        'id' => $newId + 2,
                        'role' => 'Lead Associate',
                        'assignee' => $deal->assigned_associate ?: 'Assigned Staff',
                        'status' => 'Pending',
                        'is_required' => true,
                        'acknowledged_at' => null,
                        'notes' => '',
                    ],
                ];
                if ($deal->assigned_consultant) $assignedRecipients[] = $deal->assigned_consultant;
                if ($deal->assigned_associate) $assignedRecipients[] = $deal->assigned_associate;
            }

            $newBatch = [
                'id' => $newId,
                'start_code' => $validated['start_code'],
                'batch_name' => $validated['batch_name'],
                'status' => 'Structuring',
                'opened_date' => $openedDate,
                'opened_time' => $openedTime,
                'activated_items' => $validated['activated_items'] ?? [],
                'notes' => $validated['notes'] ?? '',
                'service_memo_ref' => null,
                'service_memo_data' => null,
                'service_memo_revisions' => [],
                'history_logs' => [
                    [
                        'event' => 'Created',
                        'details' => "START Batch {$validated['start_code']} created with " . count($validated['activated_items'] ?? []) . " services",
                        'actor' => $actor,
                        'timestamp' => $openedDate,
                    ]
                ],
                'authorized_by' => null,
                'created_by' => $actor,
                'assignments' => $assignments,
            ];

            array_unshift($batches, $newBatch);
            $deal->start_batches = $batches;
            $deal->save();

            // Dispatch targeted notifications strictly to each assigned user with direct action URL
            foreach ($assignments as $asgn) {
                $asgnUser = $asgn['assignee'] ?? null;
                if ($asgnUser && $asgnUser !== 'Unassigned') {
                    $asgnUrl = route('deals.show', ['id' => $deal->id]) . '?tab=start&batch_id=' . $newId . '&assignment_id=' . $asgn['id'] . '&action=respond';
                    TargetedNotificationService::notifyAssignedUsers(
                        $asgnUser,
                        "New START Assignment: {$asgn['role']}",
                        "You have been assigned as \"{$asgn['role']}\" in START Batch \"{$validated['batch_name']}\" ({$validated['start_code']}) for Deal {$deal->deal_code}. Please review to accept or decline.",
                        $asgnUrl,
                        'START Workflow',
                        'fa-user-check',
                        'Review Assignment',
                        auth()->id()
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'START batch created successfully.',
                'batch' => $newBatch,
                'is_start_completed' => $deal->fresh()->isStartCompleted(),
            ]);
        });
    }

    /**
     * Delete a START batch.
     */
    public function destroy($dealId, $batchId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $filtered = array_values(array_filter($batches, fn ($b) => (string)($b['id'] ?? '') !== (string)$batchId));

        $deal->start_batches = $filtered;
        $deal->save();

        return response()->json([
            'success' => true,
            'message' => 'START batch deleted successfully.',
            'is_start_completed' => $deal->fresh()->isStartCompleted(),
        ]);
    }

    /**
     * Add or update an assignment in a START batch.
     */
    public function addAssignment(Request $request, $dealId, $batchId): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:Sales & Marketing,Lead Consultant,Lead Associate Assigned,Consultant,Associate'],
            'assignee' => ['required', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $updatedBatch = null;

        $role = $validated['role'];
        $assignee = $validated['assignee'];
        $isRequired = (bool) ($validated['is_required'] ?? true);
        $notes = $validated['notes'] ?? '';
        $newAssignmentId = time();

        foreach ($batches as &$batch) {
            if ((string)($batch['id'] ?? '') === (string)$batchId) {
                if (!isset($batch['assignments']) || !is_array($batch['assignments'])) {
                    $batch['assignments'] = [];
                }
                $batch['assignments'][] = [
                    'id' => $newAssignmentId,
                    'role' => $role,
                    'assignee' => $assignee,
                    'status' => 'Pending',
                    'is_required' => $isRequired,
                    'notes' => $notes,
                    'acknowledged_at' => null,
                ];
                $batch['status'] = $deal->resolveStartBatchTeamStatus($batch);
                $updatedBatch = $batch;
                break;
            }
        }
        unset($batch);

        if (!$updatedBatch) {
            return response()->json([
                'success' => false,
                'message' => 'The selected START batch could not be found.',
            ], 404);
        }

        $deal->start_batches = $batches;
        $deal->save();

        if ($assignee && $assignee !== 'Unassigned') {
            $asgnUrl = route('deals.show', ['id' => $deal->id]) . '?tab=start&batch_id=' . $batchId . '&assignment_id=' . $newAssignmentId . '&action=respond';
            TargetedNotificationService::notifyAssignedUsers(
                $assignee,
                "New START Task Assigned: {$role}",
                "You have been assigned the role of \"{$role}\" in START Batch {$updatedBatch['start_code']} for Deal {$deal->deal_code}. Please review to accept or decline.",
                $asgnUrl,
                'START Workflow',
                'fa-user-check',
                'Review Assignment',
                auth()->id()
            );
        }

        return response()->json([
            'success' => true,
            'batch' => $updatedBatch,
            'is_start_completed' => $deal->fresh()->isStartCompleted(),
            'start_status' => $deal->fresh()->getResolvedStartStatus(),
        ]);
    }

    /**
     * Send for Team Confirmation.
     */
    public function sendConfirmation(Request $request, $dealId, $batchId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $targetBatch = collect($batches)->first(fn ($batch) => (string) ($batch['id'] ?? '') === (string) $batchId);
        abort_unless($targetBatch, 404, 'START batch not found.');
        app(\App\Services\DealPaymentEligibility::class)->assertBatchEligible($deal, (array) ($targetBatch['activated_items'] ?? []));
        $updatedBatch = null;

        foreach ($batches as &$batch) {
            if ((string)($batch['id'] ?? '') === (string)$batchId) {
                $batch['status'] = $deal->resolveStartBatchTeamStatus($batch, confirmationSent: true);
                if (!isset($batch['history_logs']) || !is_array($batch['history_logs'])) {
                    $batch['history_logs'] = [];
                }
                $batch['history_logs'][] = [
                    'event' => 'Team Confirmation',
                    'details' => 'START batch sent for team confirmation',
                    'actor' => auth()->user()?->name ?? 'System',
                    'timestamp' => now()->format('M d, Y'),
                ];
                $updatedBatch = $batch;
                break;
            }
        }
        unset($batch);

        if ($updatedBatch) {
            $deal->start_batches = $batches;
            $deal->save();

            foreach ($updatedBatch['assignments'] ?? [] as $asgn) {
                $asgnUser = $asgn['assignee'] ?? null;
                if ($asgnUser && $asgnUser !== 'Unassigned' && in_array($asgn['status'] ?? '', ['Pending', 'Cannot Accept'])) {
                    $asgnUrl = route('deals.show', ['id' => $deal->id]) . '?tab=start&batch_id=' . $batchId . '&assignment_id=' . ($asgn['id'] ?? '') . '&action=respond';
                    TargetedNotificationService::notifyAssignedUsers(
                        $asgnUser,
                        "START Confirmation Required: {$asgn['role']}",
                        "Please confirm your assignment readiness for START Batch {$updatedBatch['start_code']} on Deal {$deal->deal_code}.",
                        $asgnUrl,
                        'START Workflow',
                        'fa-tasks',
                        'Review & Confirm',
                        auth()->id()
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'batch' => $updatedBatch,
        ]);
    }

    /**
     * Submit for Final Review.
     */
    public function submitReview(Request $request, $dealId, $batchId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $targetBatch = collect($batches)->first(fn ($batch) => (string) ($batch['id'] ?? '') === (string) $batchId);
        abort_unless($targetBatch, 404, 'START batch not found.');
        app(\App\Services\DealPaymentEligibility::class)->assertBatchEligible($deal, (array) ($targetBatch['activated_items'] ?? []));
        $updatedBatch = null;

        foreach ($batches as &$batch) {
            if ((string)($batch['id'] ?? '') === (string)$batchId) {
                if (!$deal->hasAllRequiredStartAssignmentsAccepted($batch)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'All required assignments must be explicitly accepted before Final Review.',
                    ], 422);
                }
                $batch['status'] = 'For Final Review';
                if (!isset($batch['history_logs']) || !is_array($batch['history_logs'])) {
                    $batch['history_logs'] = [];
                }
                $batch['history_logs'][] = [
                    'event' => 'Final Review',
                    'details' => 'START batch submitted for final approval and Service Memo review',
                    'actor' => auth()->user()?->name ?? 'System',
                    'timestamp' => now()->format('M d, Y'),
                ];
                $updatedBatch = $batch;
                break;
            }
        }
        unset($batch);

        if ($updatedBatch) {
            $deal->start_batches = $batches;
            $deal->save();
        }

        return response()->json([
            'success' => true,
            'batch' => $updatedBatch,
        ]);
    }

    /**
     * Issue official Service Memo and activate engagement operations.
     */
    public function issueMemo(Request $request, $dealId, $batchId): JsonResponse
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $dealId, $batchId): JsonResponse {
            $deal = Deal::query()->lockForUpdate()->findOrFail($dealId);
            $deal->proposal()->lockForUpdate()->first();
            $batches = (array) ($deal->start_batches ?? []);
            $targetBatch = collect($batches)->first(fn ($batch) => (string) ($batch['id'] ?? '') === (string) $batchId);
            abort_unless($targetBatch, 404, 'START batch not found.');
            app(\App\Services\DealPaymentEligibility::class)->assertBatchEligible($deal, (array) ($targetBatch['activated_items'] ?? []));
            $updatedBatch = null;
            $actor = auth()->user()?->name ?? 'System';

            foreach ($batches as &$batch) {
                if ((string)($batch['id'] ?? '') === (string)$batchId) {
                    $memoRef = "SM-" . date('Y') . "-" . str_pad($batchId, 3, '0', STR_PAD_LEFT);
                    $batch['status'] = 'Activated';
                    $batch['authorized_by'] = 'Managing Director';
                    $batch['service_memo_ref'] = $memoRef;
                    $batch['service_memo_data'] = [
                        'memo_ref' => $memoRef,
                        'issued_at' => now()->format('M d, Y'),
                        'issued_by' => $actor,
                        'authorized_by' => 'Managing Director',
                    ];
                    if (!isset($batch['history_logs']) || !is_array($batch['history_logs'])) {
                        $batch['history_logs'] = [];
                    }
                    $batch['history_logs'][] = [
                        'event' => 'Service Memo Issued',
                        'details' => "Service Memo {$memoRef} issued. Engagement activated.",
                        'actor' => $actor,
                        'timestamp' => now()->format('M d, Y'),
                    ];

                    // Generate and store Service Memo PDF document on disk
                    try {
                        $serviceMemo = $this->buildBatchServiceMemoPayload($deal, $batch);
                        $targetPath = 'generated-previews/deals/service-memo/' . ($deal->deal_code ?: $deal->id) . '-' . $batchId . '-service-memo.pdf';
                        $pdf = Pdf::loadView('project.pdf.service-memo', compact('serviceMemo'))->setPaper('letter', 'portrait');
                        Storage::disk('public')->delete($targetPath);
                        Storage::disk('public')->put($targetPath, $pdf->output());
                        $batch['service_memo_pdf_path'] = $targetPath;
                    } catch (\Throwable $e) {
                        Log::error("Failed to generate Service Memo PDF for deal {$deal->id}, batch {$batchId}: " . $e->getMessage());
                    }

                    $updatedBatch = $batch;
                    break;
                }
            }
            unset($batch);

            if ($updatedBatch) {
                $deal->start_batches = $batches;
                $deal->save();
                $this->advanceDealToActivationWhenStartIsComplete($deal);

                // Notify all assigned members of official activation
                $allAssignees = [];
                foreach ($updatedBatch['assignments'] ?? [] as $asgn) {
                    if (!empty($asgn['assignee']) && $asgn['assignee'] !== 'Unassigned') {
                        $allAssignees[] = $asgn['assignee'];
                    }
                }
                if (!empty($allAssignees)) {
                    TargetedNotificationService::notifyAssignedUsers(
                        $allAssignees,
                        "Service Memo Issued — {$updatedBatch['start_code']}",
                        "Official Service Memo {$updatedBatch['service_memo_ref']} has been issued. Downstream engagement is now activated for Deal {$deal->deal_code}.",
                        route('deals.show', ['id' => $deal->id]),
                        'START Workflow',
                        'fa-file-signature',
                        'View Service Memo',
                        auth()->id()
                    );
                }
            }

            return response()->json([
                'success' => true,
                'batch' => $updatedBatch,
                'is_start_completed' => $deal->fresh()->isStartCompleted(),
                'start_status' => $deal->fresh()->getResolvedStartStatus(),
            ]);
        });
    }

    private function advanceDealToActivationWhenStartIsComplete(Deal $deal): void
    {
        $deal->refresh();
        if (! $deal->isStartCompleted() || in_array($deal->pipeline_stage, ['Closed Won', 'Closed Lost'], true)) {
            return;
        }

        if ($deal->pipeline_stage === 'Activation') {
            if ($deal->stage !== 'Activation') {
                $deal->stage = 'Activation';
                $deal->save();
            }
            return;
        }

        $from = $deal->pipeline_stage ?: 'Inquiry';
        $deal->pipeline_stage = 'Activation';
        $deal->stage = 'Activation';
        $deal->save();
        app(\App\Services\DealHistoryService::class)->logStageChanged(
            $deal,
            $from,
            'Activation',
            'START requirements completed: required assignments accepted and Service Memo downloadable.'
        );
    }

    /**
     * Memo amendment.
     */
    public function memoAmendment(Request $request, $dealId, $batchId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $targetBatch = collect($batches)->first(fn ($batch) => (string) ($batch['id'] ?? '') === (string) $batchId);
        abort_unless($targetBatch, 404, 'START batch not found.');
        app(\App\Services\DealPaymentEligibility::class)->assertBatchEligible($deal, (array) ($targetBatch['activated_items'] ?? []));
        $updatedBatch = null;
        $reason = $request->input('amendment_reason', 'Amendment requested');
        $instructions = $request->input('special_instructions', '');
        $actor = auth()->user()?->name ?? 'System';

        foreach ($batches as &$batch) {
            if ((string)($batch['id'] ?? '') === (string)$batchId) {
                if (!isset($batch['service_memo_revisions']) || !is_array($batch['service_memo_revisions'])) {
                    $batch['service_memo_revisions'] = [];
                }
                $revNum = count($batch['service_memo_revisions']) + 1;
                $batch['service_memo_revisions'][] = [
                    'revision_number' => $revNum,
                    'amendment_reason' => $reason,
                    'special_instructions' => $instructions,
                    'created_by' => $actor,
                    'created_at' => now()->format('M d, Y'),
                ];
                if (!isset($batch['history_logs']) || !is_array($batch['history_logs'])) {
                    $batch['history_logs'] = [];
                }
                $batch['history_logs'][] = [
                    'event' => 'Amendment Created',
                    'details' => "Service Memo Amendment Rev {$revNum}: {$reason}",
                    'actor' => $actor,
                    'timestamp' => now()->format('M d, Y'),
                ];
                $updatedBatch = $batch;
                break;
            }
        }
        unset($batch);

        if ($updatedBatch) {
            $deal->start_batches = $batches;
            $deal->save();
        }

        return response()->json([
            'success' => true,
            'batch' => $updatedBatch,
        ]);
    }

    /**
     * Return batch for revision.
     */
    public function returnBatch(Request $request, $dealId, $batchId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $updatedBatch = null;
        $reason = $request->input('return_reason', 'Revision required');
        $actor = auth()->user()?->name ?? 'System';

        foreach ($batches as &$batch) {
            if ((string)($batch['id'] ?? '') === (string)$batchId) {
                $batch['status'] = 'Returned';
                if (!isset($batch['history_logs']) || !is_array($batch['history_logs'])) {
                    $batch['history_logs'] = [];
                }
                $batch['history_logs'][] = [
                    'event' => 'Returned',
                    'details' => "START batch returned for correction: {$reason}",
                    'actor' => $actor,
                    'timestamp' => now()->format('M d, Y'),
                ];
                $updatedBatch = $batch;
                break;
            }
        }
        unset($batch);

        if ($updatedBatch) {
            $deal->start_batches = $batches;
            $deal->save();
        }

        return response()->json([
            'success' => true,
            'batch' => $updatedBatch,
            'is_start_completed' => $deal->fresh()->isStartCompleted(),
        ]);
    }

    /**
     * Cancel batch.
     */
    public function cancelBatch(Request $request, $dealId, $batchId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $updatedBatch = null;
        $reason = $request->input('cancellation_reason', 'Cancelled');
        $actor = auth()->user()?->name ?? 'System';

        foreach ($batches as &$batch) {
            if ((string)($batch['id'] ?? '') === (string)$batchId) {
                $batch['status'] = 'Cancelled';
                if (!isset($batch['history_logs']) || !is_array($batch['history_logs'])) {
                    $batch['history_logs'] = [];
                }
                $batch['history_logs'][] = [
                    'event' => 'Cancelled',
                    'details' => "START batch cancelled. Reason: {$reason}",
                    'actor' => $actor,
                    'timestamp' => now()->format('M d, Y'),
                ];
                $updatedBatch = $batch;
                break;
            }
        }
        unset($batch);

        if ($updatedBatch) {
            $deal->start_batches = $batches;
            $deal->save();
        }

        return response()->json([
            'success' => true,
            'batch' => $updatedBatch,
            'is_start_completed' => $deal->fresh()->isStartCompleted(),
        ]);
    }

    /**
     * Check if the authenticated user is authorized as the assignee for this assignment.
     */
    private function isUserTheAssignee(?array $assignment): bool
    {
        if (!$assignment || !auth()->check()) {
            return false;
        }

        $user = auth()->user();
        $assignee = trim($assignment['assignee'] ?? ($assignment['assigned_to'] ?? ''));

        if (strcasecmp($assignee, trim($user->name)) === 0) {
            return true;
        }

        if (strcasecmp($assignee, trim($user->email)) === 0) {
            return true;
        }

        if (!empty($assignment['user_id']) && (int) $assignment['user_id'] === (int) $user->id) {
            return true;
        }

        // Allow super admin / administrator if needed for system administration
        if (in_array(strtolower($user->role ?? ''), ['admin', 'superadmin', 'managing director', 'president'])) {
            return true;
        }

        return false;
    }

    /**
     * Accept/Acknowledge an assignment (strictly performed by the assigned user).
     */
    public function acknowledgeAssignment(Request $request, $dealId, $assignId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $updatedBatch = null;
        $targetAssignment = null;
        $user = auth()->user();
        $actorName = $user?->name ?? 'Assigned User';

        foreach ($batches as &$batch) {
            if (!empty($batch['assignments']) && is_array($batch['assignments'])) {
                foreach ($batch['assignments'] as &$a) {
                    if ((string)($a['id'] ?? '') === (string)$assignId) {
                        if (!$this->isUserTheAssignee($a)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Only the assigned user can accept this assignment.'
                            ], 403);
                        }

                        $a['status'] = 'Acknowledged';
                        $a['response'] = 'Accepted';
                        $a['acknowledged_at'] = now()->format('M d, Y h:i A');
                        $a['decline_reason'] = null;
                        if ($request->filled('notes')) {
                            $a['notes'] = $request->input('notes');
                        }

                        $targetAssignment = $a;
                        $updatedBatch = &$batch;
                        break 2;
                    }
                }
                unset($a);
            }
        }
        unset($batch);

        if ($updatedBatch && $targetAssignment) {
            if (!isset($updatedBatch['history_logs']) || !is_array($updatedBatch['history_logs'])) {
                $updatedBatch['history_logs'] = [];
            }
            $updatedBatch['history_logs'][] = [
                'event' => 'Assignment Accepted',
                'details' => "Role \"{$targetAssignment['role']}\" accepted by {$actorName}",
                'actor' => $actorName,
                'timestamp' => now()->format('M d, Y h:i A'),
            ];

            $previousStatus = (string) ($updatedBatch['status'] ?? '');
            $updatedBatch['status'] = $deal->resolveStartBatchTeamStatus(
                $updatedBatch,
                confirmationSent: in_array($previousStatus, ['For Team Confirmation', 'Partially Acknowledged', 'Team Confirmed'], true)
            );
            if ($updatedBatch['status'] === 'Team Confirmed' && $previousStatus !== 'Team Confirmed') {
                $updatedBatch['team_confirmed_at'] = now()->toIso8601String();
                $updatedBatch['history_logs'][] = [
                    'event' => 'Team Confirmed',
                    'details' => 'All required assignees explicitly accepted their assignments.',
                    'actor' => $actorName,
                    'timestamp' => now()->format('M d, Y h:i A'),
                ];
            }

            $deal->start_batches = $batches;
            $deal->save();
            $this->advanceDealToActivationWhenStartIsComplete($deal);

            // Notify Assigner / Deal Owner that the assignee accepted
            $assignerName = $updatedBatch['created_by'] ?? ($deal->owner_name ?? null);
            if ($assignerName && strcasecmp($assignerName, $actorName) !== 0) {
                $url = route('deals.show', ['id' => $deal->id]) . '?tab=start&batch_id=' . $updatedBatch['id'];
                TargetedNotificationService::notifyAssignedUsers(
                    $assignerName,
                    "START Assignment Accepted: {$targetAssignment['role']}",
                    "{$actorName} has accepted the role of \"{$targetAssignment['role']}\" for START Batch {$updatedBatch['start_code']} ({$deal->deal_code}).",
                    $url,
                    'START Workflow',
                    'fa-check-circle',
                    'View START',
                    $user?->id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment accepted successfully.',
                'batch' => $updatedBatch,
                'is_start_completed' => $deal->fresh()->isStartCompleted(),
                'start_status' => $deal->fresh()->getResolvedStartStatus(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Assignment not found.',
        ], 404);
    }

    /**
     * Decline an assignment (strictly performed by the assigned user with reason).
     */
    public function declineAssignment(Request $request, $dealId, $assignId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $updatedBatch = null;
        $targetAssignment = null;
        $user = auth()->user();
        $actorName = $user?->name ?? 'Assigned User';

        $validated = $request->validate([
            'decline_reason' => ['required', 'string', 'max:500'],
        ]);
        $declineReason = $validated['decline_reason'];

        foreach ($batches as &$batch) {
            if (!empty($batch['assignments']) && is_array($batch['assignments'])) {
                foreach ($batch['assignments'] as &$a) {
                    if ((string)($a['id'] ?? '') === (string)$assignId) {
                        if (!$this->isUserTheAssignee($a)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Only the assigned user can submit a decline reason for this assignment.'
                            ], 403);
                        }

                        $a['status'] = 'Cannot Accept';
                        $a['response'] = 'Declined';
                        $a['decline_reason'] = $declineReason;
                        $a['acknowledged_at'] = now()->format('M d, Y h:i A');

                        $targetAssignment = $a;
                        $updatedBatch = &$batch;
                        break 2;
                    }
                }
                unset($a);
            }
        }
        unset($batch);

        if ($updatedBatch && $targetAssignment) {
            if (!isset($updatedBatch['history_logs']) || !is_array($updatedBatch['history_logs'])) {
                $updatedBatch['history_logs'] = [];
            }
            $updatedBatch['history_logs'][] = [
                'event' => 'Assignment Declined',
                'details' => "Role \"{$targetAssignment['role']}\" declined by {$actorName}. Reason: {$declineReason}",
                'actor' => $actorName,
                'timestamp' => now()->format('M d, Y h:i A'),
            ];

            $updatedBatch['status'] = $deal->resolveStartBatchTeamStatus($updatedBatch);

            $deal->start_batches = $batches;
            $deal->save();

            // Notify Assigner / Deal Owner that the assignee declined, with Reassign action
            $assignerName = $updatedBatch['created_by'] ?? ($deal->owner_name ?? null);
            if ($assignerName) {
                $reassignUrl = route('deals.show', ['id' => $deal->id]) . '?tab=start&batch_id=' . $updatedBatch['id'] . '&action=reassign&assignment_id=' . $targetAssignment['id'];
                TargetedNotificationService::notifyAssignedUsers(
                    $assignerName,
                    "START Role Declined: {$targetAssignment['role']}",
                    "{$actorName} cannot accept the role \"{$targetAssignment['role']}\" for START Batch {$updatedBatch['start_code']}. Reason: \"{$declineReason}\". Please reassign.",
                    $reassignUrl,
                    'START Workflow',
                    'fa-user-times',
                    'Reassign Role',
                    $user?->id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment declined. The assigner has been notified to reassign.',
                'batch' => $updatedBatch,
                'is_start_completed' => $deal->fresh()->isStartCompleted(),
                'start_status' => $deal->fresh()->getResolvedStartStatus(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Assignment not found.',
        ], 404);
    }

    /**
     * Reassign an assignment role to a new user (performed by the assigner/manager).
     */
    public function reassignAssignment(Request $request, $dealId, $assignId): JsonResponse
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $updatedBatch = null;
        $targetAssignment = null;
        $oldAssignee = null;
        $actorName = auth()->user()?->name ?? 'System';

        $validated = $request->validate([
            'new_assignee' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $newAssignee = $validated['new_assignee'];
        $notes = $validated['notes'] ?? '';

        foreach ($batches as &$batch) {
            if (!empty($batch['assignments']) && is_array($batch['assignments'])) {
                foreach ($batch['assignments'] as &$a) {
                    if ((string)($a['id'] ?? '') === (string)$assignId) {
                        $oldAssignee = $a['assignee'] ?? 'Unassigned';
                        $a['assignee'] = $newAssignee;
                        $a['status'] = 'Pending';
                        $a['response'] = null;
                        $a['decline_reason'] = null;
                        $a['acknowledged_at'] = null;
                        if ($notes) {
                            $a['notes'] = $notes;
                        }

                        $targetAssignment = $a;
                        $updatedBatch = &$batch;
                        break 2;
                    }
                }
                unset($a);
            }
        }
        unset($batch);

        if ($updatedBatch && $targetAssignment) {
            if (!isset($updatedBatch['history_logs']) || !is_array($updatedBatch['history_logs'])) {
                $updatedBatch['history_logs'] = [];
            }
            $updatedBatch['history_logs'][] = [
                'event' => 'Role Reassigned',
                'details' => "Role \"{$targetAssignment['role']}\" reassigned from {$oldAssignee} to {$newAssignee}",
                'actor' => $actorName,
                'timestamp' => now()->format('M d, Y h:i A'),
            ];

            $updatedBatch['status'] = $deal->resolveStartBatchTeamStatus($updatedBatch);

            $deal->start_batches = $batches;
            $deal->save();

            // Send targeted notification strictly to the NEW assignee!
            $url = route('deals.show', ['id' => $deal->id]) . '?tab=start&batch_id=' . $updatedBatch['id'] . '&assignment_id=' . $targetAssignment['id'] . '&action=respond';
            TargetedNotificationService::notifyAssignedUsers(
                $newAssignee,
                "New START Role Assigned: {$targetAssignment['role']}",
                "You have been assigned as \"{$targetAssignment['role']}\" in START Batch {$updatedBatch['start_code']} for Deal {$deal->deal_code}. Please review to accept or decline.",
                $url,
                'START Workflow',
                'fa-user-check',
                'Review Assignment',
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => "Role successfully reassigned to {$newAssignee}.",
                'batch' => $updatedBatch,
                'is_start_completed' => $deal->fresh()->isStartCompleted(),
                'start_status' => $deal->fresh()->getResolvedStartStatus(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Assignment not found.',
        ], 404);
    }

    /**
     * Download the official Service Memo PDF for a specific batch.
     */
    public function downloadServiceMemo($dealId, $batchId): \Symfony\Component\HttpFoundation\Response
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);
        $targetBatch = null;
        foreach ($batches as $b) {
            if ((string)($b['id'] ?? '') === (string)$batchId) {
                $targetBatch = $b;
                break;
            }
        }

        abort_unless($targetBatch, 404, 'The requested START batch was not found.');

        $pdfPath = $targetBatch['service_memo_pdf_path'] ?? null;
        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 404, 'Service memo document is not available for download.');

        $filename = ($targetBatch['service_memo_ref'] ?: 'service-memo') . '.pdf';

        return Storage::disk('public')->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Download the latest official Service Memo PDF for a deal.
     */
    public function downloadLatestServiceMemo($dealId): \Symfony\Component\HttpFoundation\Response
    {
        $deal = Deal::findOrFail($dealId);
        $batches = (array) ($deal->start_batches ?? []);

        foreach ($batches as $batch) {
            $pdfPath = $batch['service_memo_pdf_path'] ?? null;
            if (!empty($batch['service_memo_ref']) && $pdfPath && Storage::disk('public')->exists($pdfPath)) {
                $filename = ($batch['service_memo_ref'] ?: 'service-memo') . '.pdf';
                return Storage::disk('public')->download($pdfPath, $filename, [
                    'Content-Type' => 'application/pdf',
                ]);
            }
        }

        if ($deal->project) {
            return app(ProjectController::class)->downloadServiceMemoPdf($deal->project);
        }
        if ($deal->projects && $deal->projects->first()) {
            return app(ProjectController::class)->downloadServiceMemoPdf($deal->projects->first());
        }

        abort(404, 'No downloadable Service Memo document found for this deal.');
    }

    /**
     * Build the structured Service Memo payload for DomPDF rendering.
     */
    public function buildBatchServiceMemoPayload(Deal $deal, array $batch): array
    {
        $deal->loadMissing([
            'contact:id,first_name,last_name,email,company_name',
            'primaryContact:id,first_name,last_name,email,company_name',
        ]);

        $contactName = trim(collect([
            $deal->contact?->first_name ?: $deal->primaryContact?->first_name,
            $deal->contact?->last_name ?: $deal->primaryContact?->last_name,
        ])->filter()->implode(' '))
            ?: ($deal->primary_contact_name ?: ($deal->contact_person ?: ($deal->client_name ?: '-')));

        $leadConsultant = $deal->assigned_consultant ?: '';
        $leadAssociate = $deal->assigned_associate ?: '';
        foreach ($batch['assignments'] ?? [] as $asgn) {
            $role = strtolower(trim((string)($asgn['role'] ?? '')));
            if (str_contains($role, 'consultant') && empty($leadConsultant)) {
                $leadConsultant = $asgn['assignee'] ?? '';
            }
            if (str_contains($role, 'associate') && empty($leadAssociate)) {
                $leadAssociate = $asgn['assignee'] ?? '';
            }
        }

        $memoData = (array) ($batch['service_memo_data'] ?? []);
        $dateIssued = $memoData['issued_at'] ?? now()->format('F d, Y');

        return [
            'title' => 'SERVICE MEMO',
            'form_code' => 'ENG-F-003-v1.0-03.16.26',
            'date_issued' => $dateIssued,
            'engagement_type' => $deal->engagement_type ?: 'Professional Services',
            'start_ref_no' => $batch['start_code'] ?? '-',
            'start_cleared_date' => $batch['opened_date'] ?? now()->format('F d, Y'),
            'condeal_reference_no' => $deal->deal_code ?: '-',
            'client_name' => $contactName,
            'business_name' => $deal->company ?: ($deal->deal_title ?: '-'),
            'engagement_reference_no' => $batch['start_code'] ?? '-',
            'approved_start_date' => $deal->planned_start_date ? \Carbon\Carbon::parse($deal->planned_start_date)->format('F d, Y') : ($batch['opened_date'] ?? now()->format('F d, Y')),
            'target_completion_date' => $deal->estimated_completion_date ? \Carbon\Carbon::parse($deal->estimated_completion_date)->format('F d, Y') : '-',
            'rsat_template' => $deal->service_template ?: '-',
            'sow_template' => $batch['batch_name'] ?? '-',
            'lead_consultant' => $leadConsultant ?: 'Assigned Consultant',
            'associate' => $leadAssociate ?: 'Assigned Associate',
            'sales_marketing' => $deal->internal_sales_marketing ?: 'Sales and Marketing',
            'finance' => $deal->internal_finance ?: 'Finance',
            'office_of_president' => $deal->internal_president ?: 'Office of the President',
        ];
    }
}
