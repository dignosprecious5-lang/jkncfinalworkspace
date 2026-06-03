<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Project;
use App\Models\ProjectSow;
use App\Models\ProjectSowReport;
use App\Models\ProjectStart;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectProvisioner
{
    public function resolveDealWorkspaceStartKyc(Project $project): array
    {
        $project->loadMissing([
            'deal:id,deal_code,engagement_type,contact_id,company_name,planned_start_date,estimated_completion_date,client_preferred_completion_date,assigned_consultant,assigned_associate,internal_sales_marketing,service_area,services,products,total_estimated_engagement_value,scope_of_work,engagement_type,deal_status',
            'contact:id,first_name,middle_name,last_name,email,phone,company_name,cif_status,organization_type,business_type_organization,ownership_flag,foreign_business_nature',
            'company.primaryContact:id,first_name,middle_name,last_name,email,phone,company_name,cif_status,organization_type,business_type_organization,ownership_flag,foreign_business_nature',
            'company.latestBif',
        ]);

        if (! $project->deal) {
            return [
                'organization_type' => 'unknown',
                'sole' => [],
                'juridical' => [],
            ];
        }

        return $this->defaultStartKycRequirements($project->deal, $project->contact, $project->company);
    }

    public function createFromApprovedDeal(Deal $deal): ?Project
    {
        return $this->createOrSyncFromDeal($deal);
    }

    public function createOrSyncFromDeal(Deal $deal): ?Project
    {
        if (! Schema::hasTable('projects')) {
            return null;
        }

        $engagementType = Str::lower(trim((string) ($deal->engagement_type ?? '')));
        $isRegular = Str::contains($engagementType, 'regular');
        $isProject = Str::contains($engagementType, 'project') || Str::contains($engagementType, 'hybrid');
        $isHybrid = Str::contains($engagementType, 'hybrid');

        if (! $isRegular && ! $isProject) {
            return null;
        }

        $contact = $deal->contact()->first();
        $company = $this->resolveDealCompany($deal, $contact);
        $clientName = trim(collect([
            $deal->first_name ?: $contact?->first_name,
            $deal->middle_name ?: $contact?->middle_name,
            $deal->last_name ?: $contact?->last_name,
        ])->filter()->implode(' '));

        $projectWorkspace = null;

        if ($isProject) {
            $projectWorkspace = $this->createOrSyncWorkspace($deal, $contact, $company, $clientName, regular: false, hybrid: $isHybrid);
        }

        if ($isRegular || $isHybrid) {
            $this->createOrSyncWorkspace($deal, $contact, $company, $clientName, regular: true, hybrid: $isHybrid);
        }

        return $projectWorkspace;
    }

    private function createOrSyncWorkspace(
        Deal $deal,
        ?Contact $contact,
        ?Company $company,
        string $clientName,
        bool $regular,
        bool $hybrid,
    ): Project {
        $scopeItems = $this->defaultScopeItems($deal);
        $internalApproval = $this->defaultInternalApprovalPayload($deal);
        $workspaceType = $regular ? 'regular' : 'project';
        $workspaceEngagementType = $regular
            ? ($hybrid ? 'Hybrid Regular' : ($deal->engagement_type ?: 'Regular Retainer'))
            : ($hybrid ? 'Hybrid Project' : $deal->engagement_type);
        $workspaceNameSuffix = $regular ? 'Regular' : 'Project';

        $projectAttributes = [
            'deal_id' => $deal->id,
            'contact_id' => $deal->contact_id,
            'company_id' => $company?->id,
            'name' => ($deal->deal_code ?: ('Project for '.($deal->company_name ?: $clientName ?: 'Client'))).' - '.$workspaceNameSuffix,
            'engagement_type' => $workspaceEngagementType,
            'status' => $regular ? 'RSAT' : 'SOW',
            'current_phase' => $regular ? 'RSAT' : 'SOW',
            'current_step' => $regular ? 'RSAT Checklist' : 'SOW Preparation',
            'planned_start_date' => $deal->planned_start_date,
            'target_completion_date' => $deal->estimated_completion_date,
            'client_preferred_completion_date' => $deal->client_preferred_completion_date,
            'assigned_project_manager' => $deal->assigned_consultant,
            'assigned_consultant' => $deal->assigned_consultant,
            'assigned_associate' => $deal->assigned_associate,
            'client_name' => $clientName,
            'business_name' => $deal->company_name ?: $contact?->company_name ?: $company?->company_name,
            'service_area' => $deal->service_area,
            'services' => $deal->services,
            'products' => $deal->products,
            'deal_value' => $deal->total_estimated_engagement_value,
            'scope_summary' => $deal->scope_of_work,
            'client_confirmation_name' => $clientName,
            'metadata' => [
                'created_from' => 'deal_approval',
                'deal_stage_at_creation' => $deal->stage,
                'workspace_type' => $workspaceType,
                'source_engagement_type' => $deal->engagement_type,
                'internal_assignments' => [
                    'sales_marketing' => $deal->internal_sales_marketing,
                ],
            ],
            'opened_at' => now(),
        ];

        $existingProject = Project::query()
            ->where('deal_id', $deal->id)
            ->when(
                $regular,
                fn ($query) => $query->whereRaw('LOWER(COALESCE(engagement_type, "")) LIKE ?', ['%regular%']),
                fn ($query) => $query->where(function ($workspaceQuery): void {
                    $workspaceQuery
                        ->whereNull('engagement_type')
                        ->orWhereRaw('LOWER(engagement_type) NOT LIKE ?', ['%regular%']);
                })
            )
            ->orderBy('id')
            ->first();

        if ($existingProject) {
            $existingProject->fill([
                ...$projectAttributes,
                'opened_at' => $existingProject->opened_at ?: now(),
            ])->save();

            $project = $existingProject;
        } else {
            $project = Project::query()->create($projectAttributes);
        }

        $project->starts()->firstOrCreate(
            ['project_id' => $project->id],
            [
                'form_date' => now()->toDateString(),
                'date_started' => now()->toDateString(),
                'status' => 'pending',
                'checklist' => $this->defaultStartChecklist($deal, $contact, $company, $regular),
                'kyc_requirements' => $this->defaultStartKycRequirements($deal, $contact, $company),
                'engagement_requirements' => $this->defaultStartEngagementRequirements($deal),
                'approval_steps' => $this->defaultStartApprovalSteps($deal, $regular),
                'routing' => $this->defaultStartRouting(),
                'clearance' => $this->defaultStartClearance($deal),
            ]
        );

        if (! $regular) {
            $project->sows()->firstOrCreate(
                ['project_id' => $project->id],
                [
                    'version_number' => '1.0',
                    'date_prepared' => now()->toDateString(),
                    'within_scope_items' => $scopeItems,
                    'out_of_scope_items' => [],
                    'client_confirmation_name' => $clientName,
                    'internal_approval' => $internalApproval,
                    'approval_status' => 'draft',
                    'ntp_status' => 'pending',
                ]
            );
        }

        $project->sowReports()->firstOrCreate(
            ['project_id' => $project->id],
            [
                'version_number' => '1.0',
                'date_prepared' => now()->toDateString(),
                'within_scope_items' => $scopeItems,
                'out_of_scope_items' => [],
                'status_summary' => [
                    'total_main_tasks' => count($scopeItems),
                    'open' => count($scopeItems),
                    'in_progress' => 0,
                    'delayed' => 0,
                    'completed' => 0,
                    'on_hold' => 0,
                ],
                'project_completion_percentage' => 0,
                'key_issues' => null,
                'recommendations' => null,
                'way_forward' => null,
                'client_confirmation_name' => $clientName,
                'internal_approval' => $internalApproval,
            ]
        );

        return $project;
    }

    private function resolveDealCompany(Deal $deal, ?Contact $contact): ?Company
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        $companyName = trim((string) ($deal->company_name ?: $contact?->company_name));
        if ($companyName === '') {
            return null;
        }

        return Company::query()
            ->where('company_name', $companyName)
            ->first();
    }

    private function defaultStartChecklist(Deal $deal, ?Contact $contact, ?Company $company, bool $isRegular = false): array
    {
        $contactApproved = strtolower((string) ($contact?->cif_status ?? '')) === 'approved';
        $bifApproved = Schema::hasTable('company_bifs')
            && strtolower((string) ($company?->latestBif?->status ?? '')) === 'approved';
        $trackerLabel = $isRegular
            ? 'Regular Service Activity Tracker (RSAT)'
            : 'Service Task Activation & Routing Tracker (Start)';

        return [
            ['label' => 'Client Contact Form', 'status' => $deal->contact_id ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Deal Form', 'status' => strtolower((string) $deal->deal_status) === 'approved' ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Business Information Form', 'status' => $bifApproved ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Client Information Form', 'status' => $contactApproved ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => $trackerLabel, 'status' => 'provided', 'auto_checked' => true],
            ['label' => 'Others', 'status' => 'pending', 'auto_checked' => false],
        ];
    }

    private function defaultStartKycRequirements(Deal $deal, ?Contact $contact, ?Company $company): array
    {
        $kycContext = $this->buildStartKycContext($deal, $contact, $company);
        $contactApproved = $kycContext['contact_approved'];
        $bifApproved = strtolower((string) ($company?->latestBif?->status ?? '')) === 'approved';
        $organization = $this->resolveOrganizationType($deal, $contact, $company);
        $contactDocuments = $kycContext['contact_documents'];
        $bifDocuments = $this->bifRequirementState($company);
        $showForeignRows = $kycContext['show_foreign_rows'];

        $sole = [
            ['label' => 'Client Contact Form', 'status' => $deal->contact_id ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Client Information Form', 'status' => $contactApproved ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => '2 Valid Government IDs', 'status' => $contactDocuments['two_valid_ids'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'TIN ID', 'status' => $contactDocuments['tin_proof'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'DTI Certificate of Registration (if Sole Prop)', 'status' => $bifApproved && $bifDocuments['sole_dti_certificate_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'BIR Certificate of Registration (COR)', 'status' => $bifApproved && $bifDocuments['sole_bir_cor_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Business Permit / Mayor\'s Permit', 'status' => $bifApproved && $bifDocuments['sole_business_permit_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Proof of Billing (Residential)', 'status' => $bifApproved && $bifDocuments['sole_proof_of_billing_residential_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Proof of Billing (Business Address if different)', 'status' => $bifApproved && $bifDocuments['sole_proof_of_billing_business_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Special Power of Attorney (if representative)', 'status' => $bifApproved && $bifDocuments['sole_spa_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Representative\'s 2 Valid IDs (if applicable)', 'status' => $bifApproved && $bifDocuments['sole_representative_ids_document'] ? 'provided' : 'pending', 'auto_checked' => true],
        ];
        if ($showForeignRows) {
            $sole[] = ['label' => 'If Foreigner: Passport (Bio Page)', 'status' => $contactDocuments['passport_proof'] ? 'provided' : 'pending', 'auto_checked' => true];
            $sole[] = ['label' => 'If Foreigner: Valid Visa / ACR I-Card', 'status' => $contactDocuments['visa_or_acr'] ? 'provided' : 'pending', 'auto_checked' => true];
        }

        $juridical = [
            ['label' => 'Client Contact Form', 'status' => $deal->contact_id ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Business Information Form', 'status' => $bifApproved ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => '2 Valid Government IDs (Authorized Signatory)', 'status' => $contactDocuments['two_valid_ids'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'TIN ID (Authorized Signatory)', 'status' => $contactDocuments['tin_proof'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'SEC / CDA Certificate of Registration', 'status' => $bifApproved && $bifDocuments['juridical_sec_cda_certificate_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'BIR Certificate of Registration (COR)', 'status' => $bifApproved && $bifDocuments['juridical_bir_cor_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Business Permit / Mayor\'s Permit', 'status' => $bifApproved && $bifDocuments['juridical_business_permit_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Articles of Incorporation / Partnership', 'status' => $bifApproved && $bifDocuments['juridical_articles_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'By-Laws', 'status' => $bifApproved && $bifDocuments['juridical_bylaws_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Latest General Information Sheet (GIS)', 'status' => $bifApproved && $bifDocuments['juridical_gis_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Appointment of Officers (for OPC, if applicable)', 'status' => $bifApproved && $bifDocuments['juridical_appointment_of_officers_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Secretary Certificate OR Board Resolution', 'status' => $bifApproved && $bifDocuments['juridical_secretary_certificate_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Ultimate Beneficial Owner (UBO) Declaration', 'status' => $bifApproved && $bifDocuments['juridical_ubo_declaration_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Proof of Billing (Company Address)', 'status' => $bifApproved && $bifDocuments['juridical_company_billing_document'] ? 'provided' : 'pending', 'auto_checked' => true],
            ['label' => 'Proof of Billing (Authorized Representative, if applicable)', 'status' => $bifApproved && $bifDocuments['juridical_representative_billing_document'] ? 'provided' : 'pending', 'auto_checked' => true],
        ];
        if ($showForeignRows) {
            $juridical[] = ['label' => 'If Foreign Signatory/Director: Passport (Bio Page)', 'status' => $contactDocuments['passport_proof'] ? 'provided' : 'pending', 'auto_checked' => true];
            $juridical[] = ['label' => 'If Foreign Signatory/Director: Valid Visa / ACR I-Card', 'status' => $contactDocuments['visa_or_acr'] ? 'provided' : 'pending', 'auto_checked' => true];
        }

        return [
            'organization_type' => $organization,
            'sole' => $organization === 'sole_proprietorship' ? $sole : [],
            'juridical' => $this->isJuridicalOrganization($organization) ? $juridical : [],
        ];
    }

    private function defaultStartEngagementRequirements(Deal $deal): array
    {
        $actions = collect($this->normalizeListValue($deal->required_actions))
            ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): array => [
                'number' => null,
                'requirement' => $item,
                'notes' => '',
                'purpose' => 'Engagement-specific requirement',
                'provided_by' => 'Client',
                'submitted_to' => 'Sales & Marketing',
                'assigned_to' => $deal->assigned_associate ?: $deal->assigned_consultant ?: 'Assigned team',
                'timeline' => 'To be scheduled',
            ])
            ->values()
            ->all();

        if ($actions !== []) {
            return $actions;
        }

        return [[
            'number' => null,
            'requirement' => 'Upload signed client confirmation file when available',
            'notes' => '',
            'purpose' => 'Client confirmation',
            'provided_by' => 'Client',
            'submitted_to' => 'Sales & Marketing',
            'assigned_to' => $deal->assigned_consultant ?: 'Sales & Marketing',
            'timeline' => 'Before NTP',
        ]];
    }

    private function defaultStartApprovalSteps(Deal $deal, bool $isRegular = false): array
    {
        $sales = $deal->internal_sales_marketing ?: 'Sales & Marketing';
        $leadConsultant = $deal->assigned_consultant ?: 'Lead Consultant';
        $leadAssociate = $deal->assigned_associate ?: 'Lead Associate';
        $trackerLabel = $isRegular
            ? 'Regular Service Activity Tracker (RSAT)'
            : 'Service Task Activation & Routing Tracker (Start)';

        return [
            ['requirement' => 'Client Contact Form', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Deal Form', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Business Information Form', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Client Information Form', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => $trackerLabel, 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Engagement-Specific Requirement', 'responsible_person' => $sales.'/'.$leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Proposal/Contract', 'responsible_person' => $sales.'/'.$leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Final Quote', 'responsible_person' => $leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Invoice-Downpayment/Advance', 'responsible_person' => 'Finance', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Clearance', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Turn Over', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
        ];
    }

    private function defaultStartRouting(): array
    {
        return [
            ['role' => 'Admin', 'status' => 'pending'],
            ['role' => 'Lead Consultant', 'status' => 'pending'],
            ['role' => 'Lead Associate', 'status' => 'pending'],
            ['role' => 'Sales & Marketing', 'status' => 'pending'],
        ];
    }

    private function defaultStartClearance(Deal $deal): array
    {
        return [
            'assigned_team_lead' => '',
            'assigned_team_lead_signature' => '',
            'lead_consultant_confirmed' => $deal->assigned_consultant ?: '',
            'lead_consultant_signature' => '',
            'lead_associate_assigned' => $deal->assigned_associate ?: '',
            'lead_associate_signature' => '',
            'sales_marketing' => $deal->internal_sales_marketing ?: '',
            'sales_marketing_signature' => '',
            'record_custodian_name' => 'Record Custodian',
            'record_custodian_signature' => '',
            'date_recorded' => now()->toDateString(),
            'date_signed' => '',
        ];
    }

    private function defaultScopeItems(Deal $deal): array
    {
        $services = collect($this->normalizeListValue($deal->services))
            ->merge($this->normalizeListValue($deal->products))
            ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
            ->values();

        if ($services->isEmpty() && filled($deal->scope_of_work)) {
            $services = collect(preg_split('/[\r\n,;]+/', (string) $deal->scope_of_work))
                ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
                ->values();
        }

        return $services
            ->take(5)
            ->map(fn ($item): array => [
                'main_task_description' => trim((string) $item),
                'sub_task_description' => 'To be defined during planning',
                'responsible' => $deal->assigned_consultant ?: 'Project Team',
                'duration' => $deal->estimated_duration ?: 'TBD',
                'start_date' => optional($deal->planned_start_date)->format('Y-m-d'),
                'end_date' => optional($deal->estimated_completion_date)->format('Y-m-d'),
                'status' => 'Pending',
                'remarks' => null,
            ])
            ->values()
            ->all();
    }

    private function defaultInternalApprovalPayload(Deal $deal): array
    {
        return [
            'prepared_by' => $deal->assigned_consultant,
            'reviewed_by' => 'Admin',
            'referred_by_closed_by' => null,
            'sales_marketing' => $deal->internal_sales_marketing ?: null,
            'lead_consultant' => $deal->assigned_consultant,
            'lead_associate_assigned' => $deal->assigned_associate,
            'finance' => 'Finance',
            'president' => 'John Kelly Abalde',
            'record_custodian' => 'Record Custodian',
            'date_recorded' => now()->toDateString(),
            'date_signed' => null,
        ];
    }

    private function normalizeListValue(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item): string => trim((string) $item))
                ->filter()
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/[,;\r\n]+/', $value))
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveOrganizationType(Deal $deal, ?Contact $contact, ?Company $company): string
    {
        $organization = strtolower(trim((string) ($company?->latestBif?->business_organization
            ?: $company?->primaryContact?->business_type_organization
            ?: $company?->primaryContact?->organization_type
            ?: $contact?->business_type_organization
            ?: $contact?->organization_type
            ?: '')));

        if ($organization === '' && $company) {
            return 'corporation';
        }

        return $organization === '' ? 'unknown' : $organization;
    }

    private function isJuridicalOrganization(string $organization): bool
    {
        return in_array($organization, ['partnership', 'corporation', 'cooperative', 'ngo', 'other', 'opc'], true);
    }

    private function buildStartKycContext(Deal $deal, ?Contact $contact, ?Company $company): array
    {
        $organization = $this->resolveOrganizationType($deal, $contact, $company);
        $contacts = collect();

        if ($contact) {
            $contacts->push($contact);
        }

        if ($company?->primaryContact && (! $contact || $company->primaryContact->id !== $contact->id)) {
            $contacts->push($company->primaryContact);
        }

        $contactDocuments = [
            'two_valid_ids' => false,
            'tin_proof' => false,
            'passport_proof' => false,
            'visa_or_acr' => false,
        ];
        $contactApproved = false;
        $showForeignRows = false;

        foreach ($contacts as $candidate) {
            $contactApproved = $contactApproved || strtolower((string) ($candidate?->cif_status ?? '')) === 'approved';
            $candidateDocuments = $this->contactRequirementState($candidate);

            foreach (array_keys($contactDocuments) as $key) {
                $contactDocuments[$key] = $contactDocuments[$key] || $candidateDocuments[$key];
            }

            $showForeignRows = $showForeignRows || $this->contactHasForeignDetails($candidate);
        }

        if ($this->isJuridicalOrganization($organization)) {
            $showForeignRows = $showForeignRows
                || strtolower((string) ($company?->latestBif?->nationality_status ?? '')) === 'foreign'
                || $this->valueImpliesForeign((string) ($company?->latestBif?->authorized_signatory_nationality ?? ''));
        }

        return [
            'contact_documents' => $contactDocuments,
            'contact_approved' => $contactApproved,
            'show_foreign_rows' => $showForeignRows,
        ];
    }

    private function contactRequirementState(?Contact $contact): array
    {
        $documents = [];
        if ($contact?->id) {
            if (Schema::hasColumn('contacts', 'kyc_requirement_documents')) {
                $documents = (array) ($contact->kyc_requirement_documents ?? []);
            }

            if ($documents === []) {
                foreach ([
                    'contact-kyc-data/'.$contact->id.'-requirements.json',
                    'contact-cif-data/'.$contact->id.'-kyc-requirements.json',
                ] as $kycPath) {
                    if (Storage::disk('local')->exists($kycPath)) {
                        $documents = json_decode((string) Storage::disk('local')->get($kycPath), true) ?: [];
                        break;
                    }
                }
            }

            $cifPath = 'contact-cif-data/'.$contact->id.'-documents.json';
            if (Storage::disk('local')->exists($cifPath)) {
                $cifDocuments = json_decode((string) Storage::disk('local')->get($cifPath), true) ?: [];
                if (empty($documents['two_valid_ids']) && isset($cifDocuments['valid_id'])) {
                    $documents['two_valid_ids'] = [$cifDocuments['valid_id']];
                }
                if (empty($documents['tin_proof']) && isset($cifDocuments['tin_document'])) {
                    $documents['tin_proof'] = [$cifDocuments['tin_document']];
                }
            }
        }

        $twoIds = array_values(array_filter((array) ($documents['two_valid_ids'] ?? []), 'is_array'));
        $tinProof = array_values(array_filter((array) ($documents['tin_proof'] ?? []), 'is_array'));
        $passport = array_values(array_filter((array) ($documents['passport_proof'] ?? []), 'is_array'));
        $visa = array_values(array_filter((array) ($documents['visa_proof'] ?? []), 'is_array'));
        $acr = array_values(array_filter((array) ($documents['acr_card_proof'] ?? []), 'is_array'));

        return [
            'two_valid_ids' => count($twoIds) >= 2,
            'tin_proof' => count($tinProof) > 0,
            'passport_proof' => count($passport) > 0,
            'visa_or_acr' => count($visa) > 0 || count($acr) > 0,
        ];
    }

    private function contactHasForeignDetails(?Contact $contact): bool
    {
        if (! $contact?->id) {
            return false;
        }

        $cifData = Schema::hasColumn('contacts', 'cif_data')
            ? (array) ($contact->cif_data ?? [])
            : [];

        if ($cifData === []) {
            foreach ([
                'contact-cif-data/'.$contact->id.'.json',
                'contact-cif-data/'.$contact->id.'-data.json',
            ] as $cifPath) {
                if (Storage::disk('local')->exists($cifPath)) {
                    $cifData = json_decode((string) Storage::disk('local')->get($cifPath), true) ?: [];
                    break;
                }
            }
        }

        return in_array(strtolower((string) ($cifData['citizenship_type'] ?? '')), ['foreigner', 'dual_citizen'], true)
            || strtolower((string) ($contact->ownership_flag ?? '')) === 'foreign-owned business'
            || filled($contact->foreign_business_nature);
    }

    private function valueImpliesForeign(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return $normalized !== '' && ! in_array($normalized, ['filipino', 'philippines', 'philippine', 'ph'], true);
    }

    private function bifRequirementState(?Company $company): array
    {
        $documents = (array) ($company?->latestBif?->client_requirement_documents ?? []);
        $keys = [
            'sole_dti_certificate_document',
            'sole_bir_cor_document',
            'sole_business_permit_document',
            'sole_proof_of_billing_residential_document',
            'sole_proof_of_billing_business_document',
            'sole_spa_document',
            'sole_representative_ids_document',
            'juridical_sec_cda_certificate_document',
            'juridical_bir_cor_document',
            'juridical_business_permit_document',
            'juridical_articles_document',
            'juridical_bylaws_document',
            'juridical_gis_document',
            'juridical_appointment_of_officers_document',
            'juridical_secretary_certificate_document',
            'juridical_ubo_declaration_document',
            'juridical_company_billing_document',
            'juridical_representative_billing_document',
        ];

        $state = [];
        foreach ($keys as $key) {
            $document = data_get($documents, $key);
            if ($key === 'sole_representative_ids_document') {
                $files = array_values(array_filter((array) $document, fn ($item) => is_array($item) && filled($item['path'] ?? $item['file_path'] ?? null)));
                $state[$key] = count($files) >= 2;
                continue;
            }

            $state[$key] = filled(data_get($document, 'path') ?? data_get($document, 'file_path'));
        }

        return $state;
    }
}
