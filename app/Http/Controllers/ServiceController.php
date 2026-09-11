<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceVersion;
use App\Models\ServiceActivity;
use App\Models\ServiceRequirement;
use App\Models\ServiceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    // --- SAFE HELPER FOR SYSTEM NOTIFICATIONS ---
    private function sendUserNotification($title, $message, $url)
    {
        if (auth()->check() && Schema::hasTable('notifications')) {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => 'App\Notifications\SystemNotification',
                'notifiable_type' => get_class(auth()->user()),
                'notifiable_id'   => auth()->id(),
                'data'            => json_encode([
                    'title'   => $title,
                    'message' => $message,
                    'url'     => $url,
                ]),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    // --- DYNAMIC AUDIT LOGGING HELPER FUNCTION ---
    private function logAudit($serviceId, $action, $details = null)
    {
        if (!$serviceId) {
            return;
        }

        if (Schema::hasTable('service_audit_logs')) {
            $userName = auth()->check() ? auth()->user()->name : 'System User';

            $data = [
                'service_id'  => $serviceId,
                'user_name'   => $userName,
                'action'      => $action,
                'title'       => $action,
                'details'     => $details,
                'description' => $details,
                'message'     => $details,
            ];

            $validData = [];
            foreach ($data as $key => $value) {
                if (Schema::hasColumn('service_audit_logs', $key)) {
                    $validData[$key] = $value;
                }
            }

            ServiceAuditLog::create($validData);
        }
    }

    // --- DYNAMIC AUDIT LOGS ENDPOINT FOR FRONTEND WIRING ---
    public function getAuditLogs(Service $service)
    {
        if (!Schema::hasTable('service_audit_logs')) {
            return response()->json([]);
        }

        $logs = ServiceAuditLog::where('service_id', $service->id)
            ->latest()
            ->get()
            ->map(function ($log) {
                $user = $log->user_name ?? 'System User';

                $words = explode(' ', trim($user));
                $initials = count($words) >= 2 
                    ? strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1))
                    : strtoupper(substr($user, 0, 2));

                return [
                    'id'       => $log->id,
                    'initials' => $initials ?: 'SU',
                    'user'     => $user,
                    'action'   => $log->action ?? $log->title ?? 'Service Action',
                    'details'  => $log->details ?? $log->description ?? $log->message ?? 'No details recorded.',
                    'date'     => $log->created_at ? $log->created_at->format('M d, Y, h:i A') : '',
                ];
            });

        return response()->json($logs);
    }

    public function index(Request $request)
    {
        if (Service::count() === 0) {
            $this->autoSeedCatalog();
        }

        // 1. GLOBAL STATUS COUNTS
        $allServicesCount = [
            'incomplete'   => Service::where('status', 'incomplete')->count(),
            'draft'        => Service::where('status', 'draft')->count(),
            'for_approval' => Service::where('status', 'for_approval')->count(),
            'active'       => Service::where('status', 'active')->count(),
            'archived'     => Service::where('status', 'archived')->count(),
        ];

        // 2. MAIN TABLE QUERY
        $query = Service::query()->with('activeVersion');

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('version')) {
            $query->whereHas('activeVersion', function($q) use ($request) {
                $q->where('version_number', $request->version);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('service_code', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('engagement')) {
            $query->where('engagement_behavior', $request->engagement);
        }

        if ($request->filled('service_area')) {
            $query->where('service_area', $request->service_area);
        }

        $servicesForMetrics = (clone $query)->get();

        $perPage = $request->input('per_page', 10);
        $services = $query->orderBy('created_at', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate($perPage)
                          ->withQueryString();

        // 3. INSIGHTS METRICS DATA
        $highestPricedVer = Schema::hasColumn('service_versions', 'standard_price')
            ? ServiceVersion::with('service')->whereNotNull('standard_price')->orderBy('standard_price', 'desc')->first()
            : null;

        $mostLaborVer = Schema::hasColumn('service_versions', 'expected_hours')
            ? ServiceVersion::with('service')->whereNotNull('expected_hours')->orderBy('expected_hours', 'desc')->first()
            : null;

        $mostPurchasedName = Service::first()?->name ?? 'No data yet';
        $mostPurchasedCount = 0;

        if (Schema::hasTable('contracts')) {
            $mostPurchased = Service::withCount('contracts')->orderBy('contracts_count', 'desc')->first();
            if ($mostPurchased) {
                $mostPurchasedName = $mostPurchased->name;
                $mostPurchasedCount = $mostPurchased->contracts_count;
            }
        }

        $acceptedProposals = 0;
        $totalProposals = 0;
        $conversionRate = '0%';

        if (Schema::hasTable('proposals')) {
            $totalProposals = DB::table('proposals')->count();
            $acceptedProposals = DB::table('proposals')->where('status', 'accepted')->count();
            if ($totalProposals > 0) {
                $conversionRate = round(($acceptedProposals / $totalProposals) * 100, 1) . '%';
            }
        }

        $insights = [
            'most_purchased' => [
                'name'  => $mostPurchasedName,
                'count' => $mostPurchasedCount,
            ],
            'highest_revenue' => [
                'name'   => $highestPricedVer?->service?->name ?? 'No data yet',
                'amount' => $highestPricedVer?->standard_price ?? 0.00,
            ],
            'most_discounted' => [
                'name'   => 'No data yet',
                'events' => 0,
            ],
            'highest_margin' => [
                'name'       => 'No data yet',
                'percentage' => 0,
            ],
            'most_labor_intensive' => [
                'name'  => $mostLaborVer?->service?->name ?? 'No data yet',
                'hours' => $mostLaborVer?->expected_hours ?? 0,
            ],
            'fastest_growing' => [
                'name' => 'No data yet',
                'rate' => 'No growth data',
            ],
            'declining_services' => [
                'name'  => 'No data yet',
                'trend' => 'No trend data',
            ],
            'proposal_conversion' => [
                'accepted' => $acceptedProposals,
                'proposed' => $totalProposals,
                'rate'     => $conversionRate
            ],
        ];

        return view('services.index', compact('services', 'insights', 'servicesForMetrics', 'allServicesCount'));
    }

    private function autoSeedCatalog()
    {
        $catalog = [
            ['name' => 'Annual Tax Compliance Advisory', 'category' => 'Tax Services', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'BIR Annual Registration Renewal', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Bookkeeping & Accounting (Monthly)', 'category' => 'Bookkeeping', 'service_area' => 'Government Processing', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'Business Permit Renewal (LGU)', 'category' => 'Registration', 'service_area' => 'Government Processing', 'engagement_behavior' => 'project', 'status' => 'active'],
            ['name' => 'Corporate Income Tax Filing (1702-EX)', 'category' => 'Corporate Tax', 'service_area' => 'Tax Services', 'engagement_behavior' => 'regular', 'status' => 'active'],
            ['name' => 'SEC General Information Sheet (GIS) Filing', 'category' => 'Compliance', 'service_area' => 'Corporate & Regulatory Advisory', 'engagement_behavior' => 'project', 'status' => 'active'],
        ];

        $i = 1;
        foreach ($catalog as $item) {
            $service = Service::create([
                'service_code'        => 'SVC-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name'                => $item['name'],
                'category'            => $item['category'],
                'service_area'        => $item['service_area'],
                'engagement_behavior' => $item['engagement_behavior'],
                'status'              => $item['status'],
            ]);

            $price = rand(50, 250) * 100;
            $versionData = [
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'active',
                'is_active'      => true,
            ];

            if (Schema::hasColumn('service_versions', 'standard_price')) {
                $versionData['standard_price'] = $price;
            }
            if (Schema::hasColumn('service_versions', 'expected_hours')) {
                $versionData['expected_hours'] = rand(8, 40);
            }

            $version = ServiceVersion::create($versionData);

            $service->update(['active_version_id' => $version->id]);

            $this->logAudit($service->id, 'Draft revision created', 'Draft revision created by System User (SU)');
            $this->logAudit($service->id, 'Price updated', 'Standard Fee set to ₱' . number_format($price, 2));
            $this->logAudit($service->id, 'Service action', 'Status changed to ACTIVE');

            $i++;
        }
    }

    public function store(Request $request)
    {
        if (!$request->filled('service_code')) {
            $lastService = Service::latest('id')->first();
            $nextId = $lastService ? $lastService->id + 1 : 1;
            $request->merge([
                'service_code' => 'SVC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT)
            ]);
        }

        $validated = $request->validate([
            'service_code'        => 'required|unique:services,service_code',
            'name'                => 'required|string|max:255',
            'short_name'          => 'nullable|string|max:255',
            'service_area'        => 'required|string|max:255',
            'category'            => 'nullable|string|max:255',
            'subcategory'         => 'nullable|string|max:255',
            'engagement_behavior' => 'required|in:project,regular,both',
            'status'              => 'nullable|in:incomplete,draft,for_approval,active,inactive,archived,superseded',
        ]);

        $service = null;

        DB::transaction(function () use ($validated, &$service) {
            $service = Service::create([
                'service_code'        => $validated['service_code'],
                'name'                => $validated['name'],
                'short_name'          => $validated['short_name'] ?? null,
                'service_area'        => $validated['service_area'],
                'category'            => $validated['category'] ?? null,
                'subcategory'         => $validated['subcategory'] ?? null,
                'engagement_behavior' => $validated['engagement_behavior'],
                'status'              => $validated['status'] ?? 'draft',
            ]);

            $version = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
            ]);

            $service->update(['active_version_id' => $version->id]);

            $this->logAudit($service->id, 'Draft revision created', 'Draft revision V1.0 created');
            $this->logAudit($service->id, 'Service action', 'Initial entry created with code: ' . $service->service_code);
        });

        return redirect()->route('services.workspace', ['service' => $service->id, 'mode' => 'edit', 'tab' => 'overview']);
    }

    public function workspace(Request $request, Service $service)
    {
        // 1. DYNAMIC PARAMETER DETECTION (Tab & Mode)
        $tab = strtolower($request->query('tab', 'overview'));
        if (str_contains($tab, 'wersion') || str_contains($tab, 'version')) {
            $tab = 'versions_history';
        } elseif (str_contains($tab, 'usage')) {
            $tab = 'usage_performance';
        }

        $mode = $request->query('mode', 'view');

        $service->load([
            'versions' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'auditLogs' => function ($q) {
                $q->latest();
            },
            'activeVersion.requirements',
            'activeVersion.mainActivities.subActivities'
        ]);

        // FALLBACK LOGIC FOR ACTIVE VERSION
        $activeVer = $service->activeVersion 
                  ?? $service->versions()->where('is_active', true)->first()
                  ?? $service->versions()->latest()->first();

        if (!$activeVer) {
            $activeVer = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
            ]);
            $service->update(['active_version_id' => $activeVer->id]);
        }

        if ($service->auditLogs->count() === 0) {
            $this->logAudit($service->id, 'Draft revision created', 'Draft revision created by System User (SU)');
            if ($activeVer && !empty($activeVer->standard_price)) {
                $this->logAudit($service->id, 'Price updated', 'Standard Fee set to ₱' . number_format($activeVer->standard_price, 2));
            }
            $this->logAudit($service->id, 'Service action', 'Status changed to ' . strtoupper(str_replace('_', ' ', $service->status ?? 'Active')));

            $service->load(['auditLogs' => function ($q) { $q->latest(); }]);
        }

        // --- FLEXIBLE & RELIABLE TAB COMPLETENESS CHECKS ---

        // 1. Overview Tab
        $overviewComplete = $activeVer && (
            !empty(trim($activeVer->internal_description ?? '')) || 
            !empty(trim($activeVer->client_description ?? '')) ||
            !empty(trim($service->short_name ?? ''))
        );

        // 2. Proposal Tab
        $proposalComplete = $activeVer && (
            !empty(trim($activeVer->scope_of_work ?? '')) || 
            !empty(trim($activeVer->deliverables ?? ''))
        );

        // 3. Requirements Tab
        $reqsComplete = $activeVer && ServiceRequirement::where('service_version_id', $activeVer->id)->exists();

        // 4. Workflow Tab
        $workflowComplete = $activeVer && ServiceActivity::where('service_version_id', $activeVer->id)->exists();

        // 5. Commercials Tab (May Pricing Model, Standard Price, O Payment Structure)
        $pricingComplete = $activeVer && (
            !empty($activeVer->pricing_model) ||
            (isset($activeVer->standard_price) && (float)$activeVer->standard_price >= 0) ||
            !empty($activeVer->payment_structure)
        );

        // 6. Engagement Tab
        $engagementComplete = $activeVer && (
            !empty($service->engagement_behavior) ||
            !empty(trim($activeVer->recurrence_frequency ?? '')) || 
            !empty(trim($activeVer->billing_frequency ?? ''))
        );

        // 7. Reporting Tab
        $reportingComplete = $activeVer && (
            !empty(trim($activeVer->reporting_frequency ?? '')) ||
            !empty(trim($activeVer->project_reporting ?? ''))
        );

        // 8. Automation Tab
        $automationComplete = $activeVer && (
            !empty(trim($activeVer->instantiation_mode ?? '')) ||
            !empty(trim($activeVer->instantiation_lead_days ?? '')) ||
            !empty(trim($activeVer->default_assignment_rule ?? ''))
        );

        // 9. Terms Tab (Awtomatikong true para hindi humarang sa approval)
        $termsComplete = true;

        $isAllComplete = $overviewComplete && $proposalComplete && $reqsComplete && $workflowComplete && 
                         $pricingComplete && $engagementComplete && $reportingComplete && $automationComplete && $termsComplete;

        // Auto-advance status if INCOMPLETE
        if (($isAllComplete || $service->status === 'incomplete') && $service->status !== 'for_approval' && $service->status !== 'active') {
            $service->update(['status' => 'draft']);
            $service->status = 'draft';
            if ($activeVer && $activeVer->status === 'incomplete') {
                $activeVer->update(['status' => 'draft']);
            }
            $this->logAudit($service->id, 'Completeness Gate Passed', 'Service status auto-advanced to DRAFT');
        }

        return view('services.workspace', compact(
            'service', 
            'activeVer',
            'overviewComplete',
            'proposalComplete', 
            'pricingComplete', 
            'workflowComplete', 
            'reqsComplete',
            'engagementComplete',
            'reportingComplete',
            'automationComplete',
            'termsComplete',
            'isAllComplete',
            'tab',
            'mode'
        ));
    }

    public function updateVersion(Request $request, Service $service)
    {
        $currentTab = $request->input('tab', 'overview');

        // Determine Next Tab Navigation Sequentially
        if ($request->has('next_tab')) {
            $nextTab = $request->input('next_tab');
        } else {
            $tabSequence = [
                'overview'          => 'proposal_content',
                'proposal_content'  => 'requirements',
                'requirements'      => 'workflow',
                'workflow'          => 'commercials',
                'commercials'       => 'engagement',
                'engagement'        => 'reporting',
                'reporting'         => 'automation',
                'automation'        => 'terms',
                'terms'             => 'usage_performance',
                'usage_performance' => 'versions_history',
            ];
            $nextTab = $tabSequence[$currentTab] ?? $currentTab;
        }

        if ($request->isMethod('get')) {
            return redirect()->route('services.workspace', [
                'service' => $service->id,
                'mode'    => $request->input('mode', 'edit'),
                'tab'     => $currentTab
            ]);
        }

        $activeVersion = $service->activeVersion 
                      ?? $service->versions()->where('is_active', true)->first()
                      ?? $service->versions()->latest()->first();

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
            ]);
            $service->update(['active_version_id' => $activeVersion->id]);
        }

        $targetVersion = $activeVersion;

        // IF ACTIVE: Create a new draft revision to avoid mutating locked version
        if ($activeVersion->status === 'active') {
            DB::transaction(function () use ($service, $activeVersion, &$targetVersion) {
                $currentNum = (float) str_replace(['V', 'v'], '', $activeVersion->version_number ?? '1.0');
                $nextVerNumber = 'V' . number_format($currentNum + 1.0, 1);

                $newVersionData = [
                    'service_id'     => $service->id,
                    'version_number' => $nextVerNumber,
                    'status'         => 'draft',
                    'is_active'      => true,
                ];

                $cloneableFields = [
                    'expected_turnaround', 'effective_date', 'internal_description', 'client_description',
                    'about_service', 'purpose', 'when_to_use', 'what_it_is_not', 'scope_of_work',
                    'deliverables', 'client_responsibilities', 'exclusions', 'pricing_model', 'currency',
                    'standard_price', 'unit_rate', 'expected_hours', 'tax_treatment', 'min_price',
                    'min_cap', 'discount_allowed', 'max_discount', 'cost_of_service', 'payment_structure',
                    'payment_notes', 'reimbursables', 'instantiation_mode', 'recurrence_frequency',
                    'billing_frequency', 'instantiation_lead_days', 'auto_carryover', 'reporting_frequency',
                    'project_reporting', 'report_content', 'base_reference_date', 'default_assignment_rule',
                    'internal_reminder_days', 'client_reminder_cadence', 'notification_channel',
                    'escalation_threshold_days', 'escalation_target_role', 'requirement_gate_rule',
                ];

                foreach ($cloneableFields as $field) {
                    if (Schema::hasColumn('service_versions', $field) && isset($activeVersion->$field)) {
                        $newVersionData[$field] = $activeVersion->$field;
                    }
                }

                $targetVersion = new ServiceVersion();
                $targetVersion->forceFill($newVersionData);
                $targetVersion->save();

                $service->update([
                    'active_version_id' => $targetVersion->id,
                    'status'            => 'draft'
                ]);

                $this->logAudit($service->id, 'Draft Revision Created', "Created new draft revision {$nextVerNumber}.");
            });
        }

        // 1. UPDATE CORE SERVICE FIELDS
        $serviceData = [];
        foreach (['name', 'short_name', 'service_area', 'category', 'subcategory', 'engagement_behavior'] as $sField) {
            if ($request->has($sField)) {
                $serviceData[$sField] = $request->input($sField);
            }
        }

        if (!empty($serviceData)) {
            $service->update($serviceData);
        }

        // 2. UPDATE TARGET DRAFT VERSION FIELDS
        $possibleVersionFields = [
            'short_name', 'internal_description', 'client_description', 'about_service', 'purpose',
            'when_to_use', 'what_it_is_not', 'version_number', 'effective_date', 'expected_turnaround',
            'scope_of_work', 'deliverables', 'client_responsibilities', 'exclusions', 'pricing_model',
            'payment_structure', 'payment_notes', 'currency', 'standard_price', 'unit_rate', 'expected_hours',
            'tax_treatment', 'min_price', 'min_cap', 'discount_allowed', 'max_discount', 'cost_of_service',
            'instantiation_mode', 'recurrence_frequency', 'billing_frequency', 'instantiation_lead_days',
            'auto_carryover', 'reporting_frequency', 'project_reporting', 'report_content',
            'base_reference_date', 'default_assignment_rule', 'internal_reminder_days',
            'client_reminder_cadence', 'notification_channel', 'escalation_threshold_days',
            'escalation_target_role', 'requirement_gate_rule'
        ];

        $versionData = [];

        foreach ($possibleVersionFields as $field) {
            if ($request->has($field) && Schema::hasColumn('service_versions', $field)) {
                $versionData[$field] = $request->input($field);
            }
        }

        // CLEAN NUMBER INPUTS
        if (isset($versionData['standard_price'])) {
            $versionData['standard_price'] = (float) str_replace(',', '', $versionData['standard_price']);
        }
        if (isset($versionData['unit_rate'])) {
            $versionData['unit_rate'] = (float) str_replace(',', '', $versionData['unit_rate']);
        }
        if (isset($versionData['cost_of_service'])) {
            $versionData['cost_of_service'] = (float) str_replace(',', '', $versionData['cost_of_service']);
        }

        if ($request->has('reimbursables') && Schema::hasColumn('service_versions', 'reimbursables')) {
            $versionData['reimbursables'] = $request->input('reimbursables');
        }

        if (!empty($versionData)) {
            $targetVersion->forceFill($versionData);
            $targetVersion->save();
        }

        // Auto-advance status if INCOMPLETE
        if ($service->status === 'incomplete') {
            $service->update(['status' => 'draft']);
            $targetVersion->update(['status' => 'draft']);
        }

        $this->logAudit($service->id, 'Workspace Saved', 'Saved changes for tab: ' . ucfirst(str_replace('_', ' ', $currentTab)));

        return redirect()->route('services.workspace', [
            'service' => $service->id, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => $nextTab
        ])->with('success', 'Changes saved successfully!');
    }

    public function submitForApproval(Request $request, Service $service)
    {
        $activeVersion = $service->activeVersion;

        if (!$activeVersion) {
            return back()->with('error', 'No active service version found to submit.');
        }

        DB::transaction(function () use ($service, $activeVersion) {
            $service->update(['status' => 'for_approval']);
            $activeVersion->update(['status' => 'for_approval']);

            $this->logAudit($service->id, 'Submitted for Approval', 'Service submitted to management for review.');
        });

        $this->sendUserNotification(
            'Service Submitted for Approval',
            "Service '{$service->name}' has been submitted for approval.",
            route('services.workspace', ['service' => $service->id, 'mode' => 'view', 'tab' => 'overview'])
        );

        // DIRECT REDIRECT BACK TO SERVICES INDEX / DASHBOARD
        return redirect()->route('services.index')
            ->with('success', "Service '{$service->name}' was successfully submitted for approval!");
    }

    public function approve(Service $service)
    {
        if ($service->status !== 'for_approval') {
            return redirect()->back()->with('error', 'Only services waiting for approval can be approved.');
        }

        DB::transaction(function () use ($service) {
            ServiceVersion::where('service_id', $service->id)
                ->where('status', 'active')
                ->where('id', '!=', $service->active_version_id)
                ->update(['status' => 'superseded', 'is_active' => false]);

            if ($service->activeVersion) {
                $service->activeVersion->update(['status' => 'active', 'is_active' => true]);
            }

            $service->update(['status' => 'active']);

            $this->logAudit($service->id, 'Service Approved', 'Version APPROVED and set to ACTIVE.');
        });

        return redirect()->route('services.workspace', ['service' => $service->id, 'tab' => 'overview'])
            ->with('success', 'Service approved and is now ACTIVE!');
    }

    public function reject(Service $service)
    {
        if ($service->status !== 'for_approval') {
            return redirect()->back()->with('error', 'Only services waiting for approval can be rejected.');
        }

        $service->update(['status' => 'draft']);

        if ($service->activeVersion) {
            $service->activeVersion->update(['status' => 'draft']);
        }

        $this->logAudit($service->id, 'Service Rejected', 'Returned to DRAFT status for revisions.');

        return redirect()->route('services.workspace', ['service' => $service->id, 'tab' => 'overview'])
            ->with('success', 'Service returned to DRAFT status.');
    }

    // --- ACTIVITIES / WORKFLOW MANAGEMENT ---
    public function storeActivity(Request $request, Service $service)
    {
        $activeVersion = $service->activeVersion ?? $service->versions()->latest()->first();

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'level'                  => 'nullable|in:main,sub',
            'parent_id'              => 'nullable|exists:service_activities,id',
            'sequence'               => 'nullable|integer|min:1',
            'expected_days'          => 'required|integer|min:0',
            'expected_working_hours' => 'required|numeric|min:0',
            'description'            => 'nullable|string',
        ]);

        $parentId = ($request->input('level') === 'sub') ? $request->input('parent_id') : null;

        $activeVersion->mainActivities()->create([
            'name'                   => $validated['name'],
            'parent_id'              => $parentId,
            'sequence'               => $request->input('sequence', 1),
            'expected_days'          => $validated['expected_days'],
            'expected_working_hours' => $validated['expected_working_hours'],
            'is_billable'            => $request->has('is_billable'),
            'is_mandatory'           => $request->has('is_mandatory'),
            'description'            => $request->input('description'),
        ]);

        $this->logAudit($service->id, 'Activity Added', 'Added activity: ' . $validated['name']);

        $nextTab = $request->input('next_tab', 'workflow');

        return redirect()->route('services.workspace', [
            'service' => $service->id, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => $nextTab
        ])->with('success', 'New activity added successfully!');
    }

    public function updateActivity(Request $request, ServiceActivity $activity)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'expected_days'          => 'required|integer|min:0',
            'expected_working_hours' => 'required|numeric|min:0',
        ]);

        $activity->update([
            'name'                   => $validated['name'],
            'expected_days'          => $validated['expected_days'],
            'expected_working_hours' => $validated['expected_working_hours'],
            'is_billable'            => $request->has('is_billable'),
            'is_mandatory'           => $request->has('is_mandatory'),
            'description'            => $request->input('description'),
        ]);

        $serviceId = $activity->serviceVersion?->service_id ?? $activity->service_id;
        $this->logAudit($serviceId, 'Activity Updated', 'Updated activity: ' . $validated['name']);

        $nextTab = $request->input('next_tab', 'workflow');

        return redirect()->route('services.workspace', [
            'service' => $serviceId, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => $nextTab
        ])->with('success', 'Activity updated successfully!');
    }

    public function destroyActivity(ServiceActivity $activity)
    {
        $serviceId = $activity->serviceVersion?->service_id ?? $activity->service_id;
        $name = $activity->name;

        $activity->delete();

        if ($serviceId) {
            $this->logAudit($serviceId, 'Activity Deleted', 'Removed activity: ' . $name);
        }

        return redirect()->route('services.workspace', [
            'service' => $serviceId, 
            'mode'    => 'edit', 
            'tab'     => 'workflow'
        ])->with('success', 'Activity deleted successfully!');
    }

    public function bulkImportActivities(Request $request, Service $service)
    {
        $request->validate([
            'outline_text'          => 'nullable|string',
            'import_file'           => 'nullable|file|mimes:csv,txt,xlsx,xls|max:5120',
            'default_expected_days' => 'nullable|integer|min:0',
            'default_working_hours' => 'nullable|numeric|min:0',
        ]);

        $activeVersion = $service->activeVersion ?? $service->versions()->latest()->first();

        if (!$activeVersion) {
            return redirect()->back()->with('error', 'No active service version found.');
        }

        $defaultDays = $request->input('default_expected_days', 1);
        $defaultHours = $request->input('default_working_hours', 1.0);
        $importedCount = 0;

        if ($request->hasFile('import_file')) {
            $file = $request->file('import_file');
            $rows = array_map('str_getcsv', file($file->getRealPath()));

            if (!empty($rows)) {
                $rawHeader = array_shift($rows);
                $header = array_map(fn($col) => strtolower(trim(str_replace([' ', '-'], '_', $col))), $rawHeader);
                $headerCount = count($header);

                DB::transaction(function () use ($rows, $header, $headerCount, $activeVersion, $defaultDays, $defaultHours, &$importedCount) {
                    $mainSeq = $activeVersion->mainActivities()->whereNull('parent_id')->count() + 1;

                    foreach ($rows as $row) {
                        if (empty($row) || empty(array_filter($row))) continue;

                        $rowCount = count($row);
                        if ($rowCount < $headerCount) $row = array_pad($row, $headerCount, null);
                        elseif ($rowCount > $headerCount) $row = array_slice($row, 0, $headerCount);

                        $data = array_combine($header, $row);
                        $name = $data['name'] ?? $data['activity_name'] ?? $row[0] ?? null;

                        if (!empty(trim($name))) {
                            ServiceActivity::create([
                                'service_version_id'     => $activeVersion->id,
                                'parent_id'              => null,
                                'name'                   => trim($name),
                                'sequence'               => isset($data['sequence']) && is_numeric($data['sequence']) ? (int)$data['sequence'] : $mainSeq++,
                                'expected_days'          => isset($data['expected_days']) && is_numeric($data['expected_days']) ? (int)$data['expected_days'] : $defaultDays,
                                'expected_working_hours' => isset($data['expected_working_hours']) && is_numeric($data['expected_working_hours']) ? (float)$data['expected_working_hours'] : $defaultHours,
                                'is_mandatory'           => isset($data['is_mandatory']) ? filter_var($data['is_mandatory'], FILTER_VALIDATE_BOOLEAN) : true,
                                'is_billable'            => isset($data['is_billable']) ? filter_var($data['is_billable'], FILTER_VALIDATE_BOOLEAN) : true,
                                'include_in_report'      => isset($data['include_in_report']) ? filter_var($data['include_in_report'], FILTER_VALIDATE_BOOLEAN) : true,
                            ]);
                            $importedCount++;
                        }
                    }
                });
            }
        } elseif ($request->filled('outline_text')) {
            $lines = explode("\n", str_replace("\r", "", $request->input('outline_text')));
            $lastMainActivity = null;
            $mainSeq = $activeVersion->mainActivities()->whereNull('parent_id')->count() + 1;
            $subSeq = 1;

            DB::transaction(function () use ($lines, $activeVersion, $defaultDays, $defaultHours, &$lastMainActivity, &$mainSeq, &$subSeq, &$importedCount) {
                foreach ($lines as $line) {
                    if (trim($line) === '') continue;

                    $isSubActivity = (strpos($line, "\t") === 0) || (preg_match('/^\s{2,}/', $line));
                    $cleanName = trim($line);

                    if ($isSubActivity && $lastMainActivity) {
                        ServiceActivity::create([
                            'service_version_id'     => $activeVersion->id,
                            'parent_id'              => $lastMainActivity->id,
                            'name'                   => $cleanName,
                            'sequence'               => $subSeq++,
                            'expected_days'          => $defaultDays,
                            'expected_working_hours' => $defaultHours,
                            'is_mandatory'           => true,
                            'is_billable'            => true,
                            'include_in_report'      => true,
                        ]);
                    } else {
                        $lastMainActivity = ServiceActivity::create([
                            'service_version_id'     => $activeVersion->id,
                            'parent_id'              => null,
                            'name'                   => $cleanName,
                            'sequence'               => $mainSeq++,
                            'expected_days'          => $defaultDays,
                            'expected_working_hours' => $defaultHours,
                            'is_mandatory'           => true,
                            'is_billable'            => true,
                            'include_in_report'      => true,
                        ]);
                        $subSeq = 1;
                    }
                    $importedCount++;
                }
            });
        }

        $this->logAudit($service->id, 'Bulk Activities Imported', "Imported {$importedCount} activities.");

        return redirect()->route('services.workspace', [
            'service' => $service->id, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => 'workflow'
        ])->with('success', "Successfully imported {$importedCount} activities!");
    }

    public function bulkActionActivities(Request $request, Service $service)
    {
        $request->validate([
            'activity_ids'      => 'required|array',
            'activity_ids.*'    => 'exists:service_activities,id',
            'bulk_action_type'  => 'required|in:set_mandatory,set_optional,set_billable,set_non_billable,delete',
        ]);

        $activityIds = $request->input('activity_ids');
        $actionType = $request->input('bulk_action_type');
        $count = count($activityIds);

        switch ($actionType) {
            case 'set_mandatory':
                ServiceActivity::whereIn('id', $activityIds)->update(['is_mandatory' => true]);
                $message = "Set {$count} activities as Mandatory.";
                break;
            case 'set_optional':
                ServiceActivity::whereIn('id', $activityIds)->update(['is_mandatory' => false]);
                $message = "Set {$count} activities as Optional.";
                break;
            case 'set_billable':
                ServiceActivity::whereIn('id', $activityIds)->update(['is_billable' => true]);
                $message = "Marked {$count} activities as Billable.";
                break;
            case 'set_non_billable':
                ServiceActivity::whereIn('id', $activityIds)->update(['is_billable' => false]);
                $message = "Marked {$count} activities as Non-Billable.";
                break;
            case 'delete':
                ServiceActivity::whereIn('id', $activityIds)->delete();
                $message = "Deleted {$count} selected activities.";
                break;
            default:
                return redirect()->back()->with('error', 'Invalid bulk action type.');
        }

        $this->logAudit($service->id, 'Bulk Activity Action', $message);

        return redirect()->route('services.workspace', [
            'service' => $service->id, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => 'workflow'
        ])->with('success', $message);
    }

    // --- REQUIREMENTS MANAGEMENT ---
    public function storeRequirement(Request $request, Service $service)
    {
        $activeVersion = $service->activeVersion ?? $service->versions()->latest()->first();

        $validated = $request->validate([
            'requirement_name' => 'required|string|max:255',
            'client_type'      => 'required|string|max:255',
        ]);

        $activeVersion->requirements()->create([
            'requirement_name' => $validated['requirement_name'],
            'client_type'      => $validated['client_type'] ?? 'All',
            'source'           => $request->input('source', 'Client-supplied'),
            'instructions'     => $request->input('instructions'),
            'is_mandatory'     => $request->has('is_mandatory'),
            'file_required'    => $request->has('file_required'),
        ]);

        $this->logAudit($service->id, 'Requirement Added', 'Added requirement: ' . $validated['requirement_name']);

        // Dito na-fix: Kukunin na ang next_tab mula sa form (halimbawa: 'workflow')
        $nextTab = $request->input('next_tab', 'requirements');

        return redirect()->route('services.workspace', [
            'service' => $service->id, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => $nextTab
        ])->with('success', 'Requirement added successfully!');
    }

    public function updateRequirement(Request $request, ServiceRequirement $requirement)
    {
        $validated = $request->validate([
            'requirement_name' => 'required|string|max:255',
            'client_type'      => 'required|string|max:255',
        ]);

        $requirement->update([
            'requirement_name' => $validated['requirement_name'],
            'client_type'      => $validated['client_type'],
            'source'           => $request->input('source', 'Client-supplied'),
            'instructions'     => $request->input('instructions'),
            'is_mandatory'     => $request->has('is_mandatory'),
            'file_required'    => $request->has('file_required'),
        ]);

        $serviceId = $requirement->serviceVersion?->service_id ?? $requirement->service_id;
        $this->logAudit($serviceId, 'Requirement Updated', 'Updated requirement: ' . $validated['requirement_name']);

        // Dito na-fix: Kukunin na ang next_tab mula sa form (halimbawa: 'workflow')
        $nextTab = $request->input('next_tab', 'requirements');

        return redirect()->route('services.workspace', [
            'service' => $serviceId, 
            'mode'    => $request->input('mode', 'edit'), 
            'tab'     => $nextTab
        ])->with('success', 'Requirement updated successfully!');
    }

    public function destroyRequirement(ServiceRequirement $requirement)
    {
        $serviceId = $requirement->serviceVersion?->service_id ?? $requirement->service_id;
        $name = $requirement->requirement_name;
        $requirement->delete();

        if ($serviceId) {
            $this->logAudit($serviceId, 'Requirement Deleted', 'Removed requirement: ' . $name);
        }

        return redirect()->route('services.workspace', [
            'service' => $serviceId, 
            'mode'    => 'edit', 
            'tab'     => 'requirements'
        ])->with('success', 'Requirement deleted successfully!');
    }

    // --- DUPLICATE, EXPORT, IMPORT & TERMS ---
    public function duplicate(Request $request, Service $service)
    {
        $validated = $request->validate([
            'service_code' => 'required|unique:services,service_code',
            'name'         => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($service, $validated) {
            $newService = Service::create([
                'service_code'        => $validated['service_code'],
                'name'                => $validated['name'],
                'short_name'          => $service->short_name ? $service->short_name . ' (Copy)' : null,
                'service_area'        => $service->service_area,
                'category'            => $service->category,
                'subcategory'         => $service->subcategory,
                'engagement_behavior' => $service->engagement_behavior,
                'status'              => 'draft',
            ]);

            if ($service->activeVersion) {
                $activeVer = $service->activeVersion;

                $versionData = [
                    'service_id'     => $newService->id,
                    'version_number' => 'V1.0',
                    'status'         => 'draft',
                    'is_active'      => true,
                ];

                $possibleFields = [
                    'internal_description', 'client_description', 'about_service', 'purpose',
                    'when_to_use', 'what_it_is_not', 'scope_of_work', 'deliverables', 'client_responsibilities',
                    'exclusions', 'pricing_model', 'payment_structure', 'currency', 'standard_price', 'unit_rate', 'tax_treatment'
                ];

                foreach ($possibleFields as $f) {
                    if (Schema::hasColumn('service_versions', $f) && isset($activeVer->$f)) {
                        $versionData[$f] = $activeVer->$f;
                    }
                }

                $newVersion = ServiceVersion::create($versionData);
                $newService->update(['active_version_id' => $newVersion->id]);
            }

            $this->logAudit($service->id, 'Duplicated', 'Duplicated to new service with code: ' . $validated['service_code']);
            $this->logAudit($newService->id, 'Created from Duplicate', 'Created as duplicate from service ID: ' . $service->id);
        });

        return redirect()->route('services.index')->with('success', 'Service duplicated successfully!');
    }

    public function getInheritedTerms(Service $service)
    {
        return response()->json([
            'service_id'   => $service->id,
            'service_name' => $service->name,
            'inheritance_chain' => [
                'global'           => 'Default Organization Terms',
                'service_area'     => $service->service_area,
                'category'         => $service->category,
                'service_specific' => $service->name,
            ],
            'inherited_clauses' => [
                'global' => [
                    'title'        => 'Global Confidentiality & Standard Terms',
                    'body_text'    => 'Confidentiality agreement and general payment terms.'
                ],
                'specific' => [
                    'title'        => 'Service-Specific Operating Rules',
                    'body_text'    => 'Special regulatory compliance clauses for ' . $service->name . '.'
                ]
            ]
        ]);
    }

    public function export(Service $service)
    {
        $fileName = 'service-' . $service->service_code . '.json';
        $data = $service->load(['activeVersion.mainActivities.subActivities', 'activeVersion.requirements']);

        $this->logAudit($service->id, 'Exported', 'Exported service specifications JSON file.');

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $fileName, ['Content-Type' => 'application/json']);
    }

    public function import(Request $request)
    {
        $request->validate(['json_file' => 'required|file']);

        try {
            $content = file_get_contents($request->file('json_file')->getRealPath());
            $data = json_decode($content, true);

            if (!$data || !is_array($data)) {
                return back()->with('error', 'Invalid JSON file format.');
            }

            $service = null;

            DB::transaction(function () use ($data, &$service) {
                $baseCode = $data['service_code'] ?? 'SVC-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $serviceCode = $baseCode;
                $counter = 1;

                while (Service::where('service_code', $serviceCode)->exists()) {
                    $serviceCode = $baseCode . '-IMP' . $counter;
                    $counter++;
                }

                $service = Service::create([
                    'service_code'        => $serviceCode,
                    'name'                => $data['name'] ?? 'Imported Service',
                    'short_name'          => $data['short_name'] ?? null,
                    'service_area'        => $data['service_area'] ?? 'Corporate & Regulatory Advisory',
                    'category'            => $data['category'] ?? null,
                    'engagement_behavior' => $data['engagement_behavior'] ?? 'project',
                    'status'              => 'draft',
                ]);

                $vInput = $data['active_version'] ?? $data['active_version_id'] ?? [];
                if (!is_array($vInput)) $vInput = $data;

                $version = ServiceVersion::create([
                    'service_id'     => $service->id,
                    'version_number' => $vInput['version_number'] ?? 'V1.0',
                    'status'         => 'draft',
                    'is_active'      => true,
                ]);

                $service->update(['active_version_id' => $version->id]);
                $this->logAudit($service->id, 'Imported', 'Imported service specifications from JSON file.');
            });

            return redirect()->route('services.workspace', ['service' => $service->id, 'mode' => 'view', 'tab' => 'overview'])
                ->with('success', 'Service imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to import service: ' . $e->getMessage());
        }
    }

    public function archive(Service $service)
    {
        $service->update(['status' => 'archived']);
        $this->logAudit($service->id, 'Archived', 'Service set to ARCHIVED status.');
        return redirect()->route('services.index')->with('success', 'Service archived successfully!');
    }
}