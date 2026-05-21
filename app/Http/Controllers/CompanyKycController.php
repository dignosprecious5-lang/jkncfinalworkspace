<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Company;
use App\Models\CompanyBif;
use App\Models\User;
use App\Support\CompanyHistoryLogger;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyKycController extends Controller
{
    private const CLIENT_TYPES = [
        'new_client' => 'New Client',
        'existing_client' => 'Existing Client',
        'change_information' => 'Change Information',
    ];

    private const ORGANIZATION_TYPES = [
        'sole_proprietorship' => 'Sole Proprietorship',
        'corporation' => 'Corporation',
        'ngo' => 'NGO',
        'partnership' => 'Partnership',
        'cooperative' => 'Cooperative',
        'other' => 'Other (Please Specify)',
    ];

    private const NATIONALITY_TYPES = [
        'filipino' => 'Filipino',
        'foreign' => 'Foreign',
    ];

    private const OFFICE_TYPES = [
        'head_office' => 'Head Office',
        'regional_headquarter' => 'Regional Headquarter',
        'branch' => 'Branch',
        'other' => 'Other (Please Specify)',
    ];

    private const STATUS_LABELS = [
        'draft' => 'Draft',
        'pending_approval' => 'Waiting for Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    private const STATUS_PILL_CLASSES = [
        'approved' => 'bg-green-100 text-green-700 border border-green-200',
        'pending_approval' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'draft' => 'bg-gray-100 text-gray-700 border border-gray-200',
        'rejected' => 'bg-red-100 text-red-700 border border-red-200',
        'empty' => 'bg-gray-100 text-gray-600 border border-gray-200',
    ];

    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $latestBif = null;

        if (Schema::hasTable('company_bifs')) {
            $latestBif = CompanyBif::query()
                ->where('company_id', $company)
                ->latest('updated_at')
                ->latest('id')
                ->first();
        }

        $kycRequirements = $this->kycRequirements($company, $latestBif);
        $requirementsComplete = $this->areKycRequirementsComplete($kycRequirements);
        $missingRequirements = $this->missingRequirementLabels($kycRequirements);

        $statusKey = $latestBif?->status ?: 'empty';

        return view('company.kyc', [
            'company' => (object) array_merge($companyData, [
                'bif_no' => $latestBif?->bif_no,
            ]),
            'activeTab' => 'business-client-information',
            'bif' => $latestBif,
            'bifRecipientEmail' => $latestBif?->authorized_contact_person_email ?: ($companyData['email'] ?? ''),
            'clientTypeOptions' => self::CLIENT_TYPES,
            'organizationOptions' => self::ORGANIZATION_TYPES,
            'nationalityOptions' => self::NATIONALITY_TYPES,
            'officeTypeOptions' => self::OFFICE_TYPES,
            'statusLabel' => $latestBif ? (self::STATUS_LABELS[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey))) : 'Not Submitted',
            'statusPillClass' => self::STATUS_PILL_CLASSES[$statusKey] ?? self::STATUS_PILL_CLASSES['empty'],
            'companyKycStatus' => $latestBif ? (self::STATUS_LABELS[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey))) : 'Not Submitted',
            'companyKycStatusClass' => self::STATUS_PILL_CLASSES[$statusKey] ?? self::STATUS_PILL_CLASSES['empty'],
            'kycActivityLogs' => $this->kycActivityLogs($latestBif, $companyData),
            'kycRequirements' => $kycRequirements,
            'requirementsComplete' => $requirementsComplete,
            'missingRequirements' => $missingRequirements,
        ]);
    }

    public function viewRequirementDocument(Request $request, int $company, string $requirement)
    {
        $bif = $this->latestCompanyBif($company);
        abort_unless($bif, 404);

        $documentKey = $this->requirementDocumentKey($requirement);
        abort_unless($documentKey !== null, 404);

        $path = $this->normalizeStoredPath((string) data_get($bif->client_requirement_documents, $documentKey.'.path', ''));
        abort_unless($path !== '', 404);

        $originalName = (string) data_get($bif->client_requirement_documents, $documentKey.'.original_name', basename($path));
        $disk = Storage::disk('public')->exists($path) ? 'public' : (Storage::disk('local')->exists($path) ? 'local' : null);
        abort_unless($disk !== null, 404);

        $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
        $contents = Storage::disk($disk)->get($path);

        return response($contents, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$originalName.'"',
        ]);
    }

    public function previewRequirementTemplate(Request $request, int $company, string $requirement): View|RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $bif = $this->latestCompanyBif($company);

        if (! $bif) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'Create or save the Business Information Form first before preparing this template.']);
        }

        if ($requirement === 'sole_spa') {
            return view('company.requirements.spa-editor', [
                'company' => (object) $companyData,
                'bif' => $bif,
                'doc' => $this->spaEditorData($request, $companyData, $bif),
                'downloadUrl' => route('company.kyc.requirements.template.download', ['company' => $company, 'requirement' => $requirement]),
                'backUrl' => route('company.kyc', ['company' => $company, 'tab' => 'business-client-information']),
            ]);
        }

        if ($requirement === 'juridical_secretary_certificate') {
            return view('company.requirements.secretary-certificate-editor', [
                'company' => (object) $companyData,
                'bif' => $bif,
                'doc' => $this->secretaryCertificateEditorData($request, $companyData, $bif),
                'downloadUrl' => route('company.kyc.requirements.template.download', ['company' => $company, 'requirement' => $requirement]),
                'backUrl' => route('company.kyc', ['company' => $company, 'tab' => 'business-client-information']),
            ]);
        }

        if ($requirement === 'juridical_ubo_declaration') {
            return view('company.requirements.ubo-declaration-editor', [
                'company' => (object) $companyData,
                'bif' => $bif,
                'doc' => $this->uboDeclarationEditorData($request, $companyData, $bif),
                'downloadUrl' => route('company.kyc.requirements.template.download', ['company' => $company, 'requirement' => $requirement]),
                'backUrl' => route('company.kyc', ['company' => $company, 'tab' => 'business-client-information']),
            ]);
        }

        abort(404);
    }

    public function downloadRequirementTemplatePdf(Request $request, int $company, string $requirement)
    {
        $companyData = $this->findCompany($request, $company);
        $bif = $this->latestCompanyBif($company);
        abort_unless($bif, 404);

        if ($requirement === 'sole_spa') {
            $doc = $this->spaEditorData($request, $companyData, $bif);

            if ($request->boolean('autoprint')) {
                return view('company.requirements.spa-pdf', [
                    'company' => (object) $companyData,
                    'bif' => $bif,
                    'doc' => $doc,
                    'autoPrint' => true,
                ]);
            }

            $pdf = Pdf::loadView('company.requirements.spa-pdf', [
                'company' => (object) $companyData,
                'bif' => $bif,
                'doc' => $doc,
                'autoPrint' => false,
            ])->setPaper('A4', 'portrait');

            return $pdf->download('special-power-of-attorney.pdf');
        }

        if ($requirement === 'juridical_secretary_certificate') {
            $doc = $this->secretaryCertificateEditorData($request, $companyData, $bif);
            $pdf = Pdf::loadView('company.requirements.secretary-certificate-pdf', [
                'company' => (object) $companyData,
                'bif' => $bif,
                'doc' => $doc,
            ])->setPaper('A4', 'portrait');

            return $pdf->download('secretarys-certificate.pdf');
        }

        if ($requirement === 'juridical_ubo_declaration') {
            $doc = $this->uboDeclarationEditorData($request, $companyData, $bif);

            if ($request->boolean('autoprint')) {
                return view('company.requirements.ubo-declaration-pdf', [
                    'company' => (object) $companyData,
                    'bif' => $bif,
                    'doc' => $doc,
                    'autoPrint' => true,
                ]);
            }

            $pdf = Pdf::loadView('company.requirements.ubo-declaration-pdf', [
                'company' => (object) $companyData,
                'bif' => $bif,
                'doc' => $doc,
                'autoPrint' => false,
            ])->setPaper('A4', 'portrait');

            return $pdf->download('ubo-declaration.pdf');
        }

        abort(404);
    }

    public function submitKycForVerification(Request $request, int $company): RedirectResponse
    {
        abort_unless(! $this->isKycReviewer($request->user()), 403);

        $bif = $this->latestCompanyBif($company);
        if (! $bif) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No Business Information Form found to submit for verification.']);
        }

        if (($bif->change_request_status ?? null) === 'pending') {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'A BIF change request is still pending admin review.']);
        }

        $requirements = $this->kycRequirements($company, $bif);
        $missingLabels = $this->missingRequirementLabels($requirements);

        $bif->update([
            'status' => 'pending_approval',
            'submitted_at' => $bif->submitted_at ?? now(),
            'approved_at' => null,
            'approved_by_name' => null,
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
            'updated_by' => $request->user()?->id,
        ]);

        $userName = $request->user()?->name ?? 'System User';

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => 'Company KYC submitted for verification',
            'description' => 'Business Information Form submitted for admin review',
            'extra_label' => 'Status',
            'extra_value' => 'Waiting for Approval',
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        $redirect = redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'Company KYC submitted for verification.');

        if ($missingLabels !== []) {
            $redirect->with('bif_warning', 'Recommendation: upload the remaining onboarding documents as soon as possible. Missing: '.implode(', ', $missingLabels));
        }

        return $redirect;
    }

    public function approveKyc(Request $request, int $company): RedirectResponse
    {
        abort_unless($this->isKycReviewer($request->user()), 403);
        $companyData = $this->findCompany($request, $company);
        $bif = $this->latestCompanyBif($company);

        if (! $bif) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No Business Information Form found to approve.']);
        }

        if ((string) $bif->status !== 'pending_approval') {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'Only records submitted for verification can be approved.']);
        }

        $userName = $request->user()?->name ?? 'System User';

        $bif->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_name' => $userName,
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
            'updated_by' => $request->user()?->id,
        ]);

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => 'Company KYC approved',
            'description' => "Business KYC approved for {$companyData['company_name']}",
            'extra_label' => 'Status',
            'extra_value' => 'Approved',
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'Company KYC approved successfully.');
    }

    public function rejectKyc(Request $request, int $company): RedirectResponse
    {
        abort_unless($this->isKycReviewer($request->user()), 403);
        $companyData = $this->findCompany($request, $company);
        $bif = $this->latestCompanyBif($company);

        if (! $bif) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No Business Information Form found to reject.']);
        }

        if ((string) $bif->status !== 'pending_approval') {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'Only records submitted for verification can be rejected.']);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $reason = trim((string) ($validated['reason'] ?? ''));
        $userName = $request->user()?->name ?? 'System User';

        $bif->update([
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by_name' => null,
            'rejected_at' => now(),
            'rejected_by_name' => $userName,
            'rejection_reason' => $reason !== '' ? $reason : null,
            'updated_by' => $request->user()?->id,
        ]);

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => 'Company KYC rejected',
            'description' => "Business KYC rejected for {$companyData['company_name']}",
            'extra_label' => 'Status',
            'extra_value' => 'Rejected',
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'Company KYC rejected successfully.');
    }

    public function uploadRequirementDocument(Request $request, int $company, string $requirement): RedirectResponse
    {
        abort_unless(! $this->isKycReviewer($request->user()), 403);

        $bif = $this->latestCompanyBif($company);
        if (! $bif) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No Business Information Form found for requirement upload.']);
        }

        $documentKey = $this->requirementDocumentKey($requirement);
        abort_unless($documentKey !== null, 404);

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $documents = is_array($bif->client_requirement_documents) ? $bif->client_requirement_documents : [];
        $existingPath = data_get($documents, $documentKey.'.path');
        if (filled($existingPath) && Storage::disk('public')->exists((string) $existingPath)) {
            Storage::disk('public')->delete((string) $existingPath);
        }

        $file = $validated['document'];
        $path = $file->store("company-bifs/{$bif->id}/client-documents", 'public');

        $documents[$documentKey] = [
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'uploaded_at' => now()->toIso8601String(),
        ];

        $bif->update([
            'client_requirement_documents' => $documents,
            'updated_by' => $request->user()?->id,
            'last_submission_source' => 'manual',
            'last_manual_updated_at' => now(),
            'last_manual_updated_by_name' => $request->user()?->name ?? 'System User',
        ]);

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'Requirement document uploaded successfully.');
    }

    public function removeRequirementDocument(Request $request, int $company, string $requirement): RedirectResponse
    {
        abort_unless(! $this->isKycReviewer($request->user()), 403);

        $bif = $this->latestCompanyBif($company);
        if (! $bif) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No Business Information Form found for requirement removal.']);
        }

        $documentKey = $this->requirementDocumentKey($requirement);
        abort_unless($documentKey !== null, 404);

        $documents = is_array($bif->client_requirement_documents) ? $bif->client_requirement_documents : [];
        $existingPath = data_get($documents, $documentKey.'.path');
        if (filled($existingPath) && Storage::disk('public')->exists((string) $existingPath)) {
            Storage::disk('public')->delete((string) $existingPath);
        }
        unset($documents[$documentKey]);

        $bif->update([
            'client_requirement_documents' => $documents,
            'updated_by' => $request->user()?->id,
            'last_submission_source' => 'manual',
            'last_manual_updated_at' => now(),
            'last_manual_updated_by_name' => $request->user()?->name ?? 'System User',
        ]);

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'Requirement document removed successfully.');
    }

    private function kycActivityLogs(?CompanyBif $bif, array $companyData): array
    {
        if (! $bif) {
            return [
                "KYC profile ready for {$companyData['company_name']}",
            ];
        }

        $logs = [
            "BIF profile loaded for {$companyData['company_name']}",
        ];

        if ($bif->submitted_at) {
            $logs[] = 'Submitted for verification on '.$bif->submitted_at->format('M j, Y');
        }

        if ($bif->client_form_sent_at) {
            $logs[] = 'Client BIF link sent to '.($bif->client_form_sent_to_email ?: 'recipient').' on '.$bif->client_form_sent_at->format('M j, Y g:i A');
        }

        if ($bif->client_submitted_at) {
            $logs[] = 'Client completed the BIF on '.$bif->client_submitted_at->format('M j, Y g:i A');
        }

        if (($bif->change_request_status ?? null) === 'pending') {
            $logs[] = 'Pending BIF change request from '.($bif->change_requested_by_name ?: 'User');
        } elseif (($bif->change_request_status ?? null) === 'rejected') {
            $logs[] = 'Latest BIF change request was rejected by '.($bif->change_reviewed_by_name ?: 'Admin');
        }

        if ($bif->approved_at) {
            $logs[] = 'Approved by '.($bif->approved_by_name ?: 'System User');
        } elseif ($bif->rejected_at) {
            $logs[] = 'Rejected by '.($bif->rejected_by_name ?: 'System User');
        } elseif ($bif->updated_at) {
            $logs[] = 'Last updated on '.$bif->updated_at->format('M j, Y g:i A');
        }

        return $logs;
    }

    private function kycRequirements(int $company, ?CompanyBif $bif): array
    {
        return collect($this->requirementDefinitions($bif?->business_organization))
            ->map(function (array $group) use ($company, $bif) {
                return [
                    'group' => $group['group'],
                    'items' => collect($group['items'])
                        ->map(function (array $requirement) use ($company, $bif) {
                            $documentMeta = $this->requirementDocumentMeta($requirement['key'], $bif);
                            $uploaded = $documentMeta['uploaded'];

                            return [
                                'key' => $requirement['key'],
                                'label' => $requirement['label'],
                                'uploaded' => $uploaded,
                                'file_name' => $documentMeta['file_name'],
                                'mime_type' => $documentMeta['mime_type'],
                                'uploaded_at' => $documentMeta['uploaded_at'],
                                'template_url' => in_array($requirement['key'], ['sole_spa', 'juridical_secretary_certificate', 'juridical_ubo_declaration'], true)
                                    ? route('company.kyc.requirements.template', ['company' => $company, 'requirement' => $requirement['key']])
                                    : null,
                                'template_download_url' => in_array($requirement['key'], ['sole_spa', 'juridical_secretary_certificate', 'juridical_ubo_declaration'], true)
                                    ? route('company.kyc.requirements.template.download', ['company' => $company, 'requirement' => $requirement['key']])
                                    : null,
                                'file_url' => $uploaded
                                    ? route('company.kyc.requirements.view', ['company' => $company, 'requirement' => $requirement['key']])
                                    : null,
                                'helper' => $requirement['key'] === 'sole_spa'
                                    ? ($uploaded
                                        ? (($documentMeta['file_name'] ?: 'Signed SPA uploaded').' - Preview, edit, and export the SPA as PDF anytime.')
                                        : 'Preview and edit the SPA with autofilled details, then download it as PDF and upload the signed copy here.')
                                    : ($requirement['key'] === 'juridical_secretary_certificate'
                                        ? ($uploaded
                                            ? (($documentMeta['file_name'] ?: 'Signed Secretary Certificate uploaded').' - Preview, edit, and export the certificate as PDF anytime.')
                                            : 'Preview and edit the Secretary Certificate with autofilled details, then download it as PDF and upload the signed copy here.')
                                    : ($requirement['key'] === 'juridical_ubo_declaration'
                                        ? ($uploaded
                                            ? (($documentMeta['file_name'] ?: 'Signed UBO Declaration uploaded').' - Preview, edit, and export the declaration as PDF anytime.')
                                            : 'Preview and edit the UBO Declaration with autofilled company and beneficial owner details, then download it as PDF and upload the signed copy here.')
                                    : ($uploaded
                                        ? ($documentMeta['file_name'] ?: 'File uploaded')
                                        : 'No file uploaded yet'))),
                            ];
                        })
                        ->all(),
                ];
            })
            ->all();
    }

    private function areKycRequirementsComplete(array $requirements): bool
    {
        foreach ($requirements as $group) {
            foreach (($group['items'] ?? []) as $item) {
                if (! (bool) ($item['uploaded'] ?? false)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function missingRequirementLabels(array $requirements): array
    {
        return collect($requirements)
            ->flatMap(fn (array $group) => $group['items'] ?? [])
            ->filter(fn (array $item) => ! (bool) ($item['uploaded'] ?? false))
            ->pluck('label')
            ->values()
            ->all();
    }

    private function requirementDocumentMeta(string $key, ?CompanyBif $bif): array
    {
        $documentKey = $this->requirementDocumentKey($key);
        if ($documentKey === null) {
            return [
                'uploaded' => false,
                'file_name' => null,
                'file_url' => null,
                'mime_type' => null,
                'uploaded_at' => null,
            ];
        }

        $path = $this->normalizeStoredPath((string) data_get($bif?->client_requirement_documents, $documentKey.'.path', ''));
        $fileName = data_get($bif?->client_requirement_documents, $documentKey.'.original_name');
        $uploaded = $path !== '';
        $mimeType = null;
        if ($uploaded) {
            if (Storage::disk('public')->exists($path)) {
                $mimeType = Storage::disk('public')->mimeType($path) ?: null;
            } elseif (Storage::disk('local')->exists($path)) {
                $mimeType = Storage::disk('local')->mimeType($path) ?: null;
            }
        }

        return [
            'uploaded' => $uploaded,
            'file_name' => $uploaded ? ($fileName ?: basename($path)) : null,
            'file_url' => null,
            'mime_type' => $mimeType,
            'uploaded_at' => data_get($bif?->client_requirement_documents, $documentKey.'.uploaded_at'),
        ];
    }

    private function normalizeStoredPath(string $path): string
    {
        $value = trim(str_replace('\\', '/', $path));
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '/storage/')) {
            $value = substr($value, strpos($value, '/storage/') + 9);
        } elseif (str_starts_with($value, 'storage/')) {
            $value = substr($value, 8);
        }

        return ltrim($value, '/');
    }

    private function requirementDocumentKey(string $requirement): ?string
    {
        $documentMap = [
            'sole_dti_certificate' => 'sole_dti_certificate_document',
            'sole_bir_cor' => 'sole_bir_cor_document',
            'sole_business_permit' => 'sole_business_permit_document',
            'sole_proof_of_billing_residential' => 'sole_proof_of_billing_residential_document',
            'sole_proof_of_billing_business' => 'sole_proof_of_billing_business_document',
            'sole_spa' => 'sole_spa_document',
            'sole_representative_ids' => 'sole_representative_ids_document',
            'juridical_sec_cda_certificate' => 'juridical_sec_cda_certificate_document',
            'juridical_bir_cor' => 'juridical_bir_cor_document',
            'juridical_business_permit' => 'juridical_business_permit_document',
            'juridical_articles' => 'juridical_articles_document',
            'juridical_bylaws' => 'juridical_bylaws_document',
            'juridical_gis' => 'juridical_gis_document',
            'juridical_appointment_of_officers' => 'juridical_appointment_of_officers_document',
            'juridical_secretary_certificate' => 'juridical_secretary_certificate_document',
            'juridical_ubo_declaration' => 'juridical_ubo_declaration_document',
            'juridical_company_billing' => 'juridical_company_billing_document',
            'juridical_representative_billing' => 'juridical_representative_billing_document',
        ];

        return $documentMap[$requirement] ?? null;
    }

    private function requirementDefinitions(?string $organization): array
    {
        $sole = [
            'group' => 'Required for Sole Proprietorship',
            'items' => [
                ['key' => 'sole_dti_certificate', 'label' => 'DTI Certificate of Registration (if Sole Prop)'],
                ['key' => 'sole_bir_cor', 'label' => 'BIR Certificate of Registration (COR)'],
                ['key' => 'sole_business_permit', 'label' => 'Business Permit / Mayor\'s Permit'],
                ['key' => 'sole_proof_of_billing_residential', 'label' => 'Proof of Billing (Residential)'],
                ['key' => 'sole_proof_of_billing_business', 'label' => 'Proof of Billing (Business Address if different)'],
                ['key' => 'sole_spa', 'label' => 'Special Power of Attorney (if representative)'],
                ['key' => 'sole_representative_ids', 'label' => 'Representative\'s 2 Valid IDs (if applicable)'],
            ],
        ];

        $juridical = [
            'group' => 'Required for Partnership / Corporation / Cooperative / NGO / Other Juridical Entity',
            'items' => [
                ['key' => 'juridical_sec_cda_certificate', 'label' => 'SEC / CDA Certificate of Registration'],
                ['key' => 'juridical_bir_cor', 'label' => 'BIR Certificate of Registration (COR)'],
                ['key' => 'juridical_business_permit', 'label' => 'Business Permit / Mayor\'s Permit'],
                ['key' => 'juridical_articles', 'label' => 'Articles of Incorporation / Partnership'],
                ['key' => 'juridical_bylaws', 'label' => 'By-Laws'],
                ['key' => 'juridical_gis', 'label' => 'Latest General Information Sheet (GIS)'],
                ['key' => 'juridical_appointment_of_officers', 'label' => 'Appointment of Officers (for OPC, if applicable)'],
                ['key' => 'juridical_secretary_certificate', 'label' => 'Secretary Certificate OR Board Resolution'],
                ['key' => 'juridical_ubo_declaration', 'label' => 'Ultimate Beneficial Owner (UBO) Declaration'],
                ['key' => 'juridical_company_billing', 'label' => 'Proof of Billing (Company Address)'],
                ['key' => 'juridical_representative_billing', 'label' => 'Proof of Billing (Authorized Representative, if applicable)'],
            ],
        ];

        return match ((string) $organization) {
            'sole_proprietorship' => [$sole],
            'partnership', 'corporation', 'cooperative', 'ngo', 'other' => [$juridical],
            default => [$sole, $juridical],
        };
    }

    private function findCompany(Request $request, int $company): array
    {
        if (Schema::hasTable('companies')) {
            $record = Company::query()->find($company);

            if ($record) {
                return [
                    'id' => $record->id,
                    'company_name' => $record->company_name,
                    'company_type' => null,
                    'email' => $record->email,
                    'phone' => $record->phone,
                    'website' => $record->website,
                    'description' => $record->description,
                    'address' => $record->address,
                    'owner_name' => $record->owner_name,
                    'created_at' => optional($record->created_at)->toDateTimeString(),
                ];
            }
        }

        $companyData = collect($request->session()->get('mock_companies', $this->defaultCompanies()))
            ->firstWhere('id', $company);

        abort_unless($companyData, 404);

        return $companyData;
    }

    private function latestCompanyBif(int $company): ?CompanyBif
    {
        if (! Schema::hasTable('company_bifs')) {
            return null;
        }

        return CompanyBif::query()
            ->where('company_id', $company)
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    private function initials(string $name): string
    {
        $segments = collect(explode(' ', trim($name)))->filter()->take(2);

        if ($segments->isEmpty()) {
            return 'SU';
        }

        return $segments
            ->map(fn (string $segment): string => strtoupper(mb_substr($segment, 0, 1)))
            ->implode('');
    }

    private function defaultCompanies(): array
    {
        return [
            ['id' => 1, 'company_name' => 'Company 1', 'company_type' => 'Corporation', 'email' => 'company1@example.com', 'phone' => '09012345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Makati City', 'owner_name' => 'Owner 1', 'created_at' => '2026-03-01 10:00:00'],
            ['id' => 2, 'company_name' => 'Company 2', 'company_type' => 'Corporation', 'email' => 'company2@example.com', 'phone' => '09000345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Taguig City', 'owner_name' => 'Owner 2', 'created_at' => '2026-03-02 10:00:00'],
            ['id' => 3, 'company_name' => 'Company 3', 'company_type' => 'Corporation', 'email' => 'company3@example.com', 'phone' => '09777345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Pasig City', 'owner_name' => 'Owner 3', 'created_at' => '2026-03-03 10:00:00'],
        ];
    }

    private function spaEditorData(Request $request, array $companyData, CompanyBif $bif): array
    {
        $signatories = collect($bif->authorized_signatories ?? [])->values();
        $principalName = trim((string) ($bif->president_name ?: $bif->authorized_signatory_name ?: data_get($signatories->first(), 'full_name', '')));
        $principalAddress = trim((string) ($bif->business_address ?: data_get($signatories->first(), 'address', ($companyData['address'] ?? ''))));
        $attorneyName = trim((string) ($bif->authorized_contact_person_name ?: data_get($signatories->get(1), 'full_name', '')));
        $attorneyAddress = trim((string) ($principalAddress !== '' ? $principalAddress : ($companyData['address'] ?? '')));
        $signedAt = $bif->bif_date instanceof CarbonInterface ? $bif->bif_date : now();

        return [
            'principal_name' => (string) $request->query('principal_name', $principalName),
            'principal_nationality' => (string) $request->query('principal_nationality', $this->spaNationalityLabel((string) ($bif->nationality_status ?? ''))),
            'principal_civil_status' => (string) $request->query('principal_civil_status', ''),
            'principal_address' => (string) $request->query('principal_address', $principalAddress),
            'attorney_name' => (string) $request->query('attorney_name', $attorneyName),
            'attorney_nationality' => (string) $request->query('attorney_nationality', $this->spaNationalityLabel((string) ($bif->nationality_status ?? ''))),
            'attorney_address' => (string) $request->query('attorney_address', $attorneyAddress),
            'signed_place' => (string) $request->query('signed_place', $principalAddress),
            'signed_day' => (string) $request->query('signed_day', $signedAt->format('d')),
            'signed_month' => (string) $request->query('signed_month', $signedAt->format('F')),
            'signed_year' => (string) $request->query('signed_year', $signedAt->format('Y')),
            'principal_id_no' => (string) $request->query('principal_id_no', ''),
            'attorney_id_no' => (string) $request->query('attorney_id_no', ''),
        ];
    }

    private function secretaryCertificateEditorData(Request $request, array $companyData, CompanyBif $bif): array
    {
        $signatories = collect($bif->authorized_signatories ?? [])
            ->map(function ($row) {
                return [
                    'name' => (string) ($row['full_name'] ?? ''),
                    'position' => (string) ($row['position'] ?? ''),
                ];
            })
            ->pad(3, ['name' => '', 'position' => ''])
            ->take(3)
            ->values();

        $signedAt = $bif->bif_date instanceof CarbonInterface ? $bif->bif_date : now();
        $defaultAffiant = trim((string) ($bif->authorized_contact_person_name ?: $bif->president_name ?: ''));
        $defaultAddress = trim((string) ($bif->business_address ?: ($companyData['address'] ?? '')));

        $representatives = [];
        foreach (range(0, 2) as $index) {
            $row = $signatories[$index] ?? ['name' => '', 'position' => ''];
            $representatives[] = [
                'name' => (string) $request->query("representatives.$index.name", $row['name']),
                'position' => (string) $request->query("representatives.$index.position", $row['position']),
            ];
        }

        return [
            'affiant_name' => (string) $request->query('affiant_name', $defaultAffiant),
            'affiant_age' => (string) $request->query('affiant_age', ''),
            'affiant_address' => (string) $request->query('affiant_address', $defaultAddress),
            'corporation_name' => (string) $request->query('corporation_name', ($bif->business_name ?: ($companyData['company_name'] ?? ''))),
            'sec_registration_no' => (string) $request->query('sec_registration_no', ''),
            'principal_office_address' => (string) $request->query('principal_office_address', $defaultAddress),
            'board_resolution_no' => (string) $request->query('board_resolution_no', ''),
            'board_meeting_date' => (string) $request->query('board_meeting_date', $signedAt->format('F d, Y')),
            'representatives' => $representatives,
            'witness_city' => (string) $request->query('witness_city', 'Cebu City'),
            'witness_day' => (string) $request->query('witness_day', ''),
            'witness_month' => (string) $request->query('witness_month', ''),
            'witness_year' => (string) $request->query('witness_year', $signedAt->format('Y')),
            'corporate_secretary_name' => (string) $request->query('corporate_secretary_name', $defaultAffiant),
            'corporate_secretary_tin' => (string) $request->query('corporate_secretary_tin', (string) ($bif->tin_no ?? '')),
            'subscribed_day' => (string) $request->query('subscribed_day', ''),
            'subscribed_month' => (string) $request->query('subscribed_month', ''),
            'subscribed_year' => (string) $request->query('subscribed_year', $signedAt->format('Y')),
            'affiant_tin' => (string) $request->query('affiant_tin', (string) ($bif->tin_no ?? '')),
            'notary_public' => (string) $request->query('notary_public', ''),
            'doc_no' => (string) $request->query('doc_no', ''),
            'page_no' => (string) $request->query('page_no', ''),
            'book_no' => (string) $request->query('book_no', ''),
            'series_year' => (string) $request->query('series_year', $signedAt->format('Y')),
        ];
    }

    private function uboDeclarationEditorData(Request $request, array $companyData, CompanyBif $bif): array
    {
        $signedAt = $bif->bif_date instanceof CarbonInterface ? $bif->bif_date : now();
        $companyName = trim((string) ($bif->business_name ?: ($companyData['company_name'] ?? '')));
        $companyAddress = trim((string) ($bif->business_address ?: ($companyData['address'] ?? '')));
        $companyTin = trim((string) ($bif->tin_no ?? ''));
        $declarantName = trim((string) ($bif->authorized_contact_person_name ?: $bif->president_name ?: $bif->authorized_signatory_name ?: ''));
        $declarantPosition = trim((string) ($bif->authorized_contact_person_position ?: $bif->ubo_position ?: 'Authorized Representative'));
        $declarantNationality = $this->spaNationalityLabel((string) ($bif->nationality_status ?? ''));

        $ubos = collect($bif->ubos ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values();

        if ($ubos->isEmpty() && ($bif->ubo_name || $bif->ubo_address || $bif->ubo_position || $bif->ubo_tin)) {
            $ubos = collect([[
                'full_name' => $bif->ubo_name,
                'address' => $bif->ubo_address,
                'nationality' => $bif->ubo_nationality,
                'date_of_birth' => optional($bif->ubo_date_of_birth)?->format('Y-m-d'),
                'tin' => $bif->ubo_tin,
                'position' => $bif->ubo_position,
            ]]);
        }

        $ubos = $ubos
            ->map(function (array $row, int $index) use ($request) {
                return [
                    'full_name' => (string) $request->query("ubos.$index.full_name", (string) ($row['full_name'] ?? '')),
                    'address' => (string) $request->query("ubos.$index.address", (string) ($row['address'] ?? '')),
                    'nationality' => (string) $request->query("ubos.$index.nationality", (string) ($row['nationality'] ?? '')),
                    'date_of_birth' => (string) $request->query("ubos.$index.date_of_birth", (string) ($row['date_of_birth'] ?? '')),
                    'tin' => (string) $request->query("ubos.$index.tin", (string) ($row['tin'] ?? '')),
                    'position' => (string) $request->query("ubos.$index.position", (string) ($row['position'] ?? '')),
                ];
            })
            ->filter(function (array $row) {
                return collect($row)->contains(fn ($value) => trim((string) $value) !== '');
            })
            ->values();

        if ($ubos->isEmpty()) {
            $ubos = collect([[
                'full_name' => (string) $request->query('ubos.0.full_name', ''),
                'address' => (string) $request->query('ubos.0.address', ''),
                'nationality' => (string) $request->query('ubos.0.nationality', ''),
                'date_of_birth' => (string) $request->query('ubos.0.date_of_birth', ''),
                'tin' => (string) $request->query('ubos.0.tin', ''),
                'position' => (string) $request->query('ubos.0.position', ''),
            ]]);
        }

        return [
            'company_name' => (string) $request->query('company_name', $companyName),
            'company_address' => (string) $request->query('company_address', $companyAddress),
            'company_tin' => (string) $request->query('company_tin', $companyTin),
            'declarant_name' => (string) $request->query('declarant_name', $declarantName),
            'declarant_position' => (string) $request->query('declarant_position', $declarantPosition),
            'declarant_nationality' => (string) $request->query('declarant_nationality', $declarantNationality),
            'declaration_day' => (string) $request->query('declaration_day', $signedAt->format('d')),
            'declaration_month' => (string) $request->query('declaration_month', $signedAt->format('F')),
            'declaration_year' => (string) $request->query('declaration_year', $signedAt->format('Y')),
            'ubos' => $ubos->all(),
            'notary_city' => (string) $request->query('notary_city', 'Cebu City'),
            'notary_day' => (string) $request->query('notary_day', ''),
            'notary_month' => (string) $request->query('notary_month', ''),
            'notary_year' => (string) $request->query('notary_year', $signedAt->format('Y')),
            'notary_public' => (string) $request->query('notary_public', ''),
            'doc_no' => (string) $request->query('doc_no', ''),
            'page_no' => (string) $request->query('page_no', ''),
            'book_no' => (string) $request->query('book_no', ''),
            'series_year' => (string) $request->query('series_year', $signedAt->format('Y')),
        ];
    }

    private function spaTemplateData(array $companyData, CompanyBif $bif): array
    {
        $businessName = trim((string) ($bif->business_name ?: ($companyData['company_name'] ?? '')));
        $principalName = $this->spaPrincipalName($companyData, $bif);
        $principalAddress = $this->spaPrincipalAddress($companyData, $bif);
        $attorneyName = trim((string) ($bif->authorized_contact_person_name ?: $bif->authorized_signatory_name ?: ''));
        $attorneyAddress = trim((string) ($principalAddress !== '' ? $principalAddress : ($companyData['address'] ?? '')));
        $signedAt = $bif->bif_date instanceof CarbonInterface ? $bif->bif_date : now();

        return [
            'business_name' => $businessName,
            'principal_name' => $principalName !== '' ? $principalName : '____________________________',
            'principal_address' => $principalAddress !== '' ? $principalAddress : '____________________________',
            'attorney_name' => $attorneyName !== '' ? $attorneyName : '____________________________',
            'attorney_address' => $attorneyAddress !== '' ? $attorneyAddress : '____________________________',
            'attorney_position' => trim((string) ($bif->authorized_contact_person_position ?? '')),
            'nationality_label' => $this->spaNationalityLabel((string) ($bif->nationality_status ?? '')),
            'signed_place' => $this->spaSignedPlace($principalAddress, $companyData),
            'signed_day' => $signedAt->format('d'),
            'signed_month' => $signedAt->format('F'),
            'signed_year' => $signedAt->format('Y'),
            'acknowledgement_year_short' => $signedAt->format('y'),
        ];
    }

    private function spaPrincipalName(array $companyData, CompanyBif $bif): string
    {
        $signatories = collect($bif->authorized_signatories ?? []);
        $firstSignatory = $signatories->first(fn ($row) => filled($row['full_name'] ?? null));

        return trim((string) (
            $bif->president_name
            ?: $bif->authorized_signatory_name
            ?: ($firstSignatory['full_name'] ?? null)
            ?: $companyData['owner_name']
            ?: ''
        ));
    }

    private function spaPrincipalAddress(array $companyData, CompanyBif $bif): string
    {
        $signatories = collect($bif->authorized_signatories ?? []);
        $firstSignatory = $signatories->first(fn ($row) => filled($row['address'] ?? null));

        return trim((string) (
            $bif->business_address
            ?: ($firstSignatory['address'] ?? null)
            ?: ($companyData['address'] ?? '')
        ));
    }

    private function spaSignedPlace(string $principalAddress, array $companyData): string
    {
        $value = trim($principalAddress !== '' ? $principalAddress : (string) ($companyData['address'] ?? ''));

        if ($value === '') {
            return '____________________________';
        }

        return $value;
    }

    private function spaNationalityLabel(string $value): string
    {
        return match ($value) {
            'filipino' => 'Filipino',
            'foreign' => 'Foreign',
            default => 'Filipino',
        };
    }

    private function isKycReviewer(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || $user->isSuperAdmin()
            || $user->hasPermission('approve_corporate')
            || $user->hasPermission('access_admin_dashboard');
    }
}
