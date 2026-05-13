<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealStage;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectNtp;
use App\Models\ProjectSow;
use App\Models\ProjectSowReport;
use App\Models\ProjectStart;
use App\Models\FormTemplate;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use GeneratesPdfPreview;

    private const CLIENT_LINK_TTL_DAYS = 14;

    private const FALLBACK_SERVICE_AREA_OPTIONS = [
        'Corporate & Regulatory Advisory',
        'Governance & Policy Advisory',
        'People & Talent Solutions',
        'Strategic Situations Advisory',
        'Accounting & Compliance Advisory',
        'Business Strategy & Process Advisory',
        'Learning & Capability Development',
        'Others',
    ];

    private const FALLBACK_SERVICE_GROUPS = [
        'Corporate & Regulatory Advisory' => [
            'Business Registration (SEC / DTI / BIR)',
            'Business Permit Processing / Renewal',
            'Regulatory Compliance',
            'Loan Application Assistance',
            'Foreign Business Entry Support',
        ],
        'Accounting & Compliance Advisory' => [
            'Bookkeeping Services',
            'Tax Filing & Compliance (BIR)',
            'AFS Preparation',
            'Audit Support / Coordination',
            'Accounting Services',
        ],
        'Governance & Policy Advisory' => [
            'Corporate Secretary Services',
            'Corporate Officers Services',
            'Policy Development (HR, Finance, Ops)',
            'Board Resolutions & Minutes',
            'Risk & Internal Control Setup',
        ],
        'Business Strategy & Process Advisory' => [
            'Business Consulting / Strategy',
            'Process Improvement / SOP Development',
            'Organizational Structuring',
            'Digital Transformation',
            'Financial Planning & Analysis',
        ],
        'Strategic Situations Advisory' => [
            'Corporate Deadlock Resolution',
            'Crisis Assessment & Stabilization',
            'Business Restructuring Strategy',
            'Stakeholder Negotiation Support',
            'High-Risk / Complex Case Advisory',
        ],
        'People & Talent Solutions' => [
            'Recruitment & Hiring Support',
            'HR Structuring & Organization Design',
            'KPI & Performance Management Systems',
            'HR Documentation & Contracts',
            'Executive / Virtual Assistant Support',
        ],
        'Learning & Capability Development' => [
            'Accounting & Compliance Training',
            'Corporate Governance Workshops',
            'Business & Strategy Training',
            'Client Capability Development Programs',
            'JKNC Academy Courses',
        ],
    ];

    public function index(Request $request): View
    {
        $projects = collect();
        $contactRecords = [];
        $companyRecords = [];
        $dealRecords = [];
        $sowTemplates = collect();
        $serviceCatalog = $this->projectServiceCatalog();
        $productCatalog = $this->projectProductCatalog();

        if (Schema::hasTable('projects')) {
            $projects = Project::query()
                ->with(['deal:id,deal_code', 'company:id,company_name'])
                ->latest()
                ->get()
                ->map(fn (Project $project): Project => $this->normalizeProjectCompletionState($project))
                ->filter(fn (Project $project): bool => ! Str::contains(Str::lower(trim((string) $project->engagement_type)), 'regular'))
                ->values();
        }

        try {
            $contactRecords = $this->projectContactRecords();
            $companyRecords = $this->projectCompanyRecords();
            $dealRecords = $this->projectDealRecords();
        } catch (Throwable) {
            $contactRecords = [];
            $companyRecords = [];
            $dealRecords = [];
        }

        if (Schema::hasTable('form_templates')) {
            $sowTemplates = $this->projectSowTemplateQuery()
                ->orderBy('name')
                ->get();
        }

        $stats = [
            'all' => $projects->count(),
            'start' => $projects->whereIn('current_phase', ['Start', 'SOW'])->count(),
            'in_progress' => $projects->filter(fn (Project $project): bool => in_array($project->status, ['In Progress', 'Execution', 'Reporting', 'Delivery'], true)
                || in_array($project->current_phase, ['In Progress', 'Execution', 'Reporting', 'Delivery'], true))->count(),
            'active' => $projects->whereIn('status', ['Start', 'SOW', 'In Progress', 'For NTP Approval', 'Execution', 'Reporting', 'Delivery'])->count(),
            'completed' => $projects->where('status', 'Completed')->count(),
        ];

        return view('project.index', [
            'projects' => $projects,
            'stats' => $stats,
            'contactRecords' => $contactRecords,
            'companyRecords' => $companyRecords,
            'dealRecords' => $dealRecords,
            'sowTemplates' => $sowTemplates,
            'serviceAreaOptions' => $serviceCatalog['serviceAreaOptions'],
            'serviceGroups' => $serviceCatalog['serviceGroups'],
            'productOptionsByServiceArea' => $productCatalog['productOptionsByServiceArea'],
        ]);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_mode' => ['nullable', Rule::in(['manual', 'deal'])],
            'deal_id' => ['nullable', 'integer', 'exists:deals,id', 'unique:projects,deal_id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'service_area_options' => ['nullable', 'array'],
            'service_area_options.*' => ['nullable', 'string', 'max:255'],
            'service_area_other' => ['nullable', 'array'],
            'service_area_other.*' => ['nullable', 'string', 'max:255'],
            'service_options' => ['nullable', 'array'],
            'service_options.*' => ['nullable', 'string', 'max:255'],
            'services_other' => ['nullable', 'array'],
            'services_other.*' => ['nullable', 'string', 'max:255'],
            'product_options' => ['nullable', 'array'],
            'product_options.*' => ['nullable', 'string', 'max:255'],
            'products_other_entries' => ['nullable', 'array'],
            'products_other_entries.*' => ['nullable', 'string', 'max:255'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'services' => ['nullable', 'string', 'max:1000'],
            'products' => ['nullable', 'string', 'max:1000'],
            'planned_start_date' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date'],
            'assigned_project_manager' => ['nullable', 'string', 'max:255'],
            'assigned_consultant' => ['nullable', 'string', 'max:255'],
            'assigned_associate' => ['nullable', 'string', 'max:255'],
            'client_confirmation_name' => ['nullable', 'string', 'max:255'],
            'scope_summary' => ['nullable', 'string', 'max:2000'],
            'engagement_requirements_text' => ['nullable', 'string', 'max:4000'],
            'template_id' => Schema::hasTable('form_templates')
                ? ['nullable', 'integer', Rule::exists('form_templates', 'id')->where(function ($query) {
                    $query->where('type', 'project_sow');

                    if (Schema::hasColumn('form_templates', 'status')) {
                        $query->where('status', 'approved');
                    }
                })]
                : ['nullable'],
        ]);

        $validated['service_area'] = $this->stringifySelectedValues(
            $validated['service_area_options'] ?? [],
            $validated['service_area_other'] ?? [],
            'Others: '
        ) ?: ($validated['service_area'] ?? null);

        $validated['services'] = $this->stringifySelectedValues(
            $validated['service_options'] ?? [],
            $validated['services_other'] ?? [],
            'Custom: ',
            ['Others']
        ) ?: ($validated['services'] ?? null);

        $validated['products'] = $this->stringifySelectedValues(
            $validated['product_options'] ?? [],
            $validated['products_other_entries'] ?? [],
            'Custom: ',
            ['Others']
        ) ?: ($validated['products'] ?? null);

        $linkedDeal = ! empty($validated['deal_id']) ? Deal::query()->find($validated['deal_id']) : null;
        $linkedContact = ! empty($validated['contact_id']) ? Contact::query()->find($validated['contact_id']) : null;
        $linkedCompany = ! empty($validated['company_id']) ? Company::query()->find($validated['company_id']) : null;

        if ($linkedDeal) {
            $linkedContact ??= $linkedDeal->contact()->first();
        }

        if (! $linkedCompany && $linkedDeal) {
            $linkedCompany = Company::query()
                ->where('company_name', $linkedDeal->company_name ?: $linkedContact?->company_name)
                ->first();
        }

        if (! $linkedCompany && $linkedContact && filled($linkedContact->company_name)) {
            $linkedCompany = Company::query()->where('company_name', $linkedContact->company_name)->first();
        }

        $resolvedClientName = trim(collect([
            $validated['client_name'] ?? null,
        ])->filter()->implode(''));

        if ($resolvedClientName === '') {
            $resolvedClientName = trim(collect([
                $linkedDeal?->first_name ?: $linkedContact?->first_name,
                $linkedDeal?->middle_name ?: $linkedContact?->middle_name,
                $linkedDeal?->last_name ?: $linkedContact?->last_name,
            ])->filter()->implode(' '));
        }

        $resolvedBusinessName = trim((string) ($validated['business_name']
            ?? $linkedDeal?->company_name
            ?? $linkedContact?->company_name
            ?? $linkedCompany?->company_name
            ?? ''));

        $resolvedServiceArea = $validated['service_area']
            ?? $linkedDeal?->service_area
            ?? null;

        $resolvedServices = $validated['services']
            ?? $linkedDeal?->services
            ?? null;

        $resolvedProducts = $validated['products']
            ?? $linkedDeal?->products
            ?? null;

        $resolvedScopeSummary = $validated['scope_summary']
            ?? $linkedDeal?->scope_of_work
            ?? null;

        $selectedTemplate = ! empty($validated['template_id'])
            ? $this->projectSowTemplateQuery()
                ->find($validated['template_id'])
            : null;
        $templatePayload = (array) ($selectedTemplate?->payload ?? []);

        $project = Project::query()->create([
            'deal_id' => $linkedDeal?->id,
            'contact_id' => $linkedContact?->id,
            'company_id' => $linkedCompany?->id,
            'name' => $validated['name'],
            'engagement_type' => 'Project',
            'status' => 'SOW',
            'current_phase' => 'SOW',
            'current_step' => 'SOW Preparation',
            'planned_start_date' => $validated['planned_start_date'] ?? null,
            'target_completion_date' => $validated['target_completion_date'] ?? null,
            'assigned_project_manager' => $validated['assigned_project_manager'] ?? null,
            'assigned_consultant' => $validated['assigned_consultant'] ?? null,
            'assigned_associate' => $validated['assigned_associate'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'business_name' => $validated['business_name'] ?? null,
            'service_area' => $validated['service_area'] ?? null,
            'services' => $validated['services'] ?? null,
            'products' => $validated['products'] ?? null,
            'scope_summary' => $validated['scope_summary'] ?? null,
            'client_confirmation_name' => $validated['client_confirmation_name'] ?? ($validated['client_name'] ?? null),
            'metadata' => [
                'created_from' => $linkedDeal ? 'project_dashboard_linked_deal' : 'project_dashboard_manual',
                'source_mode' => $validated['source_mode'] ?? ($linkedDeal ? 'deal' : 'manual'),
                'template_id' => $selectedTemplate?->id,
                'template_name' => $selectedTemplate?->name,
            ],
            'opened_at' => now(),
        ]);

        ProjectStart::query()->create([
            'project_id' => $project->id,
            'form_date' => now()->toDateString(),
            'date_started' => $validated['planned_start_date'] ?? now()->toDateString(),
            'status' => 'pending',
            'checklist' => $this->manualStartChecklist(),
            'kyc_requirements' => [
                'organization_type' => 'unknown',
                'sole' => [],
                'juridical' => [],
            ],
            'engagement_requirements' => $this->buildManualRequirementRows(
                $validated['engagement_requirements_text'] ?? '',
                $validated['assigned_associate'] ?? ($validated['assigned_consultant'] ?? ($validated['assigned_project_manager'] ?? 'Assigned team')),
                'START intake'
            ),
            'approval_steps' => $this->manualStartApprovalSteps(
                $validated['assigned_consultant'] ?? null,
                $validated['assigned_associate'] ?? null
            ),
            'routing' => $this->manualRoutingRows(),
            'clearance' => $this->manualClearancePayload(
                $validated['assigned_consultant'] ?? null,
                $validated['assigned_associate'] ?? null
            ),
        ]);

        $scopeRows = collect($templatePayload['within_scope_items'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();
        if ($scopeRows === []) {
            $scopeRows = $this->manualScopeRows($validated);
        }
        $outScopeRows = collect($templatePayload['out_of_scope_items'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();
        $internalApproval = $this->mergeProjectTemplateApprovalPayload(
            (array) ($templatePayload['internal_approval'] ?? []),
            $validated
        );

        ProjectSow::query()->create([
            'project_id' => $project->id,
            'version_number' => (string) ($templatePayload['version_number'] ?? '1.0'),
            'date_prepared' => now()->toDateString(),
            'within_scope_items' => $scopeRows,
            'out_of_scope_items' => $outScopeRows,
            'client_confirmation_name' => $project->client_confirmation_name,
            'internal_approval' => $internalApproval,
            'approval_status' => in_array(($templatePayload['approval_status'] ?? 'draft'), ['draft', 'pending_review', 'approved'], true)
                ? (string) $templatePayload['approval_status']
                : 'draft',
            'ntp_status' => in_array(($templatePayload['ntp_status'] ?? 'pending'), ['pending', 'approved', 'rejected'], true)
                ? (string) $templatePayload['ntp_status']
                : 'pending',
        ]);

        ProjectSowReport::query()->create([
            'project_id' => $project->id,
            'version_number' => (string) ($templatePayload['version_number'] ?? '1.0'),
            'date_prepared' => now()->toDateString(),
            'within_scope_items' => $scopeRows,
            'out_of_scope_items' => $outScopeRows,
            'status_summary' => [
                'total_main_tasks' => count($scopeRows),
                'open' => count($scopeRows),
                'in_progress' => 0,
                'delayed' => 0,
                'completed' => 0,
                'on_hold' => 0,
            ],
            'project_completion_percentage' => 0,
            'client_confirmation_name' => $project->client_confirmation_name,
            'internal_approval' => $internalApproval,
        ]);

        return redirect()
            ->route('project.show', ['project' => $project, 'tab' => 'sow'])
            ->with('success', 'Project created and opened in Scope of Work.');
    }

    public function show(Request $request, Project $project): View
    {
        $project = $this->normalizeProjectCompletionState($project);
        $payload = $this->buildProjectDocumentPayload($project);
        extract($payload);
        $sowAutoReportSettings = $this->projectAutoReportSettings($project, 'sow');
        $ntpRecord = $project->ntps()->latest()->first();
        $coc = null;
        $sowTemplates = Schema::hasTable('form_templates')
            ? $this->projectSowTemplateQuery()->orderBy('name')->get()
            : collect();
        $tab = in_array((string) $request->query('tab', 'sow'), ['sow', 'report'], true)
            ? (string) $request->query('tab', 'sow')
            : 'sow';
        $projectLocked = $this->isProjectCompleted($project);

        if ($tab === 'sow') {
            [, $coc] = $this->buildProjectCocPreviewData($project);
        }

        return view('project.show', compact('project', 'start', 'sow', 'report', 'ntpRecord', 'sowTemplates', 'tab', 'coc', 'sowAutoReportSettings', 'projectLocked'));
    }

    public function updateSowAutoReportSettings(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        $metadata = (array) ($project->metadata ?? []);
        $current = $this->projectAutoReportSettings($project, 'sow');

        Arr::set($metadata, 'auto_report.sow', [
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'day_of_month' => (int) $validated['day_of_month'],
            'last_generated_on' => $current['last_generated_on'],
        ]);

        $project->forceFill(['metadata' => $metadata])->save();

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', 'SOW auto-report schedule updated.');
    }

    public function downloadStartPdf(Project $project)
    {
        $payload = $this->buildProjectDocumentPayload($project);
        extract($payload);
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');
        $startKyc = (array) ($start?->kyc_requirements ?? []);
        $startKycOrganization = (string) ($startKyc['organization_type'] ?? 'unknown');
        $startKycSole = collect($startKyc['sole'] ?? []);
        $startKycJuridical = collect($startKyc['juridical'] ?? []);
        $startReqs = collect($start?->engagement_requirements ?? [])->whenEmpty(fn () => collect([['number' => 1, 'requirement' => '', 'notes' => '', 'purpose' => '', 'provided_by' => '', 'submitted_to' => '', 'assigned_to' => '', 'timeline' => '']]));
        $startApprovalSteps = collect($start?->approval_steps ?? [])->whenEmpty(fn () => collect([
            ['requirement' => 'Client Contact Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Deal Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Business Information Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Client Information Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Service Task Activation & Routing Tracker (Start)', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Engagement-Specific Requirement', 'responsible_person' => 'Sales & Marketing/Consultant/Associate', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Proposal/Contract', 'responsible_person' => 'Sales & Marketing/Lead Consultant/Lead Associate', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Final Quote', 'responsible_person' => 'Lead Consultant/Lead Associate', 'name_and_signature' => '', 'date_time_done' => ''],
        ]));
        $startClearance = (array) ($start?->clearance ?? []);

        $targetPath = 'generated-previews/project/start/' . ($project->project_code ?: $project->id) . '-start-form.pdf';
        $pdfPath = $this->generatePdfPreview('project.pdf.start', compact(
            'project',
            'start',
            'contactName',
            'startKycOrganization',
            'startKycSole',
            'startKycJuridical',
            'startReqs',
            'startApprovalSteps',
            'startClearance'
        ), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate START PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    public function downloadServiceMemoPdf(Project $project): RedirectResponse
    {
        $payload = $this->buildProjectDocumentPayload($project);
        extract($payload);

        abort_unless($start && strtolower((string) $start->status) === 'approved' && $start->approved_at, 404, 'Service memo is only available for approved START records.');

        $pdfPath = $this->ensureServiceMemoGenerated($project, $start, $sow);

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    public function updateStart(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateStartPayload($request);
        $this->persistStartForm($request, $project, $validated);

        return $this->redirectToStartTarget($request, $project, 'START form updated successfully.');
    }

    public function submitStartForApproval(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateStartPayload($request);
        $start = $this->persistStartForm($request, $project, $validated);

        if (strtolower((string) $start->status) === 'approved') {
            return redirect()
                ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
                ->with('error', 'This START form is already approved.');
        }

        $start->forceFill([
            'status' => 'pending_approval',
            'approved_at' => null,
            'approved_by_name' => null,
            'rejected_at' => null,
            'rejected_by_name' => null,
        ])->save();

        return $this->redirectToStartTarget($request, $project, 'START form submitted for admin approval.');
    }

    public function approveStart(Request $request, Project $project): RedirectResponse
    {
        abort_unless($this->canReviewStart($request), 403, 'Unauthorized');

        $start = $this->resolveCurrentStart($project);
        abort_unless($start, 404, 'START form not found.');
        abort_if(strtolower((string) $start->status) !== 'pending_approval', 422, 'Only submitted START forms can be approved.');

        $start->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_name' => $request->user()?->name ?? 'System User',
            'rejected_at' => null,
            'rejected_by_name' => null,
            'rejection_reason' => null,
        ])->save();
        $this->ensureServiceMemoGenerated($project, $start, $project->sows()->latest()->first());
        $this->moveLinkedDealToStage($project, 'Activation');

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'START form approved successfully.');
    }

    public function rejectStart(Request $request, Project $project): RedirectResponse
    {
        abort_unless($this->canReviewStart($request), 403, 'Unauthorized');

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $start = $this->resolveCurrentStart($project);
        abort_unless($start, 404, 'START form not found.');
        abort_if(strtolower((string) $start->status) !== 'pending_approval', 422, 'Only submitted START forms can be rejected.');

        $start->forceFill([
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by_name' => null,
            'rejected_at' => now(),
            'rejected_by_name' => $request->user()?->name ?? 'System User',
            'rejection_reason' => trim((string) ($validated['reason'] ?? '')) ?: 'START form rejected during admin review.',
        ])->save();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'START form rejected.');
    }

    public function updateSow(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $validated = $this->validateSowPayload($request);
        $this->persistSowDocument($request, $project, $validated);

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', 'Scope of Work updated successfully.');
    }

    public function downloadSowPdf(Project $project)
    {
        $project->loadMissing([
            'deal:id,deal_code',
            'company:id,company_name',
            'contact:id,first_name,last_name',
            'sows' => fn ($query) => $query->latest(),
        ]);

        $sow = $project->sows->first();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');
        $within = collect($sow?->within_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();
        $out = collect($sow?->out_of_scope_items ?? [])->filter(fn ($row) => filled($row['main_task_description'] ?? null))->values();

        $targetPath = 'generated-previews/project/sow/' . ($project->project_code ?: $project->id) . '-scope-of-work.pdf';
        $pdfPath = $this->generatePdfPreview('project.pdf.sow', compact(
            'project',
            'sow',
            'contactName',
            'within',
            'out'
        ), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate SOW PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    public function downloadNtpPdf(Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $ntpRecord = $this->generateAndSendProjectNtp($project);

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', 'NTP generated successfully.'.($ntpRecord->client_form_sent_to_email ? ' NTP link sent to '.$ntpRecord->client_form_sent_to_email.'.' : ''))
            ->with('ntp_status', 'Waiting for client signed NTP upload.');
    }

    public function showCocPreview(Project $project): View
    {
        if (! $this->isProjectCompleted($project)) {
            $this->markCocGenerated($project);
        }
        [$project, $coc] = $this->buildProjectCocPreviewData($project);

        return view('project.coc-preview', compact('project', 'coc'));
    }

    public function downloadCocPdf(Project $project)
    {
        if (! $this->isProjectCompleted($project)) {
            $this->markCocGenerated($project);
        }
        [$project, $coc] = $this->buildProjectCocPreviewData($project);

        $pdf = Pdf::loadView('project.coc-preview', compact('project', 'coc'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(Str::slug((string) ($project->project_code ?: 'project')).'-certificate-of-completion.pdf');
    }

    public function approveCoc(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $validated = $this->validateSignedApprovalUpload($request);
        $metadata = (array) ($project->metadata ?? []);
        $existingPath = data_get($metadata, 'coc.signed_attachment_path');

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        $path = $request->file('signed_document')->store("projects/{$project->id}/coc", 'public');

        data_set($metadata, 'coc.generated_at', data_get($metadata, 'coc.generated_at') ?: now()->toDateTimeString());
        data_set($metadata, 'coc.approval_status', 'approved');
        data_set($metadata, 'coc.approved_at', now()->toDateTimeString());
        data_set($metadata, 'coc.approved_by_name', $validated['approval_name'] ?: ($request->user()?->name ?? 'Manual Override'));
        data_set($metadata, 'coc.approval_note', trim((string) ($validated['approval_note'] ?? '')) ?: 'COC manually approved using uploaded signed document.');
        data_set($metadata, 'coc.signed_attachment_path', $path);

        $project->forceFill([
            'status' => 'Completed',
            'current_phase' => 'Completed',
            'current_step' => 'Completed',
            'closed_at' => now(),
            'metadata' => $metadata,
        ])->save();

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', 'Signed COC uploaded and approved. Project marked as completed.');
    }

    public function ntpStatus(Project $project): JsonResponse
    {
        $ntpRecord = $project->ntps()->latest()->first();

        return response()->json($this->buildProjectNtpStatusPayload($project, $ntpRecord));
    }

    public function showNtpSubmission(Project $project): View
    {
        $ntpRecord = $project->ntps()->latest()->firstOrFail();
        $project->loadMissing(['deal:id,deal_code', 'contact:id,first_name,last_name,email', 'company:id,company_name']);
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: 'Client');

        return view('project.ntp-submission', [
            'project' => $project,
            'ntpRecord' => $ntpRecord,
            'ntp' => $ntpRecord->payload ?? [],
            'contactName' => $contactName,
        ]);
    }

    public function clientNtp(string $token): View
    {
        $ntpRecord = $this->findNtpByClientToken($token);
        abort_if($ntpRecord->client_access_expires_at && $ntpRecord->client_access_expires_at->isPast(), 403, 'This NTP link has expired.');
        abort_if($ntpRecord->project && $this->isProjectCompleted($ntpRecord->project), 423, 'This completed project is view-only.');

        $project = $ntpRecord->project()->with(['deal:id,deal_code', 'contact:id,first_name,last_name,email', 'company:id,company_name'])->firstOrFail();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: 'Client');

        return view('documents.client-ntp-form', [
            'project' => $project,
            'ntpRecord' => $ntpRecord,
            'ntp' => $ntpRecord->payload ?? [],
            'contactName' => $contactName,
            'clientFormAction' => route('project.ntp.client.submit', ['token' => $token]),
            'clientDownloadUrl' => route('project.ntp.client.download', ['token' => $token]),
        ]);
    }

    public function submitClientNtp(Request $request, string $token): RedirectResponse
    {
        $ntpRecord = $this->findNtpByClientToken($token);
        abort_if($ntpRecord->client_access_expires_at && $ntpRecord->client_access_expires_at->isPast(), 403, 'This NTP link has expired.');

        $validated = $request->validate([
            'client_approval_name' => ['required', 'string', 'max:255'],
            'client_approval' => ['accepted'],
            'client_response_notes' => ['nullable', 'string', 'max:4000'],
            'client_attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ], [
            'client_approval.accepted' => 'Please confirm approval before submitting.',
            'client_attachment.required' => 'Please upload the signed NTP file before submitting.',
        ]);

        if ($ntpRecord->client_attachment_path && Storage::disk('public')->exists($ntpRecord->client_attachment_path)) {
            Storage::disk('public')->delete($ntpRecord->client_attachment_path);
        }

        $ntpRecord->fill([
            'client_response_status' => 'approved_to_proceed',
            'client_approved_at' => now(),
            'client_approved_name' => $validated['client_approval_name'],
            'client_response_notes' => $validated['client_response_notes'] ?? null,
            'client_attachment_path' => $request->file('client_attachment')->store("projects/{$ntpRecord->project_id}/ntp", 'public'),
        ]);
        $ntpRecord->save();
        $this->markProjectInProgress($ntpRecord->project, 'Client signed NTP submitted');

        return redirect()
            ->route('project.ntp.client.show', ['token' => $token])
            ->with('success', 'Approved to proceed. The signed NTP was submitted successfully.');
    }

    public function manualApproveNtp(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $ntpRecord = $project->ntps()->latest()->firstOrFail();
        $validated = $this->validateSignedApprovalUpload($request);

        $this->replaceClientAttachment(
            $ntpRecord,
            $request,
            "projects/{$project->id}/ntp",
        );

        $ntpRecord->fill([
            'client_response_status' => 'approved_to_proceed',
            'client_approved_at' => now(),
            'client_approved_name' => $validated['approval_name'] ?: ($request->user()?->name ?? 'Manual Override'),
            'client_response_notes' => trim((string) ($validated['approval_note'] ?? '')) ?: 'Approved by manual override using uploaded signed NTP.',
        ]);
        $ntpRecord->save();
        $this->markProjectInProgress($project, 'Signed NTP uploaded manually');

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', 'Signed NTP uploaded and approved manually. Project is now in progress.');
    }

    public function downloadClientNtp(string $token): RedirectResponse
    {
        $ntpRecord = $this->findNtpByClientToken($token);
        abort_if($ntpRecord->client_access_expires_at && $ntpRecord->client_access_expires_at->isPast(), 403, 'This NTP link has expired.');

        return $this->downloadNtpPdfFromRecord($ntpRecord, 'project');
    }

    public function generateSowReport(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $validated = $this->validateSowPayload($request);
        $sow = $this->persistSowDocument($request, $project, $validated);
        $statusSummary = $this->buildReportStatusSummary($validated);

        $report = ProjectSowReport::query()->create([
            'project_id' => $project->id,
            'version_number' => $sow->version_number,
            'date_prepared' => $sow->date_prepared,
            'within_scope_items' => $sow->within_scope_items ?? [],
            'out_of_scope_items' => $sow->out_of_scope_items ?? [],
            'status_summary' => $statusSummary,
            'project_completion_percentage' => $this->calculateCompletionPercentage($statusSummary),
            'key_issues' => $validated['key_issues'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'way_forward' => $validated['way_forward'] ?? null,
            'client_confirmation_name' => $sow->client_confirmation_name,
            'internal_approval' => $sow->internal_approval ?? [],
        ]);

        $recipientEmail = $this->resolveProjectClientEmail($project);
        $emailSentMessage = null;

        if ($recipientEmail !== null && $this->supportsProjectSowReportClientPortal()) {
            $this->sendSowReportClientLink($project, $report, $recipientEmail);
            $emailSentMessage = " Client approval link sent to {$recipientEmail}.";
        }

        return redirect()
            ->route('project.report.preview', ['project' => $project->id, 'report' => $report->id])
            ->with('success', 'SOW report generated successfully.'.$emailSentMessage);
    }

    public function showGeneratedReport(Project $project, ProjectSowReport $report): View
    {
        abort_unless($report->project_id === $project->id, 404);

        $project->loadMissing(['deal:id,deal_code', 'company:id,company_name', 'contact:id,first_name,last_name']);
        $sow = $project->sows()->latest()->first();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');
        $clientEmail = $this->resolveProjectClientEmail($project);

        return view('project.report-preview', compact('project', 'report', 'sow', 'contactName', 'clientEmail'));
    }

    public function sendGeneratedReport(Project $project, ProjectSowReport $report, Request $request): RedirectResponse
    {
        abort_unless($report->project_id === $project->id, 404);
        $this->abortIfProjectCompleted($project);

        if (! $this->supportsProjectSowReportClientPortal()) {
            return redirect()
                ->route('project.report.preview', ['project' => $project->id, 'report' => $report->id])
                ->with('success', 'The report was generated, but client sending is not available until the latest project report migration is run.');
        }

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $recipientEmail = trim((string) $validated['recipient_email']);
        $this->sendSowReportClientLink($project, $report, $recipientEmail);

        return redirect()
            ->route('project.report.preview', ['project' => $project->id, 'report' => $report->id])
            ->with('success', "SOW report client approval link sent to {$recipientEmail}.");
    }

    public function storeSowTemplate(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        abort_if(! Schema::hasTable('form_templates'), 500, 'Templates table is not available.');

        $validated = array_merge(
            $this->validateSowPayload($request),
            $request->validate([
                'template_name' => ['required', 'string', 'max:255'],
            ])
        );

        $sow = $this->persistSowDocument($request, $project, $validated);

        FormTemplate::query()->create([
            'type' => 'project_sow',
            'name' => trim((string) $validated['template_name']),
            'payload' => [
                'version_number' => $sow->version_number,
                'within_scope_items' => $sow->within_scope_items ?? [],
                'out_of_scope_items' => $sow->out_of_scope_items ?? [],
                'internal_approval' => $sow->internal_approval ?? [],
                'approval_status' => $sow->approval_status,
                'ntp_status' => $sow->ntp_status,
            ],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', 'SOW template saved successfully.');
    }

    public function bulkDestroyGeneratedReports(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $validated = $request->validate([
            'selected_reports' => ['required', 'array', 'min:1'],
            'selected_reports.*' => ['required', 'integer'],
        ]);

        $reports = $project->sowReports()
            ->whereIn('id', $validated['selected_reports'])
            ->get();

        foreach ($reports as $report) {
            if ($report->client_attachment_path && Storage::disk('public')->exists($report->client_attachment_path)) {
                Storage::disk('public')->delete($report->client_attachment_path);
            }
        }

        $deletedCount = $project->sowReports()
            ->whereIn('id', $validated['selected_reports'])
            ->delete();

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'report'])
            ->with('success', $deletedCount === 1 ? '1 SOW report deleted successfully.' : "{$deletedCount} SOW reports deleted successfully.");
    }

    public function clientSowReport(string $token): View
    {
        abort_unless($this->supportsProjectSowReportClientPortal(), 404);

        $report = $this->findReportByClientToken($token);
        abort_if($report->client_access_expires_at && $report->client_access_expires_at->isPast(), 403, 'This SOW report link has expired.');
        abort_if($report->project && $this->isProjectCompleted($report->project), 423, 'This completed project is view-only.');

        $project = $report->project()->with(['deal:id,deal_code', 'contact:id,first_name,last_name,email', 'company:id,company_name'])->firstOrFail();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: 'Client');

        return view('project.client-report-form', [
            'project' => $project,
            'report' => $report,
            'contactName' => $contactName,
            'clientFormAction' => route('project.report.client.submit', ['token' => $token]),
        ]);
    }

    public function submitClientSowReport(Request $request, string $token): RedirectResponse
    {
        abort_unless($this->supportsProjectSowReportClientPortal(), 404);

        $report = $this->findReportByClientToken($token);
        abort_if($report->client_access_expires_at && $report->client_access_expires_at->isPast(), 403, 'This SOW report link has expired.');

        $validated = $request->validate([
            'client_approval_name' => ['required', 'string', 'max:255'],
            'client_approval' => ['accepted'],
            'client_response_notes' => ['nullable', 'string', 'max:4000'],
            'client_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ], [
            'client_approval.accepted' => 'Please confirm your approval before submitting.',
        ]);

        if ($request->hasFile('client_attachment')) {
            if ($report->client_attachment_path && Storage::disk('public')->exists($report->client_attachment_path)) {
                Storage::disk('public')->delete($report->client_attachment_path);
            }

            $report->client_attachment_path = $request->file('client_attachment')->store("projects/{$report->project_id}/reports", 'public');
        }

        $report->fill([
            'client_response_status' => 'approved',
            'client_approved_at' => now(),
            'client_approved_name' => $validated['client_approval_name'],
            'client_response_notes' => $validated['client_response_notes'] ?? null,
            'client_confirmation_name' => $validated['client_approval_name'],
        ]);
        $report->save();
        $project = $report->project()->firstOrFail();
        $this->markProjectReadyForCompletion($project, 'Client approved SOW report');

        return redirect()
            ->route('project.report.client.show', ['token' => $token])
            ->with('success', 'Your approval has been recorded successfully.');
    }

    public function manualApproveReport(Request $request, Project $project, ProjectSowReport $report): RedirectResponse
    {
        abort_unless($report->project_id === $project->id, 404);
        $this->abortIfProjectCompleted($project);

        $validated = $this->validateSignedApprovalUpload($request);

        $this->replaceClientAttachment(
            $report,
            $request,
            "projects/{$project->id}/reports",
        );

        $report->fill([
            'client_response_status' => 'approved',
            'client_approved_at' => now(),
            'client_approved_name' => $validated['approval_name'] ?: ($request->user()?->name ?? 'Manual Override'),
            'client_response_notes' => trim((string) ($validated['approval_note'] ?? '')) ?: 'Approved by manual override using uploaded signed report.',
            'client_confirmation_name' => $validated['approval_name'] ?: ($report->client_confirmation_name ?: $project->client_name),
        ]);
        $report->save();
        $this->markProjectReadyForCompletion($project, 'Signed SOW report uploaded manually');

        return redirect()
            ->route('project.report.preview', ['project' => $project->id, 'report' => $report->id])
            ->with('success', 'Signed SOW report uploaded and approved manually. You can now generate the COC.');
    }

    public function downloadClientSowReport(string $token): RedirectResponse
    {
        abort_unless($this->supportsProjectSowReportClientPortal(), 404);

        $report = $this->findReportByClientToken($token);
        abort_if($report->client_access_expires_at && $report->client_access_expires_at->isPast(), 403, 'This SOW report link has expired.');

        $project = $report->project()->with(['deal:id,deal_code', 'contact:id,first_name,last_name', 'company:id,company_name'])->firstOrFail();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');

        $targetPath = 'generated-previews/project/sow-report/' . ($project->project_code ?: $project->id) . '-' . ($report->report_number ?: $report->id) . '.pdf';
        $pdfPath = $this->generatePdfPreview('project.pdf.sow-report', compact('project', 'report', 'contactName'), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate SOW report PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    public function updateReport(Request $request, Project $project): RedirectResponse
    {
        $this->abortIfProjectCompleted($project);

        $validated = $request->validate([
            'version_number' => ['nullable', 'string', 'max:50'],
            'date_prepared' => ['nullable', 'date'],
            'project_completion_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'client_confirmation_name' => ['nullable', 'string', 'max:255'],
            'total_main_tasks' => ['nullable', 'numeric', 'min:0'],
            'open' => ['nullable', 'numeric', 'min:0'],
            'in_progress' => ['nullable', 'numeric', 'min:0'],
            'delayed' => ['nullable', 'numeric', 'min:0'],
            'completed' => ['nullable', 'numeric', 'min:0'],
            'on_hold' => ['nullable', 'numeric', 'min:0'],
            'key_issues' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'way_forward' => ['nullable', 'string'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'reviewed_by' => ['nullable', 'string', 'max:255'],
            'referred_by_closed_by' => ['nullable', 'string', 'max:255'],
            'sales_marketing' => ['nullable', 'string', 'max:255'],
            'lead_consultant' => ['nullable', 'string', 'max:255'],
            'lead_associate_assigned' => ['nullable', 'string', 'max:255'],
            'finance' => ['nullable', 'string', 'max:255'],
            'president' => ['nullable', 'string', 'max:255'],
            'record_custodian' => ['nullable', 'string', 'max:255'],
            'date_recorded' => ['nullable', 'date'],
            'date_signed' => ['nullable', 'date'],
        ]);

        $report = $project->sowReports()->latest()->first() ?: new ProjectSowReport(['project_id' => $project->id]);
        $report->fill([
            'version_number' => $validated['version_number'] ?? null,
            'date_prepared' => $validated['date_prepared'] ?? null,
            'within_scope_items' => $this->buildScopeRows($request, 'within'),
            'out_of_scope_items' => $this->buildScopeRows($request, 'out'),
            'status_summary' => $this->buildReportStatusSummary($validated),
            'project_completion_percentage' => $validated['project_completion_percentage'] ?? null,
            'key_issues' => $validated['key_issues'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'way_forward' => $validated['way_forward'] ?? null,
            'client_confirmation_name' => $validated['client_confirmation_name'] ?? null,
            'internal_approval' => $this->buildInternalApprovalPayload($validated),
        ]);
        $report->project_id = $project->id;
        $report->save();

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'report'])
            ->with('success', 'SOW report updated successfully.');
    }

    private function updateProjectSowReportSummary(Project $project, ProjectSow $sow, array $validated): void
    {
        $report = $project->sowReports()->latest()->first();

        if (! $report) {
            $report = new ProjectSowReport(['project_id' => $project->id]);
            $report->version_number = $sow->version_number;
            $report->date_prepared = $sow->date_prepared;
            $report->within_scope_items = $sow->within_scope_items ?? [];
            $report->out_of_scope_items = $sow->out_of_scope_items ?? [];
            $report->client_confirmation_name = $sow->client_confirmation_name;
            $report->internal_approval = $sow->internal_approval ?? [];
        }

        $report->fill([
            'status_summary' => $this->buildReportStatusSummary($validated),
            'project_completion_percentage' => $validated['project_completion_percentage'] ?? null,
            'key_issues' => $validated['key_issues'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'way_forward' => $validated['way_forward'] ?? null,
        ]);
        $report->project_id = $project->id;
        $report->save();
    }

    private function buildProjectCocPreviewData(Project $project): array
    {
        $project->loadMissing([
            'deal:id,deal_code,engagement_type',
            'company:id,company_name',
            'contact:id,first_name,last_name,email,company_name',
            'sows' => fn ($query) => $query->latest(),
            'sowReports' => fn ($query) => $query->latest('date_prepared')->latest(),
            'ntps' => fn ($query) => $query->latest(),
        ]);

        $sow = $project->sows->first();
        $latestReport = $project->sowReports->first();
        $ntpRecord = $project->ntps->first();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');
        $approvedStartDate = optional($project->planned_start_date)->format('M d, Y') ?: '-';
        $targetCompletionDate = optional($project->target_completion_date)->format('M d, Y') ?: '-';
        $actualCompletionDate = optional($latestReport?->date_prepared)->format('M d, Y')
            ?: optional($latestReport?->updated_at)->format('M d, Y')
            ?: now()->format('M d, Y');
        $leadConsultant = $project->assigned_consultant ?: data_get($sow?->internal_approval, 'lead_consultant') ?: '';
        $associate = $project->assigned_associate ?: data_get($sow?->internal_approval, 'lead_associate_assigned') ?: '';

        $coc = [
            'title' => 'CERTIFICATE OF COMPLETION',
            'form_code' => 'ENG-F-002-v1.0-03.16.26',
            'date_issued' => now()->format('M d, Y'),
            'coc_no' => 'COC-' . now()->format('Y') . '-' . str_pad((string) $project->id, 3, '0', STR_PAD_LEFT),
            'ntp_no' => $ntpRecord?->ntp_number ?: '-',
            'engagement_type' => $project->engagement_type ?: ($project->deal?->engagement_type ?: '-'),
            'condeal_reference_no' => $project->deal?->deal_code ?: '-',
            'client_name' => $contactName,
            'business_name' => $project->business_name ?: ($project->company?->company_name ?: '-'),
            'engagement_reference_label' => 'RSAT / SOW Ref No.:',
            'engagement_reference_no' => $sow?->sow_number ?: '-',
            'approved_start_date' => $approvedStartDate,
            'target_completion_date' => $targetCompletionDate,
            'actual_completion_date' => $actualCompletionDate,
            'client_confirmation_name' => $sow?->client_confirmation_name ?: $contactName,
            'lead_consultant' => $leadConsultant,
            'associate' => $associate,
        ];

        return [$project, $coc];
    }

    private function validateSignedApprovalUpload(Request $request): array
    {
        return $request->validate([
            'approval_name' => ['nullable', 'string', 'max:255'],
            'approval_note' => ['nullable', 'string', 'max:2000'],
            'signed_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ], [
            'signed_document.required' => 'Please upload the signed approval document.',
        ]);
    }

    private function isProjectCompleted(Project $project): bool
    {
        return strcasecmp((string) $project->status, 'Completed') === 0
            || $this->projectHasApprovedCoc($project);
    }

    private function abortIfProjectCompleted(Project $project): void
    {
        abort_if($this->isProjectCompleted($project), 423, 'Completed projects are view-only. Documents can be viewed or downloaded, but no longer edited.');
    }

    private function replaceClientAttachment(ProjectNtp|ProjectSowReport $record, Request $request, string $directory): void
    {
        if ($record->client_attachment_path && Storage::disk('public')->exists($record->client_attachment_path)) {
            Storage::disk('public')->delete($record->client_attachment_path);
        }

        $record->client_attachment_path = $request->file('signed_document')->store($directory, 'public');
    }

    private function markProjectInProgress(?Project $project, string $step): void
    {
        if (! $project) {
            return;
        }

        $project->forceFill([
            'status' => 'In Progress',
            'current_phase' => 'In Progress',
            'current_step' => $step,
        ])->save();
    }

    private function markProjectReadyForCompletion(Project $project, string $step): void
    {
        $project->forceFill([
            'status' => 'Delivery',
            'current_phase' => 'Delivery',
            'current_step' => $step,
        ])->save();
    }

    private function markCocGenerated(Project $project): void
    {
        $metadata = (array) ($project->metadata ?? []);
        data_set($metadata, 'coc.generated_at', data_get($metadata, 'coc.generated_at') ?: now()->toDateTimeString());

        if (! data_get($metadata, 'coc.approval_status')) {
            data_set($metadata, 'coc.approval_status', 'pending');
        }

        $project->forceFill([
            'metadata' => $metadata,
        ])->save();
    }

    private function projectHasApprovedCoc(Project $project): bool
    {
        return strcasecmp((string) data_get($project->metadata ?? [], 'coc.approval_status'), 'approved') === 0;
    }

    private function normalizeProjectCompletionState(Project $project): Project
    {
        if (! $this->projectHasApprovedCoc($project)) {
            return $project;
        }

        $attributes = [];

        if (strcasecmp((string) $project->status, 'Completed') !== 0) {
            $attributes['status'] = 'Completed';
        }

        if (strcasecmp((string) $project->current_phase, 'Completed') !== 0) {
            $attributes['current_phase'] = 'Completed';
        }

        if (strcasecmp((string) $project->current_step, 'Completed') !== 0) {
            $attributes['current_step'] = 'Completed';
        }

        if (! $project->closed_at) {
            $attributes['closed_at'] = data_get($project->metadata ?? [], 'coc.approved_at') ?: now();
        }

        if ($attributes === []) {
            return $project;
        }

        $project->forceFill($attributes);
        $project->save();

        return $project;
    }

    private function buildServiceMemoPayload(Project $project, ProjectStart $start, ?ProjectSow $sow): array
    {
        $project->loadMissing([
            'deal:id,deal_code,engagement_type',
            'company:id,company_name',
            'contact:id,first_name,last_name,email,company_name',
        ]);

        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');

        return [
            'title' => 'SERVICE MEMO',
            'form_code' => 'ENG-F-003-v1.0-03.16.26',
            'date_issued' => optional($start->approved_at)->format('F d, Y') ?: now()->format('F d, Y'),
            'engagement_type' => $project->engagement_type ?: ($project->deal?->engagement_type ?: '-'),
            'start_ref_no' => $start->start_code ?: '-',
            'start_cleared_date' => optional($start->approved_at)->format('F d, Y') ?: '-',
            'condeal_reference_no' => $project->deal?->deal_code ?: '-',
            'client_name' => $contactName,
            'business_name' => $project->business_name ?: ($project->company?->company_name ?: '-'),
            'engagement_reference_no' => $sow?->sow_number ?: '-',
            'approved_start_date' => optional($start->date_started ?: $project->planned_start_date)->format('F d, Y') ?: '-',
            'target_completion_date' => optional($project->target_completion_date)->format('F d, Y') ?: '-',
            'rsat_template' => data_get($project->metadata, 'template_name') ?: '-',
            'sow_template' => data_get($sow?->metadata, 'template_name') ?: ($sow?->version_number ?: '-'),
            'lead_consultant' => $project->assigned_consultant ?: ' ',
            'associate' => $project->assigned_associate ?: ' ',
            'sales_marketing' => 'Sales and Marketing',
            'finance' => 'Finance',
            'office_of_president' => 'Office of the President',
        ];
    }

    private function validateSowPayload(Request $request): array
    {
        return $request->validate([
            'version_number' => ['nullable', 'string', 'max:50'],
            'date_prepared' => ['nullable', 'date'],
            'approval_status' => ['required', 'string', 'max:50'],
            'ntp_status' => ['required', 'string', 'max:50'],
            'client_confirmation_name' => ['nullable', 'string', 'max:255'],
            'client_signed_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'project_completion_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_main_tasks' => ['nullable', 'numeric', 'min:0'],
            'open' => ['nullable', 'numeric', 'min:0'],
            'in_progress' => ['nullable', 'numeric', 'min:0'],
            'delayed' => ['nullable', 'numeric', 'min:0'],
            'completed' => ['nullable', 'numeric', 'min:0'],
            'on_hold' => ['nullable', 'numeric', 'min:0'],
            'key_issues' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'way_forward' => ['nullable', 'string'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'reviewed_by' => ['nullable', 'string', 'max:255'],
            'referred_by_closed_by' => ['nullable', 'string', 'max:255'],
            'sales_marketing' => ['nullable', 'string', 'max:255'],
            'lead_consultant' => ['nullable', 'string', 'max:255'],
            'lead_associate_assigned' => ['nullable', 'string', 'max:255'],
            'finance' => ['nullable', 'string', 'max:255'],
            'president' => ['nullable', 'string', 'max:255'],
            'record_custodian' => ['nullable', 'string', 'max:255'],
            'date_recorded' => ['nullable', 'date'],
            'date_signed' => ['nullable', 'date'],
        ]);
    }

    private function persistSowDocument(Request $request, Project $project, array $validated): ProjectSow
    {
        $sow = $project->sows()->latest()->first() ?: new ProjectSow(['project_id' => $project->id]);
        $sow->fill([
            'version_number' => $validated['version_number'] ?? null,
            'date_prepared' => $validated['date_prepared'] ?? null,
            'within_scope_items' => $this->buildScopeRows($request, 'within'),
            'out_of_scope_items' => $this->buildScopeRows($request, 'out'),
            'client_confirmation_name' => $validated['client_confirmation_name'] ?? null,
            'internal_approval' => $this->buildInternalApprovalPayload($validated),
            'approval_status' => $validated['approval_status'],
            'ntp_status' => $validated['ntp_status'],
        ]);
        $sow->project_id = $project->id;

        if ($request->hasFile('client_signed_attachment')) {
            if ($sow->client_signed_attachment_path && Storage::disk('public')->exists($sow->client_signed_attachment_path)) {
                Storage::disk('public')->delete($sow->client_signed_attachment_path);
            }
            $sow->client_signed_attachment_path = $request->file('client_signed_attachment')->store("projects/{$project->id}/sow", 'public');
        }

        $sow->save();

        return $sow;
    }

    private function projectSowTemplateQuery()
    {
        return FormTemplate::query()
            ->where('type', 'project_sow')
            ->when(
                Schema::hasColumn('form_templates', 'status'),
                fn ($query) => $query->where('status', 'approved')
            );
    }

    private function calculateCompletionPercentage(array $statusSummary): float
    {
        $total = max(0, (int) ($statusSummary['total_main_tasks'] ?? 0));
        $completed = max(0, (int) ($statusSummary['completed'] ?? 0));

        if ($total === 0) {
            return 0;
        }

        return round(($completed / $total) * 100, 2);
    }

    private function buildReportStatusSummary(array $validated): array
    {
        return [
            'total_main_tasks' => (int) ($validated['total_main_tasks'] ?? 0),
            'open' => (int) ($validated['open'] ?? 0),
            'in_progress' => (int) ($validated['in_progress'] ?? 0),
            'delayed' => (int) ($validated['delayed'] ?? 0),
            'completed' => (int) ($validated['completed'] ?? 0),
            'on_hold' => (int) ($validated['on_hold'] ?? 0),
        ];
    }

    private function buildChecklistPayload(Request $request): array
    {
        $labels = (array) $request->input('checklist_label', []);
        $statuses = (array) $request->input('checklist_status', []);

        return collect($labels)->map(function ($label, $index) use ($statuses) {
            $clean = trim((string) $label);
            if ($clean === '') {
                return null;
            }

            return [
                'label' => $clean,
                'status' => in_array($statuses[$index] ?? 'pending', ['provided', 'pending'], true) ? $statuses[$index] : 'pending',
            ];
        })->filter()->values()->all();
    }

    private function buildRequirementRows(Request $request, string $prefix): array
    {
        $requirements = (array) $request->input("{$prefix}_requirement", []);
        $notes = (array) $request->input("{$prefix}_notes", []);
        $purposes = (array) $request->input("{$prefix}_purpose", []);
        $providedBy = (array) $request->input("{$prefix}_provided_by", []);
        $submittedTo = (array) $request->input("{$prefix}_submitted_to", []);
        $assigned = (array) $request->input("{$prefix}_assigned_to", []);
        $timelines = (array) $request->input("{$prefix}_timeline", []);

        return collect($requirements)->map(function ($value, $index) use ($notes, $purposes, $providedBy, $submittedTo, $assigned, $timelines) {
            $requirement = trim((string) $value);
            if ($requirement === '') {
                return null;
            }

            return [
                'number' => $index + 1,
                'requirement' => $requirement,
                'notes' => trim((string) ($notes[$index] ?? '')),
                'purpose' => trim((string) ($purposes[$index] ?? '')),
                'provided_by' => trim((string) ($providedBy[$index] ?? '')),
                'submitted_to' => trim((string) ($submittedTo[$index] ?? '')),
                'assigned_to' => trim((string) ($assigned[$index] ?? '')),
                'timeline' => trim((string) ($timelines[$index] ?? '')),
            ];
        })->filter()->values()->all();
    }

    private function buildKycRequirementsPayload(Request $request, Project $project, array $existing = []): array
    {
        $buildGroup = function (string $prefix) use ($request): array {
            $labels = (array) $request->input("kyc_{$prefix}_label", []);
            $statuses = (array) $request->input("kyc_{$prefix}_status", []);

            return collect($labels)->map(function ($label, $index) use ($statuses) {
                $clean = trim((string) $label);
                if ($clean === '') {
                    return null;
                }

                return [
                    'label' => $clean,
                    'status' => in_array($statuses[$index] ?? 'pending', ['provided', 'pending'], true) ? (string) $statuses[$index] : 'pending',
                ];
            })->filter()->values()->all();
        };

        $organizationType = strtolower(trim((string) ($existing['organization_type'] ?? '')));
        if ($organizationType === '' || $organizationType === 'unknown') {
            $organizationType = $this->resolveStartOrganizationType($project);
        }

        return [
            'organization_type' => $organizationType === '' ? 'unknown' : $organizationType,
            'sole' => $buildGroup('sole'),
            'juridical' => $buildGroup('juridical'),
        ];
    }

    private function validateStartPayload(Request $request): array
    {
        return $request->validate([
            'form_date' => ['nullable', 'date'],
            'date_started' => ['nullable', 'date'],
            'date_completed' => ['nullable', 'date'],
            'checklist_label' => ['nullable', 'array'],
            'checklist_label.*' => ['nullable', 'string', 'max:255'],
            'checklist_status' => ['nullable', 'array'],
            'checklist_status.*' => ['nullable', 'in:provided,pending'],
            'kyc_sole_label' => ['nullable', 'array'],
            'kyc_sole_label.*' => ['nullable', 'string', 'max:255'],
            'kyc_sole_status' => ['nullable', 'array'],
            'kyc_sole_status.*' => ['nullable', 'in:provided,pending'],
            'kyc_juridical_label' => ['nullable', 'array'],
            'kyc_juridical_label.*' => ['nullable', 'string', 'max:255'],
            'kyc_juridical_status' => ['nullable', 'array'],
            'kyc_juridical_status.*' => ['nullable', 'in:provided,pending'],
            'engagement_requirement' => ['nullable', 'array'],
            'engagement_requirement.*' => ['nullable', 'string', 'max:255'],
            'engagement_notes' => ['nullable', 'array'],
            'engagement_notes.*' => ['nullable', 'string', 'max:255'],
            'engagement_purpose' => ['nullable', 'array'],
            'engagement_purpose.*' => ['nullable', 'string', 'max:255'],
            'engagement_provided_by' => ['nullable', 'array'],
            'engagement_provided_by.*' => ['nullable', 'string', 'max:255'],
            'engagement_submitted_to' => ['nullable', 'array'],
            'engagement_submitted_to.*' => ['nullable', 'string', 'max:255'],
            'engagement_assigned_to' => ['nullable', 'array'],
            'engagement_assigned_to.*' => ['nullable', 'string', 'max:255'],
            'engagement_timeline' => ['nullable', 'array'],
            'engagement_timeline.*' => ['nullable', 'string', 'max:255'],
            'approval_requirement' => ['nullable', 'array'],
            'approval_requirement.*' => ['nullable', 'string', 'max:255'],
            'approval_responsible_person' => ['nullable', 'array'],
            'approval_responsible_person.*' => ['nullable', 'string', 'max:255'],
            'approval_name_and_signature' => ['nullable', 'array'],
            'approval_name_and_signature.*' => ['nullable', 'string', 'max:255'],
            'approval_date_time_done' => ['nullable', 'array'],
            'approval_date_time_done.*' => ['nullable', 'string', 'max:255'],
            'clearance_assigned_team_lead' => ['nullable', 'string', 'max:255'],
            'clearance_assigned_team_lead_signature' => ['nullable', 'string', 'max:255'],
            'clearance_lead_consultant_confirmed' => ['nullable', 'string', 'max:255'],
            'clearance_lead_consultant_signature' => ['nullable', 'string', 'max:255'],
            'clearance_lead_associate_assigned' => ['nullable', 'string', 'max:255'],
            'clearance_lead_associate_signature' => ['nullable', 'string', 'max:255'],
            'clearance_sales_marketing' => ['nullable', 'string', 'max:255'],
            'clearance_sales_marketing_signature' => ['nullable', 'string', 'max:255'],
            'clearance_record_custodian_name' => ['nullable', 'string', 'max:255'],
            'clearance_record_custodian_signature' => ['nullable', 'string', 'max:255'],
            'clearance_date_recorded' => ['nullable', 'date'],
            'clearance_date_signed' => ['nullable', 'date'],
            'routing_role' => ['nullable', 'array'],
            'routing_role.*' => ['nullable', 'string', 'max:255'],
            'routing_status' => ['nullable', 'array'],
            'routing_status.*' => ['nullable', 'in:pending,approved,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function persistStartForm(Request $request, Project $project, array $validated): ProjectStart
    {
        $start = $this->resolveCurrentStart($project) ?: new ProjectStart(['project_id' => $project->id]);
        $resolvedFormDate = $validated['form_date']
            ?? optional($start->form_date)->toDateString()
            ?? optional($start->created_at)->toDateString()
            ?? now()->toDateString();
        $existingStatus = strtolower((string) ($start->status ?? 'draft'));
        $status = in_array($existingStatus, ['pending_approval', 'approved'], true) ? $existingStatus : 'draft';

        $start->fill([
            'form_date' => $resolvedFormDate,
            'date_started' => $validated['date_started'] ?? null,
            'date_completed' => $validated['date_completed'] ?? null,
            'status' => $status,
            'checklist' => $this->buildChecklistPayload($request),
            'kyc_requirements' => $this->buildKycRequirementsPayload($request, $project, $start->kyc_requirements ?? []),
            'engagement_requirements' => $this->buildRequirementRows($request, 'engagement'),
            'approval_steps' => $this->buildApprovalRows($request),
            'routing' => $this->buildRoutingRows($request),
            'clearance' => $this->buildClearancePayload($validated),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        $start->project_id = $project->id;
        $start->save();

        return $start;
    }

    private function ensureServiceMemoGenerated(Project $project, ProjectStart $start, ?ProjectSow $sow): string
    {
        $attachments = (array) ($start->attachments ?? []);
        $existingPath = (string) ($attachments['service_memo_pdf_path'] ?? '');
        if ($existingPath !== '' && Storage::disk('public')->exists($existingPath)) {
            return $existingPath;
        }

        $serviceMemo = $this->buildServiceMemoPayload($project, $start, $sow);
        $targetPath = 'generated-previews/project/service-memo/' . ($project->project_code ?: $project->id) . '-' . ($start->start_code ?: $start->id) . '-service-memo.pdf';
        $pdfPath = $this->generatePdfPreview('project.pdf.service-memo', compact('project', 'start', 'sow', 'serviceMemo'), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate service memo preview.');

        $attachments['service_memo_pdf_path'] = $pdfPath;
        $start->attachments = $attachments;
        $start->save();

        return $pdfPath;
    }

    private function moveLinkedDealToStage(Project $project, string $stageName): void
    {
        $deal = $project->deal()->first();
        if (! $deal) {
            return;
        }

        $payload = ['stage' => $stageName];

        if (Schema::hasTable('deal_stages') && Schema::hasColumn('deals', 'stage_id')) {
            $stage = DealStage::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($stageName))])
                ->first();

            if ($stage) {
                $payload['stage_id'] = $stage->id;
            }
        }

        $deal->forceFill($payload)->save();
    }

    private function resolveCurrentStart(Project $project): ?ProjectStart
    {
        return $project->starts()
            ->orderByRaw("
                CASE
                    WHEN LOWER(COALESCE(status, '')) = 'approved' THEN 1
                    WHEN LOWER(COALESCE(status, '')) = 'pending_approval' THEN 2
                    WHEN LOWER(COALESCE(status, '')) = 'rejected' THEN 3
                    ELSE 4
                END
            ")
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    private function redirectToStartTarget(Request $request, Project $project, string $message): RedirectResponse
    {
        $redirectUrl = $request->input('redirect_url');
        if (is_string($redirectUrl) && $redirectUrl !== '' && URL::isValidUrl($redirectUrl)) {
            $allowedBases = collect([
                rtrim((string) config('app.url'), '/'),
                rtrim($request->getSchemeAndHttpHost(), '/'),
            ])->filter()->unique();

            if ($allowedBases->contains(fn (string $base): bool => str_starts_with($redirectUrl, $base))) {
                return redirect()
                    ->to($redirectUrl)
                    ->with('success', $message);
            }
        }

        return redirect()
            ->route('project.show', ['project' => $project->id, 'tab' => 'sow'])
            ->with('success', $message);
    }

    private function buildRoutingRows(Request $request): array
    {
        $roles = (array) $request->input('routing_role', []);
        $statuses = (array) $request->input('routing_status', []);

        return collect($roles)->map(function ($value, $index) use ($statuses) {
            $role = trim((string) $value);
            if ($role === '') {
                return null;
            }

            $status = (string) ($statuses[$index] ?? 'pending');

            return [
                'role' => $role,
                'status' => in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending',
            ];
        })->filter()->values()->all();
    }

    private function buildApprovalRows(Request $request): array
    {
        $requirements = (array) $request->input('approval_requirement', []);
        $responsibles = (array) $request->input('approval_responsible_person', []);
        $signatures = (array) $request->input('approval_name_and_signature', []);
        $dates = (array) $request->input('approval_date_time_done', []);

        return collect($requirements)->map(function ($value, $index) use ($responsibles, $signatures, $dates) {
            $requirement = trim((string) $value);
            if ($requirement === '') {
                return null;
            }

            return [
                'requirement' => $requirement,
                'responsible_person' => trim((string) ($responsibles[$index] ?? '')),
                'name_and_signature' => trim((string) ($signatures[$index] ?? '')),
                'date_time_done' => trim((string) ($dates[$index] ?? '')),
            ];
        })->filter()->values()->all();
    }

    private function buildClearancePayload(array $validated): array
    {
        return [
            'assigned_team_lead' => $validated['clearance_assigned_team_lead'] ?? null,
            'assigned_team_lead_signature' => $validated['clearance_assigned_team_lead_signature'] ?? null,
            'lead_consultant_confirmed' => $validated['clearance_lead_consultant_confirmed'] ?? null,
            'lead_consultant_signature' => $validated['clearance_lead_consultant_signature'] ?? null,
            'lead_associate_assigned' => $validated['clearance_lead_associate_assigned'] ?? null,
            'lead_associate_signature' => $validated['clearance_lead_associate_signature'] ?? null,
            'sales_marketing' => $validated['clearance_sales_marketing'] ?? null,
            'sales_marketing_signature' => $validated['clearance_sales_marketing_signature'] ?? null,
            'record_custodian_name' => $validated['clearance_record_custodian_name'] ?? null,
            'record_custodian_signature' => $validated['clearance_record_custodian_signature'] ?? null,
            'date_recorded' => $validated['clearance_date_recorded'] ?? null,
            'date_signed' => $validated['clearance_date_signed'] ?? null,
        ];
    }

    private function buildScopeRows(Request $request, string $prefix): array
    {
        $mainTasks = (array) $request->input("{$prefix}_main_task_description", []);
        $subTasks = (array) $request->input("{$prefix}_sub_task_description", []);
        $responsibles = (array) $request->input("{$prefix}_responsible", []);
        $durations = (array) $request->input("{$prefix}_duration", []);
        $startDates = (array) $request->input("{$prefix}_start_date", []);
        $endDates = (array) $request->input("{$prefix}_end_date", []);
        $statuses = (array) $request->input("{$prefix}_status", []);
        $remarks = (array) $request->input("{$prefix}_remarks", []);

        return collect($mainTasks)->map(function ($value, $index) use ($subTasks, $responsibles, $durations, $startDates, $endDates, $statuses, $remarks) {
            $mainTask = trim((string) $value);
            if ($mainTask === '') {
                return null;
            }

            return [
                'main_task_description' => $mainTask,
                'sub_task_description' => trim((string) ($subTasks[$index] ?? '')),
                'responsible' => trim((string) ($responsibles[$index] ?? '')),
                'duration' => trim((string) ($durations[$index] ?? '')),
                'start_date' => $startDates[$index] ?? null,
                'end_date' => $endDates[$index] ?? null,
                'status' => trim((string) ($statuses[$index] ?? '')),
                'remarks' => trim((string) ($remarks[$index] ?? '')),
            ];
        })->filter()->values()->all();
    }

    private function buildProjectDocumentPayload(Project $project): array
    {
        $project->load([
            'deal:id,deal_code,engagement_type',
            'contact:id,first_name,last_name,email,phone,company_name',
            'company.primaryContact:id,first_name,middle_name,last_name,email,phone,company_name,cif_status,organization_type,business_type_organization,ownership_flag,foreign_business_nature',
            'company.latestBif',
            'starts' => fn ($query) => $query->latest(),
            'sows' => fn ($query) => $query->latest(),
            'sowReports' => fn ($query) => $query->latest(),
            'ntps' => fn ($query) => $query->latest(),
        ]);

        $start = $this->resolveCurrentStart($project);
        if ($start) {
            $start->kyc_requirements = $this->defaultStartKycRequirementsForProject($project);
        }

        return [
            'project' => $project,
            'start' => $start,
            'sow' => $project->sows->first(),
            'report' => $project->sowReports->first(),
            'ntpRecord' => $project->ntps->first(),
        ];
    }

    private function buildProjectNtpStatusPayload(Project $project, ?ProjectNtp $ntpRecord): array
    {
        $isApproved = $ntpRecord?->client_response_status === 'approved_to_proceed' && $ntpRecord?->client_approved_at;

        return [
            'exists' => $ntpRecord !== null,
            'is_approved' => (bool) $isApproved,
            'status_label' => $isApproved
                ? 'Client approved NTP'
                : ($ntpRecord?->client_form_sent_at ? 'Waiting for client signed NTP upload' : 'NTP not generated'),
            'button_label' => $isApproved ? 'View Approved NTP' : 'Generate NTP',
            'button_icon' => $isApproved ? 'fas fa-check-circle' : 'fas fa-file-signature',
            'button_class' => $isApproved
                ? 'project-doc-action project-doc-action-approved'
                : 'project-doc-action',
            'action_url' => $isApproved
                ? route('project.ntp.submission', $project)
                : route('project.ntp.download', $project),
            'approved_at' => optional($ntpRecord?->client_approved_at)->format('M d, Y h:i A'),
            'approved_name' => $ntpRecord?->client_approved_name,
        ];
    }

    private function mergeProjectTemplateApprovalPayload(array $templateApproval, array $validated): array
    {
        $fallback = $this->manualInternalApprovalPayload(
            $validated['assigned_project_manager'] ?? null,
            $validated['assigned_consultant'] ?? null,
            $validated['assigned_associate'] ?? null
        );

        return array_replace_recursive($fallback, array_filter($templateApproval, fn ($value) => $value !== null));
    }

    private function defaultStartKycRequirementsForProject(Project $project): array
    {
        $company = $project->company;
        $organization = $this->resolveStartOrganizationType($project);
        $kycContext = $this->buildStartKycContext($project);
        $contactApproved = $kycContext['contact_approved'];
        $bifApproved = strtolower((string) ($company?->latestBif?->status ?? '')) === 'approved';
        $contactDocs = $kycContext['contact_documents'];
        $bifDocs = (array) ($company?->latestBif?->client_requirement_documents ?? []);
        $showForeignRows = $kycContext['show_foreign_rows'];

        $hasBif = fn (string $key): bool => $bifApproved && filled(data_get($bifDocs, $key.'.path'));
        $status = fn (bool $complete): string => $complete ? 'provided' : 'pending';

        $sole = [
            ['label' => 'Client Contact Form', 'status' => $status((bool) $project->contact_id)],
            ['label' => 'Client Information Form', 'status' => $status($contactApproved)],
            ['label' => '2 Valid Government IDs', 'status' => $status($contactDocs['two_valid_ids'])],
            ['label' => 'TIN ID', 'status' => $status($contactDocs['tin_proof'])],
            ['label' => 'DTI Certificate of Registration (if Sole Prop)', 'status' => $status($hasBif('sole_dti_certificate_document'))],
            ['label' => 'BIR Certificate of Registration (COR)', 'status' => $status($hasBif('sole_bir_cor_document'))],
            ['label' => 'Business Permit / Mayor\'s Permit', 'status' => $status($hasBif('sole_business_permit_document'))],
            ['label' => 'Proof of Billing (Residential)', 'status' => $status($hasBif('sole_proof_of_billing_residential_document'))],
            ['label' => 'Proof of Billing (Business Address if different)', 'status' => $status($hasBif('sole_proof_of_billing_business_document'))],
            ['label' => 'Special Power of Attorney (if representative)', 'status' => $status($hasBif('sole_spa_document'))],
            ['label' => 'Representative\'s 2 Valid IDs (if applicable)', 'status' => $status($hasBif('sole_representative_ids_document'))],
        ];
        if ($showForeignRows) {
            $sole[] = ['label' => 'If Foreigner: Passport (Bio Page)', 'status' => $status($contactDocs['passport_proof'])];
            $sole[] = ['label' => 'If Foreigner: Valid Visa / ACR I-Card', 'status' => $status($contactDocs['visa_or_acr'])];
        }

        $juridical = [
            ['label' => 'Client Contact Form', 'status' => $status((bool) $project->contact_id)],
            ['label' => 'Business Information Form', 'status' => $status($bifApproved)],
            ['label' => '2 Valid Government IDs (Authorized Signatory)', 'status' => $status($contactDocs['two_valid_ids'])],
            ['label' => 'TIN ID (Authorized Signatory)', 'status' => $status($contactDocs['tin_proof'])],
            ['label' => 'SEC / CDA Certificate of Registration', 'status' => $status($hasBif('juridical_sec_cda_certificate_document'))],
            ['label' => 'BIR Certificate of Registration (COR)', 'status' => $status($hasBif('juridical_bir_cor_document'))],
            ['label' => 'Business Permit / Mayor\'s Permit', 'status' => $status($hasBif('juridical_business_permit_document'))],
            ['label' => 'Articles of Incorporation / Partnership', 'status' => $status($hasBif('juridical_articles_document'))],
            ['label' => 'By-Laws', 'status' => $status($hasBif('juridical_bylaws_document'))],
            ['label' => 'Latest General Information Sheet (GIS)', 'status' => $status($hasBif('juridical_gis_document'))],
            ['label' => 'Appointment of Officers (for OPC, if applicable)', 'status' => $status($hasBif('juridical_appointment_of_officers_document'))],
            ['label' => 'Secretary Certificate OR Board Resolution', 'status' => $status($hasBif('juridical_secretary_certificate_document'))],
            ['label' => 'Ultimate Beneficial Owner (UBO) Declaration', 'status' => $status($hasBif('juridical_ubo_declaration_document'))],
            ['label' => 'Proof of Billing (Company Address)', 'status' => $status($hasBif('juridical_company_billing_document'))],
            ['label' => 'Proof of Billing (Authorized Representative, if applicable)', 'status' => $status($hasBif('juridical_representative_billing_document'))],
        ];
        if ($showForeignRows) {
            $juridical[] = ['label' => 'If Foreign Signatory/Director: Passport (Bio Page)', 'status' => $status($contactDocs['passport_proof'])];
            $juridical[] = ['label' => 'If Foreign Signatory/Director: Valid Visa / ACR I-Card', 'status' => $status($contactDocs['visa_or_acr'])];
        }

        return [
            'organization_type' => $organization,
            'sole' => $organization === 'sole_proprietorship' ? $sole : [],
            'juridical' => $this->isJuridicalOrganization($organization) ? $juridical : [],
        ];
    }

    private function resolveStartOrganizationType(Project $project): string
    {
        $company = $project->company;
        $contact = $project->contact;
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

    private function buildStartKycContext(Project $project): array
    {
        $company = $project->company;
        $organization = $this->resolveStartOrganizationType($project);
        $contacts = collect();

        if ($project->contact) {
            $contacts->push($project->contact);
        }

        if ($company?->primaryContact && (! $project->contact || $company->primaryContact->id !== $project->contact->id)) {
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

        foreach ($contacts as $contact) {
            $contactApproved = $contactApproved || strtolower((string) ($contact?->cif_status ?? '')) === 'approved';
            $contactDocumentsForRecord = $this->projectContactRequirementState((int) $contact->id);

            foreach (array_keys($contactDocuments) as $key) {
                $contactDocuments[$key] = $contactDocuments[$key] || $contactDocumentsForRecord[$key];
            }

            $showForeignRows = $showForeignRows || $this->contactHasForeignDetails((int) $contact->id, $contact);
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

    private function projectContactRequirementState(int $contactId): array
    {
        $documents = [];
        if ($contactId > 0) {
            $kycPath = 'contact-kyc-data/'.$contactId.'-requirements.json';
            if (Storage::disk('local')->exists($kycPath)) {
                $documents = json_decode((string) Storage::disk('local')->get($kycPath), true) ?: [];
            }

            $cifPath = 'contact-cif-data/'.$contactId.'-documents.json';
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

        $countDocuments = function (string $key) use ($documents): int {
            return count(array_values(array_filter(
                $this->normalizeProjectDocuments($documents[$key] ?? []),
                fn (array $item): bool => filled($item['file_name'] ?? $item['path'] ?? $item['file_path'] ?? null)
            )));
        };

        return [
            'two_valid_ids' => $countDocuments('two_valid_ids') > 0,
            'tin_proof' => $countDocuments('tin_proof') > 0,
            'passport_proof' => $countDocuments('passport_proof') > 0,
            'visa_or_acr' => $countDocuments('visa_proof') > 0 || $countDocuments('acr_card_proof') > 0,
        ];
    }

    private function normalizeProjectDocuments(mixed $documents): array
    {
        if (! is_array($documents)) {
            return [];
        }

        if (array_is_list($documents)) {
            return array_values(array_filter($documents, 'is_array'));
        }

        return filled($documents['file_name'] ?? $documents['path'] ?? $documents['file_path'] ?? null)
            ? [$documents]
            : [];
    }

    private function contactHasForeignDetails(int $contactId, mixed $contact = null): bool
    {
        $contact = is_object($contact) ? $contact : null;
        $cifData = [];

        if ($contactId > 0) {
            $cifPath = 'contact-cif-data/'.$contactId.'-data.json';
            if (Storage::disk('local')->exists($cifPath)) {
                $cifData = json_decode((string) Storage::disk('local')->get($cifPath), true) ?: [];
            }
        }

        return in_array(strtolower((string) ($cifData['citizenship_type'] ?? '')), ['foreigner', 'dual_citizen'], true)
            || strtolower((string) ($contact?->ownership_flag ?? '')) === 'foreign-owned business'
            || filled($contact?->foreign_business_nature);
    }

    private function valueImpliesForeign(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return $normalized !== '' && ! in_array($normalized, ['filipino', 'philippines', 'philippine', 'ph'], true);
    }

    private function buildInternalApprovalPayload(array $validated): array
    {
        return [
            'prepared_by' => $validated['prepared_by'] ?? null,
            'reviewed_by' => $validated['reviewed_by'] ?? null,
            'referred_by_closed_by' => $validated['referred_by_closed_by'] ?? null,
            'sales_marketing' => $validated['sales_marketing'] ?? null,
            'lead_consultant' => $validated['lead_consultant'] ?? null,
            'lead_associate_assigned' => $validated['lead_associate_assigned'] ?? null,
            'finance' => $validated['finance'] ?? null,
            'president' => $validated['president'] ?? null,
            'record_custodian' => $validated['record_custodian'] ?? null,
            'date_recorded' => $validated['date_recorded'] ?? null,
            'date_signed' => $validated['date_signed'] ?? null,
        ];
    }

    private function buildManualRequirementRows(string $rawRequirements, string $assignedTo, string $purpose): array
    {
        $rows = collect(preg_split('/[\r\n]+/', trim($rawRequirements)))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->map(fn (string $item, int $index): array => [
                'number' => $index + 1,
                'requirement' => $item,
                'notes' => '',
                'purpose' => $purpose,
                'provided_by' => 'Client',
                'submitted_to' => 'Sales & Marketing',
                'assigned_to' => $assignedTo,
                'timeline' => 'To be scheduled',
            ])
            ->all();

        if ($rows !== []) {
            return $rows;
        }

        return [[
            'number' => 1,
            'requirement' => 'Initial intake and supporting documents',
            'notes' => '',
            'purpose' => $purpose,
            'provided_by' => 'Client',
            'submitted_to' => 'Sales & Marketing',
            'assigned_to' => $assignedTo,
            'timeline' => 'To be scheduled',
        ]];
    }

    private function manualStartChecklist(): array
    {
        return [
            ['label' => 'Client Contact Form', 'status' => 'pending'],
            ['label' => 'Deal Form', 'status' => 'pending'],
            ['label' => 'Business Information Form', 'status' => 'pending'],
            ['label' => 'Client Information Form', 'status' => 'pending'],
            ['label' => 'Service Task Activation & Routing Tracker (Start)', 'status' => 'provided'],
            ['label' => 'Others', 'status' => 'pending'],
        ];
    }

    private function manualStartApprovalSteps(?string $assignedConsultant, ?string $assignedAssociate): array
    {
        $leadConsultant = $assignedConsultant ?: 'Lead Consultant';
        $leadAssociate = $assignedAssociate ?: 'Lead Associate';

        return [
            ['requirement' => 'Client Contact Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Deal Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Business Information Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Client Information Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Service Task Activation & Routing Tracker (Start)', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Engagement-Specific Requirement', 'responsible_person' => 'Sales & Marketing/'.$leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Proposal/Contract', 'responsible_person' => 'Sales & Marketing/'.$leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Final Quote', 'responsible_person' => $leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
        ];
    }

    private function manualRoutingRows(): array
    {
        return [
            ['role' => 'Admin', 'status' => 'pending'],
            ['role' => 'Lead Consultant', 'status' => 'pending'],
            ['role' => 'Lead Associate', 'status' => 'pending'],
            ['role' => 'Sales & Marketing', 'status' => 'pending'],
        ];
    }

    private function manualClearancePayload(?string $assignedConsultant, ?string $assignedAssociate): array
    {
        return [
            'assigned_team_lead' => '',
            'assigned_team_lead_signature' => '',
            'lead_consultant_confirmed' => $assignedConsultant ?? '',
            'lead_consultant_signature' => '',
            'lead_associate_assigned' => $assignedAssociate ?? '',
            'lead_associate_signature' => '',
            'sales_marketing' => 'Sales & Marketing',
            'sales_marketing_signature' => '',
            'record_custodian_name' => 'Record Custodian',
            'record_custodian_signature' => '',
            'date_recorded' => now()->toDateString(),
            'date_signed' => null,
        ];
    }

    private function manualInternalApprovalPayload(?string $assignedProjectManager, ?string $assignedConsultant, ?string $assignedAssociate): array
    {
        return [
            'prepared_by' => $assignedProjectManager ?: $assignedConsultant,
            'reviewed_by' => 'Admin',
            'referred_by_closed_by' => null,
            'sales_marketing' => 'Sales & Marketing',
            'lead_consultant' => $assignedConsultant,
            'lead_associate_assigned' => $assignedAssociate,
            'finance' => 'Finance',
            'president' => 'President',
            'record_custodian' => 'Record Custodian',
            'date_recorded' => now()->toDateString(),
            'date_signed' => null,
        ];
    }

    private function manualScopeRows(array $validated): array
    {
        $items = collect(preg_split('/[\r\n,;]+/', (string) ($validated['services'] ?? '')))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();

        if ($items->isEmpty() && filled($validated['scope_summary'] ?? null)) {
            $items = collect(preg_split('/[\r\n,;]+/', (string) $validated['scope_summary']))
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->values();
        }

        return $items
            ->take(5)
            ->map(fn (string $item): array => [
                'main_task_description' => $item,
                'sub_task_description' => 'To be defined during planning',
                'responsible' => $validated['assigned_consultant'] ?? ($validated['assigned_project_manager'] ?? 'Project Team'),
                'duration' => 'TBD',
                'start_date' => $validated['planned_start_date'] ?? null,
                'end_date' => $validated['target_completion_date'] ?? null,
                'status' => 'Pending',
                'remarks' => null,
            ])
            ->values()
            ->all();
    }

    private function projectContactRecords(): array
    {
        if (! Schema::hasTable('contacts')) {
            return [];
        }

        return Contact::query()
            ->select([
                'id',
                'salutation',
                'first_name',
                'middle_initial',
                'middle_name',
                'last_name',
                'name_extension',
                'email',
                'phone',
                'contact_address',
                'company_name',
                'company_address',
                'position',
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Contact $contact): array {
                return [
                    'id' => $contact->id,
                    'label' => trim(collect([
                        $contact->salutation,
                        $contact->first_name,
                        $contact->middle_name,
                        $contact->last_name,
                    ])->filter()->implode(' ')),
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        $contact->first_name,
                        $contact->middle_initial,
                        $contact->middle_name,
                        $contact->last_name,
                        $contact->company_name,
                        $contact->email,
                        $contact->phone,
                    ]))),
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_initial' => $contact->middle_initial ?: (filled($contact->middle_name) ? mb_substr((string) $contact->middle_name, 0, 1) : null),
                    'middle_name' => $contact->middle_name,
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'email' => $contact->email,
                    'mobile' => $contact->phone,
                    'address' => $contact->contact_address,
                    'company_name' => $contact->company_name,
                    'company_address' => $contact->company_address,
                    'position' => $contact->position,
                ];
            })
            ->filter(fn (array $record): bool => filled($record['label']) || filled($record['company_name']))
            ->values()
            ->all();
    }

    private function projectCompanyRecords(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        return Company::query()
            ->with([
                'primaryContact:id,first_name,middle_name,last_name,email,phone,contact_address,position,company_name',
            ])
            ->select(['id', 'company_name', 'email', 'phone', 'address', 'owner_name', 'primary_contact_id'])
            ->orderBy('company_name')
            ->get()
            ->map(function (Company $company): array {
                $primaryContact = $company->primaryContact;

                return [
                    'id' => $company->id,
                    'label' => $company->company_name,
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        $company->company_name,
                        $company->email,
                        $company->phone,
                        $company->address,
                        $company->owner_name,
                        $primaryContact?->first_name,
                        $primaryContact?->middle_name,
                        $primaryContact?->last_name,
                        $primaryContact?->email,
                        $primaryContact?->phone,
                    ]))),
                    'company_name' => $company->company_name,
                    'company_address' => $company->address,
                    'email' => $company->email,
                    'mobile' => $company->phone,
                    'owner_name' => $company->owner_name,
                    'primary_contact_id' => $primaryContact?->id,
                    'primary_contact_name' => trim(collect([
                        $primaryContact?->first_name,
                        $primaryContact?->middle_name,
                        $primaryContact?->last_name,
                    ])->filter()->implode(' ')),
                    'primary_contact_email' => $primaryContact?->email,
                    'primary_contact_mobile' => $primaryContact?->phone,
                    'primary_contact_position' => $primaryContact?->position,
                ];
            })
            ->filter(fn (array $record): bool => filled($record['company_name']))
            ->values()
            ->all();
    }

    private function projectDealRecords(): array
    {
        if (! Schema::hasTable('deals')) {
            return [];
        }

        return Deal::query()
            ->with(['contact:id,first_name,middle_name,last_name,email,phone,company_name,company_address,position'])
            ->whereDoesntHave('projects', function ($query): void {
                $query->where(function ($workspaceQuery): void {
                    $workspaceQuery
                        ->whereNull('engagement_type')
                        ->orWhereRaw('LOWER(engagement_type) NOT LIKE ?', ['%regular%']);
                });
            })
            ->latest()
            ->get()
            ->map(function (Deal $deal): array {
                $contact = $deal->contact;
                $clientName = trim(collect([
                    $deal->first_name ?: $contact?->first_name,
                    $deal->middle_name ?: $contact?->middle_name,
                    $deal->last_name ?: $contact?->last_name,
                ])->filter()->implode(' '));

                return [
                    'id' => $deal->id,
                    'label' => trim((string) ($deal->deal_code ?: $deal->deal_name ?: ('Deal #'.$deal->id))),
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        $deal->deal_code,
                        $deal->deal_name,
                        $clientName,
                        $deal->company_name ?: $contact?->company_name,
                        $deal->service_area,
                        $deal->services,
                    ]))),
                    'deal_code' => $deal->deal_code,
                    'deal_name' => $deal->deal_name,
                    'contact_id' => $deal->contact_id,
                    'client_name' => $clientName,
                    'business_name' => $deal->company_name ?: $contact?->company_name,
                    'company_address' => $deal->company_address ?: $contact?->company_address,
                    'email' => $deal->email ?: $contact?->email,
                    'mobile' => $deal->mobile ?: $contact?->phone,
                    'position' => $deal->position ?: $contact?->position,
                    'service_area' => $deal->service_area,
                    'services' => $deal->services,
                    'products' => $deal->products,
                    'scope_summary' => $deal->scope_of_work,
                    'planned_start_date' => optional($deal->planned_start_date)->format('Y-m-d'),
                    'target_completion_date' => optional($deal->estimated_completion_date)->format('Y-m-d'),
                    'assigned_project_manager' => $deal->assigned_consultant,
                    'assigned_consultant' => $deal->assigned_consultant,
                    'assigned_associate' => $deal->assigned_associate,
                    'client_confirmation_name' => $clientName,
                    'engagement_type' => $deal->engagement_type,
                ];
            })
            ->values()
            ->all();
    }

    private function projectServiceCatalog(): array
    {
        $fallbackGroups = collect(self::FALLBACK_SERVICE_GROUPS)
            ->map(fn (array $services): array => array_values(array_unique($services)))
            ->all();

        try {
            if (! Schema::hasTable('services')) {
                return [
                    'serviceAreaOptions' => self::FALLBACK_SERVICE_AREA_OPTIONS,
                    'serviceGroups' => $fallbackGroups,
                ];
            }

            $services = Service::query()
                ->select(['service_name', 'service_area', 'service_area_other', 'status'])
                ->whereNotNull('service_name')
                ->where('service_name', '!=', '')
                ->when(Schema::hasColumn('services', 'status'), fn ($query) => $query->where('status', 'Active'))
                ->orderBy('service_name')
                ->get();

            $serviceGroups = [];

            foreach ($services as $service) {
                $serviceName = trim((string) $service->service_name);
                if ($serviceName === '') {
                    continue;
                }

                $areas = collect($service->service_area ?? [])
                    ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                    ->map(fn ($value): string => trim((string) $value))
                    ->values();

                if ($areas->contains('Others') && filled($service->service_area_other)) {
                    $areas = $areas
                        ->reject(fn ($value): bool => $value === 'Others')
                        ->push(trim((string) $service->service_area_other))
                        ->values();
                }

                if ($areas->isEmpty() && filled($service->service_area_other)) {
                    $areas = collect([trim((string) $service->service_area_other)]);
                }

                foreach ($areas as $area) {
                    $serviceGroups[$area] ??= [];
                    $serviceGroups[$area][] = $serviceName;
                }
            }

            $serviceGroups = collect($serviceGroups)
                ->map(fn (array $group): array => collect($group)->filter()->unique()->sort()->values()->all())
                ->filter(fn (array $group): bool => $group !== [])
                ->sortKeys()
                ->all();

            if ($serviceGroups === []) {
                return [
                    'serviceAreaOptions' => self::FALLBACK_SERVICE_AREA_OPTIONS,
                    'serviceGroups' => $fallbackGroups,
                ];
            }

            return [
                'serviceAreaOptions' => array_values(array_unique(array_merge(array_keys($serviceGroups), self::FALLBACK_SERVICE_AREA_OPTIONS))),
                'serviceGroups' => $serviceGroups,
            ];
        } catch (Throwable) {
            return [
                'serviceAreaOptions' => self::FALLBACK_SERVICE_AREA_OPTIONS,
                'serviceGroups' => $fallbackGroups,
            ];
        }
    }

    private function projectProductCatalog(): array
    {
        $fallbackGroups = [
            'Corporate & Regulatory Advisory' => ['Printing', 'Photocopy', 'Drafting of Letters'],
            'Accounting & Compliance Advisory' => ['Archive Retrieval', 'Digital Archive Copy', 'Drafting of Certifications'],
            'Governance & Policy Advisory' => ['Document Delivery (Metro Cebu)', 'Drafting of Agreements / Simple Contracts', 'Drafting of Board Resolutions'],
            'Business Strategy & Process Advisory' => ['Notarization - Simple Documents', 'Drafting of Reports / Formal Documents'],
            'Strategic Situations Advisory' => ['Printing', 'Photocopy', 'Drafting of Demand Letters'],
            'People & Talent Solutions' => ['Archive Retrieval', 'Drafting of Memorandum (Internal / External)'],
            'Learning & Capability Development' => ['Document Delivery (Outside Metro Cebu/LBC)', 'Drafting of Endorsement / Request Letters'],
        ];

        try {
            if (! Schema::hasTable('products')) {
                return ['productOptionsByServiceArea' => $fallbackGroups];
            }

            $products = Product::query()
                ->select(['product_name', 'product_area', 'status'])
                ->whereNotNull('product_name')
                ->where('product_name', '!=', '')
                ->when(Schema::hasColumn('products', 'status'), fn ($query) => $query->whereIn('status', ['Pending Approval', 'Active']))
                ->orderBy('product_name')
                ->get();

            $groups = [];

            foreach ($products as $product) {
                $productName = trim((string) $product->product_name);
                if ($productName === '') {
                    continue;
                }

                $areas = collect($product->product_area ?? [])
                    ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                    ->map(fn ($value): string => trim((string) $value))
                    ->reject(fn (string $value): bool => $value === 'Others' || $value === 'None')
                    ->values();

                foreach ($areas as $area) {
                    $groups[$area] ??= [];
                    $groups[$area][] = $productName;
                }
            }

            $groups = collect($groups)
                ->map(fn (array $items): array => collect($items)->filter()->unique()->sort()->values()->all())
                ->filter(fn (array $items): bool => $items !== [])
                ->sortKeys()
                ->all();

            return ['productOptionsByServiceArea' => $groups === [] ? $fallbackGroups : $groups];
        } catch (Throwable) {
            return ['productOptionsByServiceArea' => $fallbackGroups];
        }
    }

    private function resolveProjectClientEmail(Project $project): ?string
    {
        $project->loadMissing([
            'contact:id,email,first_name,last_name',
            'company.primaryContact:id,email,first_name,last_name',
        ]);

        $email = trim((string) ($project->contact?->email
            ?: $project->company?->primaryContact?->email
            ?: ''));

        return $email !== '' ? $email : null;
    }

    private function projectAutoReportSettings(Project $project, string $key): array
    {
        $raw = (array) data_get($project->metadata, "auto_report.{$key}", []);

        return [
            'enabled' => (bool) ($raw['enabled'] ?? false),
            'day_of_month' => (int) ($raw['day_of_month'] ?? 30),
            'last_generated_on' => filled($raw['last_generated_on'] ?? null) ? (string) $raw['last_generated_on'] : null,
        ];
    }

    private function supportsProjectSowReportClientPortal(): bool
    {
        return Schema::hasColumns('project_sow_reports', [
            'client_access_token',
            'client_access_expires_at',
            'client_form_sent_to_email',
            'client_form_sent_at',
            'client_response_status',
            'client_approved_at',
            'client_approved_name',
            'client_response_notes',
            'client_attachment_path',
        ]);
    }

    private function sendSowReportClientLink(Project $project, ProjectSowReport $report, string $recipientEmail): void
    {
        $project->loadMissing([
            'deal:id,deal_code',
            'contact:id,first_name,last_name,email',
            'company:id,company_name',
        ]);

        $token = Str::random(64);
        $expiresAt = now()->addDays(self::CLIENT_LINK_TTL_DAYS);
        $clientUrl = route('project.report.client.show', ['token' => $token]);
        $clientName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: 'Client');

        $report->fill([
            'client_access_token' => $token,
            'client_access_expires_at' => $expiresAt,
            'client_form_sent_to_email' => $recipientEmail,
            'client_form_sent_at' => now(),
        ]);
        $report->save();

        $emailHtml = view('emails.project.sow-report-client-link', [
            'project' => $project,
            'report' => $report,
            'clientName' => $clientName,
            'clientUrl' => $clientUrl,
            'expiresAt' => $expiresAt,
        ])->render();

        Mail::html($emailHtml, function ($message) use ($recipientEmail, $project, $report) {
            $message
                ->from(config('mail.from.address'), 'John Kelly & Company')
                ->to($recipientEmail)
                ->subject("SOW Report Approval for {$project->name} ({$report->report_number})");
        });
    }

    private function findReportByClientToken(string $token): ProjectSowReport
    {
        return ProjectSowReport::query()
            ->where('client_access_token', $token)
            ->firstOrFail();
    }

    private function buildProjectNtpPayload(Project $project): array
    {
        $project->loadMissing([
            'deal:id,deal_code',
            'contact:id,first_name,last_name',
            'sows' => fn ($query) => $query->latest(),
        ]);

        $sow = $project->sows->first();
        $contactName = trim(collect([$project->contact?->first_name, $project->contact?->last_name])->filter()->implode(' '))
            ?: ($project->client_name ?: '-');

        return [
            'title' => 'NOTICE TO PROCEED',
            'form_code' => 'PROJ-F-003',
            'date_issued' => now()->format('F d, Y'),
            'ntp_no' => null,
            'engagement_type' => $project->engagement_type ?: 'Project',
            'condeal_reference_no' => $project->deal?->deal_code ?: '-',
            'client_name' => $contactName,
            'business_name' => $project->business_name ?: '-',
            'engagement_reference_label' => 'SOW Ref No.:',
            'engagement_reference_no' => $sow?->sow_number ?: '-',
            'approved_start_date' => optional($project->planned_start_date)->format('F d, Y') ?: '-',
            'target_completion_date' => optional($project->target_completion_date)->format('F d, Y') ?: '-',
            'client_representative' => $project->client_name ?: $contactName,
            'lead_consultant' => $project->assigned_consultant ?: ($project->assigned_project_manager ?: ''),
            'associate' => $project->assigned_associate ?: '',
        ];
    }

    private function generateAndSendProjectNtp(Project $project): ProjectNtp
    {
        $ntpPayload = $this->buildProjectNtpPayload($project);
        $sow = $project->sows()->latest()->first();

        $ntpRecord = $project->ntps()->latest()->first() ?: new ProjectNtp(['project_id' => $project->id]);
        $ntpRecord->fill([
            'reference_type' => 'sow',
            'reference_number' => $sow?->sow_number,
            'date_issued' => now()->toDateString(),
            'payload' => $ntpPayload,
        ]);
        $ntpRecord->project_id = $project->id;
        $ntpRecord->save();

        $payload = $ntpRecord->payload ?? [];
        $payload['ntp_no'] = $ntpRecord->ntp_number;
        $ntpRecord->payload = $payload;
        $ntpRecord->save();

        $recipientEmail = $this->resolveProjectClientEmail($project);
        if ($recipientEmail !== null) {
            $this->sendNtpClientLink($project, $ntpRecord, $recipientEmail);
        }

        $project->forceFill([
            'status' => 'For NTP Approval',
            'current_phase' => 'For NTP Approval',
            'current_step' => 'Waiting for signed NTP',
        ])->save();

        return $ntpRecord->fresh();
    }

    private function sendNtpClientLink(Project $project, ProjectNtp $ntpRecord, string $recipientEmail): void
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(self::CLIENT_LINK_TTL_DAYS);
        $clientUrl = route('project.ntp.client.show', ['token' => $token]);
        $clientName = data_get($ntpRecord->payload, 'client_name', $project->client_name ?: 'Client');

        $ntpRecord->fill([
            'client_access_token' => $token,
            'client_access_expires_at' => $expiresAt,
            'client_form_sent_to_email' => $recipientEmail,
            'client_form_sent_at' => now(),
        ]);
        $ntpRecord->save();

        $emailHtml = view('emails.documents.ntp-client-link', [
            'project' => $project,
            'ntpRecord' => $ntpRecord,
            'clientName' => $clientName,
            'clientUrl' => $clientUrl,
            'expiresAt' => $expiresAt,
        ])->render();

        Mail::html($emailHtml, function ($message) use ($recipientEmail, $project, $ntpRecord) {
            $message
                ->from(config('mail.from.address'), 'John Kelly & Company')
                ->to($recipientEmail)
                ->subject("Notice To Proceed for {$project->name} ({$ntpRecord->ntp_number})");
        });
    }

    private function findNtpByClientToken(string $token): ProjectNtp
    {
        return ProjectNtp::query()
            ->where('client_access_token', $token)
            ->firstOrFail();
    }

    private function downloadNtpPdfFromRecord(ProjectNtp $ntpRecord, string $folderPrefix): RedirectResponse
    {
        $ntp = $ntpRecord->payload ?? [];
        $ntp['ntp_no'] = $ntpRecord->ntp_number;
        $targetPath = "generated-previews/{$folderPrefix}/ntp/" . ($ntpRecord->project?->project_code ?: $ntpRecord->project_id) . '-' . $ntpRecord->ntp_number . '.pdf';
        $pdfPath = $this->generatePdfPreview('documents.pdf.ntp', compact('ntp'), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate NTP PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    private function stringifySelectedValues(array $selected, array $custom, string $customPrefix, array $ignored = []): ?string
    {
        $customBaseValues = collect($custom)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->values();

        $base = collect($selected)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '' && ! in_array(trim((string) $value), $ignored, true))
            ->map(fn ($value): string => trim((string) $value))
            ->reject(fn (string $value): bool => $customBaseValues->contains($value));

        $customValues = $customBaseValues
            ->map(fn ($value): string => $customPrefix.$value);

        $value = $base
            ->merge($customValues)
            ->unique()
            ->values()
            ->implode(', ');

        return $value !== '' ? Str::limit($value, 1000, '') : null;
    }

    private function canReviewStart(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && method_exists($user, 'hasPermission')
            && ($user->hasPermission('access_admin_dashboard') || $user->hasPermission('approve_townhall'));
    }
}
