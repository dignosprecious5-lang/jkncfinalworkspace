<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Models\Banking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankingController extends Controller
{
    use HandlesCorporateRepositoryRecords;

    public const BANKS = ['BDO', 'BPI', 'Metrobank', 'Land Bank', 'PNB', 'RCBC', 'UnionBank', 'Security Bank', 'China Bank', 'EastWest Bank', 'HSBC', 'Citibank', 'Other'];

    public const DOCUMENT_TYPES = [
        'Bank Statement', 'Bank Certificate', 'Bank Certification', 'Certificate of Deposit', 'Passbook Copy',
        'Checkbook Records', 'Account Opening Documents', 'Signature Card', 'Board Resolution for Bank',
        "Secretary's Certificate for Bank", 'Bank Loan Documents', 'Credit Facility Documents', 'Promissory Note',
        'Loan Statement', 'Account Confirmation', 'Deposit Slip', 'Withdrawal Slip', 'Fund Transfer Record',
        'Bank Correspondence', 'KYC Documents', 'AML Compliance Documents', 'Other',
    ];

    protected function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    protected function canEditRecord(Banking $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    public function page()
    {
        return view('corporate.banking', [
            'companyDefaults' => $this->latestCorporateCompany(),
            'banks' => self::BANKS,
            'bankDocumentTypes' => self::DOCUMENT_TYPES,
            'statuses' => ['Draft', 'Pending', 'Submitted', 'Approved', 'Rejected', 'Completed'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Banking::query();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
            $query->where('workflow_status', ucfirst($request->workflow_status));
        }

        return response()->json(
            $query->orderByDesc('document_date')->orderByDesc('date_uploaded')->get()
                ->map(fn (Banking $row) => $this->transformRecord($row))
                ->values()
        );
    }

    public function show($id)
    {
        $record = Banking::findOrFail($id);

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
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'corporate/banking/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'corporate/banking/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry = Banking::create([
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
            'bank' => $this->resolveOtherChoice($validated['bank'], $validated['bank_other'] ?? null),
            'bank_doc' => $this->resolveOtherChoice($validated['bank_document_type'], $validated['bank_document_type_other'] ?? null),
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

        $this->notifyCorporateApproversOfSubmission($entry->fresh(), 'banking');

        return response()->json([
            'message' => 'Banking record saved successfully.',
            'data' => $this->transformRecord($entry),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = Banking::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($record->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'corporate/banking/drafts'));
        $approvedDocuments = $this->appendDocuments($record->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'corporate/banking/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $record->update([
            'bank' => $this->resolveOtherChoice($validated['bank'], $validated['bank_other'] ?? null),
            'bank_doc' => $this->resolveOtherChoice($validated['bank_document_type'], $validated['bank_document_type_other'] ?? null),
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
            'message' => 'Banking record updated successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    public function submit($id)
    {
        $record = Banking::findOrFail($id);

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

        $this->notifyCorporateApproversOfSubmission($record->fresh(), 'banking');

        return response()->json([
            'message' => 'Banking record submitted for approval successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    protected function validatedPayload(Request $request, bool $documentsOptional = true): array
    {
        return $request->validate(array_merge([
            'bank' => ['required', 'string', 'max:255'],
            'bank_other' => ['nullable', 'string', 'max:255'],
            'bank_document_type' => ['required', 'string', 'max:255'],
            'bank_document_type_other' => ['nullable', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
        ], $this->commonDocumentValidation()));
    }

    protected function transformRecord(Banking $record): array
    {
        $draftDocuments = $this->documentLinks($record->draft_documents);
        $approvedDocuments = $this->documentLinks($record->approved_documents);

        return [
            'id' => $record->id,
            'company' => $record->company_name ?: $record->client,
            'bank' => $record->bank,
            'bank_document_type' => $record->bank_doc,
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
            'can_edit' => $this->canEditRecord($record),
            'can_submit' => (int) $record->submitted_by === (int) Auth::id()
                && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }
}
