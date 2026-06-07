<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Accounting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyAccountingController extends AccountingController
{
    use ResolvesCompanyRecords;

    public function index(Request $request, $company = null)
    {
        $company = (int) $company;
        $companyData = $this->findCompany($request, $company);

        if ($request->expectsJson()) {
            $query = Accounting::query()->where('company_id', $company);

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

        return $this->repositoryPage($company, $companyData);
    }

    private function repositoryPage(int $company, array $companyData): View
    {
        return view('corporate.accounting', [
            'company' => (object) $companyData,
            'companyDefaults' => $companyData,
            'reportTypes' => self::REPORT_TYPES,
            'statuses' => ['Draft', 'Pending', 'Submitted', 'Approved', 'Rejected', 'Completed'],
            'repositoryRoutes' => [
                'dataUrl' => route('company.accounting', $company),
                'storeUrl' => route('company.accounting.store', $company),
                'updateUrl' => route('company.accounting.update', ['company' => $company, 'record' => '__ID__']),
                'submitUrl' => route('company.accounting.submit', ['company' => $company, 'record' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request, $company = null)
    {
        $company = (int) $company;
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'company/accounting/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'company/accounting/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry = Accounting::create([
            'company_id' => $company,
            'company_name' => $companyData['company_name'],
            'statement_type' => $this->resolveOtherChoice($validated['report_type'], $validated['report_type_other'] ?? null),
            'client' => $companyData['company_name'],
            'tin' => $companyData['tin_no'] ?? $companyData['tin'] ?? null,
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
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
            'document_name' => $primaryDocument['name'] ?? null,
            'document_path' => $primaryDocument['path'] ?? null,
        ]);

        $this->notifyCorporateApproversOfSubmission($entry->fresh(), 'accounting');

        return response()->json([
            'message' => 'Accounting report saved successfully.',
            'data' => $this->transformRecord($entry),
        ], 201);
    }

    public function update(Request $request, $company, $record = null)
    {
        $company = (int) $company;
        $record = (int) $record;
        $this->findCompany($request, $company);
        $entry = Accounting::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($entry)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request, false);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($entry->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'company/accounting/drafts'));
        $approvedDocuments = $this->appendDocuments($entry->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'company/accounting/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $entry->update([
            'statement_type' => $this->resolveOtherChoice($validated['report_type'], $validated['report_type_other'] ?? null),
            'date' => $validated['report_date'],
            'reporting_period_from' => $validated['reporting_period_from'] ?? null,
            'reporting_period_to' => $validated['reporting_period_to'] ?? null,
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
            'message' => 'Accounting report updated successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ]);
    }

    public function submitCompany(int $company, int $record)
    {
        $request = request();
        $this->findCompany($request, $company);
        $entry = Accounting::query()->where('company_id', $company)->findOrFail($record);

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

        $this->notifyCorporateApproversOfSubmission($entry->fresh(), 'accounting');

        return response()->json([
            'message' => 'Accounting report submitted for approval successfully.',
            'data' => $this->transformRecord($entry->fresh()),
        ]);
    }

    public function destroy(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $entry = Accounting::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($entry)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $entry->delete();

        return response()->json(['message' => 'Accounting report deleted successfully.']);
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, []);
    }
}
