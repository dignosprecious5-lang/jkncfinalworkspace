<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Legal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyLegalController extends LegalController
{
    use ResolvesCompanyRecords;

    public function index(Request $request, $company = null)
    {
        $company = (int) $company;
        $companyData = $this->findCompany($request, $company);

        if ($request->expectsJson()) {
            $query = Legal::query()->where('company_id', $company);

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
                    ->map(fn (Legal $row) => $this->transformRecord($row))
                    ->values()
            );
        }

        return $this->repositoryPage($company, $companyData);
    }

    private function repositoryPage(int $company, array $companyData): View
    {
        return view('corporate.legal', [
            'company' => (object) $companyData,
            'companyDefaults' => $companyData,
            'legalDocumentTypes' => self::DOCUMENT_TYPES,
            'statuses' => ['Active', 'For Renewal', 'Expiring Soon', 'Expired', 'Pending', 'Executed', 'Cancelled', 'Terminated'],
            'repositoryRoutes' => [
                'dataUrl' => route('company.legal', $company),
                'storeUrl' => route('company.legal.store', $company),
                'updateUrl' => route('company.legal.update', ['company' => $company, 'record' => '__ID__']),
                'submitUrl' => route('company.legal.submit', ['company' => $company, 'record' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request, $company = null)
    {
        $company = (int) $company;
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'company/legal/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'company/legal/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;
        $documentType = $this->resolveOtherChoice($validated['document_type'], $validated['document_type_other'] ?? null);
        $status = $this->legalStatus($validated['expiration_date'] ?? null, $validated['status'] ?? 'Pending');

        $entry = Legal::create([
            'company_id' => $company,
            'company_name' => $companyData['company_name'],
            'legal_type' => $documentType,
            'client' => $companyData['company_name'],
            'tin' => $companyData['tin_no'] ?? $companyData['tin'] ?? null,
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
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        $this->syncLegalDeadline($entry);

        return response()->json([
            'success' => true,
            'message' => 'Legal document saved successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ], 201);
    }

    public function update(Request $request, $company, $record = null)
    {
        $company = (int) $company;
        $record = (int) $record;
        $this->findCompany($request, $company);
        $entry = Legal::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($entry)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($entry->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'company/legal/drafts'));
        $approvedDocuments = $this->appendDocuments($entry->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'company/legal/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;
        $documentType = $this->resolveOtherChoice($validated['document_type'], $validated['document_type_other'] ?? null);

        $entry->update([
            'legal_type' => $documentType,
            'date' => $validated['document_date'],
            'document_type' => $documentType,
            'document_title' => $validated['document_title'],
            'effective_date' => $validated['effective_date'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'record_status' => $this->legalStatus($validated['expiration_date'] ?? null, $validated['status'] ?? $entry->record_status),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'document_name' => $primaryDocument['name'] ?? $entry->document_name,
            'document_path' => $primaryDocument['path'] ?? $entry->document_path,
            'approval_status' => ($entry->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $entry->approval_status,
            'review_note' => ($entry->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $entry->review_note,
        ]);

        $this->syncLegalDeadline($entry->fresh());

        return response()->json([
            'message' => 'Legal document updated successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ]);
    }

    public function submitCompany(int $company, int $record)
    {
        $request = request();
        $this->findCompany($request, $company);
        $entry = Legal::query()->where('company_id', $company)->findOrFail($record);

        if ((int) $entry->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        if (! in_array($entry->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true)) {
            return response()->json(['message' => 'Only uploaded or reverted records can be submitted.'], 422);
        }

        $entry->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        return response()->json([
            'message' => 'Legal document submitted for approval successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ]);
    }

    public function destroy(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $entry = Legal::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($entry)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $entry->delete();

        return response()->json(['message' => 'Legal document deleted successfully.']);
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, []);
    }
}
