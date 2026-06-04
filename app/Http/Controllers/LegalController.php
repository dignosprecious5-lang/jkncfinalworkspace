<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\Legal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalController extends Controller
{
    use HandlesCorporateRepositoryRecords;
    use SyncsDeadlineTownHallMemo;

    public const DOCUMENT_TYPES = [
        'Contracts and Agreements',
        'Contract',
        'Service Agreement',
        'Consulting Agreement',
        'Management Agreement',
        'Employment Contract',
        'Independent Contractor Agreement',
        'Non-Disclosure Agreement (NDA)',
        'Non-Compete Agreement',
        'Memorandum of Agreement (MOA)',
        'Memorandum of Understanding (MOU)',
        'Joint Venture Agreement',
        'Partnership Agreement',
        'Lease Agreement',
        'Sublease Agreement',
        'Loan Agreement',
        'Shareholders Agreement',
        'Subscription Agreement',
        'Assignment Agreement',
        'Deed of Assignment',
        'Asset Purchase Agreement',
        'Share Purchase Agreement',
        'Sale and Purchase Agreement',
        'Escrow Agreement',
        'Settlement Agreement',
        'Licensing Agreement',
        'Franchise Agreement',
        'Distribution Agreement',
        'Agency Agreement',
        'Other Agreement',
        'Corporate Documents',
        'Board Resolution',
        'Stockholders Resolution',
        "Secretary's Certificate",
        'Special Power of Attorney',
        'General Power of Attorney',
        'Corporate Certification',
        'Corporate Opinion',
        'Legal Notices',
        'Demand Letter',
        'Notice of Default',
        'Notice of Termination',
        'Notice of Breach',
        'Legal Notice',
        'Court and Legal Proceedings',
        'Complaint',
        'Answer',
        'Petition',
        'Motion',
        'Affidavit',
        'Judicial Affidavit',
        'Position Paper',
        'Memorandum',
        'Court Order',
        'Decision',
        'Judgment',
        'Settlement Documents',
        'Property Documents',
        'Deed of Sale',
        'Deed of Absolute Sale',
        'Deed of Donation',
        'Deed of Mortgage',
        'Real Estate Mortgage',
        'Chattel Mortgage',
        'Transfer Documents',
        'Intellectual Property',
        'Trademark Documents',
        'Copyright Documents',
        'Patent Documents',
        'IP Assignment',
        'Compliance Documents',
        'Legal Opinion',
        'Due Diligence Report',
        'Compliance Report',
        'Investigation Report',
        'Other',
    ];

    private function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    private function canEditRecord(Legal $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    public function page()
    {
        return view('corporate.legal', [
            'companyDefaults' => $this->latestCorporateCompany(),
            'legalDocumentTypes' => self::DOCUMENT_TYPES,
            'statuses' => ['Active', 'For Renewal', 'Expiring Soon', 'Expired', 'Pending', 'Executed', 'Cancelled', 'Terminated'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Legal::query();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        if ($request->filled('document_type') && $request->document_type !== 'All Document Types') {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
            $query->where('workflow_status', ucfirst($request->workflow_status));
        }

        return response()->json(
            $query->orderByDesc('date')->orderByDesc('id')->get()
                ->map(fn (Legal $item) => $this->transformRecord($item))
                ->values()
        );
    }

    public function show($id)
    {
        $record = Legal::findOrFail($id);

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
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'corporate/legal/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'corporate/legal/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;
        $documentType = $this->resolveOtherChoice($validated['document_type'], $validated['document_type_other'] ?? null);
        $status = $this->legalStatus($validated['expiration_date'] ?? null, $validated['status'] ?? 'Pending');

        $legal = Legal::create([
            'company_id' => $company['company_id'],
            'company_name' => $company['company_name'],
            'legal_type' => $documentType,
            'client' => $company['company_name'],
            'tin' => null,
            'date' => $validated['document_date'],
            'document_type' => $documentType,
            'document_title' => $validated['document_title'],
            'effective_date' => $validated['effective_date'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'record_status' => $status,
            'document_name' => $primaryDocument['name'] ?? null,
            'document_path' => $primaryDocument['path'] ?? null,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'uploaded_by' => $user,
            'date_uploaded_at' => now(),
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'user' => $user,
            'submitted_by' => Auth::id(),
            'workflow_status' => $isApprover ? 'Accepted' : 'Submitted',
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
        ]);

        $this->syncLegalDeadline($legal);

        return response()->json([
            'success' => true,
            'message' => 'Legal document saved successfully.',
            'data' => $this->transformRecord($legal->fresh()),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = Legal::findOrFail($id);

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($record->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'corporate/legal/drafts'));
        $approvedDocuments = $this->appendDocuments($record->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'corporate/legal/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;
        $documentType = $this->resolveOtherChoice($validated['document_type'], $validated['document_type_other'] ?? null);

        $record->update([
            'legal_type' => $documentType,
            'date' => $validated['document_date'],
            'document_type' => $documentType,
            'document_title' => $validated['document_title'],
            'effective_date' => $validated['effective_date'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'record_status' => $this->legalStatus($validated['expiration_date'] ?? null, $validated['status'] ?? $record->record_status),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'document_name' => $primaryDocument['name'] ?? $record->document_name,
            'document_path' => $primaryDocument['path'] ?? $record->document_path,
            'approval_status' => ($record->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $record->approval_status,
            'review_note' => ($record->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $record->review_note,
        ]);

        $this->syncLegalDeadline($record->fresh());

        return response()->json([
            'message' => 'Legal document updated successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    public function submit($id)
    {
        $record = Legal::findOrFail($id);

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
            'message' => 'Legal document submitted for approval successfully.',
            'data' => $this->transformRecord($record->fresh()),
        ]);
    }

    private function validatedPayload(Request $request, bool $documentsOptional = true): array
    {
        return $request->validate(array_merge([
            'document_type' => ['required', 'string', 'max:255'],
            'document_type_other' => ['nullable', 'string', 'max:255'],
            'document_title' => ['required', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'effective_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
        ], $this->commonDocumentValidation()));
    }

    private function transformRecord(Legal $item): array
    {
        $draftDocuments = $this->documentLinks($item->draft_documents);
        $approvedDocuments = $this->documentLinks($item->approved_documents);

        return [
            'id' => $item->id,
            'company' => $item->company_name ?: $item->client,
            'document_type' => $item->document_type ?: $item->legal_type,
            'document_title' => $item->document_title ?: $item->document_name,
            'document_date' => $item->date?->format('Y-m-d'),
            'effective_date' => $item->effective_date?->format('Y-m-d'),
            'expiration_date' => $item->expiration_date?->format('Y-m-d'),
            'uploaded_by' => $item->uploaded_by ?: $item->user,
            'date_uploaded' => $item->date_uploaded_at?->format('Y-m-d H:i:s') ?: $item->created_at?->format('Y-m-d H:i:s'),
            'last_updated_by' => $item->last_updated_by,
            'last_updated_date' => $item->last_updated_at?->format('Y-m-d H:i:s') ?: $item->updated_at?->format('Y-m-d H:i:s'),
            'status' => $this->legalStatus($item->expiration_date?->toDateString(), $item->record_status ?: $item->status),
            'workflow_status' => $item->workflow_status ?? 'Uploaded',
            'approval_status' => $item->approval_status ?? 'Pending',
            'review_note' => $item->review_note,
            'document_name' => $item->document_name,
            'document_url' => $draftDocuments[0]['url'] ?? $approvedDocuments[0]['url'] ?? $this->publicDocumentUrl($item->document_path),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'can_edit' => $this->canEditRecord($item),
            'can_submit' => (int) $item->submitted_by === (int) Auth::id()
                && in_array($item->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }

    private function legalStatus(?string $expirationDate, string $fallback): string
    {
        if (in_array($fallback, ['Pending', 'Executed', 'Cancelled', 'Terminated'], true)) {
            return $fallback;
        }

        return $this->repositoryStatusFromDeadline($expirationDate, $fallback ?: 'Active');
    }

    private function syncLegalDeadline(Legal $legal): void
    {
        $this->syncDeadlineTownHallMemo(
            $legal,
            $legal->expiration_date?->toDateString(),
            'Legal Compliance',
            trim(($legal->document_type ?: 'Legal Document') . ' - ' . ($legal->document_title ?: $legal->company_name)),
            'legal.show'
        );
    }
}
