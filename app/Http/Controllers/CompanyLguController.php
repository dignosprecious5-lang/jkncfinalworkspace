<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesCorporateRepositoryRecords;
use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Permit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanyLguController extends Controller
{
    use HandlesCorporateRepositoryRecords;
    use ResolvesCompanyRecords;

    private function canApproveCorporate(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('approve_corporate');
    }

    private function canEditRecord(Permit $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true);
    }

    public function index(Request $request, int $company)
    {
        $companyData = $this->findCompany($request, $company);

        if ($request->expectsJson()) {
            $query = Permit::query()->where('company_id', $company);

            if (! $this->canApproveCorporate()) {
                $query->where('submitted_by', Auth::id());
            }

            if ($request->filled('workflow_status') && $request->workflow_status !== 'all') {
                $query->where('workflow_status', ucfirst($request->workflow_status));
            }

            if ($request->filled('permit_type') && $request->permit_type !== 'All Permit Types') {
                $query->where('permit_type', $request->permit_type);
            }

            return response()->json(
                $query->orderByDesc('renewal_date')->orderByDesc('created_at')->get()
                    ->map(fn (Permit $permit) => $this->transformRecord($permit))
                    ->values()
            );
        }

        return $this->page($request, $company, $companyData);
    }

    private function page(Request $request, int $company, array $companyData): View
    {
        return view('corporate.lgu', [
            'company' => (object) $companyData,
            'companyDefaults' => $companyData,
            'permitTypes' => PermitController::PERMIT_TYPES,
            'locationData' => PermitController::LOCATION_DATA,
            'statuses' => ['Active', 'For Renewal', 'Expiring Soon', 'Expired'],
            'repositoryRoutes' => [
                'dataUrl' => route('company.lgu', $company),
                'storeUrl' => route('company.lgu.store', $company),
                'updateUrl' => route('company.lgu.update', ['company' => $company, 'record' => '__ID__']),
                'submitUrl' => route('company.lgu.submit', ['company' => $company, 'record' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request, int $company)
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->storeDocumentSet($request, 'draft_documents', 'company/lgu/drafts');
        $approvedDocuments = $this->storeDocumentSet($request, 'approved_documents', 'company/lgu/approved');
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $permit = Permit::create([
            'company_id' => $company,
            'company_name' => $companyData['company_name'],
            'province' => $validated['province'],
            'city_municipality' => $validated['city_municipality'],
            'barangay' => $validated['barangay'],
            'permit_type' => $this->resolveOtherChoice($validated['permit_type'], $validated['permit_type_other'] ?? null),
            'document_type' => 'LGU Compliance Document',
            'permit_number' => $validated['permit_number'],
            'date_of_registration' => $validated['date_registered'] ?? null,
            'renewal_date' => $validated['renewal_date'] ?? null,
            'expiration_date_of_registration' => $validated['renewal_date'] ?? null,
            'total_permit_fee' => $validated['total_permit_fee'] ?? null,
            'user' => $user,
            'tin' => $companyData['tin_no'] ?? null,
            'document_name' => $primaryDocument['name'] ?? null,
            'document_path' => $primaryDocument['path'] ?? null,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'uploaded_by' => $user,
            'date_uploaded_at' => now(),
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'approval_status' => 'Pending',
            'workflow_status' => 'Submitted',
            'submitted_by' => Auth::id(),
            'review_note' => null,
        ]);

        $this->notifyCorporateApproversOfSubmission($permit->fresh(), 'lgu');

        return response()->json([
            'message' => 'LGU compliance record saved successfully.',
            'data' => $this->transformRecord($permit->fresh()),
        ], 201);
    }

    public function update(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $permit = Permit::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($permit)) {
            abort(403, 'This record can no longer be edited.');
        }

        $validated = $this->validatedPayload($request);
        $user = $this->currentUserLabel($request);
        $draftDocuments = $this->appendDocuments($permit->draft_documents, $this->storeDocumentSet($request, 'draft_documents', 'company/lgu/drafts'));
        $approvedDocuments = $this->appendDocuments($permit->approved_documents, $this->storeDocumentSet($request, 'approved_documents', 'company/lgu/approved'));
        $primaryDocument = $draftDocuments[0] ?? $approvedDocuments[0] ?? null;

        $permit->update([
            'province' => $validated['province'],
            'city_municipality' => $validated['city_municipality'],
            'barangay' => $validated['barangay'],
            'permit_type' => $this->resolveOtherChoice($validated['permit_type'], $validated['permit_type_other'] ?? null),
            'permit_number' => $validated['permit_number'],
            'date_of_registration' => $validated['date_registered'] ?? null,
            'renewal_date' => $validated['renewal_date'] ?? null,
            'expiration_date_of_registration' => $validated['renewal_date'] ?? null,
            'total_permit_fee' => $validated['total_permit_fee'] ?? null,
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'last_updated_by' => $user,
            'last_updated_at' => now(),
            'document_name' => $primaryDocument['name'] ?? $permit->document_name,
            'document_path' => $primaryDocument['path'] ?? $permit->document_path,
            'approval_status' => ($permit->workflow_status ?? 'Uploaded') === 'Reverted' ? 'Pending' : $permit->approval_status,
            'review_note' => ($permit->workflow_status ?? 'Uploaded') === 'Reverted' ? null : $permit->review_note,
        ]);

        return response()->json([
            'message' => 'LGU compliance record updated successfully.',
            'data' => $this->transformRecord($permit->fresh()),
        ]);
    }

    public function destroy(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $permit = Permit::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($permit)) {
            abort(403, 'This record can no longer be deleted.');
        }

        $permit->delete();

        return response()->json(['message' => 'LGU compliance record deleted successfully.']);
    }

    public function submit(Request $request, int $company, int $record)
    {
        $this->findCompany($request, $company);
        $permit = Permit::query()->where('company_id', $company)->findOrFail($record);

        if (! $this->canEditRecord($permit)) {
            abort(403, 'This record cannot be submitted.');
        }

        $permit->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        $this->notifyCorporateApproversOfSubmission($permit->fresh(), 'lgu');

        return response()->json(['message' => 'LGU submitted for approval.']);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate(array_merge([
            'province' => ['required', 'string', 'max:255'],
            'city_municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'permit_type' => ['required', 'string', 'max:255'],
            'permit_type_other' => ['nullable', 'string', 'max:255'],
            'permit_number' => ['required', 'string', 'max:255'],
            'date_registered' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'total_permit_fee' => ['nullable', 'numeric', 'min:0'],
        ], $this->commonDocumentValidation()));
    }

    private function transformRecord(Permit $permit): array
    {
        $draftDocuments = $this->documentLinks($permit->draft_documents);
        $approvedDocuments = $this->documentLinks($permit->approved_documents);

        return [
            'id' => $permit->id,
            'company' => $permit->company_name ?: 'Selected Company',
            'province' => $permit->province,
            'city_municipality' => $permit->city_municipality,
            'barangay' => $permit->barangay,
            'permit_type' => $permit->permit_type,
            'permit_number' => $permit->permit_number,
            'date_registered' => $permit->date_of_registration?->format('Y-m-d'),
            'renewal_date' => $permit->renewal_date?->format('Y-m-d') ?: $permit->expiration_date_of_registration?->format('Y-m-d'),
            'total_permit_fee' => $permit->total_permit_fee,
            'status' => $permit->status,
            'uploaded_by' => $permit->uploaded_by ?: $permit->user,
            'date_uploaded' => $permit->date_uploaded_at?->format('Y-m-d H:i:s') ?: $permit->created_at?->format('Y-m-d H:i:s'),
            'last_updated_by' => $permit->last_updated_by,
            'last_updated_date' => $permit->last_updated_at?->format('Y-m-d H:i:s') ?: $permit->updated_at?->format('Y-m-d H:i:s'),
            'workflow_status' => $permit->workflow_status ?? 'Uploaded',
            'approval_status' => $permit->approval_status ?? 'Pending',
            'review_note' => $permit->review_note,
            'document_name' => $permit->document_name,
            'document_url' => $draftDocuments[0]['url'] ?? $approvedDocuments[0]['url'] ?? $this->publicDocumentUrl($permit->document_path),
            'draft_documents' => $draftDocuments,
            'approved_documents' => $approvedDocuments,
            'can_edit' => $this->canEditRecord($permit),
            'can_submit' => (int) $permit->submitted_by === (int) Auth::id()
                && in_array($permit->workflow_status ?? 'Uploaded', ['Uploaded', 'Reverted'], true),
        ];
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, []);
    }
}
