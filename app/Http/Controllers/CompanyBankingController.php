<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Banking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyBankingController extends BankingController
{
    use ResolvesCompanyRecords;

    public function index(Request $request, $company = null)
    {
        $company = (int) $company;
        $companyData = $this->findCompany($request, $company);

        if ($request->expectsJson()) {
            $query = Banking::query()->where('company_id', $company);

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

        return $this->repositoryPage($company, $companyData);
    }

    private function repositoryPage(int $company, array $companyData): View
    {
        return view('corporate.banking', [
            'company' => (object) $companyData,
            'companyDefaults' => $companyData,
            'banks' => self::BANKS,
            'bankDocumentTypes' => self::DOCUMENT_TYPES,
            'statuses' => ['Draft', 'Pending', 'Submitted', 'Approved', 'Rejected', 'Completed'],
            'repositoryRoutes' => [
                'dataUrl' => route('company.banking', $company),
                'storeUrl' => route('company.banking.store', $company),
                'updateUrl' => route('company.banking.update', ['company' => $company, 'record' => '__ID__']),
                'submitUrl' => route('company.banking.submit', ['company' => $company, 'record' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request, $company = null)
    {
        $company = (int) $company;
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'company/banking/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'company/banking/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry = Banking::create([
            'company_id' => $company,
            'company_name' => $companyData['company_name'],
            'date_uploaded' => now()->toDateString(),
            'date_uploaded_at' => now(),
            'user' => $user,
            'uploaded_by' => $user,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'submitted_by' => Auth::id(),
            'client' => $companyData['company_name'],
            'tin' => $companyData['tin_no'] ?? $companyData['tin'] ?? null,
            'bank' => $this->resolveOtherChoice($validated['bank'], $validated['bank_other'] ?? null),
            'bank_doc' => $this->resolveOtherChoice($validated['bank_document_type'], $validated['bank_document_type_other'] ?? null),
            'document_date' => $validated['document_date'],
            'status' => $validated['status'] ?? 'Pending',
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
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

    public function update(Request $request, $company, $record = null)
    {
        $company = (int) $company;
        $record = (int) $record;
        $this->findCompany($request, $company);
        $entry = Banking::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($entry)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($entry->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'company/banking/drafts'));
        $approvedDocuments = $this->appendDocuments($entry->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'company/banking/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry->update([
            'bank' => $this->resolveOtherChoice($validated['bank'], $validated['bank_other'] ?? null),
            'bank_doc' => $this->resolveOtherChoice($validated['bank_document_type'], $validated['bank_document_type_other'] ?? null),
            'document_date' => $validated['document_date'],
            'status' => $validated['status'] ?? $entry->status,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'document_name' => $primaryDocument['name'] ?? $entry->document_name,
            'document_path' => $primaryDocument['path'] ?? $entry->document_path,
            'approval_status' => ($entry->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $entry->approval_status,
            'review_note' => ($entry->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $entry->review_note,
        ]);

        return response()->json([
            'message' => 'Banking record updated successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ]);
    }

    public function submitCompany(int $company, int $record)
    {
        $request = request();
        $this->findCompany($request, $company);
        $entry = Banking::query()->where('company_id', $company)->findOrFail($record);

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

        $this->notifyCorporateApproversOfSubmission($entry->fresh(), 'banking');

        return response()->json([
            'message' => 'Banking record submitted for approval successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ]);
    }

    public function destroy(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $entry = Banking::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($entry)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $entry->delete();

        return response()->json(['message' => 'Banking record deleted successfully.']);
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, []);
    }
}
