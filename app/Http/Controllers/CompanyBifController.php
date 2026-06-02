<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyBif;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\User;
use App\Support\CompanyHistoryLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyBifController extends Controller
{
    private const CLIENT_LINK_TTL_DAYS = 14;

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

    private const STATUSES = [
        'draft' => 'Draft',
        'pending_approval' => 'Waiting for Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    private const CHANGE_REQUEST_STATUSES = [
        'pending' => 'Pending Admin Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public function create(Request $request, int $company): View
    {
        return view('company.bif.create', $this->buildViewData($request, $company, null));
    }

    public function store(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $payload = $this->validatedPayload($request);
        $action = $request->input('action', 'submit');
        $isSubmit = $action !== 'draft';
        $status = $this->resolveSubmittedStatus($isSubmit);

        $bif = CompanyBif::create([
            ...$payload,
            'company_id' => $company,
            'title' => $this->resolveTitle($payload),
            'status' => $status,
            'submitted_at' => $isSubmit ? now() : null,
            'approved_at' => $this->resolveApprovedAt($isSubmit),
            'approved_by_name' => $this->resolveApprovedByName($request, $isSubmit),
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'last_submission_source' => 'manual',
            'last_manual_updated_at' => now(),
            'last_manual_updated_by_name' => $request->user()?->name ?? 'System User',
        ]);

        if (blank($bif->bif_no)) {
            $bif->updateQuietly([
                'bif_no' => $this->generateBifNumber($bif),
            ]);
        }

        $userName = $request->user()?->name ?? 'System User';

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => $isSubmit ? 'Business Client Information Form submitted' : 'Business Client Information Form draft saved',
            'description' => $bif->title,
            'extra_label' => 'Status',
            'extra_value' => self::STATUSES[$status] ?? ucfirst($status),
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        $message = $isSubmit
            ? "Business Client Information Form saved for {$companyData['company_name']} and submitted for approval."
            : "Business Client Information Form draft saved for {$companyData['company_name']}.";

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', $message);
    }

    public function show(Request $request, int $company, int $bif): View
    {
        return view('company.bif.show', $this->buildViewData(
            $request,
            $company,
            $this->findBif($company, $bif)
        ));
    }

    public function edit(Request $request, int $company, int $bif): View
    {
        return view('company.bif.edit', $this->buildViewData(
            $request,
            $company,
            $this->findBif($company, $bif)
        ));
    }

    public function update(Request $request, int $company, int $bif): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $bifRecord = $this->findBif($company, $bif);
        $payload = $this->validatedPayload($request);
        $action = $request->input('action', 'submit');
        $isSubmit = $action !== 'draft';
        $status = $this->resolveSubmittedStatus($isSubmit);
        $userName = $request->user()?->name ?? 'System User';

        $bifRecord->update([
            ...$payload,
            'title' => $this->resolveTitle($payload),
            'status' => $status,
            'submitted_at' => $isSubmit ? now() : null,
            'approved_at' => null,
            'approved_by_name' => null,
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
            'change_request_payload' => null,
            'change_request_status' => null,
            'change_request_note' => null,
            'change_requested_at' => null,
            'change_requested_by_name' => null,
            'change_reviewed_at' => null,
            'change_reviewed_by_name' => null,
            'change_rejection_reason' => null,
            'updated_by' => $request->user()?->id,
            'last_submission_source' => 'manual',
            'last_manual_updated_at' => now(),
            'last_manual_updated_by_name' => $userName,
        ]);

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => $isSubmit ? 'Business Client Information Form updated' : 'Business Client Information Form draft updated',
            'description' => $bifRecord->title,
            'extra_label' => 'Status',
            'extra_value' => self::STATUSES[$status] ?? ucfirst($status),
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        $message = $isSubmit
            ? "Business Client Information Form updated for {$companyData['company_name']} and submitted for approval."
            : "Business Client Information Form draft updated for {$companyData['company_name']}.";

        return redirect()
            ->route('company.bif.show', ['company' => $company, 'bif' => $bifRecord->id])
            ->with('bif_success', $message);
    }

    public function approveChangeRequest(Request $request, int $company, int $bif): RedirectResponse
    {
        abort_unless($this->isKycReviewer($request), 403);

        $bifRecord = $this->findBif($company, $bif);
        $pendingPayload = is_array($bifRecord->change_request_payload) ? $bifRecord->change_request_payload : [];

        if (($bifRecord->change_request_status ?? null) !== 'pending' || $pendingPayload === []) {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No pending BIF change request found.']);
        }

        $userName = $request->user()?->name ?? 'System User';
        $resolvedStatus = in_array((string) $bifRecord->status, ['approved', 'pending_approval'], true)
            ? (string) $bifRecord->status
            : 'approved';

        $bifRecord->update([
            ...$pendingPayload,
            'title' => $this->resolveTitle($pendingPayload),
            'status' => $resolvedStatus,
            'approved_at' => $resolvedStatus === 'approved' ? now() : $bifRecord->approved_at,
            'approved_by_name' => $resolvedStatus === 'approved' ? $userName : $bifRecord->approved_by_name,
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
            'change_request_payload' => null,
            'change_request_status' => null,
            'change_request_note' => null,
            'change_requested_at' => null,
            'change_requested_by_name' => null,
            'change_reviewed_at' => now(),
            'change_reviewed_by_name' => $userName,
            'change_rejection_reason' => null,
            'updated_by' => $request->user()?->id,
            'last_submission_source' => 'manual',
            'last_manual_updated_at' => now(),
            'last_manual_updated_by_name' => $userName,
        ]);

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => 'BIF change request approved',
            'description' => $bifRecord->title,
            'extra_label' => 'Status',
            'extra_value' => self::CHANGE_REQUEST_STATUSES['approved'],
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'BIF change request approved and applied.');
    }

    public function rejectChangeRequest(Request $request, int $company, int $bif): RedirectResponse
    {
        abort_unless($this->isKycReviewer($request), 403);

        $validated = $request->validate([
            'change_rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $bifRecord = $this->findBif($company, $bif);
        if (($bifRecord->change_request_status ?? null) !== 'pending') {
            return redirect()
                ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
                ->withErrors(['kyc' => 'No pending BIF change request found.']);
        }

        $userName = $request->user()?->name ?? 'System User';
        $reason = trim((string) ($validated['change_rejection_reason'] ?? ''));

        $bifRecord->update([
            'change_request_status' => 'rejected',
            'change_reviewed_at' => now(),
            'change_reviewed_by_name' => $userName,
            'change_rejection_reason' => $reason !== '' ? $reason : null,
            'updated_by' => $request->user()?->id,
        ]);

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => 'BIF change request rejected',
            'description' => $bifRecord->title,
            'extra_label' => 'Status',
            'extra_value' => self::CHANGE_REQUEST_STATUSES['rejected'],
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', 'BIF change request rejected.');
    }

    public function print(Request $request, int $company, int $bif): View
    {
        return view('company.bif.print', [
            ...$this->buildViewData($request, $company, $this->findBif($company, $bif)),
            'autoPrint' => $request->boolean('autoprint'),
            'backUrl' => route('company.kyc', ['company' => $company, 'tab' => 'business-client-information']),
        ]);
    }

    public function sendClientForm(Request $request, int $company): RedirectResponse|JsonResponse
    {
        $companyData = $this->findCompany($request, $company);

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $bif = $this->latestOrNewBif($request, $company, $companyData);
        $token = Str::random(64);
        $expiresAt = now()->addDays(self::CLIENT_LINK_TTL_DAYS);
        $recipientEmail = trim((string) $validated['recipient_email']);

        $bif->update([
            'client_access_token' => $token,
            'client_access_expires_at' => $expiresAt,
            'client_form_sent_to_email' => $recipientEmail,
            'client_form_sent_at' => now(),
            'updated_by' => $request->user()?->id,
        ]);

        $clientUrl = route('company.bif.client.show', ['token' => $token]);

        $emailHtml = view('emails.company.bif-client-link', [
            'company' => $company,
            'companyData' => $companyData,
            'clientName' => $companyData['company_name'] ?: 'Client',
            'clientUrl' => $clientUrl,
            'expiresAt' => $expiresAt,
        ])->render();

        Mail::html($emailHtml, function ($message) use ($recipientEmail, $companyData) {
            $message
                ->from(config('mail.from.address'), 'John Kelly & Company')
                ->to($recipientEmail)
                ->subject("Business Information Form for {$companyData['company_name']}");
        });

        $userName = $request->user()?->name ?? 'System User';

        CompanyHistoryLogger::log($company, [
            'type' => 'profile',
            'title' => 'Business Information Form link sent to client',
            'description' => "Secure BIF link sent to {$recipientEmail}",
            'extra_label' => 'BIF',
            'extra_value' => $bif->bif_no ?: 'Draft',
            'user_name' => $userName,
            'user_initials' => $this->initials($userName),
        ]);

        if ($this->prefersJsonResponse($request)) {
            return response()->json([
                'message' => "Business Information Form link sent to {$recipientEmail}.",
                'recipient_email' => $recipientEmail,
            ]);
        }

        return redirect()
            ->route('company.kyc', ['company' => $company, 'tab' => 'business-client-information'])
            ->with('bif_success', "Business Information Form link sent to {$recipientEmail}.")
            ->with('bif_client_email', $recipientEmail);
    }

    public function clientForm(Request $request, string $token): View
    {
        $bif = $this->findBifByClientToken($token);

        abort_if(
            $bif->client_access_expires_at && $bif->client_access_expires_at->isPast(),
            403,
            'This Business Information Form link has expired.'
        );

        return view('company.bif.client-form', [
            'bif' => $bif,
            'company' => $bif->company,
            'clientTypeOptions' => self::CLIENT_TYPES,
            'organizationOptions' => self::ORGANIZATION_TYPES,
            'nationalityOptions' => self::NATIONALITY_TYPES,
            'officeTypeOptions' => self::OFFICE_TYPES,
            'documentFields' => $this->clientDocumentDefinitions(),
            'clientFormAction' => route('company.bif.client.submit', ['token' => $token]),
            'clientPreviewUrl' => route('company.bif.client.preview', ['token' => $token]),
            'clientDownloadUrl' => route('company.bif.client.download', ['token' => $token, 'autoprint' => 1]),
        ]);
    }

    public function submitClientForm(Request $request, string $token): RedirectResponse|JsonResponse
    {
        $bif = $this->findBifByClientToken($token);

        abort_if(
            $bif->client_access_expires_at && $bif->client_access_expires_at->isPast(),
            403,
            'This Business Information Form link has expired.'
        );

        $payload = $this->validatedPayload($request);
        $documentPayload = $this->storeClientRequirementDocuments($request, $bif, $payload);
        $status = $this->resolveSubmittedStatus(true);

        $bif->update([
            ...$payload,
            'title' => $this->resolveTitle($payload),
            'status' => $status,
            'client_requirement_documents' => $documentPayload,
            'client_submitted_at' => now(),
            'last_submission_source' => 'client',
            'submitted_at' => $bif->submitted_at ?? now(),
            'approved_at' => $this->resolveApprovedAt(true),
            'approved_by_name' => $this->resolveApprovedByName($request, true),
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
            'updated_by' => null,
        ]);

        if (blank($bif->bif_no)) {
            $bif->updateQuietly([
                'bif_no' => $this->generateBifNumber($bif),
            ]);
        }

        if ($bif->company) {
            $bif->company->update(array_filter([
                'company_name' => $payload['business_name'] ?? null,
                'address' => $payload['business_address'] ?? null,
                'phone' => $payload['business_phone'] ?? ($payload['mobile_no'] ?? null),
                'email' => $payload['authorized_contact_person_email'] ?? null,
            ], fn ($value) => filled($value)));
        }

        CompanyHistoryLogger::log($bif->company_id, [
            'type' => 'profile',
            'title' => 'Client submitted Business Information Form',
            'description' => $bif->title,
            'extra_label' => 'Source',
            'extra_value' => 'Client Submission',
            'user_name' => $bif->authorized_contact_person_name ?: 'Client',
            'user_initials' => $this->initials($bif->authorized_contact_person_name ?: 'Client'),
        ]);

        if ($this->prefersJsonResponse($request)) {
            return response()->json([
                'message' => 'Your Business Information Form has been submitted successfully.',
            ]);
        }

        return redirect()
            ->route('company.bif.client.show', ['token' => $token])
            ->with('bif_success', 'Your Business Information Form has been submitted successfully.');
    }

    public function previewClientBif(Request $request, string $token): View
    {
        $bif = $this->findBifByClientToken($token);

        abort_if(
            $bif->client_access_expires_at && $bif->client_access_expires_at->isPast(),
            403,
            'This Business Information Form link has expired.'
        );

        return view('company.bif.print', [
            'company' => $bif->company,
            'bif' => $bif,
            'clientTypeOptions' => self::CLIENT_TYPES,
            'organizationOptions' => self::ORGANIZATION_TYPES,
            'nationalityOptions' => self::NATIONALITY_TYPES,
            'officeTypeOptions' => self::OFFICE_TYPES,
            'autoPrint' => false,
            'backUrl' => route('company.bif.client.show', ['token' => $token]),
        ]);
    }

    public function downloadClientBif(Request $request, string $token): View
    {
        $bif = $this->findBifByClientToken($token);

        abort_if(
            $bif->client_access_expires_at && $bif->client_access_expires_at->isPast(),
            403,
            'This Business Information Form link has expired.'
        );

        return view('company.bif.print', [
            'company' => $bif->company,
            'bif' => $bif,
            'clientTypeOptions' => self::CLIENT_TYPES,
            'organizationOptions' => self::ORGANIZATION_TYPES,
            'nationalityOptions' => self::NATIONALITY_TYPES,
            'officeTypeOptions' => self::OFFICE_TYPES,
            'autoPrint' => $request->boolean('autoprint'),
            'backUrl' => route('company.bif.client.show', ['token' => $token]),
        ]);
    }

    private function buildViewData(Request $request, int $company, ?CompanyBif $bif): array
    {
        $companyData = $this->findCompany($request, $company);

        return [
            'company' => (object) $companyData,
            'bif' => $this->bifWithDefaults($companyData, $bif),
            'clientTypeOptions' => self::CLIENT_TYPES,
            'organizationOptions' => self::ORGANIZATION_TYPES,
            'nationalityOptions' => self::NATIONALITY_TYPES,
            'officeTypeOptions' => self::OFFICE_TYPES,
            'statusLabels' => self::STATUSES,
            'employeeOptions' => $this->employeeOptions(),
            'roleContactOptions' => $this->roleContactOptions(),
        ];
    }

    private function latestOrNewBif(Request $request, int $company, array $companyData): CompanyBif
    {
        if (Schema::hasTable('company_bifs')) {
            $existing = CompanyBif::query()
                ->where('company_id', $company)
                ->latest('updated_at')
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $bif = CompanyBif::create([
            'company_id' => $company,
            'title' => "Business Client Information Form - {$companyData['company_name']}",
            'bif_date' => now()->toDateString(),
            'client_type' => 'new_client',
            'business_name' => $companyData['company_name'],
            'business_address' => $companyData['address'] ?? null,
            'authorized_contact_person_email' => $companyData['email'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'last_submission_source' => 'manual',
            'last_manual_updated_at' => now(),
            'last_manual_updated_by_name' => $request->user()?->name ?? 'System User',
        ]);

        $bif->updateQuietly([
            'bif_no' => $this->generateBifNumber($bif),
        ]);

        return $bif->fresh();
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
                    'primary_contact_id' => $record->primary_contact_id,
                    'owner_name' => $record->owner_name,
                    'created_at' => optional($record->created_at)->toDateTimeString(),
                ];
            }
        }

        $companyData = collect($request->session()->get('mock_companies', $this->defaultCompanies()))
            ->firstWhere('id', $company);

        abort_unless($companyData, 404);

        return [
            'company_type' => 'Corporation',
            'email' => null,
            'phone' => null,
            'website' => null,
            'description' => null,
            'address' => null,
            'primary_contact_id' => null,
            'owner_name' => null,
            ...$companyData,
        ];
    }

    private function bifWithDefaults(array $companyData, ?CompanyBif $bif): CompanyBif
    {
        $defaults = $this->companyBifDefaults($companyData);
        $resolved = $bif ? $bif->replicate() : new CompanyBif();

        if ($bif) {
            $resolved->exists = true;
            $resolved->setAttribute('id', $bif->id);
            $resolved->setAttribute('created_at', $bif->created_at);
            $resolved->setAttribute('updated_at', $bif->updated_at);
        }

        foreach ($defaults as $field => $value) {
            if (blank($resolved->{$field}) && filled($value)) {
                $resolved->setAttribute($field, $value);
            }
        }

        return $resolved;
    }

    private function companyBifDefaults(array $companyData): array
    {
        $contact = null;
        $primaryContactId = (int) ($companyData['primary_contact_id'] ?? 0);

        if ($primaryContactId > 0 && Schema::hasTable('contacts')) {
            $contact = Contact::query()->find($primaryContactId);
        }

        $contactName = $contact ? trim(collect([
            $contact->first_name,
            $contact->middle_name,
            $contact->last_name,
            $contact->name_extension,
        ])->filter()->implode(' ')) : '';
        $industryDefaults = $this->industryDefaultsFromNature($contact?->nature_of_business);

        return [
            'bif_date' => now()->toDateString(),
            'client_type' => 'new_client',
            'business_name' => $companyData['company_name'] ?? null,
            'business_address' => $companyData['address'] ?? null,
            'business_phone' => $companyData['phone'] ?? null,
            'mobile_no' => $contact?->phone,
            'authorized_contact_person_name' => $contactName,
            'authorized_contact_person_position' => $contact?->position,
            'authorized_contact_person_email' => $contact?->email ?: ($companyData['email'] ?? null),
            'authorized_contact_person_phone' => $contact?->phone ?: ($companyData['phone'] ?? null),
            'signature_printed_name' => $contactName,
            'signature_position' => $contact?->position,
            'sales_marketing_name' => $contact?->sales_marketing,
            'referred_by' => $contact?->referred_by,
            'consultant_lead' => $contact?->consultant_lead,
            'lead_associate' => $contact?->lead_associate,
            'industry_services' => in_array('services', $industryDefaults['types'], true),
            'industry_export_import' => in_array('export_import', $industryDefaults['types'], true),
            'industry_education' => in_array('education', $industryDefaults['types'], true),
            'industry_financial_services' => in_array('financial_services', $industryDefaults['types'], true),
            'industry_transportation' => in_array('transportation', $industryDefaults['types'], true),
            'industry_distribution' => in_array('distribution', $industryDefaults['types'], true),
            'industry_manufacturing' => in_array('manufacturing', $industryDefaults['types'], true),
            'industry_government' => in_array('government', $industryDefaults['types'], true),
            'industry_wholesale_retail_trade' => in_array('wholesale_retail_trade', $industryDefaults['types'], true),
            'industry_other' => in_array('other', $industryDefaults['types'], true),
            'industry_other_text' => $industryDefaults['other_text'],
        ];
    }

    private function employeeOptions(): array
    {
        if (! Schema::hasTable('employees')) {
            return User::query()
                ->select(['id', 'name', 'email', 'role'])
                ->whereIn('role', ['Admin', 'Employee', 'SuperAdmin'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'employee_code' => null,
                    'email' => $user->email,
                    'position' => null,
                    'department' => null,
                ])
                ->filter(fn (array $employee): bool => filled($employee['name']))
                ->values()
                ->all();
        }

        return Employee::query()
            ->with('department:id,department_name')
            ->select(['id', 'employee_code', 'first_name', 'last_name', 'email', 'position', 'department_id'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => (int) $employee->id,
                'name' => $employee->full_name,
                'employee_code' => $employee->employee_code,
                'email' => $employee->email,
                'position' => $employee->position,
                'department' => $employee->department?->department_name,
            ])
            ->filter(fn (array $employee): bool => filled($employee['name']))
            ->values()
            ->all();
    }

    private function roleContactOptions(): array
    {
        if (! Schema::hasTable('contacts')) {
            return [];
        }

        return Contact::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'name_extension',
                'position',
                'email',
                'phone',
                'contact_address',
                'company_name',
                'company_address',
                'tin',
                'date_of_birth',
                'sales_marketing',
                'consultant_lead',
                'lead_associate',
                'referred_by',
                'cif_status',
            ])
            ->map(function (Contact $contact): array {
                $fullName = trim(collect([
                    $contact->first_name,
                    $contact->middle_name,
                    $contact->last_name,
                    $contact->name_extension,
                ])->filter()->implode(' '));
                $address = collect([$contact->contact_address, $contact->company_address])->first(fn ($value) => filled($value));

                return [
                    'id' => (int) $contact->id,
                    'label' => $fullName !== '' ? $fullName : 'Contact #'.$contact->id,
                    'search_blob' => Str::lower(collect([
                        $fullName,
                        $contact->company_name,
                        $contact->position,
                        $contact->email,
                        $contact->phone,
                        $address,
                        $contact->tin,
                    ])->filter()->implode(' ')),
                    'company_name' => $contact->company_name,
                    'position' => $contact->position,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'address' => $address,
                    'nationality' => null,
                    'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                    'tin' => $contact->tin,
                    'sales_marketing_name' => $contact->sales_marketing,
                    'consultant_lead' => $contact->consultant_lead,
                    'lead_associate' => $contact->lead_associate,
                    'referred_by' => $contact->referred_by,
                    'cif_status' => $contact->cif_status,
                ];
            })
            ->values()
            ->all();
    }

    private function industryDefaultsFromNature(?string $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return ['types' => [], 'other_text' => null];
        }

        $normalized = Str::of($raw)->lower()->replace(['&', '/', '-'], ' ')->squish()->toString();
        $options = [
            'services' => ['services', 'service'],
            'export_import' => ['export import', 'export', 'import'],
            'education' => ['education', 'school', 'academy'],
            'financial_services' => ['financial services', 'finance', 'financial', 'banking'],
            'transportation' => ['transportation', 'transport', 'logistics'],
            'distribution' => ['distribution', 'distributor'],
            'manufacturing' => ['manufacturing', 'manufacturer'],
            'government' => ['government'],
            'wholesale_retail_trade' => ['wholesale retail trade', 'wholesale', 'retail', 'trade'],
        ];

        foreach ($options as $key => $needles) {
            foreach ($needles as $needle) {
                if ($normalized === $needle || Str::contains($normalized, $needle)) {
                    return ['types' => [$key], 'other_text' => null];
                }
            }
        }

        return ['types' => ['other'], 'other_text' => $raw];
    }

    private function findBif(int $company, int $bif): CompanyBif
    {
        abort_unless(Schema::hasTable('company_bifs'), 404);

        return CompanyBif::query()
            ->where('company_id', $company)
            ->findOrFail($bif);
    }

    private function findBifByClientToken(string $token): CompanyBif
    {
        abort_unless(Schema::hasTable('company_bifs'), 404);

        return CompanyBif::query()
            ->with('company')
            ->where('client_access_token', $token)
            ->firstOrFail();
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'bif_no' => ['nullable', 'string', 'max:255'],
            'bif_date' => ['required', 'date'],
            'client_type' => ['required', Rule::in(array_keys(self::CLIENT_TYPES))],
            'business_organization' => ['nullable', Rule::in(array_keys(self::ORGANIZATION_TYPES))],
            'business_organization_other' => ['nullable', 'string', 'max:255'],
            'nationality_status' => ['nullable', Rule::in(array_keys(self::NATIONALITY_TYPES))],
            'office_type' => ['nullable', Rule::in(array_keys(self::OFFICE_TYPES))],
            'office_type_other' => ['nullable', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'alternative_business_name' => ['nullable', 'string', 'max:255'],
            'business_address' => ['nullable', 'string'],
            'zip_code' => ['nullable', 'string', 'max:50'],
            'business_phone' => ['nullable', 'string', 'max:255'],
            'mobile_no' => ['nullable', 'string', 'max:255'],
            'tin_no' => ['nullable', 'string', 'max:255'],
            'place_of_incorporation' => ['nullable', 'string', 'max:255'],
            'date_of_incorporation' => ['nullable', 'date'],
            'industry_other_text' => ['nullable', 'string', 'max:255'],
            'employee_male' => ['nullable', 'integer', 'min:0'],
            'employee_female' => ['nullable', 'integer', 'min:0'],
            'employee_pwd' => ['nullable', 'integer', 'min:0'],
            'employee_total' => ['nullable', 'integer', 'min:0'],
            'source_other_text' => ['nullable', 'string', 'max:255'],
            'president_name' => ['nullable', 'string', 'max:255'],
            'treasurer_name' => ['nullable', 'string', 'max:255'],
            'authorized_signatories' => ['nullable', 'array'],
            'authorized_signatories.*.full_name' => ['nullable', 'string', 'max:255'],
            'authorized_signatories.*.address' => ['nullable', 'string', 'max:255'],
            'authorized_signatories.*.nationality' => ['nullable', 'string', 'max:255'],
            'authorized_signatories.*.date_of_birth' => ['nullable', 'date'],
            'authorized_signatories.*.tin' => ['nullable', 'string', 'max:255'],
            'authorized_signatories.*.position' => ['nullable', 'string', 'max:255'],
            'authorized_signatory_name' => ['nullable', 'string', 'max:255'],
            'authorized_signatory_address' => ['nullable', 'string'],
            'authorized_signatory_nationality' => ['nullable', 'string', 'max:255'],
            'authorized_signatory_date_of_birth' => ['nullable', 'date'],
            'authorized_signatory_tin' => ['nullable', 'string', 'max:255'],
            'authorized_signatory_position' => ['nullable', 'string', 'max:255'],
            'ubos' => ['nullable', 'array'],
            'ubos.*.full_name' => ['nullable', 'string', 'max:255'],
            'ubos.*.address' => ['nullable', 'string', 'max:255'],
            'ubos.*.nationality' => ['nullable', 'string', 'max:255'],
            'ubos.*.date_of_birth' => ['nullable', 'date'],
            'ubos.*.tin' => ['nullable', 'string', 'max:255'],
            'ubos.*.position' => ['nullable', 'string', 'max:255'],
            'ubo_name' => ['nullable', 'string', 'max:255'],
            'ubo_address' => ['nullable', 'string'],
            'ubo_nationality' => ['nullable', 'string', 'max:255'],
            'ubo_date_of_birth' => ['nullable', 'date'],
            'ubo_tin' => ['nullable', 'string', 'max:255'],
            'ubo_position' => ['nullable', 'string', 'max:255'],
            'authorized_contact_person_name' => ['nullable', 'string', 'max:255'],
            'authorized_contact_person_position' => ['nullable', 'string', 'max:255'],
            'authorized_contact_person_email' => ['nullable', 'email', 'max:255'],
            'authorized_contact_person_phone' => ['nullable', 'string', 'max:255'],
            'signature_printed_name' => ['nullable', 'string', 'max:255'],
            'signature_position' => ['nullable', 'string', 'max:255'],
            'review_signature_printed_name' => ['nullable', 'string', 'max:255'],
            'review_signature_position' => ['nullable', 'string', 'max:255'],
            'sales_marketing_name' => ['nullable', 'string', 'max:255'],
            'sales_marketing_date_signature' => ['nullable', 'string', 'max:255'],
            'finance_name' => ['nullable', 'string', 'max:255'],
            'finance_date_signature' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'consultant_lead' => ['nullable', 'string', 'max:255'],
            'lead_associate' => ['nullable', 'string', 'max:255'],
            'president_use_only_name' => ['nullable', 'string', 'max:255'],
        ]);

        $booleanFields = [
            'industry_services',
            'industry_export_import',
            'industry_education',
            'industry_financial_services',
            'industry_transportation',
            'industry_distribution',
            'industry_manufacturing',
            'industry_government',
            'industry_wholesale_retail_trade',
            'industry_other',
            'capital_micro',
            'capital_small',
            'capital_medium',
            'capital_large',
            'source_revenue_income',
            'source_investments',
            'source_remittance',
            'source_other',
            'source_fees',
        ];

        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        if (($validated['business_organization'] ?? null) !== 'other') {
            $validated['business_organization_other'] = null;
        }

        if (($validated['office_type'] ?? null) !== 'other') {
            $validated['office_type_other'] = null;
        }

        if (!($validated['industry_other'] ?? false)) {
            $validated['industry_other_text'] = null;
        }

        if (!($validated['source_other'] ?? false)) {
            $validated['source_other_text'] = null;
        }

        $validated['authorized_signatories'] = collect($validated['authorized_signatories'] ?? [])
            ->filter(fn (array $row) => collect($row)->contains(fn ($value) => filled($value)))
            ->values()
            ->all();

        $validated['ubos'] = collect($validated['ubos'] ?? [])
            ->filter(fn (array $row) => collect($row)->contains(fn ($value) => filled($value)))
            ->values()
            ->all();

        $firstSignatory = $validated['authorized_signatories'][0] ?? [];
        $validated['authorized_signatory_name'] = $firstSignatory['full_name'] ?? ($validated['authorized_signatory_name'] ?? null);
        $validated['authorized_signatory_address'] = $firstSignatory['address'] ?? ($validated['authorized_signatory_address'] ?? null);
        $validated['authorized_signatory_nationality'] = $firstSignatory['nationality'] ?? ($validated['authorized_signatory_nationality'] ?? null);
        $validated['authorized_signatory_date_of_birth'] = $firstSignatory['date_of_birth'] ?? ($validated['authorized_signatory_date_of_birth'] ?? null);
        $validated['authorized_signatory_tin'] = $firstSignatory['tin'] ?? ($validated['authorized_signatory_tin'] ?? null);
        $validated['authorized_signatory_position'] = $firstSignatory['position'] ?? ($validated['authorized_signatory_position'] ?? null);

        $firstUbo = $validated['ubos'][0] ?? [];
        $validated['ubo_name'] = $firstUbo['full_name'] ?? ($validated['ubo_name'] ?? null);
        $validated['ubo_address'] = $firstUbo['address'] ?? ($validated['ubo_address'] ?? null);
        $validated['ubo_nationality'] = $firstUbo['nationality'] ?? ($validated['ubo_nationality'] ?? null);
        $validated['ubo_date_of_birth'] = $firstUbo['date_of_birth'] ?? ($validated['ubo_date_of_birth'] ?? null);
        $validated['ubo_tin'] = $firstUbo['tin'] ?? ($validated['ubo_tin'] ?? null);
        $validated['ubo_position'] = $firstUbo['position'] ?? ($validated['ubo_position'] ?? null);

        return $validated;
    }

    private function clientDocumentDefinitions(): array
    {
        return [
            'sole_proprietorship' => [
                ['key' => 'sole_dti_certificate_document', 'label' => 'DTI Certificate of Registration (if Sole Prop)'],
                ['key' => 'sole_bir_cor_document', 'label' => 'BIR Certificate of Registration (COR)'],
                ['key' => 'sole_business_permit_document', 'label' => 'Business Permit / Mayor\'s Permit'],
                ['key' => 'sole_proof_of_billing_residential_document', 'label' => 'Proof of Billing (Residential)'],
                ['key' => 'sole_proof_of_billing_business_document', 'label' => 'Proof of Billing (Business Address if different)'],
                ['key' => 'sole_spa_document', 'label' => 'Special Power of Attorney (if representative)'],
                ['key' => 'sole_representative_ids_document', 'label' => 'Representative\'s 2 Valid IDs (if applicable)'],
            ],
            'juridical_entity' => [
                ['key' => 'juridical_sec_cda_certificate_document', 'label' => 'SEC / CDA Certificate of Registration'],
                ['key' => 'juridical_bir_cor_document', 'label' => 'BIR Certificate of Registration (COR)'],
                ['key' => 'juridical_business_permit_document', 'label' => 'Business Permit / Mayor\'s Permit'],
                ['key' => 'juridical_articles_document', 'label' => 'Articles of Incorporation / Partnership'],
                ['key' => 'juridical_bylaws_document', 'label' => 'By-Laws'],
                ['key' => 'juridical_gis_document', 'label' => 'Latest General Information Sheet (GIS)'],
                ['key' => 'juridical_appointment_of_officers_document', 'label' => 'Appointment of Officers (for OPC, if applicable)'],
                ['key' => 'juridical_secretary_certificate_document', 'label' => 'Secretary Certificate OR Board Resolution'],
                ['key' => 'juridical_ubo_declaration_document', 'label' => 'Ultimate Beneficial Owner (UBO) Declaration'],
                ['key' => 'juridical_company_billing_document', 'label' => 'Proof of Billing (Company Address)'],
                ['key' => 'juridical_representative_billing_document', 'label' => 'Proof of Billing (Authorized Representative, if applicable)'],
            ],
        ];
    }

    private function storeClientRequirementDocuments(Request $request, CompanyBif $bif, array $payload = []): array
    {
        $rules = [];

        foreach ($this->clientDocumentDefinitions() as $group) {
            foreach ($group as $document) {
                $rules[$document['key']] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'];
            }
        }

        $validated = $request->validate($rules);
        $stored = $bif->client_requirement_documents ?? [];
        $uploadedBy = trim((string) ($payload['authorized_contact_person_name'] ?? $bif->authorized_contact_person_name ?? ''));
        $uploadedBy = $uploadedBy !== '' ? $uploadedBy : 'Client';
        $issuedBy = strcasecmp($uploadedBy, 'Client') === 0 ? 'Client' : "Client - {$uploadedBy}";

        foreach ($validated as $key => $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store("company-bifs/{$bif->id}/client-documents", 'public');

            $stored[$key] = [
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'uploaded_at' => now()->toIso8601String(),
                'uploaded_by' => $uploadedBy,
                'uploaded_by_role' => 'Client',
                'issued_by' => $issuedBy,
            ];
        }

        return $stored;
    }

    private function resolveTitle(array $payload): string
    {
        $name = trim((string) ($payload['business_name'] ?? ''));

        return $name !== '' ? "Business Client Information Form - {$name}" : 'Business Client Information Form';
    }

    private function generateBifNumber(CompanyBif $bif): string
    {
        return 'BIF-' . now()->format('Ymd') . '-' . str_pad((string) $bif->id, 4, '0', STR_PAD_LEFT);
    }

    private function resolveSubmittedStatus(bool $isSubmit): string
    {
        if (! $isSubmit) {
            return 'draft';
        }

        return 'pending_approval';
    }

    private function resolveApprovedAt(bool $isSubmit)
    {
        return null;
    }

    private function resolveApprovedByName(Request $request, bool $isSubmit): ?string
    {
        return null;
    }

    private function defaultCompanies(): array
    {
        return [
            [
                'id' => 1,
                'company_name' => 'Company 1',
                'company_type' => 'Corporation',
                'email' => 'company1@example.com',
                'phone' => '09012345678',
                'website' => 'https://bigin.example',
                'description' => 'Sample company record',
                'address' => 'Makati City',
                'owner_name' => 'Owner 1',
                'created_at' => '2026-03-01 10:00:00',
            ],
            [
                'id' => 2,
                'company_name' => 'Company 2',
                'company_type' => 'Corporation',
                'email' => 'company2@example.com',
                'phone' => '09000345678',
                'website' => 'https://bigin.example',
                'description' => 'Sample company record',
                'address' => 'Taguig City',
                'owner_name' => 'Owner 2',
                'created_at' => '2026-03-02 10:00:00',
            ],
            [
                'id' => 3,
                'company_name' => 'Company 3',
                'company_type' => 'Corporation',
                'email' => 'company3@example.com',
                'phone' => '09777345678',
                'website' => 'https://bigin.example',
                'description' => 'Sample company record',
                'address' => 'Pasig City',
                'owner_name' => 'Owner 3',
                'created_at' => '2026-03-03 10:00:00',
            ],
        ];
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    private function prefersJsonResponse(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || str_contains(Str::lower((string) $request->header('Accept')), 'application/json');
    }

    private function isKycReviewer(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || $user->isSuperAdmin()
            || $user->hasPermission('approve_corporate')
            || $user->hasPermission('access_admin_dashboard');
    }
}
