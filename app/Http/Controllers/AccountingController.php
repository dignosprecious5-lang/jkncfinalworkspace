<?php

namespace App\Http\Controllers;

use App\Models\Accounting;
use App\Models\User;
use App\Notifications\AccountingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AccountingController extends Controller
{
    private function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    private function canEditRecord(Accounting $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    private function canApproveRecord(Accounting $record): bool
    {
        return $this->canApproveCorporate()
            && in_array($record->workflow_status ?? 'Uploaded', ['Submitted', 'On Hold'], true);
    }

    private function canRevertRecord(Accounting $record): bool
    {
        return $this->canApproveCorporate()
            && in_array($record->workflow_status ?? 'Uploaded', ['Submitted', 'On Hold'], true);
    }

    private function canHoldRecord(Accounting $record): bool
    {
        return $this->canApproveCorporate()
            && in_array($record->workflow_status ?? 'Uploaded', ['Submitted'], true);
    }

    private function getAccountingApprovers()
    {
        return User::query()
            ->whereHas('permissions', function ($query) {
                $query->where('name', 'approve_corporate');
            })
            ->where('id', '!=', Auth::id())
            ->get();
    }

    private function getNotificationRecipients(Accounting $record, string $action)
    {
        $submitter = $record->submitted_by ? User::query()->find($record->submitted_by) : null;
        $approvers = $this->getAccountingApprovers();

        $recipients = match ($action) {
            'submitted' => $approvers->merge($submitter ? [$submitter] : []),
            'approved' => collect($submitter ? [$submitter] : []),
            'reverted' => collect($submitter ? [$submitter] : []),
            'held' => collect($submitter ? [$submitter] : []),
            'updated' => in_array($record->workflow_status ?? 'Uploaded', ['Submitted', 'On Hold'], true)
                ? $approvers->merge($submitter ? [$submitter] : [])
                : collect($submitter ? [$submitter] : []),
            default => collect($submitter ? [$submitter] : []),
        };

        return $recipients
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => Auth::check() && (int) $user->id === (int) Auth::id())
            ->values();
    }

    private function sendAccountingNotification(Accounting $record, string $action, ?string $reviewNote = null): void
    {
        $freshRecord = $record->fresh() ?: $record;
        $recipients = $this->getNotificationRecipients($freshRecord, $action);

        if ($recipients->isEmpty()) {
            return;
        }

        $recordLabel = trim(implode(' - ', array_filter([
            $freshRecord->statement_type,
            $freshRecord->client,
            optional($freshRecord->date)->format('Y-m-d'),
        ]))) ?: ('Accounting Record #' . $freshRecord->id);

        [$title, $body, $buttonLabel] = match ($action) {
            'submitted' => [
                'Accounting Record Submitted: ' . $recordLabel,
                'An accounting record has been submitted and is ready for review.',
                'Review Record',
            ],
            'approved' => [
                'Accounting Record Approved: ' . $recordLabel,
                'An accounting record has been approved.',
                'View Record',
            ],
            'reverted' => [
                'Accounting Record Returned for Revision: ' . $recordLabel,
                'An accounting record has been returned for revision.',
                'View Record',
            ],
            'held' => [
                'Accounting Record Placed on Hold: ' . $recordLabel,
                'An accounting record has been placed on hold.',
                'View Record',
            ],
            'updated' => [
                'Accounting Record Updated: ' . $recordLabel,
                'An accounting record has been updated.',
                'View Record',
            ],
            default => [
                'Accounting Record Notification: ' . $recordLabel,
                'An accounting record requires attention.',
                'View Record',
            ],
        };

        try {
            Notification::send($recipients, new AccountingNotification(
                recordId: $freshRecord->id,
                action: $action,
                title: $title,
                body: $body,
                buttonLabel: $buttonLabel,
                url: route('corporate.accounting.show', $freshRecord->id),
                reviewNote: $reviewNote
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function transformRecord(Accounting $record): array
    {
        return [
            'id' => $record->id,
            'statement_type' => $record->statement_type,
            'client' => $record->client,
            'tin' => $record->tin,
            'date' => optional($record->date)->format('Y-m-d'),
            'user' => $record->user,
            'submitted_by' => $record->submitted_by,
            'status' => $record->status ?? 'Active',
            'workflow_status' => $record->workflow_status ?? 'Uploaded',
            'approval_status' => $record->approval_status ?? 'Pending',
            'approved_by' => $record->approved_by,
            'approved_at' => optional($record->approved_at)->format('Y-m-d H:i:s'),
            'review_note' => $record->review_note,
            'document_name' => $record->document_name,
            'document_path' => $record->document_path,
            'can_edit' => $this->canEditRecord($record),
            'can_submit' => (
                (int) $record->submitted_by === (int) Auth::id()
                && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)
            ),
            'can_approve' => $this->canApproveRecord($record),
            'can_revert' => $this->canRevertRecord($record),
            'can_hold' => $this->canHoldRecord($record),
        ];
    }

    public function page()
    {
        return view('corporate.accounting');
    }

    public function index(Request $request)
    {
        $statementType = $request->get('statement_type');
        $workflowStatus = $request->get('workflow_status');

        $query = Accounting::query();

        if (!$this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        if ($statementType && $statementType !== 'All Statement Types') {
            $query->where('statement_type', $statementType);
        }

        if ($workflowStatus && $workflowStatus !== 'all') {
            $query->where('workflow_status', ucfirst($workflowStatus));
        }

        $data = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($row) => $this->transformRecord($row))
            ->values();

        return response()->json($data);
    }

    public function show($id)
    {
        $record = Accounting::findOrFail($id);

        if (!$this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($this->transformRecord($record));
    }

    public function store(Request $request)
    {
        $request->validate([
            'statement_type' => 'required|in:PNL,Balance Sheet,Cash Flow,Income Statement,AFS',
            'client' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'date' => 'required|date',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('document');
        $path = $file->store('accounting_documents', 'public');

        $isApprover = $this->canApproveCorporate();

        $entry = Accounting::create([
            'statement_type' => $request->statement_type,
            'client' => $request->client,
            'tin' => $request->tin,
            'date' => $request->date,
            'user' => Auth::check() ? Auth::user()->name : 'Unknown User',
            'submitted_by' => Auth::id(),
            'status' => 'Active',
            'workflow_status' => $isApprover ? 'Accepted' : 'Uploaded',
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
            'document_name' => $file->getClientOriginalName(),
            'document_path' => 'storage/' . $path,
        ]);

        return response()->json([
            'message' => $isApprover
                ? 'Accounting entry saved successfully.'
                : 'Accounting entry saved as uploaded record.',
            'data' => $this->transformRecord($entry),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = Accounting::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $request->validate([
            'statement_type' => 'required|in:PNL,Balance Sheet,Cash Flow,Income Statement,AFS',
            'client' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $payload = [
            'statement_type' => $request->statement_type,
            'client' => $request->client,
            'tin' => $request->tin,
            'date' => $request->date,
        ];

        if ($request->hasFile('document')) {
            $request->validate([
                'document' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            $file = $request->file('document');
            $path = $file->store('accounting_documents', 'public');

            $payload['document_name'] = $file->getClientOriginalName();
            $payload['document_path'] = 'storage/' . $path;
        }

        if (($record->workflow_status ?? 'Uploaded') === 'Reverted') {
            $payload['approval_status'] = 'Pending';
            $payload['review_note'] = null;
        }

        $record->update($payload);
        $record = $record->fresh();

        // Send notification for updated record
        if (in_array($record->workflow_status ?? 'Uploaded', ['Submitted', 'On Hold'], true)) {
            $this->sendAccountingNotification($record, 'updated');
        }

        return response()->json([
            'message' => 'Accounting entry updated successfully.',
            'data' => $this->transformRecord($record),
        ]);
    }

    public function submit($id)
    {
        $record = Accounting::findOrFail($id);

        if ((int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)) {
            return response()->json([
                'message' => 'Only uploaded or reverted records can be submitted.'
            ], 422);
        }

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        $record = $record->fresh();

        // Send notification to approvers
        $this->sendAccountingNotification($record, 'submitted');

        return response()->json([
            'message' => 'Accounting entry submitted for approval successfully.',
            'data' => $this->transformRecord($record),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $record = Accounting::findOrFail($id);

        if (!$this->canApproveRecord($record)) {
            abort(403, 'This record cannot be approved at this time.');
        }

        $record->update([
            'workflow_status' => 'Accepted',
            'approval_status' => 'Approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_note' => null,
        ]);

        $record = $record->fresh();

        // Send notification to submitter
        $this->sendAccountingNotification($record, 'approved');

        return response()->json([
            'message' => 'Accounting entry approved successfully.',
            'data' => $this->transformRecord($record),
        ]);
    }

    public function revert(Request $request, $id)
    {
        $record = Accounting::findOrFail($id);

        if (!$this->canRevertRecord($record)) {
            abort(403, 'This record cannot be reverted at this time.');
        }

        $request->validate([
            'review_note' => 'required|string|max:1000',
        ]);

        $record->update([
            'workflow_status' => 'Reverted',
            'approval_status' => 'Needs Revision',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_note' => $request->review_note,
        ]);

        $record = $record->fresh();

        // Send notification with review note to submitter
        $this->sendAccountingNotification($record, 'reverted', $request->review_note);

        return response()->json([
            'message' => 'Accounting entry reverted for revision.',
            'data' => $this->transformRecord($record),
        ]);
    }

    public function hold(Request $request, $id)
    {
        $record = Accounting::findOrFail($id);

        if (!$this->canHoldRecord($record)) {
            abort(403, 'This record cannot be placed on hold at this time.');
        }

        $request->validate([
            'review_note' => 'required|string|max:1000',
        ]);

        $record->update([
            'workflow_status' => 'On Hold',
            'approval_status' => 'On Hold',
            'review_note' => $request->review_note,
        ]);

        $record = $record->fresh();

        // Send notification with review note to submitter
        $this->sendAccountingNotification($record, 'held', $request->review_note);

        return response()->json([
            'message' => 'Accounting entry placed on hold.',
            'data' => $this->transformRecord($record),
        ]);
    }
}
