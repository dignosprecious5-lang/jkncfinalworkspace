<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceArea;
use App\Models\ServiceCategory;
use App\Models\ServiceVersion;
use App\Models\ServiceActivity;
use App\Models\ServiceRequirement;
use App\Models\GlobalRequirement;
use App\Models\ServiceAuditLog;
use App\Imports\ServicesMultiImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ServiceController extends Controller
{
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

    private function logAudit($serviceId, $action, $details = null)
    {
        if (!$serviceId) {
            return;
        }

        if (Schema::hasTable('service_audit_logs')) {
            $userName = auth()->check()
                ? auth()->user()->name
                : 'System User';

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
                    ? strtoupper(
                        substr($words[0], 0, 1) .
                        substr(end($words), 0, 1)
                    )
                    : strtoupper(substr($user, 0, 2));

                return [
                    'id'       => $log->id,
                    'initials' => $initials ?: 'SU',
                    'user'     => $user,
                    'action'   => $log->action
                        ?? $log->title
                        ?? 'Service Action',
                    'details'  => $log->details
                        ?? $log->description
                        ?? $log->message
                        ?? 'No details recorded.',
                    'date'     => $log->created_at
                        ? $log->created_at->format('M d, Y, h:i A')
                        : '',
                ];
            });

        return response()->json($logs);
    }

    public function index(Request $request)
    {
        if (Service::count() === 0) {
            $this->autoSeedCatalog();
        }

        $allServicesCount = [
            'incomplete'   => Service::where('status', 'incomplete')->count(),
            'draft'        => Service::where('status', 'draft')->count(),
            'for_approval' => Service::where('status', 'for_approval')->count(),
            'active'       => Service::where('status', 'active')->count(),
            'archived'     => Service::where('status', 'archived')->count(),
        ];

        $query = Service::query()->with('activeVersion');

        // MASTER SERVICE AREAS
        $serviceAreas = ServiceArea::where('status', 'active')
            ->orderBy('name')
            ->pluck('name');

        $existingServiceAreas = Service::whereNotNull('service_area')
            ->where('service_area', '!=', '')
            ->distinct()
            ->orderBy('service_area')
            ->pluck('service_area');

        $serviceAreas = $serviceAreas
            ->merge($existingServiceAreas)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // MASTER SERVICE CATEGORIES
        $serviceCategories = ServiceCategory::where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        $existingServiceCategories = Service::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $serviceCategories = $serviceCategories
            ->merge($existingServiceCategories)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($request->filled('from_date')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->to_date
            );
        }

        if ($request->filled('version')) {
            $query->whereHas('activeVersion', function ($q) use ($request) {
                $q->where(
                    'version_number',
                    $request->version
                );
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'service_code',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'short_name',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('category')) {
            $query->where(
                'category',
                $request->category
            );
        }

        if ($request->filled('engagement')) {
            $query->where(
                'engagement_behavior',
                $request->engagement
            );
        }

        if ($request->filled('service_area')) {
            $query->where(
                'service_area',
                $request->service_area
            );
        }

        $servicesForMetrics = (clone $query)->get();

        $perPage = $request->input(
            'per_page',
            10
        );

        $services = $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // ======================================================================
        // DYNAMIC DASHBOARD INSIGHTS / METRICS CARDS
        // ======================================================================

        $mostPurchasedService = null;
        $purchasedCount = 0;

        if (Schema::hasTable('proposals')) {
            $topProposal = DB::table('proposals')
                ->select(
                    'service_id',
                    DB::raw('count(*) as total')
                )
                ->groupBy('service_id')
                ->orderBy('total', 'desc')
                ->first();

            if (
                $topProposal &&
                $topProposal->service_id
            ) {
                $mostPurchasedService = Service::find(
                    $topProposal->service_id
                );

                $purchasedCount = $topProposal->total;
            }
        }

        if (!$mostPurchasedService) {
            $mostPurchasedService = Service::first();
        }

        $highestPricedService = Service::query()
            ->join(
                'service_versions',
                'services.active_version_id',
                '=',
                'service_versions.id'
            )
            ->select(
                'services.*',
                'service_versions.standard_price'
            )
            ->orderBy(
                'service_versions.standard_price',
                'desc'
            )
            ->first();

        $mostLaborIntensiveService = Service::query()
            ->join(
                'service_versions',
                'services.active_version_id',
                '=',
                'service_versions.id'
            )
            ->select(
                'services.*',
                'service_versions.expected_hours'
            )
            ->orderBy(
                'service_versions.expected_hours',
                'desc'
            )
            ->first();

        $highestMarginService = null;

        if (
            Schema::hasColumn(
                'service_versions',
                'standard_price'
            )
            &&
            Schema::hasColumn(
                'service_versions',
                'cost_of_service'
            )
        ) {
            $highestMarginService = Service::query()
                ->join(
                    'service_versions',
                    'services.active_version_id',
                    '=',
                    'service_versions.id'
                )
                ->select(
                    'services.*',
                    'service_versions.standard_price',
                    'service_versions.cost_of_service'
                )
                ->orderByRaw(
                    '(service_versions.standard_price - service_versions.cost_of_service) DESC'
                )
                ->first();
        }

        $mostDiscountedService = null;
        $maxDiscountValue = 0;

        if (
            Schema::hasColumn(
                'service_versions',
                'max_discount'
            )
        ) {
            $mostDiscountedService = Service::query()
                ->join(
                    'service_versions',
                    'services.active_version_id',
                    '=',
                    'service_versions.id'
                )
                ->select(
                    'services.*',
                    'service_versions.max_discount'
                )
                ->orderBy(
                    'service_versions.max_discount',
                    'desc'
                )
                ->first();

            $maxDiscountValue =
                $mostDiscountedService->max_discount ?? 0;
        }

        $insights = [
            'most_purchased' => [
                'name'  => $mostPurchasedService->name ?? 'No data yet',
                'count' => $purchasedCount,
            ],

            'highest_revenue' => [
                'name' => $highestPricedService->name ?? 'No data yet',
                'amount' =>
                    $highestPricedService->standard_price ?? 0.00,
            ],

            'most_discounted' => [
                'name' =>
                    $mostDiscountedService->name ?? 'No data yet',
                'events' => $maxDiscountValue,
            ],

            'highest_margin' => [
                'name' =>
                    $highestMarginService->name ?? 'No data yet',
                'percentage' =>
                    (
                        $highestMarginService &&
                        isset(
                            $highestMarginService->standard_price,
                            $highestMarginService->cost_of_service
                        )
                    )
                        ? round(
                            (
                                (
                                    $highestMarginService->standard_price
                                    -
                                    $highestMarginService->cost_of_service
                                )
                                /
                                max(
                                    $highestMarginService->standard_price,
                                    1
                                )
                            ) * 100,
                            1
                        )
                        : 0,
            ],

            'most_labor_intensive' => [
                'name' =>
                    $mostLaborIntensiveService->name ?? 'No data yet',
                'hours' =>
                    $mostLaborIntensiveService->expected_hours ?? 0,
            ],

            'fastest_growing' => [
                'name' => 'No data yet',
                'rate' => 'No growth data',
            ],

            'declining_services' => [
                'name' => 'No data yet',
                'trend' => 'No trend data',
            ],
        ];

        return view(
            'services.index',
            compact(
                'services',
                'insights',
                'servicesForMetrics',
                'allServicesCount',
                'serviceAreas',
                'serviceCategories'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE / QUICK ADD SERVICE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'service_area' => [
                'nullable',
                'string',
                'max:255',
            ],

            'category' => [
                'nullable',
                'string',
                'max:255',
            ],

            'engagement_behavior' => [
                'required',
                'in:project,regular,hybrid,both',
            ],

            'tax_treatment' => [
                'nullable',
                'string',
                'max:100',
            ],

            'short_description' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | GENERATE SERVICE CODE BEFORE FIRST SAVE
        |--------------------------------------------------------------------------
        |
        | services.service_code is NOT NULL in the current database.
        | Therefore the code must exist before the Service is saved.
        |
        */

        $nextServiceId =
            ((int) (Service::max('id') ?? 0)) + 1;

        $serviceCode =
            'SVC-' .
            str_pad(
                $nextServiceId,
                4,
                '0',
                STR_PAD_LEFT
            );

        /*
        |--------------------------------------------------------------------------
        | CREATE SERVICE
        |--------------------------------------------------------------------------
        */

        $service = new Service();

        $service->service_code =
            $serviceCode;

        $service->name =
            $validated['name'];

        if (Schema::hasColumn('services', 'service_area')) {
            $service->service_area =
                $validated['service_area'] ?? null;
        }

        if (Schema::hasColumn('services', 'category')) {
            $service->category =
                $validated['category'] ?? null;
        }

        if (Schema::hasColumn('services', 'engagement_behavior')) {
            $service->engagement_behavior =
                $validated['engagement_behavior'];
        }

        if (Schema::hasColumn('services', 'short_description')) {
            $service->short_description =
                $validated['short_description'] ?? null;
        }

        if (Schema::hasColumn('services', 'status')) {
            $service->status =
                'incomplete';
        }

        /*
        |--------------------------------------------------------------------------
        | FIRST SAVE
        |--------------------------------------------------------------------------
        */

        $service->save();

        /*
        |--------------------------------------------------------------------------
        | CREATE INITIAL SERVICE VERSION
        |--------------------------------------------------------------------------
        */

        $versionData = [
            'service_id'     => $service->id,
            'version_number' => 'V1.0',
            'status'         => 'draft',
            'is_active'      => true,
        ];

        if (Schema::hasColumn('service_versions', 'tax_treatment')) {
            $versionData['tax_treatment'] =
                $validated['tax_treatment'] ?? null;
        }

        $version =
            ServiceVersion::create(
                $versionData
            );

        /*
        |--------------------------------------------------------------------------
        | LINK SERVICE TO INITIAL VERSION
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('services', 'active_version_id')) {
            $service->active_version_id =
                $version->id;

            $service->save();
        }

        /*
        |--------------------------------------------------------------------------
        | AUDIT
        |--------------------------------------------------------------------------
        */

        $this->logAudit(
            $service->id,
            'Service Created',
            'Created new service through Quick Add.'
        );

        /*
        |--------------------------------------------------------------------------
        | REDIRECT TO WORKSPACE
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'services.workspace',
                [
                    'service' => $service->id,
                    'mode'    => 'edit',
                    'tab'     => 'overview',
                ]
            )
            ->with(
                'success',
                'Service created successfully. Continue setup in the Service Workspace.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE WORKSPACE
    |--------------------------------------------------------------------------
    */

    public function workspace(
        Request $request,
        Service $service
    ) {
        /*
        |--------------------------------------------------------------------------
        | LOAD ACTIVE VERSION
        |--------------------------------------------------------------------------
        */

        $service->load('activeVersion');

        $activeVersion = $service->activeVersion;

        /*
        |--------------------------------------------------------------------------
        | FALLBACK TO LATEST SERVICE VERSION
        |--------------------------------------------------------------------------
        */

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'service_id',
                    $service->id
                )
                ->orderByDesc('is_active')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | ALL SERVICE VERSIONS
        |--------------------------------------------------------------------------
        */

        $versions = ServiceVersion::query()
            ->where(
                'service_id',
                $service->id
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | WORKFLOW ACTIVITIES
        |--------------------------------------------------------------------------
        */

        $activities = collect();

        if ($activeVersion) {
            $activities = ServiceActivity::query()
                ->where(
                    'service_version_id',
                    $activeVersion->id
                )
                ->orderBy('sequence')
                ->orderBy('id')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICE REQUIREMENTS
        |--------------------------------------------------------------------------
        */

        $requirementsQuery = ServiceRequirement::query()
            ->where(
                'service_id',
                $service->id
            );

        if ($activeVersion) {
            $requirementsQuery->where(
                'service_version_id',
                $activeVersion->id
            );
        }

        $requirements = $requirementsQuery
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | GLOBAL REQUIREMENTS / TEMPLATE LIBRARY
        |--------------------------------------------------------------------------
        */
        $globalRequirements = collect();
        if (class_exists(\App\Models\GlobalRequirement::class)) {
            $globalRequirements = \App\Models\GlobalRequirement::all(); 
        } elseif (Schema::hasTable('global_requirements')) {
            $globalRequirements = DB::table('global_requirements')->get();
        } else {
            $globalRequirements = ServiceRequirement::whereNull('service_id')->get();
        }

        // Kunin ang aktwal na count ng na-filter na listahan
        $templateCount = $globalRequirements->count();
        /*
        |--------------------------------------------------------------------------
        | AUDIT HISTORY
        |--------------------------------------------------------------------------
        */

        $auditLogs = collect();

        if (
            Schema::hasTable(
                'service_audit_logs'
            )
        ) {
            $auditLogs = ServiceAuditLog::query()
                ->where(
                    'service_id',
                    $service->id
                )
                ->latest()
                ->paginate(5);
        }

        /*
        |--------------------------------------------------------------------------
        | WORKSPACE STATE
        |--------------------------------------------------------------------------
        */

        $currentTab = $request->input(
            'tab',
            'overview'
        );

        $mode = $request->input(
            'mode',
            'view'
        );

        /*
        |--------------------------------------------------------------------------
        | VERSION ALIAS
        |--------------------------------------------------------------------------
        */

        $version = $activeVersion;

        /*
        |--------------------------------------------------------------------------
        | RETURN WORKSPACE
        |--------------------------------------------------------------------------
        */

        return view(
            'services.workspace',
            compact(
                'service',
                'activeVersion',
                'version',
                'versions',
                'activities',
                'requirements',
                'globalRequirements',
                'templateCount',
                'auditLogs',
                'currentTab',
                'mode'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE PERFORMANCE REPORTS
    |--------------------------------------------------------------------------
    */

    public function reports(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | SERVICE QUERY
        |--------------------------------------------------------------------------
        */

        $serviceQuery = Service::query();

        if ($request->filled('service_area')) {
            $serviceQuery->where(
                'service_area',
                $request->service_area
            );
        }

        if ($request->filled('category')) {
            $serviceQuery->where(
                'category',
                $request->category
            );
        }

        if ($request->filled('engagement')) {
            $serviceQuery->where(
                'engagement_behavior',
                $request->engagement
            );
        }

        if ($request->filled('status')) {
            $serviceQuery->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERED SERVICE IDS
        |--------------------------------------------------------------------------
        */

        $serviceIds =
            (clone $serviceQuery)->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | SERVICE COUNTS
        |--------------------------------------------------------------------------
        */

        $totalServices =
            (clone $serviceQuery)->count();

        $activeServices =
            (clone $serviceQuery)
                ->where(
                    'status',
                    'active'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | ENGAGEMENT QUERY
        |--------------------------------------------------------------------------
        */

        $engagementQuery =
            \App\Models\Engagement::query()
                ->whereIn(
                    'service_id',
                    $serviceIds
                );

        /*
        |--------------------------------------------------------------------------
        | REPORTING PERIOD
        |--------------------------------------------------------------------------
        */

        if ($request->filled('from_date')) {
            $engagementQuery->whereDate(
                'created_at',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $engagementQuery->whereDate(
                'created_at',
                '<=',
                $request->to_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL ENGAGEMENTS
        |--------------------------------------------------------------------------
        */

        $totalEngagements =
            (clone $engagementQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | SERVICE REPORT ROWS
        |--------------------------------------------------------------------------
        */

        $services = $serviceQuery
    ->orderByDesc('id')
    ->paginate(10)
    ->withQueryString();

        foreach ($services as $service) {

            /*
            |--------------------------------------------------------------------------
            | SERVICE VERSION
            |--------------------------------------------------------------------------
            */

            $version = $service->activeVersion;

            /*
            |--------------------------------------------------------------------------
            | SERVICE VERSION DATA
            |--------------------------------------------------------------------------
            */

            $service->report_version =
                $version?->version_number ?? '—';

            $service->report_standard_price =
                $version?->standard_price ?? null;

            $service->report_unit_rate =
               $version?->unit_rate ?? null;

            $service->report_expected_hours =
                $version?->expected_hours ?? null;

            $service->report_reporting_frequency =
                $version?->reporting_frequency ?? null;

            /*
            |--------------------------------------------------------------------------
            | SERVICE ENGAGEMENT COUNTS
            |--------------------------------------------------------------------------
            */

            $serviceEngagements =
                \App\Models\Engagement::query()
                    ->where(
                        'service_id',
                        $service->id
                    );

            if ($request->filled('from_date')) {
                $serviceEngagements->whereDate(
                    'created_at',
                    '>=',
                    $request->from_date
                );
            }

            if ($request->filled('to_date')) {
                $serviceEngagements->whereDate(
                    'created_at',
                    '<=',
                    $request->to_date
                );
            }

            $service->report_engagements =
                (clone $serviceEngagements)->count();

            $service->report_active =
                (clone $serviceEngagements)
                    ->where(
                        'status',
                        'active'
                    )
                    ->count();

            $service->report_completed =
                (clone $serviceEngagements)
                    ->where(
                        'status',
                        'completed'
                    )
                    ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER OPTIONS
        |--------------------------------------------------------------------------
        */

        $serviceAreas = Service::query()
            ->whereNotNull('service_area')
            ->where(
                'service_area',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('service_area')
            ->pluck('service_area');

        $categories = Service::query()
            ->whereNotNull('category')
            ->where(
                'category',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $engagementTypes = Service::query()
            ->whereNotNull('engagement_behavior')
            ->where(
                'engagement_behavior',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('engagement_behavior')
            ->pluck('engagement_behavior');

        $statuses = Service::query()
            ->whereNotNull('status')
            ->where(
                'status',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'services.reports',
            compact(
                'services',
                'totalServices',
                'activeServices',
                'totalEngagements',
                'serviceAreas',
                'categories',
                'engagementTypes',
                'statuses'
            )
        );
    }

    public function updateVersion(
        Request $request,
        Service $service
    ) {
        $currentTab =
            $request->input(
                'tab',
                'overview'
            );

        $nextTab =
            $request->input(
                'next_tab',
                'overview'
            );

        $activeVersion = null;

        if (!empty($service->active_version_id)) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'id',
                    $service->active_version_id
                )
                ->where(
                    'service_id',
                    $service->id
                )
                ->first();
        }

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'service_id',
                    $service->id
                )
                ->latest('created_at')
                ->latest('id')
                ->first();
        }

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
            ]);

            $service->update([
                'active_version_id' => $activeVersion->id,
            ]);
        }

        $possibleVersionFields = [
            'short_name',
            'expected_turnaround', // <--- Idagdag din ito kung wala pa
            'effective_date',
            'internal_description',
            'client_description',
            'about_service',
            'purpose',
            'when_to_use',
            'what_it_is_not',
            'scope_of_work',
            'deliverables',
            'client_responsibilities',
            'exclusions',
            'pricing_model',
            'payment_structure',
            'payment_notes',
            'currency',
            'standard_price',
            'unit_rate',
            'expected_hours',
            'tax_treatment',
            'min_price',
            'min_cap',
            'discount_allowed',
            'max_discount',
            'cost_of_service',
            'instantiation_mode',
            'recurrence_frequency',
            'billing_frequency',
            'instantiation_lead_days',
            'auto_carryover',
            'reporting_frequency',
            'project_reporting',
            'project_reporting_type',
            'report_content',
            'base_reference_date',
            'default_assignment_rule',
            'internal_reminder_days',
            'client_reminder_cadence',
            'notification_channel',
            'escalation_threshold_days',
            'escalation_target_role',
            'requirement_gate_rule',
        ];

        $versionData = [];

        foreach (
            $possibleVersionFields as $field
        ) {
            if (
                $request->has($field)
                &&
                Schema::hasColumn(
                    'service_versions',
                    $field
                )
            ) {
                $versionData[$field] =
                    $request->input($field);
            }
        }

        if (!empty($versionData)) {
            $activeVersion->forceFill(
                $versionData
            );

            $activeVersion->save();
        }

        $this->logAudit(
            $service->id,
            'Workspace Saved',
            'Saved changes for tab: ' .
            ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $currentTab
                )
            )
        );

        return redirect()
            ->route(
                'services.workspace',
                [
                    'service' => $service->id,
                    'mode' =>
                        $request->input(
                            'mode',
                            'edit'
                        ),
                    'tab' => $nextTab,
                ]
            )
            ->with(
                'success',
                'Changes saved successfully!'
            );
    }

    public function submitForApproval(
        Request $request,
        Service $service
    ) {
        $version =
            $service->activeVersion;

        if (!$version) {
            return back()->with(
                'error',
                'No active service version found.'
            );
        }

        $version->status =
            'pending_approval';

        $version->save();

        $service->status =
            'pending_approval';

        $service->save();

        return redirect()
            ->route(
                'services.index'
            )
            ->with(
                'success',
                "Service {$version->version_number} submitted for approval successfully."
            );
    }

    public function approve(Service $service)
    {
        return redirect()
            ->route(
                'services.workspace',
                [
                    'service' => $service->id,
                ]
            )
            ->with(
                'success',
                'Approved!'
            );
    }

    public function reject(Service $service)
    {
        return redirect()
            ->route(
                'services.workspace',
                [
                    'service' => $service->id,
                ]
            )
            ->with(
                'success',
                'Rejected!'
            );
    }

    public function storeActivity(
        Request $request,
        Service $service
    ) {
        $validated = $request->validate([
            'level' =>
                ['required', 'in:main,sub'],

            'name' =>
                ['required', 'string', 'max:255'],

            'description' =>
                ['nullable', 'string'],

            'sequence' =>
                ['nullable', 'integer', 'min:1'],

            'expected_days' =>
                ['nullable', 'numeric', 'min:0'],

            'expected_working_hours' =>
                ['nullable', 'numeric', 'min:0'],

            'is_billable' =>
                ['nullable', 'in:0,1'],

            'is_mandatory' =>
                ['nullable', 'in:0,1'],

            'parent_id' =>
                ['nullable', 'integer'],
        ]);

        $activeVersion = null;

        if (!empty($service->active_version_id)) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'id',
                    $service->active_version_id
                )
                ->where(
                    'service_id',
                    $service->id
                )
                ->first();
        }

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'service_id',
                    $service->id
                )
                ->latest('created_at')
                ->latest('id')
                ->first();
        }

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
            ]);

            $service->update([
                'active_version_id' =>
                    $activeVersion->id,
            ]);
        }

        $parentId = null;

        if (
            ($validated['level'] ?? 'main')
            === 'sub'
        ) {
            $parentId =
                $validated['parent_id'] ?? null;

            if ($parentId) {
                $parentExists =
                    ServiceActivity::query()
                        ->where(
                            'id',
                            $parentId
                        )
                        ->where(
                            'service_version_id',
                            $activeVersion->id
                        )
                        ->exists();

                if (!$parentExists) {
                    $parentId = null;
                }
            }
        }

        $isMandatory =
            isset($validated['is_mandatory'])
                ? (bool) $validated['is_mandatory']
                : true;

        $isBillable =
            isset($validated['is_billable'])
                ? (bool) $validated['is_billable']
                : true;

        $activity =
            new ServiceActivity();

        $activity->service_version_id =
            $activeVersion->id;

        $activity->parent_id =
            $parentId;

        $activity->name =
            $validated['name'];

        $activity->description =
            $validated['description'] ?? null;

        // Awtomatikong alamin ang sunod na sequence number batay sa dami ng existing activities sa version na ito
        $maxSequence = ServiceActivity::query()
            ->where('service_version_id', $activeVersion->id)
            ->max('sequence');

        $activity->sequence = $maxSequence ? $maxSequence + 1 : 1;

        $activity->is_mandatory =
            $isMandatory;

        $activity->expected_working_hours =
            isset(
                $validated['expected_working_hours']
            )
                ? (float) $validated['expected_working_hours']
                : 0;

        $activity->expected_days =
            isset(
                $validated['expected_days']
            )
                ? (int) $validated['expected_days']
                : 0;

        $activity->is_billable =
            $isBillable;

        $activity->include_in_report =
            true;

        $activity->time_tracking_required =
            false;

        $activity->save();

        $this->logAudit(
            $service->id,
            'Activity Added',
            'Added workflow activity: ' .
            $activity->name
        );

        return redirect()
            ->route(
                'services.workspace',
                [
                    'service' => $service->id,
                    'mode'    => 'edit',
                    'tab'     => 'workflow',
                ]
            )
            ->with(
                'success',
                'Service activity added successfully.'
            );
    }

    public function updateActivity(
        Request $request,
        ServiceActivity $activity
    ) {
        return back()->with(
            'success',
            'Activity updated.'
        );
    }

    public function destroyActivity(
        ServiceActivity $activity
    ) {
        return back()->with(
            'success',
            'Activity deleted.'
        );
    }

    public function bulkActionActivities(
        Request $request,
        Service $service
    ) {
        return back()->with(
            'success',
            'Bulk action completed.'
        );
    }

    public function storeRequirement(
        Request $request,
        Service $service
    ) {
        $validated = $request->validate([
            'requirement_name' =>
                ['required', 'string', 'max:255'],

            'client_type' =>
                ['nullable', 'string', 'max:100'],

            'source' =>
                ['nullable', 'string', 'max:100'],

            'is_mandatory' =>
                ['nullable', 'boolean'],

            'file_required' =>
                ['nullable', 'boolean'],

            'conditional_rule' =>
                ['nullable', 'string', 'max:1000'],

            'validity_expiration' =>
                ['nullable', 'string', 'max:255'],

            'due_date' =>
                ['nullable', 'date'],

            'template_link' =>
                ['nullable', 'string', 'max:1000'],

            'instructions' =>
                ['nullable', 'string'],
        ]);

        $activeVersion = null;

        if (!empty($service->active_version_id)) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'id',
                    $service->active_version_id
                )
                ->where(
                    'service_id',
                    $service->id
                )
                ->first();
        }

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::query()
                ->where(
                    'service_id',
                    $service->id
                )
                ->latest('created_at')
                ->latest('id')
                ->first();
        }

        if (!$activeVersion) {
            $activeVersion = ServiceVersion::create([
                'service_id'     => $service->id,
                'version_number' => 'V1.0',
                'status'         => 'draft',
                'is_active'      => true,
            ]);

            $service->update([
                'active_version_id' =>
                    $activeVersion->id,
            ]);
        }

        $requirement =
            ServiceRequirement::create([
                'service_id' =>
                    $service->id,

                'service_version_id' =>
                    $activeVersion->id,

                'requirement_name' =>
                    $validated['requirement_name'],

                'client_type' =>
                    $validated['client_type']
                    ?? 'All',

                'source' =>
                    $validated['source']
                    ?? 'Client-supplied',

                'is_mandatory' =>
                    (int) (
                        $validated['is_mandatory']
                        ?? 0
                    ),

                'file_required' =>
                    (int) (
                        $validated['file_required']
                        ?? 0
                    ),

                'conditional_rule' =>
                    $validated['conditional_rule']
                    ?? null,

                'validity_expiration' =>
                    $validated['validity_expiration']
                    ?? null,

                'due_date' =>
                    $validated['due_date']
                    ?? null,

                'template_link' =>
                    $validated['template_link']
                    ?? null,

                'instructions' =>
                    $validated['instructions']
                    ?? null,

                'status' =>
                    'active',
            ]);

        if (!empty($validated['requirement_name'])) {
            GlobalRequirement::firstOrCreate(
                [
                    'requirement_name' =>
                        $validated['requirement_name'],
                ],
                [
                    'client_type' =>
                        $validated['client_type']
                        ?? 'All',

                    'is_mandatory' =>
                        (bool) (
                            $validated['is_mandatory']
                            ?? false
                        ),

                    'source' =>
                        $validated['source']
                        ?? 'Client-supplied',

                    'file_required' =>
                        (bool) (
                            $validated['file_required']
                            ?? false
                        ),

                    'instructions' =>
                        $validated['instructions']
                        ?? null,

                    'validity_expiration' =>
                        $validated['validity_expiration']
                        ?? null,

                    'due_date' =>
                        $validated['due_date']
                        ?? null,

                    'status' =>
                        'active',
                ]
            );
        }

        $this->logAudit(
            $service->id,
            'Requirement Added',
            'Added requirement: ' .
            $requirement->requirement_name
        );

        return back()->with(
            'success',
            'Requirement added successfully.'
        );
    }

    public function updateRequirement(
        Request $request,
        ServiceRequirement $requirement
    ) {
        $validated = $request->validate([
            'requirement_name' =>
                ['required', 'string', 'max:255'],

            'client_type' =>
                ['nullable', 'string', 'max:100'],

            'source' =>
                ['nullable', 'string', 'max:100'],

            'is_mandatory' =>
                ['nullable', 'boolean'],

            'file_required' =>
                ['nullable', 'boolean'],

            'conditional_rule' =>
                ['nullable', 'string', 'max:1000'],

            'validity_expiration' =>
                ['nullable', 'string', 'max:255'],

            'due_date' =>
                ['nullable', 'date'],

            'template_link' =>
                ['nullable', 'string', 'max:1000'],

            'instructions' =>
                ['nullable', 'string'],
        ]);

        $requirement->update([
            'requirement_name' =>
                $validated['requirement_name'],

            'client_type' =>
                $validated['client_type']
                ?? 'All',

            'source' =>
                $validated['source']
                ?? 'Client-supplied',

            'is_mandatory' =>
                (int) (
                    $validated['is_mandatory']
                    ?? 0
                ),

            'file_required' =>
                (int) (
                    $validated['file_required']
                    ?? 0
                ),

            'conditional_rule' =>
                $validated['conditional_rule']
                ?? null,

            'validity_expiration' =>
                $validated['validity_expiration']
                ?? null,

            'due_date' =>
                $validated['due_date']
                ?? null,

            'template_link' =>
                $validated['template_link']
                ?? null,

            'instructions' =>
                $validated['instructions']
                ?? null,
        ]);

        $this->logAudit(
            $requirement->service_id,
            'Requirement Updated',
            'Updated requirement: ' .
            $requirement->requirement_name
        );

        return back()->with(
            'success',
            'Requirement updated successfully.'
        );
    }

    public function destroyRequirement(
        ServiceRequirement $requirement
    ) {
        $serviceId =
            $requirement->service_id;

        $requirement->delete();

        $this->logAudit(
            $serviceId,
            'Requirement Deleted',
            'Deleted requirement.'
        );

        return back()->with(
            'success',
            'Requirement deleted successfully.'
        );
    }

    public function bulkToTemplateLibrary(
        Request $request,
        Service $service
    ) {
        $requirementIds =
            $request->input(
                'requirement_ids',
                []
            );

        $requirements =
            ServiceRequirement::whereIn(
                'id',
                $requirementIds
            )->get();

        foreach ($requirements as $req) {
            GlobalRequirement::create([
                'requirement_name' =>
                    $req->requirement_name,

                'client_type' =>
                    $req->client_type
                    ?? 'All',

                'is_mandatory' =>
                    (bool) (
                        $req->is_mandatory
                        ?? false
                    ),

                'source' =>
                    $req->source
                    ?? 'Client-supplied',

                'file_required' =>
                    (bool) (
                        $req->file_required
                        ?? false
                    ),

                'instructions' =>
                    $req->instructions
                    ?? null,

                'validity_expiration' =>
                    $req->validity_expiration
                    ?? null,

                'due_date' =>
                    $req->due_date
                    ?? null,

                'status' =>
                    'active',
            ]);
        }

        return back()->with(
            'success',
            'Requirements successfully added to template library!'
        );
    }

    public function duplicate(
        Request $request,
        Service $service
    ) {
        $newService =
            $service->replicate();

        $newService->name =
            $service->name . ' (Copy)';

        $maxId =
            Service::max('id') + 1;

        $newService->service_code =
            'SVC-' .
            str_pad(
                $maxId,
                4,
                '0',
                STR_PAD_LEFT
            );

        $newService->status =
            'draft';

        $newService->active_version_id =
            null;

        $newService->save();

        if ($service->activeVersion) {
            $newVersion =
                $service->activeVersion->replicate();

            $newVersion->service_id =
                $newService->id;

            $newVersion->save();

            $newService->active_version_id =
                $newVersion->id;

            $newService->save();
        }

        return redirect()
            ->route(
                'services.workspace',
                [
                    'service' =>
                        $newService->id,
                    'mode' =>
                        'edit',
                ]
            )
            ->with(
                'success',
                'Service duplicated successfully!'
            );
    }

    public function getInheritedTerms(
        Service $service
    ) {
        return response()->json([]);
    }

    public function export(
        Service $service
    ) {
        return response()->json([]);
    }

    public function import(
        Request $request
    ) {
        return back()->with(
            'success',
            'Imported.'
        );
    }

  public function archive(Service $service)
{
    // I-update ang status ng service para maging archived
    $service->status = 'archived';
    $service->save();

    return redirect()
        ->route('services.index')
        ->with(
            'success',
            'Archived.'
        );
}
    public function exportAll()
    {
        return response()->json([]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY GLOBAL REQUIREMENT / TEMPLATE LIBRARY ITEM
    |--------------------------------------------------------------------------
    */
public function destroyGlobalRequirement($id)
    {
        // Debug para makita natin kung anong ID ang pinapasa
        // Kung gusto mong i-check, maaari mong tanggalin ang comment sa ibaba:
        // \Log::info("Deleting global requirement with ID: " . $id);

        $template = GlobalRequirement::where('id', $id)->first();

        if ($template) {
            $template->delete();
        } else {
            DB::table('global_requirements')->where('id', $id)->delete();
        }

        return back()->with('success', 'Template removed successfully.');
    }

    
}