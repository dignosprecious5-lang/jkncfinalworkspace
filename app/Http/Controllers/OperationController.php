<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Models\Note;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationController extends Controller
{
    use HandlesCorporateRepositoryRecords;

    public const OPERATION_TYPES = [
        'Administration', 'Operations', 'Service Delivery', 'Project Management', 'Procurement',
        'Inventory Management', 'Asset Management', 'Facilities Management', 'Quality Assurance',
        'Risk Management', 'Compliance', 'Human Resources Operations', 'Finance Operations',
        'Client Management', 'Vendor Management', 'Information Technology', 'Other',
    ];

    public const DOCUMENT_TYPES = [
        'Policy', 'Procedure', 'Process Flow', 'Work Instruction', 'Standard Operating Procedure (SOP)',
        'Operations Manual', 'Employee Handbook', 'Service Manual', 'Project Plan', 'Project Report',
        'Accomplishment Report', 'Incident Report', 'Investigation Report', 'Corrective Action Report',
        'Preventive Action Report', 'Inspection Report', 'Monitoring Report', 'Inventory Report',
        'Asset Report', 'Procurement Documents', 'Purchase Request', 'Purchase Order', 'Delivery Receipt',
        'Acceptance Report', 'Service Report', 'Meeting Minutes', 'Operations Memorandum', 'Notice to Proceed',
        'Scope of Work', 'Transmittal', 'Checklist', 'Form Template', 'Other',
    ];

    protected function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    protected function canEditRecord(Operation $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    public function page()
    {
        return view('corporate.operations', [
            'companyDefaults' => $this->latestCorporateCompany(),
            'operationTypes' => self::OPERATION_TYPES,
            'operationDocumentTypes' => self::DOCUMENT_TYPES,
            'statuses' => ['Draft', 'Pending', 'Submitted', 'Approved', 'Rejected', 'Completed'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Operation::query();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
            $query->where('workflow_status', ucfirst($request->workflow_status));
        }

        return response()->json(
            $query->orderByDesc('document_date')->orderByDesc('date_uploaded')->get()
                ->map(fn (Operation $row) => $this->transformRecord($row))
                ->values()
        );
    }

    public function show($id)
    {
        $record = Operation::findOrFail($id);

        if (! $this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($this->transformRecord($record));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);
        $company = $this->latestCorporateCompany();
        $user = $this->currentUserLabel($request);
        $isApprover = false;
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'corporate/operations/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'corporate/operations/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry = Operation::create([
            'company_id' => $company['company_id'],
            'company_name' => $company['company_name'],
            'date_uploaded' => now()->toDateString(),
            'date_uploaded_at' => now(),
            'user' => $user,
            'uploaded_by' => $user,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'submitted_by' => Auth::id(),
            'client' => $company['company_name'],
            'tin' => null,
            'operation_type' => $this->resolveOtherChoice($validated['operation_type'], $validated['operation_type_other'] ?? null),
            'document_type' => $this->resolveOtherChoice($validated['document_type'], $validated['document_type_other'] ?? null),
            'document_title' => $validated['document_title'],
            'document_date' => $validated['document_date'],
            'status' => $validated['status'] ?? 'Pending',
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'workflow_status' => $isApprover ? 'Accepted' : 'Submitted',
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
            'document_name' => $primaryDocument['name'] ?? null,
            'document_path' => $primaryDocument['path'] ?? null,
        ]);

        $this->notifyCorporateApproversOfSubmission($entry->fresh(), 'operations');

        return response()->json([
            'message' => 'Operations record saved successfully.',
            'data' => $this->transformRecord($entry),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = Operation::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($record->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'corporate/operations/drafts'));
        $approvedDocuments = $this->appendDocuments($record->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'corporate/operations/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $record->update([
            'operation_type' => $this->resolveOtherChoice($validated['operation_type'], $validated['operation_type_other'] ?? null),
            'document_type' => $this->resolveOtherChoice($validated['document_type'], $validated['document_type_other'] ?? null),
            'document_title' => $validated['document_title'],
            'document_date' => $validated['document_date'],
            'status' => $validated['status'] ?? $record->status,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'document_name' => $primaryDocument['name'] ?? $record->document_name,
            'document_path' => $primaryDocument['path'] ?? $record->document_path,
            'approval_status' => ($record->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $record->approval_status,
            'review_note' => ($record->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $record->review_note,
        ]);

        return response()->json([
            'message' => 'Operations record updated successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    public function submit($id)
    {
        $record = Operation::findOrFail($id);

        if ((int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (! in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)) {
            return response()->json(['message' => 'Only uploaded or reverted records can be submitted.'], 422);
        }

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        $this->notifyCorporateApproversOfSubmission($record->fresh(), 'operations');

        return response()->json([
            'message' => 'Operations record submitted for approval successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    public function storeNote(Request $request, $id)
    {
        $record = Operation::findOrFail($id);

        if (! $this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $record->notes()->create([
            'content' => $validated['content'],
            'owner' => Auth::user()?->name ?? Auth::user()?->email ?? 'System User',
        ]);

        return response()->json([
            'message' => 'Note added successfully.',
            'data' => $this->transformRecord($record->fresh('notes')),
        ]);
    }

    public function destroyNote($id, Note $note)
    {
        $record = Operation::findOrFail($id);

        abort_unless(
            $note->noteable_type === Operation::class
            && (int) $note->noteable_id === (int) $record->id,
            404
        );

        $currentOwner = Auth::user()?->name ?? Auth::user()?->email ?? 'System User';

        if (! $this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if ($note->owner !== $currentOwner && ! Auth::user()?->isSuperAdmin()) {
            abort(403, 'You can only delete notes that you made.');
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully.',
            'data' => $this->transformRecord($record->fresh('notes')),
        ]);
    }

    protected function validatedPayload(Request $request, bool $documentsOptional = true): array
    {
        return $request->validate(array_merge([
            'operation_type' => ['required', 'string', 'max:255'],
            'operation_type_other' => ['nullable', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:255'],
            'document_type_other' => ['nullable', 'string', 'max:255'],
            'document_title' => ['required', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
        ], $this->commonDocumentValidation()));
    }

    protected function transformRecord(Operation $record): array
    {
        $record->loadMissing('notes');
        $draftDocuments = $this->documentLinks($record->draft_documents);
        $approvedDocuments = $this->documentLinks($record->approved_documents);
        $currentOwner = Auth::user()?->name ?? Auth::user()?->email ?? 'System User';

        return [
            'id' => $record->id,
            'company' => $record->company_name ?: $record->client,
            'operation_type' => $record->operation_type,
            'document_type' => $record->document_type,
            'document_title' => $record->document_title,
            'document_date' => optional($record->document_date ?: $record->date_uploaded)->format('Y-m-d'),
            'uploaded_by' => $record->uploaded_by ?: $record->user,
            'date_uploaded' => $record->date_uploaded_at?->format('Y-m-d H:i:s') ?: $record->created_at?->format('Y-m-d H:i:s'),
            'last_updated_by' => $record->last_updated_by,
            'last_updated_date' => $record->last_updated_at?->format('Y-m-d H:i:s') ?: $record->updated_at?->format('Y-m-d H:i:s'),
            'status' => $record->status ?? 'Pending',
            'workflow_status' => $record->workflow_status ?? 'Uploaded',
            'approval_status' => $record->approval_status ?? 'Pending',
            'review_note' => $record->review_note,
            'document_name' => $record->document_name,
            'document_url' => $draftDocuments[0]['url'] ?? $approvedDocuments[0]['url'] ?? $this->publicDocumentUrl($record->document_path),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'notes' => $record->notes
                ->sortByDesc('created_at')
                ->map(fn (Note $note) => [
                    'id' => $note->id,
                    'content' => $note->content,
                    'owner' => $note->owner,
                    'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
                    'can_delete' => $note->owner === $currentOwner || Auth::user()?->isSuperAdmin(),
                ])
                ->values()
                ->all(),
            'can_edit' => $this->canEditRecord($record),
            'can_submit' => (int) $record->submitted_by === (int) Auth::id()
                && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }
}
