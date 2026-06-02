<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesPdfPreview;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectNtp;
use App\Models\ProjectSowReport;
use App\Models\ProjectStart;
use App\Models\Service;
use App\Models\FormTemplate;
use App\Services\ProjectProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegularController extends Controller
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

    public function __construct(private readonly ProjectProvisioner $projectProvisioner)
    {
    }

    public function index(): View
    {
        $this->provisionMissingRegularRecords();

        $regulars = collect();
        $contactRecords = [];
        $companyRecords = [];
        $dealRecords = [];

        if (Schema::hasTable('projects')) {
            $regulars = Project::query()
                ->with(['deal:id,deal_code', 'company:id,company_name'])
                ->latest()
                ->get()
                ->filter(fn (Project $project): bool => $this->isRegularEngagement($project->engagement_type))
                ->values();
        }

        $stats = [
            'all' => $regulars->count(),
            'rsat' => $regulars->where('current_phase', 'RSAT')->count(),
            'in_progress' => $regulars->filter(fn (Project $regular): bool => in_array($regular->status, ['In Progress', 'Execution', 'Reporting', 'Delivery'], true)
                || in_array($regular->current_phase, ['In Progress', 'Execution', 'Reporting', 'Delivery'], true))->count(),
            'active' => $regulars->whereIn('status', ['RSAT', 'In Progress', 'For NTP Approval', 'Execution', 'Reporting', 'Delivery'])->count(),
            'completed' => $regulars->where('status', 'Completed')->count(),
        ];

        $rsatTemplates = Schema::hasTable('form_templates')
            ? $this->regularRsatTemplateQuery()->orderBy('name')->get()
            : collect();

        $serviceCatalog = $this->regularServiceCatalog();
        $productCatalog = $this->regularProductCatalog();
        $catalogWarnings = array_values(array_filter([
            empty($serviceCatalog['serviceGroups'] ?? []) ? 'Service options are currently unavailable. Add active services in the Services module to populate regular setup selections.' : null,
            empty($productCatalog['productOptionsByServiceArea'] ?? []) ? 'Product options are currently unavailable. Add active products in the Products module to populate regular setup selections.' : null,
        ]));

        try {
            $contactRecords = $this->regularContactRecords();
            $companyRecords = $this->regularCompanyRecords();
            $dealRecords = $this->regularDealRecords();
        } catch (\Throwable) {
            $contactRecords = [];
            $companyRecords = [];
            $dealRecords = [];
        }

        $employeeRecords = $this->employeeLookupRecords();
        $defaultApprovalPreparedBy = $this->currentEmployeeDisplayName(request());

        return view('regular.index', compact(
            'regulars',
            'stats',
            'rsatTemplates',
            'contactRecords',
            'companyRecords',
            'dealRecords',
            'employeeRecords',
            'defaultApprovalPreparedBy',
            'serviceCatalog',
            'productCatalog',
            'catalogWarnings'
        ));
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
            'sales_marketing' => ['nullable', 'string', 'max:255'],
            'finance' => ['nullable', 'string', 'max:255'],
            'client_confirmation_name' => ['nullable', 'string', 'max:255'],
            'engagement_requirements_text' => ['nullable', 'string', 'max:4000'],
            'template_id' => Schema::hasTable('form_templates')
                ? ['nullable', 'integer', Rule::exists('form_templates', 'id')->where(function ($query) {
                    $query->where('type', 'regular_rsat');

                    if (Schema::hasColumn('form_templates', 'status')) {
                        $query->where('status', 'approved');
                    }
                })]
                : ['nullable'],
        ]);
        $validated['prepared_by_default'] = $this->currentEmployeeDisplayName($request);

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

        $selectedTemplate = ! empty($validated['template_id'])
            ? $this->regularRsatTemplateQuery()
                ->find($validated['template_id'])
            : null;
        $templatePayload = (array) ($selectedTemplate?->payload ?? []);

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

        $regular = Project::query()->create([
            'deal_id' => $linkedDeal?->id,
            'contact_id' => $linkedContact?->id,
            'company_id' => $linkedCompany?->id,
            'name' => $validated['name'],
            'engagement_type' => 'Regular Retainer',
            'status' => 'RSAT',
            'current_phase' => 'RSAT',
            'current_step' => 'RSAT Checklist',
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
            'client_confirmation_name' => $validated['client_confirmation_name'] ?? ($validated['client_name'] ?? null),
            'metadata' => [
                'created_from' => $linkedDeal ? 'regular_dashboard_linked_deal' : 'regular_dashboard_manual',
                'source_mode' => $validated['source_mode'] ?? ($linkedDeal ? 'deal' : 'manual'),
                'template_id' => $selectedTemplate?->id,
                'template_name' => $selectedTemplate?->name,
                'internal_assignments' => [
                    'sales_marketing' => $validated['sales_marketing'] ?? null,
                    'finance' => $validated['finance'] ?? null,
                ],
            ],
            'opened_at' => now(),
        ]);

        $rsatRequirements = collect($templatePayload['engagement_requirements'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();
        if ($rsatRequirements === []) {
            $rsatRequirements = $this->buildManualRequirementRows(
                $validated['engagement_requirements_text'] ?? '',
                $validated['assigned_associate'] ?? ($validated['assigned_consultant'] ?? ($validated['assigned_project_manager'] ?? 'Assigned team'))
            );
        }

        ProjectStart::query()->create([
            'project_id' => $regular->id,
            'form_date' => now()->toDateString(),
            'date_started' => $validated['planned_start_date'] ?? now()->toDateString(),
            'status' => 'pending',
            'engagement_requirements' => $rsatRequirements,
            'approval_steps' => $this->mergeRegularTemplateApprovalSteps(
                (array) ($templatePayload['approval_steps'] ?? []),
                $validated['sales_marketing'] ?? null,
                $validated['finance'] ?? null,
                $validated['assigned_consultant'] ?? null,
                $validated['assigned_associate'] ?? null
            ),
            'clearance' => $this->mergeRegularTemplateClearancePayload(
                (array) ($templatePayload['clearance'] ?? []),
                $validated['prepared_by_default'] ?? ($validated['assigned_project_manager'] ?? ($validated['assigned_consultant'] ?? null)),
                $validated['sales_marketing'] ?? null,
                $validated['assigned_consultant'] ?? null,
                $validated['assigned_associate'] ?? null
            ),
        ]);

        ProjectSowReport::query()->create([
            'project_id' => $regular->id,
            'report_number' => '1.0',
            'date_prepared' => now()->toDateString(),
            'within_scope_items' => [],
            'out_of_scope_items' => [],
            'client_confirmation_name' => $regular->client_confirmation_name,
            'internal_approval' => [
                'report_period' => null,
                'prepared_by' => $validated['prepared_by_default'] ?? ($validated['assigned_project_manager'] ?? ($validated['assigned_consultant'] ?? null)),
                'prepared_by_name' => null,
                'prepared_by_date' => null,
                'reviewed_by' => $validated['assigned_project_manager'] ?? ($validated['assigned_consultant'] ?? 'Admin'),
                'reviewed_by_name' => null,
                'reviewed_by_date' => null,
                'referred_by_closed_by' => null,
                'sales_marketing' => $validated['sales_marketing'] ?? null,
                'lead_consultant' => $validated['assigned_consultant'] ?? null,
                'lead_associate_assigned' => $validated['assigned_associate'] ?? null,
                'finance' => $validated['finance'] ?? null,
                'president' => 'John Kelly Abalde',
                'record_custodian' => 'Record Custodian',
                'date_recorded' => now()->toDateString(),
                'date_signed' => null,
                'transmittal_no' => null,
                'date_submitted_for_transmittal' => null,
            ],
        ]);

        return redirect()
            ->route('regular.show', ['regular' => $regular, 'tab' => 'rsat'])
            ->with('success', 'Regular engagement created and opened in RSAT form.');
    }

    public function show(Request $request, Project $regular): View
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);

        $regular->load([
            'deal:id,deal_code,engagement_type',
            'contact:id,first_name,last_name,email,phone,company_name',
            'company:id,company_name',
            'company.latestBif',
            'starts' => fn ($query) => $query->latest(),
        ]);

        $rsat = $regular->starts->first() ?: new ProjectStart(['project_id' => $regular->id]);
        $report = $this->resolveDraftReport($regular);
        $ntpRecord = $regular->ntps()->latest()->first();
        $generatedReports = $regular->sowReports()
            ->whereJsonContains('internal_approval->__meta->generated', true)
            ->latest('date_prepared')
            ->latest()
            ->get();
        $rsatTemplates = Schema::hasTable('form_templates')
            ? $this->regularRsatTemplateQuery()->orderBy('name')->get()
            : collect();
        $rsatAutoReportSettings = $this->regularAutoReportSettings($regular);
        $tab = in_array((string) $request->query('tab', 'rsat'), ['rsat', 'report'], true)
            ? (string) $request->query('tab', 'rsat')
            : 'rsat';
        $regularLocked = $this->isRegularCompleted($regular);

        return view('regular.show', compact('regular', 'rsat', 'report', 'generatedReports', 'ntpRecord', 'rsatTemplates', 'tab', 'rsatAutoReportSettings', 'regularLocked'));
    }

    public function updateRsatAutoReportSettings(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        $metadata = (array) ($regular->metadata ?? []);
        $current = $this->regularAutoReportSettings($regular);

        data_set($metadata, 'auto_report.rsat', [
            'enabled' => (bool) ($validated['enabled'] ?? false),
            'day_of_month' => (int) $validated['day_of_month'],
            'last_generated_on' => $current['last_generated_on'],
        ]);

        $regular->forceFill(['metadata' => $metadata])->save();

        return redirect()
            ->route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat'])
            ->with('success', 'RSAT auto-report schedule updated.');
    }

    public function updateRsat(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);

        $validated = $this->validateRsatPayload($request);
        $this->persistRsatDocument($request, $regular, $validated);

        return redirect()->route('regular.show', ['regular' => $regular, 'tab' => 'rsat'])->with('success', 'RSAT form updated successfully.');
    }

    public function manualApproveRsat(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);

        $validated = $request->validate([
            'signed_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'approval_name' => ['nullable', 'string', 'max:255'],
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $rsat = $regular->starts()->latest()->first() ?: new ProjectStart(['project_id' => $regular->id]);
        $rsat->project_id = $regular->id;
        $rsat->status = 'approved';
        $rsat->approved_at = now();
        $rsat->approved_by_name = $this->currentEmployeeDisplayName($request) ?: 'Manual Override';

        $attachments = (array) ($rsat->attachments ?? []);
        if ($request->hasFile('signed_document')) {
            $attachments[] = [
                'label' => 'Manual approved RSAT',
                'path' => $request->file('signed_document')->store("regular/{$regular->id}/rsat/manual-approval", 'public'),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }
        $rsat->attachments = $attachments;
        $rsat->rejection_reason = trim((string) ($validated['approval_note'] ?? '')) ?: null;
        $rsat->save();

        $regular->forceFill([
            'status' => 'For NTP Approval',
            'current_phase' => 'For NTP Approval',
            'current_step' => 'RSAT manually approved',
        ])->save();

        return redirect()
            ->route('regular.show', ['regular' => $regular, 'tab' => 'rsat'])
            ->with('success', 'RSAT manually approved successfully.');
    }

    public function downloadRsatPdf(Project $regular)
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);

        $regular->load(['deal:id,deal_code', 'contact:id,first_name,last_name', 'starts' => fn ($query) => $query->latest()]);
        $rsat = $regular->starts->first();
        $rsatRequirements = collect($rsat?->engagement_requirements ?? []);
        $rsatApprovalSteps = collect($rsat?->approval_steps ?? []);
        $rsatClearance = (array) ($rsat?->clearance ?? []);

        $targetPath = 'generated-previews/regular/rsat/' . ($regular->project_code ?: $regular->id) . '-rsat-form.pdf';
        $pdfPath = $this->generatePdfPreview('regular.pdf.rsat', compact(
            'regular',
            'rsat',
            'rsatRequirements',
            'rsatApprovalSteps',
            'rsatClearance'
        ), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate RSAT PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    public function downloadNtpPdf(Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);
        $ntpRecord = $this->generateAndSendRegularNtp($regular);

        return redirect()
            ->route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat'])
            ->with('success', 'NTP generated successfully.'.($ntpRecord->client_form_sent_to_email ? ' NTP link sent to '.$ntpRecord->client_form_sent_to_email.'.' : ''))
            ->with('ntp_status', 'Waiting for client signed NTP upload.');
    }

    public function updateReport(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);

        $validated = $this->validateReportPayload($request);
        $this->persistDraftReport($request, $regular, $validated);

        return redirect()->route('regular.show', ['regular' => $regular, 'tab' => 'report'])->with('success', 'RSAT report updated successfully.');
    }

    public function generateReport(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);

        $validated = $this->validateRsatPayload($request);
        $rsat = $this->persistRsatDocument($request, $regular, $validated);

        $report = ProjectSowReport::query()->create([
            'project_id' => $regular->id,
            'report_number' => ProjectSowReport::generateNextRsatCode(),
            'date_prepared' => now()->toDateString(),
            'within_scope_items' => $this->buildReportRowsFromRsat($rsat),
            'out_of_scope_items' => [],
            'client_confirmation_name' => $regular->client_name,
            'internal_approval' => array_merge($this->buildReportApprovalFromRsat($regular, $rsat), [
                '__meta' => [
                    'generated' => true,
                    'generated_at' => now()->toDateTimeString(),
                ],
            ]),
        ]);

        $recipientEmail = $this->resolveRegularClientEmail($regular);
        $emailSentMessage = null;

        if ($recipientEmail !== null && $this->supportsRegularReportClientPortal()) {
            $this->sendRsatReportClientLink($regular, $report, $recipientEmail);
            $emailSentMessage = " Client approval link sent to {$recipientEmail}.";
        }

        return redirect()
            ->route('regular.report.preview', ['regular' => $regular->id, 'report' => $report->id])
            ->with('success', 'RSAT report generated successfully.'.$emailSentMessage);
    }

    public function showGeneratedReport(Project $regular, ProjectSowReport $report): View
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        abort_unless($report->project_id === $regular->id, 404);
        abort_unless((bool) data_get($report->internal_approval, '__meta.generated', false), 404);

        $regular->loadMissing([
            'deal:id,deal_code,customer_type,engagement_type',
            'contact:id,first_name,last_name,email,phone,company_name',
            'company:id,company_name',
            'company.latestBif',
            'starts' => fn ($query) => $query->latest(),
        ]);

        $rsat = $regular->starts->first() ?: new ProjectStart(['project_id' => $regular->id]);
        $clientEmail = $this->resolveRegularClientEmail($regular);
        $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: '-');

        return view('regular.report-preview', compact('regular', 'rsat', 'report', 'clientEmail', 'contactName'));
    }

    public function sendGeneratedReport(Project $regular, ProjectSowReport $report, Request $request): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        abort_unless($report->project_id === $regular->id, 404);
        $this->abortIfRegularCompleted($regular);

        if (! $this->supportsRegularReportClientPortal()) {
            return redirect()
                ->route('regular.report.preview', ['regular' => $regular->id, 'report' => $report->id])
                ->with('success', 'The report was generated, but client sending is not available until the latest project report migration is run.');
        }

        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $recipientEmail = trim((string) $validated['recipient_email']);
        $this->sendRsatReportClientLink($regular, $report, $recipientEmail);

        return redirect()
            ->route('regular.report.preview', ['regular' => $regular->id, 'report' => $report->id])
            ->with('success', "RSAT report client approval link sent to {$recipientEmail}.");
    }

    public function storeRsatTemplate(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);
        abort_if(! Schema::hasTable('form_templates'), 500, 'Templates table is not available.');

        $validated = array_merge(
            $this->validateRsatPayload($request),
            $request->validate([
                'template_name' => ['required', 'string', 'max:255'],
            ])
        );

        $rsat = $this->persistRsatDocument($request, $regular, $validated);

        FormTemplate::query()->create([
            'type' => 'regular_rsat',
            'name' => trim((string) $validated['template_name']),
            'payload' => [
                'engagement_requirements' => $rsat->engagement_requirements ?? [],
                'approval_steps' => $rsat->approval_steps ?? [],
                'clearance' => $rsat->clearance ?? [],
            ],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat'])
            ->with('success', 'RSAT template saved successfully.');
    }

    public function bulkDestroyGeneratedReports(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);
        $validated = $request->validate([
            'selected_reports' => ['required', 'array', 'min:1'],
            'selected_reports.*' => ['required', 'integer'],
        ]);

        $reports = $regular->sowReports()
            ->whereIn('id', $validated['selected_reports'])
            ->get();

        foreach ($reports as $report) {
            if ($report->client_attachment_path && Storage::disk('public')->exists($report->client_attachment_path)) {
                Storage::disk('public')->delete($report->client_attachment_path);
            }
        }

        $deletedCount = $regular->sowReports()
            ->whereIn('id', $validated['selected_reports'])
            ->delete();

        return redirect()
            ->route('regular.show', ['regular' => $regular->id, 'tab' => 'report'])
            ->with('success', $deletedCount === 1 ? '1 RSAT report deleted successfully.' : "{$deletedCount} RSAT reports deleted successfully.");
    }

    public function clientRsatReport(string $token): View
    {
        abort_unless($this->supportsRegularReportClientPortal(), 404);

        $report = $this->findRegularReportByClientToken($token);
        abort_if($report->client_access_expires_at && $report->client_access_expires_at->isPast(), 403, 'This RSAT report link has expired.');
        abort_if($report->project && $this->isRegularCompleted($report->project), 423, 'This completed regular engagement is view-only.');

        $regular = $report->project()->with(['deal:id,deal_code', 'contact:id,first_name,last_name,email', 'company:id,company_name'])->firstOrFail();
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: 'Client');

        return view('regular.client-report-form', [
            'regular' => $regular,
            'report' => $report,
            'contactName' => $contactName,
            'clientFormAction' => route('regular.report.client.submit', ['token' => $token]),
        ]);
    }

    public function submitClientRsatReport(Request $request, string $token): RedirectResponse
    {
        abort_unless($this->supportsRegularReportClientPortal(), 404);

        $report = $this->findRegularReportByClientToken($token);
        abort_if($report->client_access_expires_at && $report->client_access_expires_at->isPast(), 403, 'This RSAT report link has expired.');

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

            $report->client_attachment_path = $request->file('client_attachment')->store("regular/{$report->project_id}/reports", 'public');
        }

        $report->fill([
            'client_response_status' => 'approved',
            'client_approved_at' => now(),
            'client_approved_name' => $validated['client_approval_name'],
            'client_response_notes' => $validated['client_response_notes'] ?? null,
            'client_confirmation_name' => $validated['client_approval_name'],
        ]);
        $report->save();
        $regular = $report->project()->firstOrFail();
        $this->markRegularReadyForNextProcess($regular, 'Client approved RSAT report');

        return redirect()
            ->route('regular.report.client.show', ['token' => $token])
            ->with('success', 'Your approval has been recorded successfully.');
    }

    public function downloadClientRsatReport(string $token): RedirectResponse
    {
        abort_unless($this->supportsRegularReportClientPortal(), 404);

        $report = $this->findRegularReportByClientToken($token);
        abort_if($report->client_access_expires_at && $report->client_access_expires_at->isPast(), 403, 'This RSAT report link has expired.');

        $regular = $report->project()->with([
            'deal:id,deal_code,customer_type,engagement_type',
            'contact:id,first_name,last_name,email,phone,company_name',
            'company:id,company_name',
            'company.latestBif',
            'starts' => fn ($query) => $query->latest(),
        ])->firstOrFail();
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);

        $rsat = $regular->starts->first() ?: new ProjectStart(['project_id' => $regular->id]);
        $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: '-');

        $targetPath = 'generated-previews/regular/rsat-report/' . ($regular->project_code ?: $regular->id) . '-' . ($report->report_number ?: $report->id) . '.pdf';
        $pdfPath = $this->generatePdfPreview('regular.pdf.rsat-report', compact('regular', 'rsat', 'report', 'contactName'), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate RSAT report PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    public function clientNtp(string $token): View
    {
        $ntpRecord = $this->findRegularNtpByClientToken($token);
        abort_if($ntpRecord->client_access_expires_at && $ntpRecord->client_access_expires_at->isPast(), 403, 'This NTP link has expired.');
        abort_if($ntpRecord->project && $this->isRegularCompleted($ntpRecord->project), 423, 'This completed regular engagement is view-only.');

        $regular = $ntpRecord->project()->with(['deal:id,deal_code', 'contact:id,first_name,last_name,email', 'company:id,company_name'])->firstOrFail();
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: 'Client');

        return view('documents.client-ntp-form', [
            'project' => $regular,
            'ntpRecord' => $ntpRecord,
            'ntp' => $ntpRecord->payload ?? [],
            'contactName' => $contactName,
            'clientFormAction' => route('regular.ntp.client.submit', ['token' => $token]),
            'clientDownloadUrl' => route('regular.ntp.client.download', ['token' => $token]),
        ]);
    }

    public function showNtpSubmission(Project $regular): View
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);

        $ntpRecord = $regular->ntps()->latest()->firstOrFail();
        $regular->loadMissing(['deal:id,deal_code', 'contact:id,first_name,last_name,email', 'company:id,company_name']);
        $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: 'Client');

        return view('project.ntp-submission', [
            'project' => $regular,
            'ntpRecord' => $ntpRecord,
            'ntp' => $ntpRecord->payload ?? [],
            'contactName' => $contactName,
        ]);
    }

    public function submitClientNtp(Request $request, string $token): RedirectResponse
    {
        $ntpRecord = $this->findRegularNtpByClientToken($token);
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
            'client_attachment_path' => $request->file('client_attachment')->store("regular/{$ntpRecord->project_id}/ntp", 'public'),
        ]);
        $ntpRecord->save();
        $this->markRegularInProgress($ntpRecord->project, 'Client signed NTP submitted');

        return redirect()
            ->route('regular.ntp.client.show', ['token' => $token])
            ->with('success', 'Approved to proceed. The signed NTP was submitted successfully.');
    }

    public function manualApproveNtp(Request $request, Project $regular): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        $this->abortIfRegularCompleted($regular);

        $ntpRecord = $regular->ntps()->latest()->first()
            ?: $this->generateAndSendRegularNtp($regular, false);
        $validated = $this->validateSignedApprovalUpload($request);

        $this->replaceClientAttachment($ntpRecord, $request, "regular/{$regular->id}/ntp");
        $ntpRecord->fill([
            'client_response_status' => 'approved_to_proceed',
            'client_approved_at' => now(),
            'client_approved_name' => $this->currentEmployeeDisplayName($request) ?: 'Manual Override',
            'client_response_notes' => trim((string) ($validated['approval_note'] ?? '')) ?: 'Approved by manual override using uploaded signed NTP.',
        ]);
        $ntpRecord->save();
        $this->markRegularInProgress($regular, 'Signed NTP uploaded manually');

        return redirect()
            ->route('regular.show', ['regular' => $regular->id, 'tab' => 'rsat'])
            ->with('success', 'Signed NTP uploaded and approved manually. Regular engagement is now in progress.');
    }

    public function manualApproveReport(Request $request, Project $regular, ProjectSowReport $report): RedirectResponse
    {
        abort_unless($this->isRegularEngagement($regular->engagement_type), 404);
        abort_unless($report->project_id === $regular->id, 404);
        $this->abortIfRegularCompleted($regular);

        $validated = $this->validateSignedApprovalUpload($request);

        $this->replaceClientAttachment($report, $request, "regular/{$regular->id}/reports");
        $report->fill([
            'client_response_status' => 'approved',
            'client_approved_at' => now(),
            'client_approved_name' => $this->currentEmployeeDisplayName($request) ?: 'Manual Override',
            'client_response_notes' => trim((string) ($validated['approval_note'] ?? '')) ?: 'Approved by manual override using uploaded signed RSAT report.',
            'client_confirmation_name' => $report->client_confirmation_name ?: $regular->client_name,
        ]);
        $report->save();
        $this->markRegularReadyForNextProcess($regular, 'Signed RSAT report uploaded manually');

        return redirect()
            ->route('regular.report.preview', ['regular' => $regular->id, 'report' => $report->id])
            ->with('success', 'Signed RSAT report uploaded and approved manually.');
    }

    public function downloadClientNtp(string $token): RedirectResponse
    {
        $ntpRecord = $this->findRegularNtpByClientToken($token);
        abort_if($ntpRecord->client_access_expires_at && $ntpRecord->client_access_expires_at->isPast(), 403, 'This NTP link has expired.');

        return $this->downloadRegularNtpPdfFromRecord($ntpRecord);
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

    private function replaceClientAttachment(ProjectNtp|ProjectSowReport $record, Request $request, string $directory): void
    {
        if ($record->client_attachment_path && Storage::disk('public')->exists($record->client_attachment_path)) {
            Storage::disk('public')->delete($record->client_attachment_path);
        }

        $record->client_attachment_path = $request->file('signed_document')->store($directory, 'public');
    }

    private function markRegularInProgress(?Project $regular, string $step): void
    {
        if (! $regular) {
            return;
        }

        $regular->forceFill([
            'status' => 'In Progress',
            'current_phase' => 'In Progress',
            'current_step' => $step,
        ])->save();
    }

    private function markRegularReadyForNextProcess(Project $regular, string $step): void
    {
        $regular->forceFill([
            'status' => 'Delivery',
            'current_phase' => 'Delivery',
            'current_step' => $step,
        ])->save();
    }

    private function isRegularCompleted(Project $regular): bool
    {
        return strcasecmp((string) $regular->status, 'Completed') === 0;
    }

    private function abortIfRegularCompleted(Project $regular): void
    {
        abort_if($this->isRegularCompleted($regular), 423, 'Completed regular engagements are view-only. Documents can be viewed or downloaded, but no longer edited.');
    }

    private function provisionMissingRegularRecords(): void
    {
        if (! Schema::hasTable('deals') || ! Schema::hasTable('projects')) {
            return;
        }

        Deal::query()
            ->whereDoesntHave('projects', fn ($query) => $query->whereRaw('LOWER(COALESCE(engagement_type, "")) LIKE ?', ['%regular%']))
            ->get()
            ->filter(fn (Deal $deal): bool => $this->isRegularEngagement($deal->engagement_type))
            ->each(fn (Deal $deal) => $this->projectProvisioner->createOrSyncFromDeal($deal));
    }

    private function isRegularEngagement(?string $engagementType): bool
    {
        return Str::contains(Str::lower(trim((string) $engagementType)), 'regular');
    }

    private function validateRsatPayload(Request $request): array
    {
        return $request->validate([
            'form_date' => ['nullable', 'date'],
            'date_started' => ['nullable', 'date'],
            'date_completed' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'engagement_requirement' => ['nullable', 'array'],
            'engagement_requirement.*' => ['nullable', 'string', 'max:255'],
            'engagement_notes' => ['nullable', 'array'],
            'engagement_notes.*' => ['nullable', 'string', 'max:255'],
            'engagement_purpose' => ['nullable', 'array'],
            'engagement_purpose.*' => ['nullable', 'string', 'max:255'],
            'engagement_provided_by' => ['nullable', 'array'],
            'engagement_provided_by.*' => ['nullable', 'string', 'max:255'],
            'engagement_submitted_to' => ['nullable', 'array'],
            'engagement_submitted_to.*' => ['nullable', 'date'],
            'engagement_assigned_to' => ['nullable', 'array'],
            'engagement_assigned_to.*' => ['nullable', 'string', 'max:255'],
            'engagement_timeline' => ['nullable', 'array'],
            'engagement_timeline.*' => ['nullable', 'string', 'max:255'],
            'engagement_status' => ['nullable', 'array'],
            'engagement_status.*' => ['nullable', 'in:open,in_progress,delayed,completed,on_hold'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,ppt,pptx,txt', 'max:10240'],
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
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function persistRsatDocument(Request $request, Project $regular, array $validated): ProjectStart
    {
        $rsat = $regular->starts()->latest()->first() ?: new ProjectStart(['project_id' => $regular->id]);
        $resolvedFormDate = $validated['form_date']
            ?? optional($rsat->form_date)->toDateString()
            ?? optional($rsat->created_at)->toDateString()
            ?? now()->toDateString();

        $rsat->fill([
            'form_date' => $resolvedFormDate,
            'date_started' => $validated['date_started'] ?? null,
            'date_completed' => $validated['date_completed'] ?? null,
            'status' => $validated['status'],
            'engagement_requirements' => $this->buildRequirementRows($request, 'engagement'),
            'attachments' => $this->storeRsatAttachments($request, $regular, $rsat),
            'approval_steps' => $this->buildApprovalRows($request),
            'clearance' => $this->buildClearancePayload($validated),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);
        $rsat->project_id = $regular->id;
        $rsat->save();

        return $rsat;
    }

    private function regularRsatTemplateQuery()
    {
        return FormTemplate::query()
            ->where('type', 'regular_rsat')
            ->when(
                Schema::hasColumn('form_templates', 'status'),
                fn ($query) => $query->where('status', 'approved')
            );
    }

    private function validateReportPayload(Request $request): array
    {
        return $request->validate([
            'report_number' => ['nullable', 'string', 'max:50'],
            'date_prepared' => ['nullable', 'date'],
            'report_period' => ['nullable', 'string', 'max:255'],
            'report_service' => ['nullable', 'array'],
            'report_service.*' => ['nullable', 'string', 'max:255'],
            'report_activity_output' => ['nullable', 'array'],
            'report_activity_output.*' => ['nullable', 'string', 'max:255'],
            'report_frequency' => ['nullable', 'array'],
            'report_frequency.*' => ['nullable', 'string', 'max:255'],
            'report_reminder_lead_time' => ['nullable', 'array'],
            'report_reminder_lead_time.*' => ['nullable', 'string', 'max:255'],
            'report_deadline' => ['nullable', 'array'],
            'report_deadline.*' => ['nullable', 'string', 'max:255'],
            'client_confirmation_name' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'prepared_by_name' => ['nullable', 'string', 'max:255'],
            'prepared_by_date' => ['nullable', 'string', 'max:255'],
            'reviewed_by' => ['nullable', 'string', 'max:255'],
            'reviewed_by_name' => ['nullable', 'string', 'max:255'],
            'reviewed_by_date' => ['nullable', 'string', 'max:255'],
            'referred_by_closed_by' => ['nullable', 'string', 'max:255'],
            'sales_marketing' => ['nullable', 'string', 'max:255'],
            'lead_consultant' => ['nullable', 'string', 'max:255'],
            'lead_associate_assigned' => ['nullable', 'string', 'max:255'],
            'finance' => ['nullable', 'string', 'max:255'],
            'president' => ['nullable', 'string', 'max:255'],
            'record_custodian' => ['nullable', 'string', 'max:255'],
            'date_recorded' => ['nullable', 'date'],
            'date_signed' => ['nullable', 'date'],
            'transmittal_no' => ['nullable', 'string', 'max:255'],
            'date_submitted_for_transmittal' => ['nullable', 'date'],
        ]);
    }

    private function resolveDraftReport(Project $regular): ProjectSowReport
    {
        return $regular->sowReports()
            ->where(function ($query) {
                $query->whereNull('internal_approval->__meta->generated')
                    ->orWhere('internal_approval->__meta->generated', false);
            })
            ->latest()
            ->first() ?: new ProjectSowReport(['project_id' => $regular->id]);
    }

    private function persistDraftReport(Request $request, Project $regular, array $validated): ProjectSowReport
    {
        $report = $this->resolveDraftReport($regular);
        $approval = [
            'report_period' => $validated['report_period'] ?? null,
            'prepared_by' => $validated['prepared_by'] ?? null,
            'prepared_by_name' => $validated['prepared_by_name'] ?? null,
            'prepared_by_date' => $validated['prepared_by_date'] ?? null,
            'reviewed_by' => $validated['reviewed_by'] ?? null,
            'reviewed_by_name' => $validated['reviewed_by_name'] ?? null,
            'reviewed_by_date' => $validated['reviewed_by_date'] ?? null,
            'referred_by_closed_by' => $validated['referred_by_closed_by'] ?? null,
            'sales_marketing' => $validated['sales_marketing'] ?? null,
            'lead_consultant' => $validated['lead_consultant'] ?? null,
            'lead_associate_assigned' => $validated['lead_associate_assigned'] ?? null,
            'finance' => $validated['finance'] ?? null,
            'president' => $validated['president'] ?? null,
            'record_custodian' => $validated['record_custodian'] ?? null,
            'date_recorded' => $validated['date_recorded'] ?? null,
            'date_signed' => $validated['date_signed'] ?? null,
            'transmittal_no' => $validated['transmittal_no'] ?? null,
            'date_submitted_for_transmittal' => $validated['date_submitted_for_transmittal'] ?? null,
            '__meta' => [
                'generated' => false,
            ],
        ];

        $report->fill([
            'report_number' => $validated['report_number']
                ?: ($report->report_number ?: ProjectSowReport::generateNextRsatCode()),
            'date_prepared' => $validated['date_prepared'] ?? null,
            'within_scope_items' => $this->buildReportRows($request),
            'out_of_scope_items' => [],
            'client_confirmation_name' => $validated['client_confirmation_name'] ?? null,
            'internal_approval' => $approval,
        ]);
        $report->project_id = $regular->id;
        $report->save();

        return $report;
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
        $statuses = (array) $request->input("{$prefix}_status", []);

        return collect($requirements)->map(function ($value, $index) use ($notes, $purposes, $providedBy, $submittedTo, $assigned, $timelines, $statuses) {
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
                'status' => in_array(($statuses[$index] ?? 'open'), ['open', 'in_progress', 'delayed', 'completed', 'on_hold'], true)
                    ? (string) ($statuses[$index] ?? 'open')
                    : 'open',
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

    private function buildReportRows(Request $request): array
    {
        $services = (array) $request->input('report_service', []);
        $activities = (array) $request->input('report_activity_output', []);
        $frequencies = (array) $request->input('report_frequency', []);
        $reminders = (array) $request->input('report_reminder_lead_time', []);
        $deadlines = (array) $request->input('report_deadline', []);
        $statuses = (array) $request->input('report_status', []);

        return collect($activities)->map(function ($value, $index) use ($services, $frequencies, $reminders, $deadlines, $statuses) {
            $service = trim((string) ($services[$index] ?? ''));
            $activity = trim((string) $value);
            $frequency = trim((string) ($frequencies[$index] ?? ''));
            $reminder = trim((string) ($reminders[$index] ?? ''));
            $deadline = trim((string) ($deadlines[$index] ?? ''));
            $status = trim((string) ($statuses[$index] ?? 'open'));

            if ($service === '' && $activity === '' && $frequency === '' && $reminder === '' && $deadline === '' && $status === '') {
                return null;
            }

            return [
                'service' => $service,
                'activity_output' => $activity,
                'frequency' => $frequency,
                'reminder_lead_time' => $reminder,
                'deadline' => $deadline,
                'status' => in_array($status, ['open', 'in_progress', 'delayed', 'completed', 'on_hold'], true) ? $status : 'open',
            ];
        })->filter()->values()->all();
    }

    private function buildReportRowsFromRsat(ProjectStart $rsat): array
    {
        return collect($rsat->engagement_requirements ?? [])
            ->map(fn (array $row): array => [
                'service' => trim((string) ($row['purpose'] ?? '')),
                'activity_output' => trim((string) ($row['requirement'] ?? '')),
                'frequency' => trim((string) ($row['notes'] ?? '')),
                'reminder_lead_time' => trim((string) ($row['timeline'] ?? '')),
                'deadline' => trim((string) ($row['submitted_to'] ?? '')),
                'status' => trim((string) ($row['status'] ?? 'open')),
            ])
            ->filter(fn (array $row): bool => collect($row)->contains(fn ($value) => $value !== ''))
            ->values()
            ->all();
    }

    private function mergeRegularTemplateApprovalSteps(array $templateSteps, ?string $salesMarketing, ?string $finance, ?string $consultant, ?string $associate): array
    {
        $fallback = $this->buildManualApprovalSteps($salesMarketing, $consultant, $associate);

        $steps = collect($templateSteps)
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all() ?: $fallback;

        if (filled($salesMarketing) || filled($finance)) {
            $steps = collect($steps)
                ->map(function (array $row) use ($salesMarketing, $finance): array {
                    $responsible = trim((string) ($row['responsible_person'] ?? ''));
                    if (filled($salesMarketing) && $responsible === 'Sales & Marketing') {
                        $row['responsible_person'] = $salesMarketing;
                    } elseif (filled($salesMarketing) && str_starts_with($responsible, 'Sales & Marketing/')) {
                        $row['responsible_person'] = $salesMarketing.substr($responsible, strlen('Sales & Marketing'));
                    } elseif (filled($finance) && $responsible === 'Finance') {
                        $row['responsible_person'] = $finance;
                    }

                    return $row;
                })
                ->values()
                ->all();
        }

        return $steps;
    }

    private function mergeRegularTemplateClearancePayload(array $templateClearance, ?string $preparedBy, ?string $salesMarketing, ?string $consultant, ?string $associate): array
    {
        $fallback = $this->buildManualClearancePayload($preparedBy, $salesMarketing, $consultant, $associate);

        $merged = array_replace_recursive($fallback, array_filter($templateClearance, fn ($value) => $value !== null));
        if (filled($salesMarketing)) {
            $merged['sales_marketing'] = $salesMarketing;
        }

        return $merged;
    }

    private function regularContactRecords(): array
    {
        if (! Schema::hasTable('contacts')) {
            return [];
        }

        return Contact::query()
            ->select(['id', 'salutation', 'first_name', 'middle_initial', 'middle_name', 'last_name', 'name_extension', 'email', 'phone', 'contact_address', 'company_name', 'company_address', 'position'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Contact $contact): array {
                return [
                    'id' => $contact->id,
                    'label' => trim(collect([$contact->salutation, $contact->first_name, $contact->middle_name, $contact->last_name])->filter()->implode(' ')),
                    'search_blob' => Str::lower(implode(' ', array_filter([$contact->first_name, $contact->middle_initial, $contact->middle_name, $contact->last_name, $contact->company_name, $contact->email, $contact->phone]))),
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

    private function regularCompanyRecords(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        return Company::query()
            ->with(['primaryContact:id,first_name,middle_name,last_name,email,phone,contact_address,position,company_name'])
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
                    'primary_contact_name' => trim(collect([$primaryContact?->first_name, $primaryContact?->middle_name, $primaryContact?->last_name])->filter()->implode(' ')),
                    'primary_contact_email' => $primaryContact?->email,
                    'primary_contact_mobile' => $primaryContact?->phone,
                    'primary_contact_position' => $primaryContact?->position,
                ];
            })
            ->filter(fn (array $record): bool => filled($record['company_name']))
            ->values()
            ->all();
    }

    private function regularDealRecords(): array
    {
        if (! Schema::hasTable('deals')) {
            return [];
        }

        return Deal::query()
            ->with(['contact:id,first_name,middle_name,last_name,email,phone,company_name,company_address,position'])
            ->whereDoesntHave('projects', fn ($query) => $query->whereRaw('LOWER(COALESCE(engagement_type, "")) LIKE ?', ['%regular%']))
            ->latest()
            ->get()
            ->filter(fn (Deal $deal): bool => $this->isRegularEngagement($deal->engagement_type))
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
                    'planned_start_date' => optional($deal->planned_start_date)->format('Y-m-d'),
                    'target_completion_date' => optional($deal->estimated_completion_date)->format('Y-m-d'),
                    'assigned_project_manager' => $deal->assigned_consultant,
                    'assigned_consultant' => $deal->assigned_consultant,
                    'assigned_associate' => $deal->assigned_associate,
                    'sales_marketing' => $deal->internal_sales_marketing,
                    'client_confirmation_name' => $clientName,
                    'engagement_type' => $deal->engagement_type,
                ];
            })
            ->values()
            ->all();
    }

    private function regularServiceCatalog(): array
    {
        try {
            if (! Schema::hasTable('services')) {
                return [
                    'serviceAreaOptions' => [],
                    'serviceGroups' => [],
                ];
            }

            $services = \App\Models\Service::query()
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

            return [
                'serviceAreaOptions' => array_values(array_unique(array_keys($serviceGroups))),
                'serviceGroups' => $serviceGroups,
            ];
        } catch (\Throwable) {
            return [
                'serviceAreaOptions' => [],
                'serviceGroups' => [],
            ];
        }
    }

    private function regularProductCatalog(): array
    {
        try {
            if (! Schema::hasTable('products')) {
                return ['productOptionsByServiceArea' => []];
            }

            $select = ['product_name', 'status'];
            foreach (['linked_service_id', 'linked_service_ids'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $select[] = $column;
                }
            }

            $serviceAreasById = $this->regularLinkedServiceAreasById();
            $products = Product::query()
                ->select($select)
                ->whereNotNull('product_name')
                ->where('product_name', '!=', '')
                ->when(Schema::hasColumn('products', 'status'), fn ($query) => $query->whereIn('status', ['Pending Approval', 'Active']))
                ->orderBy('product_name')
                ->get();

            $groups = [
                'Products Without Service Area' => [],
            ];

            foreach ($products as $product) {
                $productName = trim((string) $product->product_name);
                if ($productName === '') {
                    continue;
                }

                $areas = $this->normalizeProductServiceAreas($product, $serviceAreasById);

                if ($areas === []) {
                    $groups['Products Without Service Area'][] = $productName;
                    continue;
                }

                foreach ($areas as $area) {
                    $groups[$area] ??= [];
                    $groups[$area][] = $productName;
                }
            }

            $groups = collect($groups)
                ->map(fn (array $group): array => collect($group)->filter()->unique()->sort()->values()->all())
                ->filter(fn (array $group): bool => $group !== [])
                ->sortKeys()
                ->all();

            return ['productOptionsByServiceArea' => $groups];
        } catch (\Throwable) {
            return ['productOptionsByServiceArea' => []];
        }
    }

    private function normalizeProductServiceAreas(Product $product, array $serviceAreasById): array
    {
        $linkedServiceIds = $this->normalizeProductLinkedServiceIds($product);

        return $linkedServiceIds === []
            ? []
            : collect($serviceAreasById)
                ->only($linkedServiceIds)
                ->flatten()
                ->filter()
                ->unique()
                ->values()
                ->all();
    }

    private function normalizeProductLinkedServiceIds(Product $product): array
    {
        return collect($product->linked_service_ids ?? [])
            ->merge(filled($product->linked_service_id) ? [$product->linked_service_id] : [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function regularLinkedServiceAreasById(): array
    {
        if (! Schema::hasTable('services')) {
            return [];
        }

        return Service::query()
            ->select(['id', 'service_area', 'service_area_other'])
            ->get()
            ->mapWithKeys(fn (Service $service): array => [
                $service->id => $this->normalizeServiceAreas($service),
            ])
            ->filter(fn (array $areas): bool => $areas !== [])
            ->all();
    }

    private function normalizeServiceAreas(Service $service): array
    {
        $areas = collect($service->service_area ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->reject(fn (string $value): bool => Str::lower($value) === 'none')
            ->values();

        if ($areas->contains('Others') && filled($service->service_area_other)) {
            $areas = $areas
                ->reject(fn (string $value): bool => $value === 'Others')
                ->push(trim((string) $service->service_area_other))
                ->values();
        }

        if ($areas->isEmpty() && filled($service->service_area_other)) {
            $areas = collect([trim((string) $service->service_area_other)]);
        }

        return $areas->unique()->values()->all();
    }

    private function stringifySelectedValues(array $selected, array $custom, string $customPrefix, array $ignored = []): ?string
    {
        $base = collect($selected)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '' && ! in_array($value, $ignored, true) && $value !== 'Others')
            ->values();

        $customValues = collect($custom)
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->map(fn (string $value): string => $customPrefix.$value)
            ->values();

        $values = $base->concat($customValues)->unique()->values();

        return $values->isEmpty() ? null : $values->implode(', ');
    }

    private function buildReportApprovalFromRsat(Project $regular, ProjectStart $rsat): array
    {
        $clearance = (array) ($rsat->clearance ?? []);

        return [
            'report_period' => null,
            'prepared_by' => $clearance['assigned_team_lead'] ?? null,
            'prepared_by_name' => null,
            'prepared_by_date' => null,
            'reviewed_by' => $clearance['lead_consultant_confirmed'] ?? null,
            'reviewed_by_name' => null,
            'reviewed_by_date' => null,
            'referred_by_closed_by' => $rsat->rejection_reason,
            'sales_marketing' => $clearance['sales_marketing'] ?? null,
            'lead_consultant' => $regular->assigned_consultant,
            'lead_associate_assigned' => $clearance['lead_associate_assigned'] ?? null,
            'finance' => data_get($regular->metadata ?? [], 'internal_assignments.finance'),
            'president' => 'John Kelly Abalde',
            'record_custodian' => $clearance['record_custodian_name'] ?? null,
            'date_recorded' => $clearance['date_recorded'] ?? null,
            'date_signed' => $clearance['date_signed'] ?? null,
            'transmittal_no' => null,
            'date_submitted_for_transmittal' => null,
        ];
    }

    private function storeRsatAttachments(Request $request, Project $regular, ProjectStart $rsat): array
    {
        $existing = collect($rsat->attachments ?? [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['path'] ?? null))
            ->values();

        if (! $request->hasFile('attachments')) {
            return $existing->all();
        }

        $newFiles = collect($request->file('attachments', []))
            ->filter()
            ->map(function ($file) use ($regular): array {
                $path = $file->store("regular/{$regular->id}/rsat-attachments", 'public');

                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            });

        return $existing->concat($newFiles)->values()->all();
    }

    private function buildManualRequirementRows(string $rawRequirements, string $assignedTo): array
    {
        $rows = collect(preg_split('/[\r\n]+/', trim($rawRequirements)))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->map(fn (string $item, int $index): array => [
                'number' => $index + 1,
                'requirement' => $item,
                'notes' => '',
                'purpose' => 'Regular service activity',
                'provided_by' => 'Client',
                'submitted_to' => '',
                'assigned_to' => $assignedTo,
                'timeline' => 'Recurring schedule',
                'status' => 'open',
            ])
            ->all();

        if ($rows !== []) {
            return $rows;
        }

        return [[
            'number' => 1,
            'requirement' => 'Initial RSAT intake and service routing',
            'notes' => '',
            'purpose' => 'Regular service activity',
            'provided_by' => 'Client',
            'submitted_to' => '',
            'assigned_to' => $assignedTo,
            'timeline' => 'Recurring schedule',
            'status' => 'open',
        ]];
    }

    private function buildManualApprovalSteps(?string $salesMarketing, ?string $assignedConsultant, ?string $assignedAssociate): array
    {
        $sales = $salesMarketing ?: 'Sales & Marketing';
        $leadConsultant = $assignedConsultant ?: 'Lead Consultant';
        $leadAssociate = $assignedAssociate ?: 'Lead Associate';

        return [
            ['requirement' => 'Client Contact Form', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Business Information Form', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Regular Service Activity Tracker (RSAT)', 'responsible_person' => $sales, 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Engagement-Specific Requirement', 'responsible_person' => $sales.'/'.$leadConsultant.'/'.$leadAssociate, 'name_and_signature' => '', 'date_time_done' => ''],
        ];
    }

    private function buildManualClearancePayload(?string $preparedBy, ?string $salesMarketing, ?string $assignedConsultant, ?string $assignedAssociate): array
    {
        return [
            'assigned_team_lead' => $preparedBy ?? '',
            'assigned_team_lead_signature' => '',
            'lead_consultant_confirmed' => $assignedConsultant ?? '',
            'lead_consultant_signature' => '',
            'lead_associate_assigned' => $assignedAssociate ?? '',
            'lead_associate_signature' => '',
            'sales_marketing' => $salesMarketing ?? '',
            'sales_marketing_signature' => '',
            'record_custodian_name' => 'Record Custodian',
            'record_custodian_signature' => '',
            'date_recorded' => now()->toDateString(),
            'date_signed' => null,
        ];
    }

    private function resolveRegularClientEmail(Project $regular): ?string
    {
        $regular->loadMissing([
            'contact:id,email,first_name,last_name',
            'company.primaryContact:id,email,first_name,last_name',
        ]);

        $email = trim((string) ($regular->contact?->email
            ?: $regular->company?->primaryContact?->email
            ?: ''));

        return $email !== '' ? $email : null;
    }

    private function supportsRegularReportClientPortal(): bool
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

    private function sendRsatReportClientLink(Project $regular, ProjectSowReport $report, string $recipientEmail): void
    {
        $regular->loadMissing([
            'deal:id,deal_code',
            'contact:id,first_name,last_name,email',
            'company:id,company_name',
        ]);

        $token = Str::random(64);
        $expiresAt = now()->addDays(self::CLIENT_LINK_TTL_DAYS);
        $clientUrl = route('regular.report.client.show', ['token' => $token]);
        $clientName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: 'Client');

        $report->fill([
            'client_access_token' => $token,
            'client_access_expires_at' => $expiresAt,
            'client_form_sent_to_email' => $recipientEmail,
            'client_form_sent_at' => now(),
        ]);
        $report->save();

        $emailHtml = view('emails.regular.rsat-report-client-link', [
            'regular' => $regular,
            'report' => $report,
            'clientName' => $clientName,
            'clientUrl' => $clientUrl,
            'expiresAt' => $expiresAt,
        ])->render();

        Mail::html($emailHtml, function ($message) use ($recipientEmail, $regular, $report) {
            $message
                ->from(config('mail.from.address'), 'John Kelly & Company')
                ->to($recipientEmail)
                ->subject("RSAT Report Approval for {$regular->name} ({$report->report_number})");
        });
    }

    private function findRegularReportByClientToken(string $token): ProjectSowReport
    {
        return ProjectSowReport::query()
            ->where('client_access_token', $token)
            ->firstOrFail();
    }

    private function buildRegularNtpPayload(Project $regular): array
    {
        $regular->loadMissing([
            'deal:id,deal_code',
            'contact:id,first_name,last_name',
            'sowReports' => fn ($query) => $query->latest(),
        ]);

        $report = $regular->sowReports()
            ->whereJsonContains('internal_approval->__meta->generated', true)
            ->latest('date_prepared')
            ->latest()
            ->first();
        $contactName = trim(collect([$regular->contact?->first_name, $regular->contact?->last_name])->filter()->implode(' '))
            ?: ($regular->client_name ?: '-');

        return [
            'title' => 'NOTICE TO PROCEED',
            'form_code' => 'REG-F-002',
            'date_issued' => now()->format('F d, Y'),
            'ntp_no' => null,
            'engagement_type' => $regular->engagement_type ?: 'Regular Retainer',
            'condeal_reference_no' => $regular->deal?->deal_code ?: '-',
            'client_name' => $contactName,
            'business_name' => $regular->business_name ?: '-',
            'engagement_reference_label' => 'RSAT Ref No.:',
            'engagement_reference_no' => $report?->report_number ?: '-',
            'approved_start_date' => optional($regular->planned_start_date)->format('F d, Y') ?: '-',
            'target_completion_date' => optional($regular->target_completion_date)->format('F d, Y') ?: '-',
            'client_representative' => $regular->client_name ?: $contactName,
            'lead_consultant' => $regular->assigned_consultant ?: ($regular->assigned_project_manager ?: ''),
            'associate' => $regular->assigned_associate ?: '',
        ];
    }

    private function generateAndSendRegularNtp(Project $regular, bool $sendClientLink = true): ProjectNtp
    {
        $payload = $this->buildRegularNtpPayload($regular);
        $report = $regular->sowReports()
            ->whereJsonContains('internal_approval->__meta->generated', true)
            ->latest('date_prepared')
            ->latest()
            ->first();

        $ntpRecord = $regular->ntps()->latest()->first() ?: new ProjectNtp(['project_id' => $regular->id]);
        $ntpRecord->fill([
            'reference_type' => 'rsat',
            'reference_number' => $report?->report_number,
            'date_issued' => now()->toDateString(),
            'payload' => $payload,
        ]);
        $ntpRecord->project_id = $regular->id;
        $ntpRecord->save();

        $payload = $ntpRecord->payload ?? [];
        $payload['ntp_no'] = $ntpRecord->ntp_number;
        $ntpRecord->payload = $payload;
        $ntpRecord->save();

        $recipientEmail = $this->resolveRegularClientEmail($regular);
        if ($sendClientLink && $recipientEmail !== null) {
            $this->sendRegularNtpClientLink($regular, $ntpRecord, $recipientEmail);
        }

        $regular->forceFill([
            'status' => 'For NTP Approval',
            'current_phase' => 'For NTP Approval',
            'current_step' => 'Waiting for signed NTP',
        ])->save();

        return $ntpRecord->fresh();
    }

    private function sendRegularNtpClientLink(Project $regular, ProjectNtp $ntpRecord, string $recipientEmail): void
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(self::CLIENT_LINK_TTL_DAYS);
        $clientUrl = route('regular.ntp.client.show', ['token' => $token]);
        $clientName = data_get($ntpRecord->payload, 'client_name', $regular->client_name ?: 'Client');

        $ntpRecord->fill([
            'client_access_token' => $token,
            'client_access_expires_at' => $expiresAt,
            'client_form_sent_to_email' => $recipientEmail,
            'client_form_sent_at' => now(),
        ]);
        $ntpRecord->save();

        $emailHtml = view('emails.documents.ntp-client-link', [
            'project' => $regular,
            'ntpRecord' => $ntpRecord,
            'clientName' => $clientName,
            'clientUrl' => $clientUrl,
            'expiresAt' => $expiresAt,
        ])->render();

        Mail::html($emailHtml, function ($message) use ($recipientEmail, $regular, $ntpRecord) {
            $message
                ->from(config('mail.from.address'), 'John Kelly & Company')
                ->to($recipientEmail)
                ->subject("Notice To Proceed for {$regular->name} ({$ntpRecord->ntp_number})");
        });
    }

    private function findRegularNtpByClientToken(string $token): ProjectNtp
    {
        return ProjectNtp::query()
            ->where('client_access_token', $token)
            ->firstOrFail();
    }

    private function downloadRegularNtpPdfFromRecord(ProjectNtp $ntpRecord): RedirectResponse
    {
        $ntp = $ntpRecord->payload ?? [];
        $ntp['ntp_no'] = $ntpRecord->ntp_number;
        $targetPath = 'generated-previews/regular/ntp/' . ($ntpRecord->project?->project_code ?: $ntpRecord->project_id) . '-' . $ntpRecord->ntp_number . '.pdf';
        $pdfPath = $this->generatePdfPreview('documents.pdf.ntp', compact('ntp'), $targetPath);

        abort_unless($pdfPath && Storage::disk('public')->exists($pdfPath), 500, 'Unable to generate NTP PDF preview.');

        return redirect()->route('uploads.show', ['path' => $pdfPath, 'download' => 1]);
    }

    private function regularAutoReportSettings(Project $regular): array
    {
        $raw = (array) data_get($regular->metadata, 'auto_report.rsat', []);

        return [
            'enabled' => (bool) ($raw['enabled'] ?? false),
            'day_of_month' => (int) ($raw['day_of_month'] ?? 30),
            'last_generated_on' => filled($raw['last_generated_on'] ?? null) ? (string) $raw['last_generated_on'] : null,
        ];
    }

    private function employeeLookupRecords(): array
    {
        if (! Schema::hasTable('employees')) {
            return [];
        }

        return Employee::query()
            ->select(['id', 'employee_code', 'first_name', 'last_name', 'position', 'email', 'work_email'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Employee $employee): array {
                $label = trim((string) $employee->full_name);

                return [
                    'id' => $employee->id,
                    'label' => $label,
                    'position' => $employee->position,
                    'employee_code' => $employee->employee_code,
                    'email' => $employee->work_email ?: $employee->email,
                    'search_blob' => Str::lower(implode(' ', array_filter([
                        $label,
                        $employee->employee_code,
                        $employee->position,
                        $employee->work_email,
                        $employee->email,
                    ]))),
                ];
            })
            ->filter(fn (array $employee): bool => $employee['label'] !== '')
            ->values()
            ->all();
    }

    private function currentEmployeeDisplayName(Request $request): ?string
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $employee = $user->employeeProfile;

        return filled($employee?->full_name) ? $employee->full_name : ($user->name ?: null);
    }
}
