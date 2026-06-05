<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Correspondence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyCorrespondenceController extends CorrespondenceController
{
    use ResolvesCompanyRecords;

    public function index(?Request $request = null, ?int $company = null): View
    {
        $request ??= request();
        abort_unless($company, 404);
        $companyData = $this->findCompany($request, $company);
        $latestGisRecord = $this->latestApprovedGisRecord($company);
        $gisCompanyInfo = $this->latestGisCompanyInfo($latestGisRecord);
        $companyInfo = [
            'company_name' => $latestGisRecord ? $gisCompanyInfo['company_name'] : ($companyData['company_name'] ?? ''),
            'registration_number' => $latestGisRecord ? $gisCompanyInfo['registration_number'] : $this->companyRegistrationNumber($companyData),
            'principal_address' => $latestGisRecord ? $gisCompanyInfo['principal_address'] : ($companyData['address'] ?? ''),
        ];

        return view('corporate.correspondence', [
            'company' => (object) $companyData,
            'isCompanyScopedCorrespondence' => true,
            'latestGisRecord' => $latestGisRecord,
            'companyInfo' => $companyInfo,
            'correspondenceLogoUrl' => $this->gisLogoUrl($latestGisRecord),
            'managementApprovers' => $this->activeEmployeeApprovers(),
            'executiveApprovers' => $this->executiveApproversFromGis($company),
            'correspondenceDataUrl' => route('company.correspondence.data', $company),
            'correspondenceStoreUrl' => route('company.correspondence.store', $company),
            'correspondenceSubmitUrlTemplate' => url('/correspondence/__ID__/submit'),
            'correspondenceTemplateUrlTemplate' => route('correspondence.template', ['type' => '__TYPE__', 'id' => '__ID__']),
            'correspondenceDownloadUrlTemplate' => route('correspondence.download', '__ID__'),
            'correspondenceTitle' => 'Correspondence',
            'correspondenceSubtitle' => 'View approved official correspondence for ' . ($companyData['company_name'] ?? 'this company') . '.',
            'correspondenceEyebrow' => 'Company Corporate Records',
            'emptyCorrespondenceText' => 'Only approved correspondence for this company will appear here.',
        ]);
    }

    public function data(Request $request, ?int $company = null)
    {
        abort_unless($company, 404);
        $companyData = $this->findCompany($request, $company);
        $query = $this->approvedCorrespondenceQuery()
            ->where(function ($q) use ($company, $companyData) {
                if (Schema::hasColumn('correspondences', 'company_id')) {
                    $q->where('company_id', $company);
                }

                $companyName = trim((string) ($companyData['company_name'] ?? ''));
                if ($companyName !== '') {
                    $q->orWhere('company_name', $companyName);
                }
            });

        if ($request->filled('type') && $request->type !== 'All') {
            $query->where('type', $request->type);
        }

        return $query->latest()
            ->get()
            ->map(fn (Correspondence $item) => $this->correspondenceTableRow($item))
            ->values();
    }

    public function store(Request $request, ?int $company = null)
    {
        abort_unless($company, 404);
        $companyData = $this->findCompany($request, $company);
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'correspondence_date' => ['nullable', 'date'],
            'tin' => ['nullable', 'string', 'max:100'],
            'to_for_label' => ['nullable', 'string', 'max:10', 'in:To,For'],
            'to_for' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'department_stakeholder' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'cc' => ['nullable', 'string', 'max:255'],
            'additional' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'sent_via' => ['nullable', 'string', 'max:100'],
            'management_approver_id' => ['required', 'integer'],
            'executive_approver_id' => ['required', 'integer'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx', 'max:5120'],
        ]);

        $latestGisRecord = $this->latestApprovedGisRecord($company);
        $gisCompanyInfo = $this->latestGisCompanyInfo($latestGisRecord);
        $companyInfo = [
            'company_name' => $latestGisRecord ? $gisCompanyInfo['company_name'] : ($companyData['company_name'] ?? ''),
            'registration_number' => $latestGisRecord ? $gisCompanyInfo['registration_number'] : $this->companyRegistrationNumber($companyData),
            'principal_address' => $latestGisRecord ? $gisCompanyInfo['principal_address'] : ($companyData['address'] ?? ''),
        ];

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('correspondence_attachments', 'public');
        }

        $record = Correspondence::create(array_merge($validated, [
            'ref_no' => null,
            'company_id' => Schema::hasColumn('correspondences', 'company_id') ? $company : null,
            'correspondence_date' => $validated['correspondence_date'] ?? now()->format('Y-m-d'),
            'company_name' => $companyInfo['company_name'],
            'registration_number' => $companyInfo['registration_number'],
            'principal_address' => $companyInfo['principal_address'],
            'from_name' => $validated['from_name'] ?: (Auth::user()->name ?? 'System Admin'),
            'body' => $validated['body'] ?? null,
            'sent_via' => $validated['sent_via'] ?? 'Email',
            'status' => 'Open',
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'is_archived' => false,
            'archived_at' => null,
            'submitted_at' => now(),
            'management_approval_status' => 'Pending',
            'executive_approval_status' => 'Waiting for Level 1',
            'created_by' => Auth::id(),
        ], $this->buildApprovalData(
            $request->input('management_approver_id'),
            $request->input('executive_approver_id'),
            $company
        )));

        $record->update([
            'ref_no' => 'COR-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT),
        ]);

        $record = $this->syncApproverEmailsFromDatabase($record);
        $this->sendCorrespondenceLevelApprovalEmail($record, 1);

        return response()->json([
            'message' => 'Company correspondence submitted successfully.',
            'record' => $record->fresh(),
        ]);
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, $this->defaultCompanies());
    }

    private function defaultCompanies(): array
    {
        return [
            ['id' => 1, 'company_name' => 'Company 1', 'company_type' => 'Corporation', 'email' => 'company1@example.com', 'phone' => '09012345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Makati City', 'owner_name' => 'Owner 1', 'created_at' => '2026-03-01 10:00:00'],
            ['id' => 2, 'company_name' => 'Company 2', 'company_type' => 'Corporation', 'email' => 'company2@example.com', 'phone' => '09000345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Taguig City', 'owner_name' => 'Owner 2', 'created_at' => '2026-03-02 10:00:00'],
            ['id' => 3, 'company_name' => 'Company 3', 'company_type' => 'Corporation', 'email' => 'company3@example.com', 'phone' => '09777345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Pasig City', 'owner_name' => 'Owner 3', 'created_at' => '2026-03-03 10:00:00'],
        ];
    }

    private function companyRegistrationNumber(array $companyData): string
    {
        return trim((string) ($companyData['registration_number'] ?? $companyData['company_reg_no'] ?? $companyData['sec_registration_number'] ?? ''));
    }
}
