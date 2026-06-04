<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Models\Accounting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    use HandlesCorporateRepositoryRecords;

    public const REPORT_TYPES = [
        'Audited Financial Statements',
        'Unaudited Financial Statements',
        'Statement of Financial Position / Balance Sheet',
        'Statement of Comprehensive Income / Income Statement',
        'Statement of Changes in Equity',
        'Statement of Cash Flows',
        'Notes to Financial Statements',
        'Trial Balance',
        'General Ledger',
        'Subsidiary Ledger',
        'Accounts Receivable Report',
        'Accounts Payable Report',
        'Aging Report',
        'Bank Reconciliation Report',
        'Cash Position Report',
        'Expense Report',
        'Revenue Report',
        'Collection Report',
        'Disbursement Report',
        'Budget Report',
        'Financial Analysis Report',
        'Management Report',
        'Other',
    ];

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

    public function page()
    {
        return view('corporate.accounting', [
            'companyDefaults' => $this->latestCorporateCompany(),
            'reportTypes' => self::REPORT_TYPES,
            'statuses' => ['Draft', 'Pending', 'Submitted', 'Approved', 'Rejected', 'Completed'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Accounting::query();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        if ($request->filled('report_type') && $request->report_type !== 'All Report Types') {
            $query->where('statement_type', $request->report_type);
        }

        if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
            $query->where('workflow_status', ucfirst($request->workflow_status));
        }

        return response()->json(
            $query->orderByDesc('date')->orderByDesc('created_at')->get()
                ->map(fn (Accounting $row) => $this->transformRecord($row))
                ->values()
        );
    }

    public function show($id)
    {
        $record = Accounting::findOrFail($id);

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
        $isApprover = $this->canApproveCorporate();
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'corporate/accounting/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'corporate/accounting/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry = Accounting::create([
            'company_id' => $company['company_id'],
            'company_name' => $company['company_name'],
            'statement_type' => $this->resolveOtherChoice($validated['report_type'], $validated['report_type_other'] ?? null),
            'client' => $company['company_name'],
            'tin' => null,
            'date' => $validated['report_date'],
            'reporting_period_from' => $validated['reporting_period_from'] ?? null,
            'reporting_period_to' => $validated['reporting_period_to'] ?? null,
            'user' => $user,
            'submitted_by' => Auth::id(),
            'status' => $validated['status'] ?? 'Pending',
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'uploaded_by' => $user,
            'date_uploaded_at' => now(),
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'workflow_status' => $isApprover ? 'Accepted' : 'Submitted',
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
            'document_name' => $primaryDocument['name'] ?? null,
            'document_path' => $primaryDocument['path'] ?? null,
        ]);

        return response()->json([
            'message' => 'Accounting report saved successfully.',
            'data' => $this->transformRecord($entry),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = Accounting::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($record->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'corporate/accounting/drafts'));
        $approvedDocuments = $this->appendDocuments($record->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'corporate/accounting/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $record->update([
            'statement_type' => $this->resolveOtherChoice($validated['report_type'], $validated['report_type_other'] ?? null),
            'date' => $validated['report_date'],
            'reporting_period_from' => $validated['reporting_period_from'] ?? null,
            'reporting_period_to' => $validated['reporting_period_to'] ?? null,
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
            'message' => 'Accounting report updated successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    public function submit($id)
    {
        $record = Accounting::findOrFail($id);

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

        return response()->json([
            'message' => 'Accounting report submitted for approval successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    private function validatedPayload(Request $request, bool $documentsOptional = true): array
    {
        return $request->validate(array_merge([
            'report_type' => ['required', 'string', 'max:255'],
            'report_type_other' => ['nullable', 'string', 'max:255'],
            'report_date' => ['required', 'date'],
            'reporting_period_from' => ['nullable', 'date'],
            'reporting_period_to' => ['nullable', 'date', 'after_or_equal:reporting_period_from'],
            'status' => ['nullable', 'string', 'max:255'],
        ], $this->commonDocumentValidation()));
    }

    private function transformRecord(Accounting $record): array
    {
        $draftDocuments = $this->documentLinks($record->draft_documents);
        $approvedDocuments = $this->documentLinks($record->approved_documents);

        return [
            'id' => $record->id,
            'company' => $record->company_name ?: $record->client,
            'report_type' => $record->statement_type,
            'report_date' => optional($record->date)->format('Y-m-d'),
            'reporting_period_from' => optional($record->reporting_period_from)->format('Y-m-d'),
            'reporting_period_to' => optional($record->reporting_period_to)->format('Y-m-d'),
            'reporting_period' => trim(collect([
                optional($record->reporting_period_from)->format('Y-m-d'),
                optional($record->reporting_period_to)->format('Y-m-d'),
            ])->filter()->implode(' to ')),
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
            'can_edit' => $this->canEditRecord($record),
            'can_submit' => (int) $record->submitted_by === (int) Auth::id()
                && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }
}
