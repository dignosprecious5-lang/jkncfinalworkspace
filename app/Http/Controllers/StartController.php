<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\StartRecord;
use App\Models\StartEngagementGroup;
use App\Models\EngagementAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StartController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | START Workspace / Batches Index
    |--------------------------------------------------------------------------
    */

    public function show($dealId)
    {
        $deal = Deal::with([
            'startRecords.engagementGroups.assignments',
            'startRecords.assignments',
            'projectScopeItems',
            'dealContacts',
            'proposal',
            'company',
            'account'
        ])->findOrFail($dealId);

        $users = User::with([
            'userPositions.position',
            'userPositions.department',
        ])->orderBy('name')->get();

        $batches = $deal->startRecords()->with(['engagementGroups.assignments', 'assignments'])->orderBy('id')->get();

        // If no batch exists yet, optionally create initial or allow user to create progressive batch
        $start = $batches->first();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'deal' => $deal,
                'batches' => $batches,
            ]);
        }

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start');
    }

    /*
    |--------------------------------------------------------------------------
    | Create Progressive START Batch
    |--------------------------------------------------------------------------
    */

    public function storeBatch(Request $request, $dealId)
    {
        $deal = Deal::findOrFail($dealId);

        $validated = $request->validate([
            'batch_name' => 'nullable|string|max:255',
            'start_code' => 'nullable|string|max:50',
            'activated_items' => 'nullable|array',
            'scope_deliverables' => 'nullable|string',
            'engagement_type' => 'nullable|string|max:255',
            'planned_start_date' => 'nullable|date',
            'target_completion_date' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:1',
            'service_frequency' => 'nullable|string|max:255',
            'billing_frequency' => 'nullable|string|max:255',
            'reporting_frequency' => 'nullable|string|max:255',
            'assignments' => 'nullable|array',
            'assignments.*.role' => 'nullable|string|max:255',
            'assignments.*.assigned_to' => 'nullable|string|max:255',
            'assignments.*.is_required' => 'nullable|boolean',
            'assignments.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $batchCount = $deal->startRecords()->count() + 1;
        $startCode = $validated['start_code'] ?: ('ST-' . date('Y') . '-' . str_pad($batchCount, 3, '0', STR_PAD_LEFT));
        $batchName = $validated['batch_name'] ?: ('START Batch ' . str_pad($batchCount, 3, '0', STR_PAD_LEFT));
        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        $now = now();
        $historyLogs = [
            [
                'event' => 'Created',
                'title' => 'START Batch Created',
                'timestamp' => $now->format('M d, Y h:i A'),
                'actor' => $currentUser,
                'notes' => 'Created ' . $batchName . ' with ' . count($validated['activated_items'] ?? []) . ' activated items.'
            ],
            [
                'event' => 'Structuring',
                'title' => 'Engagement Structuring',
                'timestamp' => $now->format('M d, Y h:i A'),
                'actor' => $currentUser,
                'notes' => 'Operational engagement structure and assignment roles defined.'
            ]
        ];

        $start = StartRecord::create([
            'deal_id' => $deal->id,
            'start_code' => $startCode,
            'batch_name' => $batchName,
            'status' => 'Structuring',
            'memo_status' => 'Draft',
            'activated_items' => $validated['activated_items'] ?? [],
            'activation_summary' => [
                'items_count' => count($validated['activated_items'] ?? []),
                'items' => $validated['activated_items'] ?? [],
                'engagement_type' => $validated['engagement_type'] ?? ($deal->engagement_type ?: 'Regular Engagement'),
                'planned_start_date' => $validated['planned_start_date'] ?? null,
                'target_completion_date' => $validated['target_completion_date'] ?? null,
                'duration_days' => $validated['duration_days'] ?? null,
            ],
            'history_logs' => $historyLogs,
            'created_by' => $currentUser,
            'authorized_by' => $deal->lead_consultant ?: $deal->owner_name ?: 'John Kelly Abalde',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create default Engagement Group
        $group = StartEngagementGroup::create([
            'start_record_id' => $start->id,
            'engagement_type' => $validated['engagement_type'] ?? ($deal->engagement_type ?: 'Regular Engagement'),
            'group_code' => 'GRP-' . str_pad($batchCount, 2, '0', STR_PAD_LEFT),
            'temporary_group_key' => 'REG-' . str_pad($batchCount, 2, '0', STR_PAD_LEFT),
            'group_name' => $batchName,
            'title' => $batchName,
            'deal_item_ids' => $validated['activated_items'] ?? [],
            'scope_deliverables' => $validated['scope_deliverables'] ?? ($deal->scope_of_work ?: 'Advisory and compliance coordination deliverables.'),
            'start_date' => $validated['planned_start_date'] ?? null,
            'target_end_date' => $validated['target_completion_date'] ?? null,
            'duration_days' => $validated['duration_days'] ?? null,
            'service_frequency' => $validated['service_frequency'] ?? 'As Scheduled',
            'billing_frequency' => $validated['billing_frequency'] ?? ($deal->payment_terms ?: 'As Incurred'),
            'reporting_frequency' => $validated['reporting_frequency'] ?? 'Monthly Status Report',
            'status' => 'Structuring',
            'project_manager' => $deal->lead_consultant ?: $currentUser,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create initial assignments if provided or default roles
        $assignmentsData = $validated['assignments'] ?? [];
        if (empty($assignmentsData)) {
            // Default roles from deal
            $defaultRoles = [
                ['role' => 'Lead Consultant', 'assigned_to' => $deal->lead_consultant ?: 'John Kelly Abalde', 'is_required' => true],
                ['role' => 'Lead Associate', 'assigned_to' => $deal->lead_associate ?: ($deal->assigned_associate ?: 'Ma. Lourdes T. Mata'), 'is_required' => true],
                ['role' => 'Support Associate', 'assigned_to' => $deal->assigned_consultant ?: 'Rubeca Potayre', 'is_required' => true],
            ];
            $assignmentsData = $defaultRoles;
        }

        foreach ($assignmentsData as $aData) {
            if (empty($aData['assigned_to'])) continue;

            EngagementAssignment::create([
                'start_record_id' => $start->id,
                'start_engagement_group_id' => $group->id,
                'role' => $aData['role'] ?? 'Consultant',
                'assigned_to' => $aData['assigned_to'],
                'status' => 'Pending',
                'is_required' => $aData['is_required'] ?? true,
                'notes' => $aData['notes'] ?? null,
            ]);

            if (($aData['role'] ?? '') === 'Lead Consultant' && !empty($aData['assigned_to'])) {
                $deal->update(['lead_consultant' => $aData['assigned_to']]);
            }
            if (($aData['role'] ?? '') === 'Lead Associate' && !empty($aData['assigned_to'])) {
                $deal->update(['lead_associate' => $aData['assigned_to']]);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            $start->load(['engagementGroups.assignments', 'assignments']);
            return response()->json([
                'success' => true,
                'message' => 'Progressive START Batch created successfully.',
                'batch' => $start,
            ]);
        }

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start')->with('success', 'Progressive START Batch created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Single START Batch Details (JSON)
    |--------------------------------------------------------------------------
    */

    public function getBatch($dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::with(['engagementGroups.assignments', 'assignments'])->where('deal_id', $deal->id)->findOrFail($batchId);

        return response()->json([
            'success' => true,
            'deal' => $deal,
            'batch' => $start,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update START Batch
    |--------------------------------------------------------------------------
    */

    public function updateBatch(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->findOrFail($batchId);

        $validated = $request->validate([
            'batch_name' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'activated_items' => 'nullable|array',
            'notes' => 'nullable|string',
            'authorized_by' => 'nullable|string|max:255',
        ]);

        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        if (isset($validated['status']) && $validated['status'] !== $start->status) {
            $this->logHistory($start, $validated['status'], 'Status updated to ' . $validated['status'], $currentUser);
        }

        $start->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            $start->load(['engagementGroups.assignments', 'assignments']);
            return response()->json([
                'success' => true,
                'message' => 'START Batch updated successfully.',
                'batch' => $start,
            ]);
        }

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start')->with('success', 'START Batch updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Send For Team Confirmation
    |--------------------------------------------------------------------------
    */

    public function sendTeamConfirmation(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::with(['assignments', 'engagementGroups.assignments'])->where('deal_id', $deal->id)->findOrFail($batchId);

        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        // Set status to For Team Confirmation
        $start->update([
            'status' => 'For Team Confirmation',
        ]);

        // Send notifications to all assignees
        $assignments = $start->assignments->isEmpty()
            ? $start->engagementGroups->flatMap->assignments
            : $start->assignments;

        foreach ($assignments as $assignment) {
            $this->notifyUser(
                $assignment->assigned_to,
                'START Assignment Confirmation Required: ' . ($start->start_code ?: 'Batch ' . $start->id),
                'You have been assigned as ' . $assignment->role . ' for ' . ($deal->company ?: $deal->deal_title) . '. Please review and acknowledge your assignment.',
                route('deals.show', ['id' => $deal->id]) . '#start'
            );
        }

        $this->logHistory(
            $start,
            'For Team Confirmation',
            'Assignment notifications dispatched to ' . $assignments->count() . ' team members.',
            $currentUser
        );

        // Recompute status in case some were already acknowledged
        $this->recomputeBatchStatus($start);

        return response()->json([
            'success' => true,
            'message' => 'START Batch submitted for Team Confirmation. Assignment notifications sent.',
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add Assignment to START Batch
    |--------------------------------------------------------------------------
    */

    public function storeBatchAssignment(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->findOrFail($batchId);

        $validated = $request->validate([
            'role' => 'required|string|max:255',
            'assigned_to' => 'required|string|max:255',
            'is_required' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $group = $start->engagementGroups()->first();
        $groupId = $group ? $group->id : null;

        $assignment = EngagementAssignment::create([
            'start_record_id' => $start->id,
            'start_engagement_group_id' => $groupId,
            'role' => $validated['role'],
            'assigned_to' => $validated['assigned_to'],
            'status' => 'Pending',
            'is_required' => $validated['is_required'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');
        $this->logHistory($start, 'Assignment Added', 'Assigned ' . $assignment->assigned_to . ' as ' . $assignment->role . '.', $currentUser);

        // Recompute status
        $this->recomputeBatchStatus($start);

        return response()->json([
            'success' => true,
            'message' => 'Assignment added successfully.',
            'assignment' => $assignment,
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Assignee Acknowledges Assignment
    |--------------------------------------------------------------------------
    */

    public function acknowledgeAssignment(Request $request, $dealId, $assignmentId)
    {
        $deal = Deal::findOrFail($dealId);
        $assignment = EngagementAssignment::findOrFail($assignmentId);
        $start = $assignment->startRecord ?: ($assignment->engagementGroup ? $assignment->engagementGroup->startRecord : null);

        if (!$start || $start->deal_id != $deal->id) {
            return response()->json(['success' => false, 'message' => 'Assignment does not belong to this deal.'], 404);
        }

        $notes = $request->input('notes', $assignment->notes);
        $acknowledgedBy = auth()->check() ? auth()->user()->name : $assignment->assigned_to;

        $assignment->update([
            'status' => 'Acknowledged',
            'response' => 'Accepted',
            'acknowledged_at' => now(),
            'notes' => $notes,
        ]);

        $this->logHistory(
            $start,
            'Acknowledgement Received',
            $assignment->assigned_to . ' accepted assignment for ' . $assignment->role . '.',
            $acknowledgedBy
        );

        // Recompute batch status strictly:
        // Partially Acknowledged if some acknowledged but some pending
        // Team Confirmed only when ALL required assignments have acknowledged
        $newStatus = $this->recomputeBatchStatus($start);

        return response()->json([
            'success' => true,
            'message' => 'Assignment acknowledged successfully.',
            'status' => $newStatus,
            'assignment' => $assignment,
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Assignee Declines / Cannot Accept Assignment
    |--------------------------------------------------------------------------
    */

    public function declineAssignment(Request $request, $dealId, $assignmentId)
    {
        $deal = Deal::findOrFail($dealId);
        $assignment = EngagementAssignment::findOrFail($assignmentId);
        $start = $assignment->startRecord ?: ($assignment->engagementGroup ? $assignment->engagementGroup->startRecord : null);

        if (!$start || $start->deal_id != $deal->id) {
            return response()->json(['success' => false, 'message' => 'Assignment does not belong to this deal.'], 404);
        }

        $reason = $request->input('decline_reason', 'Cannot accept due to scheduling/capacity constraint.');
        $declinedBy = auth()->check() ? auth()->user()->name : $assignment->assigned_to;

        $assignment->update([
            'status' => 'Cannot Accept',
            'response' => 'Declined',
            'decline_reason' => $reason,
            'acknowledged_at' => now(),
        ]);

        // Per Rule: If a required assignee declines/cannot accept -> set status to Needs Reassignment
        $start->update([
            'status' => 'Needs Reassignment',
        ]);

        $this->logHistory(
            $start,
            'Needs Reassignment',
            $assignment->assigned_to . ' declined assignment (' . $assignment->role . '). Reason: ' . $reason,
            $declinedBy
        );

        // Notify Lead Consultant / Creator
        $this->notifyUser(
            $start->created_by ?: ($deal->owner_name ?: 'John Kelly Abalde'),
            'Reassignment Required: ' . ($start->start_code ?: 'Batch ' . $start->id),
            $assignment->assigned_to . ' cannot accept role ' . $assignment->role . '. Please reassign.',
            route('deals.show', ['id' => $deal->id]) . '#start'
        );

        return response()->json([
            'success' => true,
            'message' => 'Assignment response recorded. Status updated to Needs Reassignment.',
            'assignment' => $assignment,
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reassign Assignment
    |--------------------------------------------------------------------------
    */

    public function reassignAssignment(Request $request, $dealId, $assignmentId)
    {
        $deal = Deal::findOrFail($dealId);
        $assignment = EngagementAssignment::findOrFail($assignmentId);
        $start = $assignment->startRecord ?: ($assignment->engagementGroup ? $assignment->engagementGroup->startRecord : null);

        if (!$start || $start->deal_id != $deal->id) {
            return response()->json(['success' => false, 'message' => 'Assignment does not belong to this deal.'], 404);
        }

        $validated = $request->validate([
            'new_assignee' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $prevAssignee = $assignment->assigned_to;
        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        $assignment->update([
            'assigned_to' => $validated['new_assignee'],
            'status' => 'Pending',
            'response' => null,
            'decline_reason' => null,
            'acknowledged_at' => null,
            'notes' => $validated['notes'] ?? ('Reassigned from ' . $prevAssignee),
        ]);

        // Send notification to new assignee
        $this->notifyUser(
            $validated['new_assignee'],
            'New START Assignment: ' . ($start->start_code ?: 'Batch ' . $start->id),
            'You have been reassigned as ' . $assignment->role . ' for ' . ($deal->company ?: $deal->deal_title) . '. Please review and acknowledge.',
            route('deals.show', ['id' => $deal->id]) . '#start'
        );

        $this->logHistory(
            $start,
            'Reassigned',
            'Reassigned ' . $assignment->role . ' from ' . $prevAssignee . ' to ' . $validated['new_assignee'] . '.',
            $currentUser
        );

        $newStatus = $this->recomputeBatchStatus($start);

        return response()->json([
            'success' => true,
            'message' => 'Reassigned successfully to ' . $validated['new_assignee'] . '.',
            'status' => $newStatus,
            'assignment' => $assignment,
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Submit For Final Review
    |--------------------------------------------------------------------------
    */

    public function submitFinalReview(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::with(['assignments', 'engagementGroups.assignments'])->where('deal_id', $deal->id)->findOrFail($batchId);

        // Recompute status using the standard source of truth
        $currentStatus = $this->recomputeBatchStatus($start);

        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        // Check that all required assignments are acknowledged
        $allAssignments = $start->assignments->isEmpty()
            ? $start->engagementGroups->flatMap->assignments
            : $start->assignments;

        $required = $allAssignments->where('is_required', true);
        $acknowledged = $required->where('status', 'Acknowledged');

        if ($currentStatus !== 'Team Confirmed' && ($required->count() > 0 && $acknowledged->count() < $required->count())) {
            return response()->json([
                'success' => false,
                'message' => 'Service Memo cannot proceed to Final Review. Waiting for Team Confirmation (' . $acknowledged->count() . ' of ' . $required->count() . ' required roles confirmed).'
            ], 422);
        }

        $start->update([
            'status' => 'For Final Review',
            'memo_status' => 'Ready',
        ]);

        $this->logHistory(
            $start,
            'For Final Review',
            'All assignments confirmed. START submitted for Final Review and Service Memo is ready.',
            $currentUser
        );

        // Notify Lead Consultant / Approver
        $this->notifyUser(
            $start->authorized_by ?: ($deal->lead_consultant ?: 'John Kelly Abalde'),
            'START Ready for Final Review: ' . ($start->start_code ?: 'Batch ' . $start->id),
            'All ' . $required->count() . ' assignments acknowledged. Service Memo is ready for issuance.',
            route('deals.show', ['id' => $deal->id]) . '#start'
        );

        return response()->json([
            'success' => true,
            'message' => 'START submitted for Final Review. Service Memo is ready for issuance.',
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Issue Service Memo & Activate Downstream
    |--------------------------------------------------------------------------
    */

    public function issueServiceMemo(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::with(['assignments', 'engagementGroups.assignments'])->where('deal_id', $deal->id)->findOrFail($batchId);

        if (!in_array($start->status, ['For Final Review', 'Team Confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Service Memo cannot be issued before Final Review is conducted.'
            ], 422);
        }

        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');
        $authorizedBy = $request->input('authorized_by', $start->authorized_by ?: ($deal->lead_consultant ?: 'John Kelly Abalde'));
        $memoRef = $start->service_memo_ref ?: ('SM-' . date('Y') . '-' . str_pad($start->id, 3, '0', STR_PAD_LEFT));
        $now = now();

        $allAssignments = $start->assignments->isEmpty()
            ? $start->engagementGroups->flatMap->assignments
            : $start->assignments;

        $memoData = [
            'memo_ref' => $memoRef,
            'start_code' => $start->start_code ?: ('ST-' . date('Y') . '-' . str_pad($start->id, 3, '0', STR_PAD_LEFT)),
            'deal_code' => $deal->deal_code ?: ('CONDEAL-' . date('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT)),
            'client_name' => $deal->company ?: ($deal->primary_contact_name ?: $deal->deal_title),
            'primary_contact' => $deal->primary_contact_name ?: ($deal->first_name ? trim($deal->first_name . ' ' . $deal->last_name) : 'Authorized Representative'),
            'engagement_type' => $deal->engagement_type ?: 'Regular Engagement',
            'activated_items' => $start->activated_items ?? [],
            'assignments' => $allAssignments->map(fn($a) => [
                'role' => $a->role,
                'assigned_to' => $a->assigned_to,
                'status' => $a->status,
                'acknowledged_at' => optional($a->acknowledged_at)->format('M d, Y h:i A'),
            ])->toArray(),
            'authorized_by' => $authorizedBy,
            'issued_by' => $currentUser,
            'issued_at' => $now->format('M d, Y h:i A'),
            'special_instructions' => $request->input('special_instructions', $start->notes ?: 'Commence operational service onboarding and client compliance coordination.'),
        ];

        $start->update([
            'status' => 'Activated',
            'memo_status' => 'Issued',
            'service_memo_ref' => $memoRef,
            'service_memo_data' => $memoData,
            'issued_by' => $currentUser,
            'issued_at' => $now,
            'authorized_by' => $authorizedBy,
            'reviewed_by' => $authorizedBy,
            'reviewed_at' => $now,
        ]);

        // Downstream group update
        foreach ($start->engagementGroups as $grp) {
            $grp->update(['status' => 'Activated']);
        }

        $this->logHistory(
            $start,
            'Activated',
            'Service Memo ' . $memoRef . ' issued by ' . $currentUser . ' (Authorized by ' . $authorizedBy . '). Downstream engagement activated.',
            $currentUser
        );

        // Notify all team members
        foreach ($allAssignments as $assignment) {
            $this->notifyUser(
                $assignment->assigned_to,
                'Service Memo Issued: ' . $memoRef,
                'START batch ' . ($start->start_code ?: 'ST-' . $start->id) . ' has been Activated. Downstream operations have commenced.',
                route('deals.show', ['id' => $deal->id]) . '#start'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Service Memo ' . $memoRef . ' successfully issued. START Batch is now Activated.',
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Service Memo Revision / Amendment (Preserves Original)
    |--------------------------------------------------------------------------
    */

    public function createMemoRevision(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->findOrFail($batchId);

        $validated = $request->validate([
            'amendment_reason' => 'required|string|max:500',
            'special_instructions' => 'nullable|string',
            'activated_items' => 'nullable|array',
        ]);

        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');
        $revisions = $start->service_memo_revisions ?? [];
        $revNumber = count($revisions) + 1;
        $now = now();

        $newRevision = [
            'revision_number' => $revNumber,
            'revision_label' => 'Rev ' . $revNumber . ' (Amendment)',
            'amendment_reason' => $validated['amendment_reason'],
            'special_instructions' => $validated['special_instructions'] ?? ($start->service_memo_data['special_instructions'] ?? ''),
            'previous_memo_snapshot' => $start->service_memo_data,
            'created_by' => $currentUser,
            'created_at' => $now->format('M d, Y h:i A'),
        ];

        $revisions[] = $newRevision;

        // Update active memo data with amendment note
        $memoData = $start->service_memo_data ?: [];
        $memoData['amended_at'] = $now->format('M d, Y h:i A');
        $memoData['amended_by'] = $currentUser;
        $memoData['active_revision'] = 'Rev ' . $revNumber;
        if (!empty($validated['special_instructions'])) {
            $memoData['special_instructions'] = $validated['special_instructions'];
        }
        if (!empty($validated['activated_items'])) {
            $memoData['activated_items'] = $validated['activated_items'];
            $start->activated_items = $validated['activated_items'];
        }

        $start->update([
            'memo_status' => 'Amended',
            'service_memo_data' => $memoData,
            'service_memo_revisions' => $revisions,
        ]);

        $this->logHistory(
            $start,
            'Amended',
            'Created Service Memo Revision ' . $revNumber . '. Reason: ' . $validated['amendment_reason'],
            $currentUser
        );

        return response()->json([
            'success' => true,
            'message' => 'Service Memo amendment Rev ' . $revNumber . ' created successfully.',
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Return START for Correction
    |--------------------------------------------------------------------------
    */

    public function returnBatch(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->findOrFail($batchId);

        $reason = $request->input('return_reason', 'Returned for correction.');
        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        $start->update([
            'status' => 'Returned',
            'return_reason' => $reason,
        ]);

        $this->logHistory(
            $start,
            'Returned',
            'START returned for correction. Reason: ' . $reason,
            $currentUser
        );

        $this->notifyUser(
            $start->created_by ?: ($deal->owner_name ?: 'John Kelly Abalde'),
            'START Batch Returned: ' . ($start->start_code ?: 'Batch ' . $start->id),
            'START batch returned for correction. Reason: ' . $reason,
            route('deals.show', ['id' => $deal->id]) . '#start'
        );

        return response()->json([
            'success' => true,
            'message' => 'START returned for correction.',
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel START Batch
    |--------------------------------------------------------------------------
    */

    public function cancelBatch(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->findOrFail($batchId);

        $reason = $request->input('cancellation_reason', 'Activation cancelled.');
        $currentUser = auth()->check() ? auth()->user()->name : ($deal->owner_name ?: 'John Kelly Abalde');

        $start->update([
            'status' => 'Cancelled',
            'cancellation_reason' => $reason,
        ]);

        $this->logHistory(
            $start,
            'Cancelled',
            'START Batch activation cancelled. Reason: ' . $reason,
            $currentUser
        );

        return response()->json([
            'success' => true,
            'message' => 'START Batch cancelled.',
            'batch' => $start->fresh(['assignments', 'engagementGroups.assignments']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Recompute Batch Status
    |--------------------------------------------------------------------------
    | Strictly enforces:
    | - If any required assignee has "Cannot Accept" / declined -> Needs Reassignment
    | - If some acknowledged but some pending -> Partially Acknowledged
    | - If ALL required assignments acknowledged -> Team Confirmed
    | - Does NOT auto-confirm on single acknowledgement if more are required!
    */

    protected function recomputeBatchStatus(StartRecord $start): string
    {
        // Don't downgrade from Activated or Cancelled unless explicit
        if (in_array($start->status, ['Activated', 'Cancelled', 'Returned'])) {
            return $start->status;
        }

        $allAssignments = $start->assignments->isEmpty()
            ? $start->engagementGroups->flatMap->assignments
            : $start->assignments;

        if ($allAssignments->isEmpty()) {
            $start->update(['status' => 'Structuring']);
            return 'Structuring';
        }

        $required = $allAssignments->where('is_required', true);
        $totalRequired = $required->count();

        if ($totalRequired === 0) {
            $totalRequired = $allAssignments->count();
            $required = $allAssignments;
        }

        $declined = $required->filter(fn($a) => in_array($a->status, ['Cannot Accept', 'Declined', 'Reassigned']));
        $acknowledged = $required->filter(fn($a) => $a->status === 'Acknowledged');
        $pending = $required->filter(fn($a) => $a->status === 'Pending' || empty($a->status));

        $newStatus = $start->status;

        if ($declined->count() > 0) {
            $newStatus = 'Needs Reassignment';
        } elseif ($acknowledged->count() === $totalRequired && $totalRequired > 0) {
            $newStatus = 'Team Confirmed';
        } elseif ($acknowledged->count() > 0 && $pending->count() > 0) {
            $newStatus = 'Partially Acknowledged';
        } elseif ($start->status === 'For Team Confirmation' || $start->status === 'Partially Acknowledged') {
            $newStatus = 'For Team Confirmation';
        }

        if ($newStatus !== $start->status) {
            $start->update(['status' => $newStatus]);
            $this->logHistory(
                $start,
                $newStatus,
                'Status automatically updated to ' . $newStatus . ' (' . $acknowledged->count() . ' of ' . $totalRequired . ' acknowledged).',
                'System'
            );
        }

        return $newStatus;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: History Logger
    |--------------------------------------------------------------------------
    */

    protected function logHistory(StartRecord $start, string $event, string $notes, string $actor = 'System')
    {
        $logs = $start->history_logs ?? [];
        array_unshift($logs, [
            'event' => $event,
            'title' => $event,
            'timestamp' => now()->format('M d, Y h:i A'),
            'actor' => $actor,
            'notes' => $notes,
        ]);
        $start->history_logs = $logs;
        $start->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Notification Dispatcher
    |--------------------------------------------------------------------------
    */

    protected function notifyUser(string $userName, string $title, string $message, string $actionUrl = '')
    {
        try {
            // Find user by name or email
            $user = User::where('name', $userName)->orWhere('email', $userName)->first();
            $userId = $user ? $user->id : 1;
            $userType = $user ? get_class($user) : 'App\\Models\\User';

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\StartBatchNotification',
                'notifiable_type' => $userType,
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'title' => $title,
                    'message' => $message,
                    'action_url' => $actionUrl,
                    'created_at' => now()->toIso8601String(),
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Log silent fallback if notifications table has specific constraints
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Group & Assignment Methods (Maintained for Backwards Compatibility)
    |--------------------------------------------------------------------------
    */

    public function storeGroup(Request $request, $dealId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::firstOrCreate(
            ['deal_id' => $deal->id],
            ['status' => 'Draft', 'memo_status' => 'Draft']
        );

        $validated = $request->validate([
            'engagement_type' => ['required', 'in:Regular Engagement,Project Engagement'],
            'group_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'scope_deliverables' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'service_frequency' => ['nullable', 'string', 'max:255'],
            'billing_frequency' => ['nullable', 'string', 'max:255'],
            'reporting_frequency' => ['nullable', 'string', 'max:255'],
            'project_manager' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['start_record_id'] = $start->id;
        $validated['status'] = 'Draft';

        StartEngagementGroup::create($validated);

        if ($start->status === 'Draft') {
            $start->update(['status' => 'Structuring']);
        }

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start')->with('success', 'Engagement group created successfully.');
    }

    public function updateGroup(Request $request, $dealId, $groupId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->firstOrFail();
        $group = StartEngagementGroup::where('id', $groupId)->where('start_record_id', $start->id)->firstOrFail();

        $validated = $request->validate([
            'engagement_type' => ['required', 'in:Regular Engagement,Project Engagement'],
            'group_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'scope_deliverables' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'service_frequency' => ['nullable', 'string', 'max:255'],
            'billing_frequency' => ['nullable', 'string', 'max:255'],
            'reporting_frequency' => ['nullable', 'string', 'max:255'],
            'project_manager' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $group->update($validated);

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start')->with('success', 'Engagement group updated successfully.');
    }

    public function storeAssignment(Request $request, $dealId, $groupId)
    {
        $deal = Deal::findOrFail($dealId);
        $start = StartRecord::where('deal_id', $deal->id)->firstOrFail();
        $group = StartEngagementGroup::where('id', $groupId)->where('start_record_id', $start->id)->firstOrFail();

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
            'assigned_to' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:Assigned,Pending,Reassigned,Completed,Acknowledged,Cannot Accept'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['start_record_id'] = $start->id;
        $validated['start_engagement_group_id'] = $group->id;

        EngagementAssignment::create($validated);

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start')->with('success', 'Engagement assignment created successfully.');
    }

    public function destroyBatch(Request $request, $dealId, $batchId)
    {
        $deal = Deal::findOrFail($dealId);
        $batch = StartRecord::where('deal_id', $deal->id)->where('id', $batchId)->first();

        if ($batch) {
            $batch->assignments()->delete();
            $batch->engagementGroups()->delete();
            $batch->delete();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'START batch deleted successfully.'
            ]);
        }

        return redirect()->route('deals.show', ['id' => $deal->id])->withFragment('start')->with('success', 'START batch deleted successfully.');
    }
}