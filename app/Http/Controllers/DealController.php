<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Company;
use App\Models\Deal;
use App\Models\DealStage;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\ProjectProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DealController extends Controller
{
    public function __construct(private readonly ProjectProvisioner $projectProvisioner)
    {
    }

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
        $search = trim((string) $request->query('search', ''));
        $stages = $this->dealStages();

        $deals = [];
        $owners = $this->ownerOptions();
        $financeUsers = $this->financeUserOptions();
        $employeeOptions = $this->employeeOptions();
        $defaultOwnerId = (int) ($owners[0]['id'] ?? 1001);
        $defaultOwner = collect($owners)->firstWhere('id', $defaultOwnerId) ?: collect($owners)->first();
        $companyOptions = [
            'ABC Company',
            'XYZ Company',
            'Global Enterprises',
            'Consulting Group',
            'Innovate Co',
            'Startup Hub',
        ];
        $contactOptions = [
            'David Lee',
            'Robert Johnson',
            'Sarah Williams',
            'Michael Brown',
        ];
        $contactRecords = [];
        $companyRecords = [];
        $dealRecords = [];

        try {
            if (Schema::hasTable('contacts')) {
                $contactColumns = array_values(array_filter([
                    'id',
                    'cif_status',
                    'customer_type',
                    'client_status',
                    'salutation',
                    'first_name',
                    'middle_initial',
                    'middle_name',
                    'last_name',
                    'name_extension',
                    'sex',
                    'date_of_birth',
                    'email',
                    'phone',
                    'contact_address',
                    'company_name',
                    'company_address',
                    'position',
                ], fn (string $column): bool => Schema::hasColumn('contacts', $column)));

                if (! in_array('id', $contactColumns, true)) {
                    $contactColumns[] = 'id';
                }

                $contacts = Contact::query()
                    ->select($contactColumns)
                    ->orderBy('first_name')
                    ->orderBy('last_name')
                    ->get();

                $contactRecords = $contacts
                    ->map(function (Contact $contact): array {
                        return [
                            'id' => $contact->id,
                            'label' => trim(collect([
                                $contact->salutation,
                                $contact->first_name,
                                $contact->middle_name,
                                $contact->last_name,
                            ])->filter()->implode(' ')),
                            'search_blob' => strtolower(implode(' ', array_filter([
                                $contact->salutation,
                                $contact->first_name,
                                $contact->middle_initial,
                                $contact->middle_name,
                                $contact->last_name,
                                $contact->company_name,
                                $contact->email,
                                $contact->phone,
                            ]))),
                            'customer_type' => $contact->customer_type,
                            'client_status' => $contact->client_status,
                            'salutation' => $contact->salutation,
                            'first_name' => $contact->first_name,
                            'middle_initial' => $contact->middle_initial ?: (filled($contact->middle_name) ? mb_substr((string) $contact->middle_name, 0, 1) : null),
                            'middle_name' => $contact->middle_name,
                            'last_name' => $contact->last_name,
                            'name_extension' => $contact->name_extension,
                            'sex' => $contact->sex,
                            'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                            'email' => $contact->email,
                            'mobile' => $contact->phone,
                            'address' => $contact->contact_address,
                            'company_name' => $contact->company_name,
                            'company_address' => $contact->company_address,
                            'position' => $contact->position,
                            'client_requirement_status_map' => $this->buildClientRequirementStatusMap(
                                strtolower((string) ($contact->cif_status ?? '')) === 'approved',
                                false,
                            ),
                        ];
                    })
                    ->filter(fn (array $record): bool => $record['label'] !== '' || filled($record['company_name']))
                    ->values()
                    ->all();

                $contactOptions = $contacts
                    ->map(fn (Contact $contact): string => trim(($contact->first_name ?? '').' '.($contact->last_name ?? '')))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $companyOptions = array_values(array_unique(array_merge(
                    ['Consulting Group'],
                    $contacts->pluck('company_name')->filter()->values()->all()
                )));
            }

            if (Schema::hasTable('companies')) {
                $companyColumns = array_values(array_filter([
                    'id',
                    'company_name',
                    'email',
                    'phone',
                    'address',
                    'primary_contact_id',
                    'owner_name',
                ], fn (string $column): bool => Schema::hasColumn('companies', $column)));

                if (! in_array('id', $companyColumns, true)) {
                    $companyColumns[] = 'id';
                }

                $companies = Company::query()
                    ->with([
                        'latestBif' => fn ($query) => $query->select([
                            'company_bifs.id',
                            'company_bifs.company_id',
                            'company_bifs.status',
                            'company_bifs.business_organization',
                            'company_bifs.authorized_contact_person_name',
                            'company_bifs.authorized_contact_person_position',
                            'company_bifs.authorized_contact_person_email',
                            'company_bifs.authorized_contact_person_phone',
                        ]),
                        'primaryContact' => fn ($query) => $query->select([
                            'contacts.id',
                            'contacts.cif_status',
                            'contacts.salutation',
                            'contacts.first_name',
                            'contacts.middle_initial',
                            'contacts.middle_name',
                            'contacts.last_name',
                            'contacts.name_extension',
                            'contacts.sex',
                            'contacts.date_of_birth',
                            'contacts.email',
                            'contacts.phone',
                            'contacts.contact_address',
                            'contacts.position',
                            'contacts.company_name',
                        ]),
                    ])
                    ->select($companyColumns)
                    ->orderBy('company_name')
                    ->get();

                $companyRecords = $companies
                    ->map(function (Company $company): array {
                        $primaryContact = $company->primaryContact;
                        $authorizedContactName = trim((string) ($company->latestBif?->authorized_contact_person_name ?: trim(collect([
                            $primaryContact?->first_name,
                            $primaryContact?->middle_name,
                            $primaryContact?->last_name,
                        ])->filter()->implode(' '))));

                        return [
                            'id' => $company->id,
                            'label' => $company->company_name,
                            'search_blob' => strtolower(implode(' ', array_filter([
                                $company->company_name,
                                $company->email,
                                $company->phone,
                                $company->owner_name,
                                $company->address,
                                $authorizedContactName,
                                $company->latestBif?->authorized_contact_person_email,
                                $company->latestBif?->authorized_contact_person_phone,
                                $primaryContact?->email,
                                $primaryContact?->phone,
                            ]))),
                            'company_name' => $company->company_name,
                            'company_address' => $company->address,
                            'email' => $company->email,
                            'mobile' => $company->phone,
                            'owner_name' => $company->owner_name,
                            'primary_contact_id' => $primaryContact?->id,
                            'authorized_contact_name' => $authorizedContactName,
                            'authorized_contact_position' => $company->latestBif?->authorized_contact_person_position ?: $primaryContact?->position,
                            'authorized_contact_email' => $company->latestBif?->authorized_contact_person_email ?: $primaryContact?->email,
                            'authorized_contact_mobile' => $company->latestBif?->authorized_contact_person_phone ?: $primaryContact?->phone,
                            'authorized_contact_first_name' => $primaryContact?->first_name,
                            'authorized_contact_middle_initial' => $primaryContact?->middle_initial ?: (filled($primaryContact?->middle_name) ? mb_substr((string) $primaryContact?->middle_name, 0, 1) : null),
                            'authorized_contact_middle_name' => $primaryContact?->middle_name,
                            'authorized_contact_last_name' => $primaryContact?->last_name,
                            'authorized_contact_salutation' => $primaryContact?->salutation,
                            'authorized_contact_name_extension' => $primaryContact?->name_extension,
                            'authorized_contact_sex' => $primaryContact?->sex,
                            'authorized_contact_date_of_birth' => optional($primaryContact?->date_of_birth)->format('Y-m-d'),
                            'authorized_contact_address' => $primaryContact?->contact_address,
                            'business_organization' => $company->latestBif?->business_organization,
                            'client_requirement_status_map' => $this->buildClientRequirementStatusMap(
                                strtolower((string) ($primaryContact?->cif_status ?? '')) === 'approved',
                                strtolower((string) ($company->latestBif?->status ?? '')) === 'approved',
                            ),
                        ];
                    })
                    ->filter(fn (array $record): bool => filled($record['company_name']))
                    ->values()
                    ->all();

                $companyOptions = array_values(array_unique(array_merge(
                    $companyOptions,
                    $companies->pluck('company_name')->filter()->values()->all()
                )));
            }

            if (Schema::hasTable('deals')) {
                $this->backfillMissingDealCodes();

                $storedDeals = Deal::query()
                    ->with(['contact:id,first_name,last_name', 'stage', 'assignedFinance'])
                    ->latest()
                    ->get();

                $storedDeals->each(function (Deal $deal): void {
                    $this->ensureDealCodeAssigned($deal);
                    $stageRelation = $deal->relationLoaded('stage') ? $deal->getRelation('stage') : null;
                    if ($deal->stage_id && $stageRelation instanceof DealStage && (string) $deal->getAttribute('stage') !== (string) $stageRelation->name) {
                        $deal->forceFill(['stage' => $stageRelation->name])->save();
                    }
                });

                $accessibleStoredDeals = $storedDeals
                    ->filter(fn (Deal $deal): bool => $this->canViewDeal($deal))
                    ->values();

                $dealRecords = $accessibleStoredDeals
                    ->mapWithKeys(function (Deal $storedDeal): array {
                        $contact = $storedDeal->contact;
                        $stageName = (string) ($storedDeal->stage ?: 'Inquiry');
                        $dealFormData = $this->normalizeDealFormData([
                            ...$storedDeal->toArray(),
                            'stage' => $stageName,
                            'first_name' => $storedDeal->first_name ?: $contact?->first_name,
                            'middle_initial' => $storedDeal->middle_initial ?? $contact?->middle_initial,
                            'middle_name' => $storedDeal->middle_name ?: $contact?->middle_name,
                            'last_name' => $storedDeal->last_name ?: $contact?->last_name,
                            'name_extension' => $storedDeal->name_extension ?? $contact?->name_extension,
                            'email' => $storedDeal->email ?: $contact?->email,
                            'mobile' => $storedDeal->mobile ?: $contact?->phone,
                            'address' => $storedDeal->address ?: $contact?->contact_address,
                            'company_name' => $storedDeal->company_name ?: $contact?->company_name,
                            'company_address' => $storedDeal->company_address ?: $contact?->company_address,
                            'position' => $storedDeal->position ?: $contact?->position,
                        ]);

                        return [
                            (string) $storedDeal->id => [
                                ...$dealFormData,
                                'id' => $storedDeal->id,
                                'contact_id' => $storedDeal->contact_id,
                                'owner_id' => $storedDeal->owner_id,
                                'stage' => $stageName,
                                'deal_code' => $storedDeal->deal_code,
                                'deal_name' => $storedDeal->deal_name ?: $storedDeal->deal_code,
                                'created_by' => $storedDeal->created_by ?: optional(Auth::user())->name ?: 'System',
                                'created_at_label' => optional($storedDeal->created_at)->format('F d, Y • h:i:s A') ?: now()->format('F d, Y • h:i:s A'),
                            ],
                        ];
                    })
                    ->all();

                $storedDeals = $storedDeals
                    ->map(function (Deal $deal): array {
                        $contactName = trim(collect([
                            $deal->first_name ?: $deal->contact?->first_name,
                            $deal->last_name ?: $deal->contact?->last_name,
                        ])->filter()->implode(' '));
                        $canAccess = $this->canViewDeal($deal);

                        return [
                            'id' => $deal->id,
                            'deal_code' => $deal->deal_code,
                            'deal_name' => $deal->deal_name,
                            'contact_name' => $contactName !== '' ? $contactName : 'Linked Contact',
                            'company_name' => $deal->company_name ?: (optional($deal->contact)->company_name ?: '-'),
                            'amount' => (int) round((float) ($deal->total_estimated_engagement_value ?? 0)),
                            'expected_close' => optional($deal->estimated_completion_date)->format('M d, Y') ?: 'TBD',
                            'owner_name' => $deal->assigned_consultant ?: 'Unassigned',
                            'stage' => $deal->stage,
                            'can_access' => $canAccess,
                            'created_by' => $deal->created_by ?: optional(Auth::user())->name ?: 'System',
                            'created_at_label' => optional($deal->created_at)->format('F d, Y • h:i:s A') ?: now()->format('F d, Y • h:i:s A'),
                            'search_blob' => Str::lower(implode(' ', array_filter([
                                $deal->deal_code,
                                $deal->deal_name,
                                $contactName,
                            ]))),
                        ];
                    })
                    ->all();

                $deals = $storedDeals;
            }
        } catch (Throwable) {
            $deals = [];
            $contactRecords = [];
            $companyRecords = [];
            $contactOptions = [];
            $companyOptions = [];
            $dealRecords = [];
        }

        if ($search !== '') {
            $deals = array_values(array_filter($deals, function (array $deal) use ($search): bool {
                $blob = Str::lower(implode(' ', array_filter([
                    $deal['deal_code'] ?? null,
                    $deal['deal_name'] ?? null,
                    $deal['contact_name'] ?? null,
                    $deal['search_blob'] ?? null,
                ])));

                return Str::contains($blob, Str::lower($search));
            }));
        }

        $groupedByStage = [];
        foreach ($stages as $stage) {
            $stageDeals = array_values(array_filter($deals, fn (array $deal): bool => $deal['stage'] === $stage['name']));
            $stageTotal = array_sum(array_column($stageDeals, 'amount'));
            $groupedByStage[] = [
                'id' => $stage['id'],
                'stage' => $stage['name'],
                'color' => $stage['color'],
                'total_amount' => $stageTotal,
                'deals' => $stageDeals,
            ];
        }

        $draft = session('deals.preview_payload', []);

        $serviceCatalog = $this->dealServiceCatalog();
        $productCatalog = $this->dealProductCatalog();

        return view('deals.index', [
            'stageColumns' => $groupedByStage,
            'totalDeals' => count($deals),
            'search' => $search,
            'stageOptions' => array_values(array_map(fn (array $stage): string => $stage['name'], $stages)),
            'companyOptions' => $companyOptions,
            'contactOptions' => $contactOptions,
            'contactRecords' => $contactRecords,
            'companyRecords' => $companyRecords,
            'dealRecords' => $dealRecords,
            'dealDraft' => is_array($draft) ? $draft : [],
            'openDealModal' => (bool) request()->boolean('open_deal_modal'),
            'serviceAreaOptions' => $serviceCatalog['serviceAreaOptions'],
            'serviceGroups' => $serviceCatalog['serviceGroups'],
            'servicePricing' => $serviceCatalog['servicePricing'],
            'serviceRequirementCatalog' => $serviceCatalog['serviceRequirementCatalog'],
            'productOptionsByServiceArea' => $productCatalog['productOptionsByServiceArea'],
            'productPricing' => $productCatalog['productPricing'],
            'ownerLabel' => $defaultOwner['name'] ?? 'Shine Florence Padillo',
            'owners' => $owners,
            'financeUsers' => $financeUsers,
            'employeeOptions' => $employeeOptions,
            'defaultOwnerId' => $defaultOwnerId,
        ]);
    }

    private function dealServiceCatalog(): array
    {
        $fallbackGroups = collect(self::FALLBACK_SERVICE_GROUPS)
            ->map(fn (array $services): array => array_values(array_unique($services)))
            ->all();
        $fallbackPricing = collect($fallbackGroups)
            ->flatten()
            ->mapWithKeys(fn (string $service): array => [$service => 2500])
            ->all();

        try {
            if (! Schema::hasTable('services')) {
                return [
                    'serviceAreaOptions' => self::FALLBACK_SERVICE_AREA_OPTIONS,
                    'serviceGroups' => $fallbackGroups,
                    'servicePricing' => $fallbackPricing,
                    'serviceRequirementCatalog' => [],
                ];
            }

            $query = Service::query()
                ->select(['service_name', 'service_area', 'service_area_other', 'price_fee', 'rate_per_unit', 'status', 'requirements'])
                ->whereNotNull('service_name')
                ->where('service_name', '!=', '');

            if (Schema::hasColumn('services', 'status')) {
                $query->where('status', 'Active');
            }

            $services = $query->orderBy('service_name')->get();

            $serviceGroups = [];
            $servicePricing = [];
            $serviceRequirementCatalog = [];

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

                $servicePricing[$serviceName] = (float) ($service->price_fee ?? $service->rate_per_unit ?? 0);
                $serviceRequirementCatalog[$serviceName] = $this->normalizeServiceRequirementCatalogEntry($service->requirements);
            }

            $serviceGroups = collect($serviceGroups)
                ->map(fn (array $group): array => collect($group)->filter()->unique()->sort()->values()->all())
                ->filter(fn (array $group): bool => $group !== [])
                ->sortKeys()
                ->all();

            $serviceAreaOptions = array_values(array_unique(array_merge(
                array_keys($serviceGroups),
                self::FALLBACK_SERVICE_AREA_OPTIONS
            )));

            if ($serviceGroups === []) {
                return [
                    'serviceAreaOptions' => self::FALLBACK_SERVICE_AREA_OPTIONS,
                    'serviceGroups' => $fallbackGroups,
                    'servicePricing' => $fallbackPricing,
                    'serviceRequirementCatalog' => [],
                ];
            }

            return [
                'serviceAreaOptions' => $serviceAreaOptions,
                'serviceGroups' => $serviceGroups,
                'servicePricing' => $servicePricing + $fallbackPricing,
                'serviceRequirementCatalog' => $serviceRequirementCatalog,
            ];
        } catch (Throwable) {
            return [
                'serviceAreaOptions' => self::FALLBACK_SERVICE_AREA_OPTIONS,
                'serviceGroups' => $fallbackGroups,
                'servicePricing' => $fallbackPricing,
                'serviceRequirementCatalog' => [],
            ];
        }
    }

    private function dealProductCatalog(): array
    {
        $fallbackGroups = [
            'Corporate & Regulatory Advisory' => [
                'Printing',
                'Photocopy',
                'Drafting of Letters',
                'Drafting of Notices',
                'Drafting of Demand Letters',
                'Drafting of Emails (Formal / Business)',
            ],
            'Accounting & Compliance Advisory' => [
                'Archive Retrieval',
                'Digital Archive Copy',
                'Drafting of Responses to Letters / Notices',
                'Drafting of Memorandum (Internal / External)',
                'Drafting of Certifications',
                'Drafting of Compliance Documents',
            ],
            'Governance & Policy Advisory' => [
                'Document Delivery (Metro Cebu)',
                'Document Delivery (Outside Metro Cebu/LBC)',
                'Drafting of Affidavits (Non-Legal Advice)',
                'Drafting of Agreements / Simple Contracts',
                'Drafting of Board Resolutions',
                'Drafting of Endorsement / Request Letters',
            ],
            'Business Strategy & Process Advisory' => [
                'Notarization - Simple Documents',
                'Notarization - Complex Documents',
                "Drafting of Secretary's Certificates",
                'Drafting of Policies & Procedures',
                'Drafting of Reports / Formal Documents',
            ],
            'Strategic Situations Advisory' => [
                'Printing',
                'Photocopy',
                'Drafting of Letters',
                'Drafting of Notices',
                'Drafting of Demand Letters',
                'Drafting of Emails (Formal / Business)',
            ],
            'People & Talent Solutions' => [
                'Archive Retrieval',
                'Digital Archive Copy',
                'Drafting of Responses to Letters / Notices',
                'Drafting of Memorandum (Internal / External)',
                'Drafting of Certifications',
                'Drafting of Compliance Documents',
            ],
            'Learning & Capability Development' => [
                'Document Delivery (Metro Cebu)',
                'Document Delivery (Outside Metro Cebu/LBC)',
                'Drafting of Affidavits (Non-Legal Advice)',
                'Drafting of Agreements / Simple Contracts',
                'Drafting of Board Resolutions',
                'Drafting of Endorsement / Request Letters',
            ],
        ];

        try {
            if (! Schema::hasTable('products')) {
                return [
                    'productOptionsByServiceArea' => $fallbackGroups,
                    'productPricing' => collect($fallbackGroups)->flatten()->mapWithKeys(fn ($name) => [$name => 350])->all(),
                ];
            }

            $products = Product::query()
                ->select(['product_name', 'product_area', 'price', 'status'])
                ->whereNotNull('product_name')
                ->where('product_name', '!=', '')
                ->when(
                    Schema::hasColumn('products', 'status'),
                    fn ($query) => $query->whereIn('status', ['Pending Approval', 'Active'])
                )
                ->orderBy('product_name')
                ->get();

            $groups = [];
            $pricing = [];

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

                $pricing[$productName] = (float) ($product->price ?? 350);
            }

            $groups = collect($groups)
                ->map(fn (array $items): array => collect($items)->filter()->unique()->sort()->values()->all())
                ->filter(fn (array $items): bool => $items !== [])
                ->sortKeys()
                ->all();

            if ($groups === []) {
                return [
                    'productOptionsByServiceArea' => $fallbackGroups,
                    'productPricing' => collect($fallbackGroups)->flatten()->mapWithKeys(fn ($name) => [$name => 350])->all(),
                ];
            }

            return [
                'productOptionsByServiceArea' => $groups,
                'productPricing' => $pricing + collect($fallbackGroups)->flatten()->mapWithKeys(fn ($name) => [$name => 350])->all(),
            ];
        } catch (Throwable) {
            return [
                'productOptionsByServiceArea' => $fallbackGroups,
                'productPricing' => collect($fallbackGroups)->flatten()->mapWithKeys(fn ($name) => [$name => 350])->all(),
            ];
        }
    }

    private function normalizeServiceRequirementCatalogEntry(mixed $requirements): array
    {
        if (! is_array($requirements)) {
            return [];
        }

        if (is_array($requirements['groups'] ?? null)) {
            return collect($requirements['groups'])
                ->map(fn ($items) => collect(is_array($items) ? $items : [])
                    ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                    ->map(fn ($item) => trim((string) $item))
                    ->values()
                    ->all())
                ->filter(fn ($items) => $items !== [])
                ->all();
        }

        $legacyCategory = (string) ($requirements['category'] ?? '');
        $legacyItems = collect(is_array($requirements['items'] ?? null) ? $requirements['items'] : [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => trim((string) $item))
            ->values()
            ->all();

        if ($legacyItems === []) {
            return [];
        }

        $legacyKey = match ($legacyCategory) {
            'SOLE / NATURAL PERSON / INDIVIDUAL' => 'individual',
            'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)' => 'juridical',
            default => 'other',
        };

        return [$legacyKey => $legacyItems];
    }

    public function storeStage(Request $request): JsonResponse
    {
        $this->ensureDealStagesInfrastructure();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('deal_stages', 'name')],
        ]);

        $nextOrder = ((int) DealStage::query()->max('order')) + 1;
        $createdStage = DealStage::query()->create([
            'name' => trim($validated['name']),
            'order' => $nextOrder,
            'color' => null,
        ]);

        return response()->json([
            'stage' => [
                'id' => $createdStage->id,
                'name' => $createdStage->name,
                'order' => (int) $createdStage->order,
                'color' => $createdStage->color,
            ],
        ]);
    }

    public function updateStage(Request $request, DealStage $stage): JsonResponse
    {
        $this->ensureDealStagesInfrastructure();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100', Rule::unique('deal_stages', 'name')->ignore($stage->id)],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $originalName = $stage->name;
        $updates = [];

        if (array_key_exists('name', $validated) && filled($validated['name']) && $validated['name'] !== $stage->name) {
            $updates['name'] = trim($validated['name']);
        }

        if (array_key_exists('color', $validated)) {
            $updates['color'] = $validated['color'] ?: null;
        }

        if ($updates !== []) {
            $stage->update($updates);
        }

        if (isset($updates['name']) && Schema::hasTable('deals')) {
            Deal::query()->where('stage', $originalName)->update(['stage' => $updates['name']]);
        }

        return response()->json([
            'stage' => [
                'id' => $stage->id,
                'name' => $stage->fresh()->name,
                'order' => (int) $stage->fresh()->order,
                'color' => $stage->fresh()->color,
            ],
        ]);
    }

    public function moveStage(Request $request, DealStage $stage): JsonResponse
    {
        $this->ensureDealStagesInfrastructure();

        $validated = $request->validate([
            'direction' => ['required', 'in:left,right'],
        ]);

        $stages = DealStage::query()->orderBy('order')->get()->values();
        $currentIndex = $stages->search(fn (DealStage $item) => $item->id === $stage->id);
        $swapIndex = $validated['direction'] === 'left' ? $currentIndex - 1 : $currentIndex + 1;

        if ($currentIndex === false || ! isset($stages[$swapIndex])) {
            return response()->json(['ok' => true]);
        }

        $current = $stages[$currentIndex];
        $swap = $stages[$swapIndex];
        $currentOrder = $current->order;
        $current->update(['order' => $swap->order]);
        $swap->update(['order' => $currentOrder]);

        return response()->json(['ok' => true]);
    }

    public function destroyStage(DealStage $stage): JsonResponse
    {
        $this->ensureDealStagesInfrastructure();

        if (DealStage::query()->count() <= 1) {
            return response()->json(['message' => 'At least one stage must remain.'], 422);
        }

        if (Schema::hasTable('deals')) {
            $hasStageIdColumn = Schema::hasColumn('deals', 'stage_id');
            $hasStageNameColumn = Schema::hasColumn('deals', 'stage');
            $hasLinkedDeals = Deal::query()->where(function ($query) use ($stage, $hasStageIdColumn, $hasStageNameColumn) {
                if ($hasStageIdColumn) {
                    $query->orWhere('stage_id', $stage->id);
                }
                if ($hasStageNameColumn) {
                    $query->orWhere('stage', $stage->name);
                }
            })->exists();

            if ($hasLinkedDeals) {
                return response()->json(['message' => 'Stage cannot be deleted while it has deals.'], 422);
            }
        }

        $deletedStageId = $stage->id;
        $stage->delete();

        DealStage::query()->orderBy('order')->get()->values()->each(function (DealStage $item, int $index) {
            $item->update(['order' => $index + 1]);
        });

        return response()->json([
            'ok' => true,
            'deleted_stage_id' => $deletedStageId,
        ]);
    }

    public function updateDealStage(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            if (! Schema::hasTable('deals')) {
                return $this->dealStageErrorResponse($request, $id, 'Deals are not available right now. Please try again in a moment.', 503);
            }

            $validated = $request->validate([
                'stage_id' => ['required'],
            ]);

            $stageInput = trim((string) ($validated['stage_id'] ?? ''));
            if ($stageInput === '') {
                return $this->dealStageErrorResponse($request, $id, 'Select a stage before saving.', 422);
            }

            $deal = Deal::query()->find($id);
            if (! $deal) {
                return $this->dealStageErrorResponse($request, $id, 'The deal you are trying to update could not be found.', 404);
            }

            if (! $this->canViewDeal($deal)) {
                return $this->dealStageErrorResponse($request, $id, "You don't have access to this deal.", 403);
            }

            $hasStageTable = Schema::hasTable('deal_stages');
            $stage = null;
            $resolvedStageName = $stageInput;

            if ($hasStageTable) {
                if (is_numeric($stageInput)) {
                    $stage = DealStage::query()->find((int) $stageInput);
                } else {
                    $stage = DealStage::query()->where('name', $stageInput)->first();
                }
            }

            if (is_numeric($stageInput) && ! $stage) {
                return $this->dealStageErrorResponse($request, $id, 'The selected stage is no longer available. Refresh the page and try again.', 422);
            }

            if ($stage) {
                $resolvedStageName = $stage->name;
            }

            $updates = [];
            if ($stage && Schema::hasColumn('deals', 'stage_id')) {
                $updates['stage_id'] = $stage->id;
            }
            if (Schema::hasColumn('deals', 'stage')) {
                $updates['stage'] = $resolvedStageName;
            }

            if ($updates === []) {
                return $this->dealStageErrorResponse($request, $id, 'This deal cannot be moved because stage tracking is not configured yet.', 500);
            }

            $deal->update($updates);

            $stages = collect($this->dealStages())->values();
            $currentStage = $stage
                ? ($stages->firstWhere('id', $stage->id) ?: [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'order' => 0,
                    'color' => $stage->color,
                ])
                : ($stages->firstWhere('name', $resolvedStageName) ?: [
                    'id' => null,
                    'name' => $resolvedStageName,
                    'order' => 0,
                    'color' => null,
                ]);

            $payload = [
                'success' => true,
                'message' => 'Stage updated successfully.',
                'deal' => [
                    'id' => $deal->id,
                    'stage' => $resolvedStageName,
                    'stage_id' => $stage?->id,
                ],
                'current_stage' => $currentStage,
                'stages' => $stages->all(),
            ];

            if ($request->expectsJson()) {
                return response()->json($payload);
            }

            return redirect()
                ->route('deals.show', $id)
                ->with('stage_success', $payload['message']);
        } catch (Throwable $exception) {
            report($exception);

            return $this->dealStageErrorResponse(
                $request,
                $id,
                'We could not update the deal stage right now. Please try again.',
                500
            );
        }
    }

    public function preview(Request $request): RedirectResponse
    {
        $validated = $this->validateDealPayload($request);
        $validated = $this->applyInternalApprovalDefaults($validated, $request);

        try {
            $contact = $this->resolveContact((int) $validated['contact_id']);
            if (! $contact) {
                return $this->redirectToDealFormWithError(
                    $request,
                    'Select a valid existing client before previewing this deal.'
                );
            }

            $draft = $this->buildPreviewPayload($validated, $contact);

            $request->session()->put('deals.preview_payload', $draft);

            return redirect()->route('deals.preview.show');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToDealFormWithError(
                $request,
                'We could not generate the preview right now. Your entries are still here, so please review and try again.'
            );
        }
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $draft = $request->except('_token');
        $request->session()->put('deals.preview_payload', $draft);

        return redirect()->route('deals.index')->with('success', 'Deal draft saved to mock session.');
    }

    public function previewPage(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get('deals.preview_payload');

        if (! is_array($draft) || empty($draft)) {
            return redirect()->route('deals.index')->with('error', 'No deal draft found for preview.');
        }

        return view('deals.preview', [
            'draft' => $draft,
            'hiddenFields' => $this->hiddenDraftFields($draft),
            'dealFormData' => $this->normalizeDealFormData($draft),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDealPayload($request);
        $validated = $this->applyInternalApprovalDefaults($validated, $request);
        $validated = $this->applyCalculatedTimeline($validated);

        try {
            $validated['deal_code'] = Deal::hasValidDealCode($validated['deal_code'] ?? null)
                ? $validated['deal_code']
                : Deal::generateNextDealCode();
            $validated['deal_name'] = $validated['deal_code'];

            $contact = $this->resolveContact((int) $validated['contact_id']);
            if (! $contact) {
                return $this->redirectToDealFormWithError(
                    $request,
                    'Select a valid existing client before saving this deal.'
                );
            }

            if (! Schema::hasTable('deals') || ! Contact::query()->whereKey($validated['contact_id'])->exists()) {
                $mockDeal = $this->buildMockSavedDeal($validated, $contact);
                $request->session()->put('deals.mock_saved', $mockDeal);
                $request->session()->put('deals.mock_saved_payload', [
                    'id' => $mockDeal['id'],
                    ...$validated,
                ]);
                $request->session()->forget('deals.preview_payload');

                return redirect()->route('deals.show', $mockDeal['id'])->with('success', 'Mock deal saved and submitted for approval.');
            }

            $createdDeal = Deal::query()->create([
                ...$this->dealPersistencePayload($validated),
                ...(Schema::hasColumn('deals', 'stage_id') ? ['stage_id' => $this->resolveStageIdByName((string) ($validated['stage'] ?? ''))] : []),
                ...(Schema::hasColumn('deals', 'created_by') ? ['created_by' => optional(Auth::user())->name ?: 'System'] : []),
                'customer_type' => $validated['customer_type'] ?? $contact->customer_type,
                'salutation' => $validated['salutation'] ?? $contact->salutation,
                'first_name' => $validated['first_name'] ?? $contact->first_name,
                'middle_name' => $validated['middle_name'] ?? $contact->middle_name,
                'last_name' => $validated['last_name'] ?? $contact->last_name,
                'email' => $validated['email'] ?? $contact->email,
                'mobile' => $validated['mobile'] ?? $contact->phone,
                'address' => $validated['address'] ?? $contact->contact_address,
                'company_name' => $validated['company_name'] ?? $contact->company_name,
                'company_address' => $validated['company_address'] ?? $contact->company_address,
                'position' => $validated['position'] ?? $contact->position,
            ]);
            $this->applyFeePersistence($createdDeal, $validated);
            $this->syncDealNameToCode($createdDeal);
            $createdDeal->save();
            $this->projectProvisioner->createOrSyncFromDeal($createdDeal);

            $request->session()->forget('deals.preview_payload');

            return redirect()->route('deals.show', $createdDeal->id)->with('success', 'Deal created and submitted for approval.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToDealFormWithError(
                $request,
                'We could not save the deal right now. Please review the highlighted details and try again.'
            );
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $existingDeal = null;
        if (Schema::hasTable('deals')) {
            $existingDeal = Deal::query()->find($id);

            if (! $request->filled('contact_id') && filled($existingDeal?->contact_id)) {
                $request->merge([
                    'contact_id' => (int) $existingDeal->contact_id,
                ]);
            }
        }

        $validated = $this->validateDealPayload($request);
        $validated = $this->applyInternalApprovalDefaults($validated, $request, $existingDeal);
        $validated = $this->applyCalculatedTimeline($validated);

        try {
            $contact = $this->resolveContact((int) $validated['contact_id']);
            if (! $contact) {
                return $this->redirectToDealFormWithError(
                    $request,
                    'Select a valid existing client before updating this deal.',
                    $id
                );
            }

            if (! Schema::hasTable('deals')) {
                $mockSaved = $this->buildMockSavedDeal($validated, $contact) + ['id' => $id];
                $request->session()->put('deals.mock_saved', $mockSaved);
                $request->session()->put('deals.mock_saved_payload', [
                    'id' => $id,
                    ...$validated,
                ]);

                return redirect()->route('deals.show', $id)->with('success', 'Mock deal updated and resubmitted for approval.');
            }

            $deal = $existingDeal ?: Deal::query()->find($id);
            if (! $deal) {
                return redirect()
                    ->route('deals.index')
                    ->with('error', 'The deal you are trying to update could not be found.');
            }

            if (! $this->canViewDeal($deal)) {
                return redirect()
                    ->route('deals.index')
                    ->with('deal_access_denied', "You don't have access to this deal.");
            }

            $validated['deal_code'] = Deal::hasValidDealCode($deal->deal_code)
                ? $deal->deal_code
                : Deal::generateNextDealCode(
                    year: optional($deal->created_at)->year ?: (int) now()->format('Y'),
                    ignoreDealId: $deal->id
                );
            $validated['deal_name'] = $validated['deal_code'];
            $deal->fill([
                ...$this->dealPersistencePayload($validated),
                ...(Schema::hasColumn('deals', 'stage_id') ? ['stage_id' => $this->resolveStageIdByName((string) ($validated['stage'] ?? ''))] : []),
                'customer_type' => $validated['customer_type'] ?? $contact->customer_type,
                'salutation' => $validated['salutation'] ?? $contact->salutation,
                'first_name' => $validated['first_name'] ?? $contact->first_name,
                'middle_name' => $validated['middle_name'] ?? $contact->middle_name,
                'last_name' => $validated['last_name'] ?? $contact->last_name,
                'email' => $validated['email'] ?? $contact->email,
                'mobile' => $validated['mobile'] ?? $contact->phone,
                'address' => $validated['address'] ?? $contact->contact_address,
                'company_name' => $validated['company_name'] ?? $contact->company_name,
                'company_address' => $validated['company_address'] ?? $contact->company_address,
                'position' => $validated['position'] ?? $contact->position,
            ]);
            $this->applyFeePersistence($deal, $validated);
            $this->ensureDealCodeAssigned($deal, $contact);
            $this->syncDealNameToCode($deal);
            $deal->save();
            $this->projectProvisioner->createOrSyncFromDeal($deal);

            return redirect()->route('deals.show', $deal->id)->with('success', 'Deal updated and resubmitted for approval.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToDealFormWithError(
                $request,
                'We could not update the deal right now. Please review the highlighted details and try again.',
                $id
            );
        }
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        if (! $this->isDealReviewer($request)) {
            return redirect()
                ->route('deals.show', $id)
                ->with('error', 'You do not have permission to approve this deal.');
        }

        if (! Schema::hasTable('deals')) {
            return redirect()
                ->route('deals.index')
                ->with('error', 'Deals are not available right now. Please try again later.');
        }

        $deal = Deal::query()->find($id);
        if (! $deal) {
            return redirect()
                ->route('deals.index')
                ->with('error', 'The deal you are trying to approve could not be found.');
        }

        try {
            DB::transaction(function () use ($deal, $request): void {
                $deal->forceFill([
                    'qualification_result' => 'qualified',
                    'deal_status' => 'approved',
                    'stage' => $this->resolveQualifiedStage('qualified', (string) ($deal->stage ?? 'Qualification')),
                    'stage_id' => Schema::hasColumn('deals', 'stage_id')
                        ? $this->resolveStageIdByName($this->resolveQualifiedStage('qualified', (string) ($deal->stage ?? 'Qualification')))
                        : $deal->stage_id,
                    'approved_at' => now(),
                    'approved_by_name' => $request->user()?->name ?? 'System User',
                    'reviewed_by' => $request->user()?->name ?? 'System User',
                    'rejected_at' => null,
                    'rejected_by_name' => null,
                    'rejection_reason' => null,
                ])->save();

                $this->projectProvisioner->createFromApprovedDeal($deal);
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('deals.show', $deal->id)
                ->with('error', 'We could not approve the deal right now. Please try again.');
        }

        return redirect()
            ->route('deals.show', $deal->id)
            ->with('success', 'Deal qualified and approved successfully.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        if (! $this->isDealReviewer($request)) {
            return redirect()
                ->route('deals.show', $id)
                ->with('error', 'You do not have permission to reject this deal.');
        }

        if (! Schema::hasTable('deals')) {
            return redirect()
                ->route('deals.index')
                ->with('error', 'Deals are not available right now. Please try again later.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $deal = Deal::query()->find($id);
        if (! $deal) {
            return redirect()
                ->route('deals.index')
                ->with('error', 'The deal you are trying to reject could not be found.');
        }

        try {
            $deal->forceFill([
                'qualification_result' => 'not_qualified',
                'deal_status' => 'rejected',
                'stage' => 'Closed Lost',
                'stage_id' => Schema::hasColumn('deals', 'stage_id')
                    ? $this->resolveStageIdByName('Closed Lost')
                    : $deal->stage_id,
                'approved_at' => null,
                'approved_by_name' => null,
                'rejected_at' => now(),
                'rejected_by_name' => $request->user()?->name ?? 'System User',
                'rejection_reason' => trim((string) ($validated['reason'] ?? '')) ?: 'Not qualified for engagement.',
            ])->save();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('deals.show', $deal->id)
                ->with('error', 'We could not reject the deal right now. Please try again.');
        }

        return redirect()
            ->route('deals.show', $deal->id)
            ->with('success', 'Deal marked as not qualified and rejected.');
    }

    public function show(int $id): View|RedirectResponse
    {
        if (Schema::hasTable('deals')) {
            $storedDeal = Deal::query()->with(['contact', 'stage', 'project', 'regularProject', 'proposal', 'assignedFinance'])->find($id);
            if ($storedDeal) {
                if (! $this->canViewDeal($storedDeal)) {
                    return redirect()
                        ->route('deals.index')
                        ->with('deal_access_denied', "You don't have access to this deal.");
                }

                $stages = $this->dealStages();
                $currentStage = null;
                if (Schema::hasColumn('deals', 'stage_id')) {
                    if (! $storedDeal->stage_id && filled($storedDeal->stage)) {
                        $mappedStageId = $this->resolveStageIdByName((string) $storedDeal->stage);
                        if ($mappedStageId) {
                            $storedDeal->stage_id = $mappedStageId;
                            $storedDeal->save();
                            $storedDeal->refresh();
                        }
                    }

                    if ($storedDeal->stage_id) {
                        $currentStage = collect($stages)->firstWhere('id', $storedDeal->stage_id);
                    }
                }

                if (! $currentStage && filled($storedDeal->stage)) {
                    $currentStage = collect($stages)->firstWhere('name', $storedDeal->stage);
                }

                if (! $currentStage) {
                    $currentStage = collect($stages)->first();
                }

                if (! $currentStage) {
                    abort(500, 'Deal stage is missing from the stage list. Create deal stages first.');
                }

                if (Schema::hasColumn('deals', 'stage_id') && (int) ($storedDeal->stage_id ?? 0) !== (int) $currentStage['id']) {
                    $storedDeal->stage_id = $currentStage['id'];
                    $storedDeal->save();
                }

                $this->ensureDealCodeAssigned($storedDeal);

                $resolvedStageName = $currentStage['name'];
                $deal = [
                    'id' => $storedDeal->id,
                    'deal_code' => $storedDeal->deal_code,
                    'deal_name' => $storedDeal->deal_name,
                    'project_id' => $storedDeal->project?->id,
                    'contact_name' => trim(collect([$storedDeal->first_name, $storedDeal->last_name])->filter()->implode(' ')),
                    'company_name' => $storedDeal->company_name ?: (optional($storedDeal->contact)->company_name ?: '-'),
                    'amount' => (int) round((float) ($storedDeal->total_estimated_engagement_value ?? 0)),
                    'expected_close' => optional($storedDeal->estimated_completion_date)->format('M d, Y') ?: 'TBD',
                    'owner_name' => $storedDeal->assigned_consultant ?: 'Unassigned',
                    'stage' => $resolvedStageName,
                    'stage_id' => $currentStage['id'] ?? null,
                    'created_by' => $storedDeal->created_by ?: 'System',
                    'created_at_label' => optional($storedDeal->created_at)->format('F d, Y • h:i:s A') ?: now()->format('F d, Y • h:i:s A'),
                ];

                $detail = [
                    'related_contact' => $deal['contact_name'],
                    'related_company' => $deal['company_name'],
                    'deal_amount' => $deal['amount'],
                    'expected_close_date' => $deal['expected_close'],
                    'contact_person_name' => $deal['contact_name'],
                    'contact_person_position' => $storedDeal->position ?: '-',
                    'email_address' => $storedDeal->email ?: '-',
                    'contact_number' => $storedDeal->mobile ?: '-',
                    'client_type' => $storedDeal->customer_type ?: '-',
                    'industry' => $storedDeal->service_area ?: '-',
                    'deal_stage' => $resolvedStageName,
                    'deal_owner' => $storedDeal->assigned_consultant ?: 'Unassigned',
                    'created_date' => optional($storedDeal->created_at)->format('n/j/Y') ?: '-',
                    'last_modified' => optional($storedDeal->updated_at)->format('Y-m-d h:i A') ?: '-',
                    'deal_status' => $this->displayDealApprovalStatus($storedDeal),
                    'qualification_result' => $this->displayQualificationResult($storedDeal->qualification_result),
                    'qualification_notes' => $storedDeal->qualification_notes ?: '-',
                    'service' => [
                        'service_type' => $storedDeal->services ?: '-',
                        'product_type' => $storedDeal->products ?: '-',
                        'engagement_type' => $storedDeal->engagement_type ?: '-',
                        'engagement_duration' => $storedDeal->estimated_duration ?: '-',
                    ],
                    'financial' => [
                        'deal_value' => (int) round((float) ($storedDeal->total_estimated_engagement_value ?? 0)),
                        'pricing_model' => $storedDeal->engagement_type ?: '-',
                        'payment_terms' => $storedDeal->payment_terms ?: '-',
                        'commission_applicable' => $storedDeal->support_required ?: '-',
                    ],
                    'referral' => [
                        'lead_source' => optional($storedDeal->contact)->lead_source ?: '-',
                        'referred_by' => optional($storedDeal->contact)->referred_by ?: '-',
                        'referral_type' => optional($storedDeal->contact)->service_inquiry_type ?: '-',
                    ],
                    'ownership' => [
                        'lead_consultant' => $storedDeal->assigned_consultant ?: '-',
                        'lead_associate' => $storedDeal->assigned_associate ?: '-',
                        'finance' => $storedDeal->assignedFinance?->name ?: '-',
                        'finance_email' => $storedDeal->assignedFinance?->email,
                        'handling_team' => $storedDeal->service_department_unit ?: '-',
                        'assigned_members' => array_values(array_filter([
                            $storedDeal->assigned_consultant,
                            $storedDeal->assigned_associate,
                            $storedDeal->assignedFinance?->name,
                        ])),
                    ],
                    'project' => [
                        'id' => $storedDeal->project?->id,
                        'code' => $storedDeal->project?->project_code,
                        'status' => $storedDeal->project?->status,
                    ],
                    'regular_project' => [
                        'id' => $storedDeal->regularProject?->id,
                        'code' => $storedDeal->regularProject?->project_code,
                        'status' => $storedDeal->regularProject?->status,
                    ],
                    'progress' => [
                        'stages' => $stages,
                        'current_stage' => $currentStage,
                    ],
                    'timeline' => [
                        [
                            'icon' => 'fa-file-circle-plus',
                            'title' => 'Deal created',
                            'timestamp' => optional($storedDeal->created_at)->format('Y-m-d, h:i A') ?: '-',
                            'user' => $storedDeal->assigned_consultant ?: 'System',
                        ],
                    ],
                    'stage_history' => [
                        [
                            'stage' => $resolvedStageName,
                            'amount' => (int) round((float) ($storedDeal->total_estimated_engagement_value ?? 0)),
                            'duration' => $storedDeal->estimated_duration ?: '-',
                            'modified_by' => $storedDeal->assigned_consultant ?: 'System',
                            'date' => optional($storedDeal->updated_at)->format('M d, Y h:i A') ?: '-',
                        ],
                    ],
                ];

                $linkedProjectContext = $storedDeal->project
                    ? $this->buildLinkedProjectStartContext($storedDeal->project)
                    : [];

                return view('deals.show', [
                    'deal' => $deal,
                    'detail' => $detail,
                    'stages' => $stages,
                    'hasSavedProposal' => $storedDeal->proposal !== null,
                    'proposalRecord' => $storedDeal->proposal,
                    'dealFormData' => $this->storedDealFormData($storedDeal),
                    ...$linkedProjectContext,
                    ...$this->dealPanelContext($this->storedDealFormData($storedDeal)),
                    'financeUsers' => $this->financeUserOptions(),
                    'employeeOptions' => $this->employeeOptions(),
                    'openDealModal' => (bool) request()->boolean('edit_deal'),
                ]);
            }
        }

        $mockPayload = session('deals.mock_saved_payload');
        if (is_array($mockPayload) && (int) ($mockPayload['id'] ?? 0) === $id) {
            $mockDeal = session('deals.mock_saved', []);
            $stages = $this->dealStages();
            $currentStage = $this->resolveCurrentStageForDeal($mockPayload['stage'] ?? ($mockDeal['stage'] ?? 'Inquiry'), null);
            $deal = [
                'id' => $id,
                'deal_code' => $mockPayload['deal_code'] ?? ($mockDeal['deal_code'] ?? $this->generateDealCodeFromNames($mockPayload['first_name'] ?? '', $mockPayload['last_name'] ?? '', $id)),
                'deal_name' => $mockPayload['deal_name'] ?? ($mockDeal['deal_name'] ?? 'Mock Deal'),
                'contact_name' => trim(collect([$mockPayload['first_name'] ?? '', $mockPayload['last_name'] ?? ''])->filter()->implode(' ')) ?: ($mockDeal['contact_name'] ?? 'Linked Contact'),
                'company_name' => $mockPayload['company_name'] ?? ($mockDeal['company_name'] ?? '-'),
                'amount' => (int) round((float) ($mockPayload['total_estimated_engagement_value'] ?? 0)),
                'expected_close' => filled($mockPayload['estimated_completion_date'] ?? null)
                    ? Carbon::parse($mockPayload['estimated_completion_date'])->format('M d, Y')
                    : ($mockDeal['expected_close'] ?? 'TBD'),
                'owner_name' => $mockPayload['assigned_consultant'] ?? ($mockDeal['owner_name'] ?? 'Unassigned'),
                'stage' => $mockPayload['stage'] ?? ($mockDeal['stage'] ?? 'Inquiry'),
                'stage_id' => $currentStage['id'] ?? null,
            ];

            $detail = [
                'related_contact' => $deal['contact_name'],
                'related_company' => $deal['company_name'],
                'deal_amount' => $deal['amount'],
                'expected_close_date' => $deal['expected_close'],
                'contact_person_name' => $deal['contact_name'],
                'contact_person_position' => $mockPayload['position'] ?? '-',
                'email_address' => $mockPayload['email'] ?? '-',
                'contact_number' => $mockPayload['mobile'] ?? '-',
                'client_type' => $mockPayload['customer_type'] ?? '-',
                'industry' => $mockPayload['service_area'] ?? '-',
                'deal_stage' => $deal['stage'],
                'deal_owner' => $deal['owner_name'],
                'created_date' => now()->format('n/j/Y'),
                'last_modified' => now()->format('Y-m-d h:i A'),
                'deal_status' => strtolower((string) ($mockPayload['deal_status'] ?? 'pending')) === 'approved'
                    ? 'Approved'
                    : (strtolower((string) ($mockPayload['deal_status'] ?? 'pending')) === 'rejected' ? 'Rejected' : 'Pending'),
                'qualification_result' => strtolower((string) ($mockPayload['qualification_result'] ?? 'qualified')) === 'not_qualified'
                    ? 'Not Qualified / Archived'
                    : 'Qualified',
                'qualification_notes' => $mockPayload['qualification_notes'] ?? '-',
                'service' => [
                    'service_type' => $mockPayload['services'] ?? '-',
                    'product_type' => $mockPayload['products'] ?? '-',
                    'engagement_type' => $mockPayload['engagement_type'] ?? '-',
                    'engagement_duration' => $mockPayload['estimated_duration'] ?? '-',
                ],
                'financial' => [
                    'deal_value' => (int) round((float) ($mockPayload['total_estimated_engagement_value'] ?? 0)),
                    'pricing_model' => $mockPayload['engagement_type'] ?? '-',
                    'payment_terms' => $mockPayload['payment_terms'] ?? '-',
                    'commission_applicable' => $mockPayload['support_required'] ?? '-',
                ],
                'referral' => [
                    'lead_source' => $mockPayload['lead_source'] ?? '-',
                    'referred_by' => $mockPayload['referred_by'] ?? '-',
                    'referral_type' => $mockPayload['referral_type'] ?? '-',
                ],
                'ownership' => [
                    'lead_consultant' => $mockPayload['assigned_consultant'] ?? '-',
                    'lead_associate' => $mockPayload['assigned_associate'] ?? '-',
                    'handling_team' => $mockPayload['service_department_unit'] ?? '-',
                    'assigned_members' => array_values(array_filter([
                        $mockPayload['assigned_consultant'] ?? null,
                        $mockPayload['assigned_associate'] ?? null,
                    ])),
                ],
                'progress' => [
                    'stages' => $stages,
                    'current_stage' => $currentStage,
                ],
                'timeline' => [
                    [
                        'icon' => 'fa-file-circle-plus',
                        'title' => 'Mock deal created',
                        'timestamp' => now()->format('Y-m-d, h:i A'),
                        'user' => $deal['owner_name'],
                    ],
                ],
                'stage_history' => [
                    [
                        'stage' => $deal['stage'],
                        'amount' => $deal['amount'],
                        'duration' => $mockPayload['estimated_duration'] ?? '-',
                        'modified_by' => $deal['owner_name'],
                        'date' => now()->format('M d, Y h:i A'),
                    ],
                ],
            ];

            return view('deals.show', [
                'deal' => $deal,
                'detail' => $detail,
                'stages' => $stages,
                'hasSavedProposal' => false,
                'dealFormData' => $this->normalizeDealFormData($mockPayload),
                ...$this->dealPanelContext($this->normalizeDealFormData($mockPayload)),
                'financeUsers' => $this->financeUserOptions(),
                'employeeOptions' => $this->employeeOptions(),
                'openDealModal' => (bool) request()->boolean('edit_deal'),
            ]);
        }

        $deal = collect($this->mockDeals())->firstWhere('id', $id);

        if (! $deal) {
            return redirect()
                ->route('deals.index')
                ->with('error', 'The deal you are looking for could not be found.');
        }

        $stages = $this->dealStages();
        $currentStage = $this->resolveCurrentStageForDeal($deal['stage'], null);
        $detail = [
            'related_contact' => $deal['contact_name'],
            'related_company' => $deal['company_name'],
            'deal_amount' => $deal['amount'],
            'expected_close_date' => '6/10/2026',
            'contact_person_name' => $deal['contact_name'],
            'contact_person_position' => 'CEO',
            'email_address' => strtolower(str_replace(' ', '.', $deal['contact_name'])).'@consulting.com',
            'contact_number' => '+63 933 789 0123',
            'client_type' => 'Corporation',
            'industry' => 'Consulting',
            'deal_stage' => $deal['stage'],
            'deal_owner' => $deal['owner_name'],
            'created_date' => '2/22/2026',
            'last_modified' => '2026-02-24 01:49 PM',
            'deal_status' => 'Pending',
            'qualification_result' => 'Qualified',
            'qualification_notes' => '-',
            'service' => [
                'service_type' => 'Tax Advisory',
                'product_type' => 'Compliance Audit',
                'engagement_type' => 'Regular Retainer',
                'engagement_duration' => 'Annual',
            ],
            'financial' => [
                'deal_value' => $deal['amount'],
                'pricing_model' => 'Retainer',
                'payment_terms' => 'Installment',
                'commission_applicable' => 'Yes',
            ],
            'referral' => [
                'lead_source' => 'Website',
                'referred_by' => 'John Smith',
                'referral_type' => 'Partner Referral',
            ],
            'ownership' => [
                'lead_consultant' => 'Admin User',
                'lead_associate' => 'Karen User',
                'finance' => '-',
                'finance_email' => null,
                'handling_team' => 'Tax Team',
                'assigned_members' => ['Admin User', 'Karen User', 'John Adams'],
            ],
            'progress' => [
                'stages' => $stages,
                'current_stage' => $currentStage,
            ],
            'timeline' => [
                [
                    'icon' => 'fa-file-circle-plus',
                    'title' => 'Deal created',
                    'timestamp' => '2026-02-22, 08:00 AM',
                    'user' => 'Admin User',
                ],
                [
                    'icon' => 'fa-circle-arrow-up',
                    'title' => 'Stage changed to Inquiry',
                    'timestamp' => '2026-02-24, 01:49 PM',
                    'user' => 'Admin User',
                ],
            ],
            'stage_history' => [
                [
                    'stage' => 'Inquiry',
                    'amount' => 920000,
                    'duration' => '2 days',
                    'modified_by' => 'Admin User',
                    'date' => 'Feb 24, 2026 01:49 PM',
                ],
                [
                    'stage' => 'Qualification',
                    'amount' => 920000,
                    'duration' => '3 days',
                    'modified_by' => 'Admin User',
                    'date' => 'Feb 27, 2026 10:15 AM',
                ],
            ],
        ];

        if ((int) $deal['id'] === 501) {
            $detail = [
                'related_contact' => 'David Lee',
                'related_company' => 'Consulting Group',
                'deal_amount' => 920000,
                'expected_close_date' => '2026-06-10',
                'contact_person_name' => 'David Lee',
                'contact_person_position' => 'CEO',
                'email_address' => 'david.lee@consulting.com',
                'contact_number' => '09331234567',
                'client_type' => 'Corporation',
                'industry' => 'Tax Advisory',
                'deal_stage' => 'Inquiry',
                'deal_owner' => 'John Admin',
                'created_date' => '2026-03-17',
                'last_modified' => '2026-03-17 10:00 AM',
                'deal_status' => 'Pending',
                'qualification_result' => 'Qualified',
                'qualification_notes' => '-',
                'service' => [
                    'service_type' => 'Tax Advisory',
                    'product_type' => 'Compliance Audit',
                    'engagement_type' => 'Regular Retainer',
                    'engagement_duration' => 'Ongoing',
                ],
                'financial' => [
                    'deal_value' => 920000,
                    'pricing_model' => 'Retainer',
                    'payment_terms' => 'Installment',
                    'commission_applicable' => 'Yes',
                ],
                'referral' => [
                    'lead_source' => 'Website',
                    'referred_by' => 'John Smith',
                    'referral_type' => 'Partner Referral',
                ],
                'ownership' => [
                    'lead_consultant' => 'Admin User',
                    'lead_associate' => 'Karen User',
                    'handling_team' => 'Tax Team',
                    'assigned_members' => ['Admin User', 'Karen User'],
                ],
                'progress' => [
                    'stages' => $stages,
                    'current_stage' => $currentStage,
                ],
                'timeline' => [
                    [
                        'icon' => 'fa-file-circle-plus',
                        'title' => 'Mock deal loaded',
                        'timestamp' => '2026-03-17, 10:00 AM',
                        'user' => 'John Admin',
                    ],
                ],
                'stage_history' => [
                    [
                        'stage' => 'Inquiry',
                        'amount' => 920000,
                        'duration' => 'Mock flow',
                        'modified_by' => 'John Admin',
                        'date' => 'Mar 17, 2026 10:00 AM',
                    ],
                ],
            ];
        }

        return view('deals.show', [
            'deal' => $deal,
            'detail' => $detail,
            'stages' => $stages,
            'hasSavedProposal' => false,
            'dealFormData' => $this->normalizeDealFormData([
                ...$deal,
                'contact_id' => 101,
                'email' => $detail['email_address'] ?? null,
                'mobile' => $detail['contact_number'] ?? null,
                'service_area' => $detail['industry'] ?? null,
                'services' => data_get($detail, 'service.service_type'),
                'products' => data_get($detail, 'service.product_type'),
                'engagement_type' => data_get($detail, 'service.engagement_type'),
                'estimated_duration' => data_get($detail, 'service.engagement_duration'),
                'payment_terms' => data_get($detail, 'financial.payment_terms'),
                'assigned_consultant' => data_get($detail, 'ownership.lead_consultant'),
                'assigned_associate' => data_get($detail, 'ownership.lead_associate'),
                'service_department_unit' => data_get($detail, 'ownership.handling_team'),
                'referred_by' => data_get($detail, 'referral.referred_by'),
            ]),
            ...$this->dealPanelContext($this->normalizeDealFormData([
                ...$deal,
                'contact_id' => 101,
                'email' => $detail['email_address'] ?? null,
                'mobile' => $detail['contact_number'] ?? null,
            ])),
            'financeUsers' => $this->financeUserOptions(),
            'employeeOptions' => $this->employeeOptions(),
            'openDealModal' => (bool) request()->boolean('edit_deal'),
        ]);
    }

    public function downloadPdf(Request $request, int $id): View|RedirectResponse
    {
        if (Schema::hasTable('deals') && ($deal = Deal::query()->with('assignedFinance')->find($id))) {
            if (! $this->canViewDeal($deal)) {
                return redirect()
                    ->route('deals.index')
                    ->with('deal_access_denied', "You don't have access to this deal.");
            }
        }

        $payload = $this->buildDealPdfPayload($id);
        $payload['autoPrint'] = $request->boolean('autoprint');

        return view('pdf.deal', $payload);
    }

    private function canViewDeal(Deal $deal, ?User $user = null): bool
    {
        $user ??= Auth::user();

        return $deal->userCanAccess($user);
    }

    private function dealStages(): array
    {
        $this->ensureDealStagesInfrastructure();

        $defaults = [
            ['id' => null, 'name' => 'Inquiry', 'order' => 1, 'color' => '#2563eb'],
            ['id' => null, 'name' => 'Qualification', 'order' => 2, 'color' => '#4f46e5'],
            ['id' => null, 'name' => 'Consultation', 'order' => 3, 'color' => '#0891b2'],
            ['id' => null, 'name' => 'Proposal', 'order' => 4, 'color' => '#d97706'],
            ['id' => null, 'name' => 'Negotiation', 'order' => 5, 'color' => '#ea580c'],
            ['id' => null, 'name' => 'Payment', 'order' => 6, 'color' => '#059669'],
            ['id' => null, 'name' => 'Activation', 'order' => 7, 'color' => '#7c3aed'],
            ['id' => null, 'name' => 'Closed Won', 'order' => 8, 'color' => '#16a34a'],
            ['id' => null, 'name' => 'Closed Lost', 'order' => 9, 'color' => '#dc2626'],
        ];

        $stages = DealStage::query()
            ->orderBy('order')
            ->get(['id', 'name', 'order', 'color'])
            ->map(fn (DealStage $stage): array => [
                'id' => $stage->id,
                'name' => $stage->name,
                'order' => (int) $stage->order,
                'position' => (int) $stage->order,
                'color' => $stage->color,
            ])
            ->all();

        if ($stages !== []) {
            return $stages;
        }

        foreach ($defaults as $stage) {
            DealStage::query()->create([
                'name' => $stage['name'],
                'order' => $stage['order'],
                'color' => $stage['color'],
            ]);
        }

        return DealStage::query()
            ->orderBy('order')
            ->get(['id', 'name', 'order', 'color'])
            ->map(fn (DealStage $stage): array => [
                'id' => $stage->id,
                'name' => $stage->name,
                'order' => (int) $stage->order,
                'position' => (int) $stage->order,
                'color' => $stage->color,
            ])
            ->all();
    }

    private function ensureDealStagesInfrastructure(): void
    {
        if (! Schema::hasTable('deal_stages')) {
            Schema::create('deal_stages', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedInteger('order')->default(0);
                $table->string('color')->nullable();
                $table->timestamps();
            });
        }
    }

    private function mockDeals(): array
    {
        return [
            [
                'id' => 501,
                'deal_code' => 'DL-2026-001',
                'deal_name' => 'Tax Advisory Compliance Audit Regular Retainer',
                'contact_name' => 'David Lee',
                'company_name' => 'Consulting Group',
                'amount' => 920000,
                'expected_close' => 'Jun 10, 2026',
                'owner_name' => 'John Admin',
                'stage' => 'Inquiry',
            ],
            [
                'id' => 2,
                'deal_code' => 'DL-2026-002',
                'deal_name' => 'Data Analytics Platform',
                'contact_name' => 'David Lee',
                'company_name' => 'Consulting Group',
                'amount' => 920000,
                'expected_close' => 'Jun 10, 2026',
                'owner_name' => 'Admin User',
                'stage' => 'Qualification',
            ],
            [
                'id' => 3,
                'deal_code' => 'RJ-2026-001',
                'deal_name' => 'Cloud Migration Services',
                'contact_name' => 'Robert Johnson',
                'company_name' => 'Global Enterprises',
                'amount' => 800000,
                'expected_close' => 'May 30, 2026',
                'owner_name' => 'John Adams',
                'stage' => 'Consultation',
            ],
            [
                'id' => 4,
                'deal_code' => 'RJ-2026-002',
                'deal_name' => 'Cloud Migration Services',
                'contact_name' => 'Robert Johnson',
                'company_name' => 'Global Enterprises',
                'amount' => 800000,
                'expected_close' => 'May 30, 2026',
                'owner_name' => 'John Adams',
                'stage' => 'Proposal',
            ],
            [
                'id' => 5,
                'deal_code' => 'RJ-2026-003',
                'deal_name' => 'Cloud Migration Services',
                'contact_name' => 'Robert Johnson',
                'company_name' => 'Global Enterprises',
                'amount' => 800000,
                'expected_close' => 'May 30, 2026',
                'owner_name' => 'John Adams',
                'stage' => 'Negotiation',
            ],
            [
                'id' => 6,
                'deal_code' => 'RJ-2026-004',
                'deal_name' => 'Cloud Migration Services',
                'contact_name' => 'Robert Johnson',
                'company_name' => 'Global Enterprises',
                'amount' => 800000,
                'expected_close' => 'May 30, 2026',
                'owner_name' => 'John Adams',
                'stage' => 'Payment',
            ],
            [
                'id' => 7,
                'deal_code' => 'RJ-2026-005',
                'deal_name' => 'Cloud Migration Services',
                'contact_name' => 'Robert Johnson',
                'company_name' => 'Global Enterprises',
                'amount' => 800000,
                'expected_close' => 'May 30, 2026',
                'owner_name' => 'John Adams',
                'stage' => 'Activation',
            ],
            [
                'id' => 8,
                'deal_code' => 'MB-2026-001',
                'deal_name' => 'Website Redesign Project',
                'contact_name' => 'Michael Brown',
                'company_name' => 'Startup Hub',
                'amount' => 180000,
                'expected_close' => 'Feb 20, 2026',
                'owner_name' => 'Admin User',
                'stage' => 'Closed Lost',
            ],
            [
                'id' => 9,
                'deal_code' => 'SW-2026-001',
                'deal_name' => 'Security Audit Package',
                'contact_name' => 'Sarah Williams',
                'company_name' => 'Innovate Co.',
                'amount' => 120000,
                'expected_close' => 'Mar 25, 2026',
                'owner_name' => 'John Adams',
                'stage' => 'Inquiry',
            ],
        ];
    }

    private function dealPanelContext(array $draft = []): array
    {
        $owners = $this->ownerOptions();
        $defaultOwnerId = (int) ($owners[0]['id'] ?? 1001);
        $defaultOwner = collect($owners)->firstWhere('id', $defaultOwnerId) ?: collect($owners)->first();
        $contactRecords = [$this->mockContactRecord()];
        $contactOptions = ['David Lee'];
        $companyOptions = ['Consulting Group'];
        $companyRecords = [];

        if (Schema::hasTable('contacts')) {
            $contactColumns = array_values(array_filter([
                'id',
                'customer_type',
                'client_status',
                'salutation',
                'first_name',
                'middle_initial',
                'middle_name',
                'last_name',
                'name_extension',
                'sex',
                'date_of_birth',
                'email',
                'phone',
                'contact_address',
                'company_name',
                'company_address',
                'position',
            ], fn (string $column): bool => Schema::hasColumn('contacts', $column)));
            if (! in_array('id', $contactColumns, true)) {
                $contactColumns[] = 'id';
            }

            $contacts = Contact::query()->select($contactColumns)->orderBy('first_name')->orderBy('last_name')->get();
            $contactRecords = $contacts->map(function (Contact $contact): array {
                return [
                    'id' => $contact->id,
                    'label' => trim(collect([$contact->salutation, $contact->first_name, $contact->middle_name, $contact->last_name])->filter()->implode(' ')),
                    'search_blob' => strtolower(implode(' ', array_filter([
                        $contact->salutation,
                        $contact->first_name,
                        $contact->middle_initial,
                        $contact->middle_name,
                        $contact->last_name,
                        $contact->company_name,
                        $contact->email,
                        $contact->phone,
                    ]))),
                    'customer_type' => $contact->customer_type,
                    'client_status' => $contact->client_status,
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_initial' => $contact->middle_initial ?: (filled($contact->middle_name) ? mb_substr((string) $contact->middle_name, 0, 1) : null),
                    'middle_name' => $contact->middle_name,
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'sex' => $contact->sex,
                    'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                    'email' => $contact->email,
                    'mobile' => $contact->phone,
                    'address' => $contact->contact_address,
                    'company_name' => $contact->company_name,
                    'company_address' => $contact->company_address,
                    'position' => $contact->position,
                    'client_requirement_status_map' => $this->buildClientRequirementStatusMap(
                        strtolower((string) ($contact->cif_status ?? '')) === 'approved',
                        false,
                    ),
                ];
            })->filter(fn (array $record): bool => $record['label'] !== '' || filled($record['company_name']))->values()->all();

            if (! collect($contactRecords)->contains(fn (array $record) => (int) $record['id'] === 101)) {
                array_unshift($contactRecords, $this->mockContactRecord());
            }

            $contactOptions = $contacts->map(fn (Contact $contact): string => trim(($contact->first_name ?? '').' '.($contact->last_name ?? '')))->filter()->unique()->values()->all();
            $companyOptions = array_values(array_unique(array_merge(['Consulting Group'], $contacts->pluck('company_name')->filter()->values()->all())));
        }

        if (Schema::hasTable('companies')) {
            $companyColumns = array_values(array_filter([
                'id',
                'company_name',
                'email',
                'phone',
                'address',
                'owner_name',
            ], fn (string $column): bool => Schema::hasColumn('companies', $column)));
            if (! in_array('id', $companyColumns, true)) {
                $companyColumns[] = 'id';
            }

            $companies = Company::query()
                ->with([
                    'latestBif' => fn ($query) => $query->select([
                        'company_bifs.id',
                        'company_bifs.company_id',
                        'company_bifs.status',
                    ]),
                ])
                ->select($companyColumns)
                ->orderBy('company_name')
                ->get();
            $companyRecords = $companies->map(function (Company $company): array {
                return [
                    'id' => $company->id,
                    'label' => $company->company_name,
                    'search_blob' => strtolower(implode(' ', array_filter([
                        $company->company_name,
                        $company->email,
                        $company->phone,
                        $company->owner_name,
                        $company->address,
                    ]))),
                    'company_name' => $company->company_name,
                    'company_address' => $company->address,
                    'email' => $company->email,
                    'mobile' => $company->phone,
                    'owner_name' => $company->owner_name,
                    'client_requirement_status_map' => $this->buildClientRequirementStatusMap(
                        false,
                        strtolower((string) ($company->latestBif?->status ?? '')) === 'approved',
                    ),
                ];
            })->filter(fn (array $record): bool => filled($record['company_name']))->values()->all();

            $companyOptions = array_values(array_unique(array_merge($companyOptions, $companies->pluck('company_name')->filter()->values()->all())));
        }

        $serviceCatalog = $this->dealServiceCatalog();
        $productCatalog = $this->dealProductCatalog();

        return [
            'stageOptions' => array_values(array_map(fn (array $stage): string => $stage['name'], $this->dealStages())),
            'companyOptions' => $companyOptions,
            'contactOptions' => $contactOptions,
            'contactRecords' => $contactRecords,
            'companyRecords' => $companyRecords,
            'serviceAreaOptions' => $serviceCatalog['serviceAreaOptions'],
            'serviceGroups' => $serviceCatalog['serviceGroups'],
            'servicePricing' => $serviceCatalog['servicePricing'],
            'serviceRequirementCatalog' => $serviceCatalog['serviceRequirementCatalog'],
            'productOptionsByServiceArea' => $productCatalog['productOptionsByServiceArea'],
            'productPricing' => $productCatalog['productPricing'],
            'ownerLabel' => $defaultOwner['name'] ?? 'Shine Florence Padillo',
            'owners' => $owners,
            'defaultOwnerId' => $defaultOwnerId,
            'dealDraft' => $draft,
        ];
    }

    private function normalizeDealFormData(array $payload): array
    {
        $normalized = $payload;

        if (is_array($normalized['stage'] ?? null)) {
            $normalized['stage'] = (string) ($normalized['stage']['name'] ?? $normalized['stage']['stage'] ?? '');
        }

        $normalized['service_area_options'] = $this->normalizeListValue($payload['service_area_options'] ?? ($payload['service_area'] ?? null));
        $normalized['service_area_other'] = $this->parseCustomEntries($payload['service_area_other'] ?? ($payload['service_area'] ?? null));
        $normalized['service_options'] = $this->normalizeListValue($payload['service_options'] ?? ($payload['services'] ?? null));
        $normalized['product_options'] = $this->normalizeListValue($payload['product_options'] ?? ($payload['products'] ?? null));
        $normalized['service_identification_custom'] = $this->parseCustomEntries($payload['service_identification_custom'] ?? ($payload['services'] ?? null));
        $normalized['client_requirements_custom'] = $this->parseClientRequirementCustomEntries($payload['client_requirements_custom'] ?? ($payload['requirements_status'] ?? null));
        $normalized['required_actions_options'] = $this->normalizeListValue($payload['required_actions_options'] ?? ($payload['required_actions'] ?? null));
        $normalized['required_actions_custom'] = $this->parseCustomEntries($payload['required_actions_custom'] ?? ($payload['required_actions'] ?? null));
        $normalized['payment_terms_custom'] = $this->parseCustomEntries($payload['payment_terms_custom'] ?? ($payload['payment_terms_other'] ?? null));
        $normalized['service_complexity_custom'] = $this->parseCustomEntries($payload['service_complexity_custom'] ?? null);
        $normalized['support_required_options'] = $this->normalizeListValue($payload['support_required_options'] ?? ($payload['support_required'] ?? null));
        $normalized['support_required_custom'] = $this->parseCustomEntries($payload['support_required_custom'] ?? ($payload['support_required'] ?? null));
        $normalized['requirements_status_map'] = $this->normalizeRequirementStatusMap($payload['requirements_status_map'] ?? ($payload['requirements_status'] ?? null));
        if (filled($payload['id'] ?? null)) {
            $normalized['requirements_status_map']['deal_form'] = 'provided';
        }
        $normalized['other_fees'] = $this->normalizeOtherFees($payload['other_fees'] ?? null, $payload);
        $normalized['other_fees_titles'] = array_map(
            fn (array $fee): string => (string) ($fee['title'] ?? ''),
            $normalized['other_fees']
        );
        $normalized['other_fees_amounts'] = array_map(
            fn (array $fee): string => isset($fee['amount']) ? number_format((float) $fee['amount'], 2, '.', '') : '',
            $normalized['other_fees']
        );

        foreach ([
            'estimated_professional_fee',
            'estimated_government_fees',
            'estimated_service_support_fee',
            'total_service_fee',
            'total_product_fee',
            'deal_discount',
            'total_estimated_engagement_value',
        ] as $moneyField) {
            if (! array_key_exists($moneyField, $normalized) || blank($normalized[$moneyField])) {
                $normalized[$moneyField] = match ($moneyField) {
                    'estimated_government_fees' => $payload['estimated_government_fee'] ?? ($normalized[$moneyField] ?? null),
                    'total_estimated_engagement_value' => $payload['total_estimated_value'] ?? ($normalized[$moneyField] ?? null),
                    default => $normalized[$moneyField] ?? null,
                };
            }

            if (filled($normalized[$moneyField] ?? null) && is_numeric(str_replace(',', '', (string) $normalized[$moneyField]))) {
                $normalized[$moneyField] = 'P'.number_format((float) str_replace(',', '', (string) $normalized[$moneyField]), 2);
            }
        }

        return $normalized;
    }

    private function storedDealFormData(Deal $storedDeal): array
    {
        $contact = $storedDeal->contact;
        $stageName = (string) (($storedDeal->stage instanceof DealStage)
            ? $storedDeal->stage->name
            : ($storedDeal->stage ?: 'Inquiry'));

        return $this->normalizeDealFormData([
            ...$storedDeal->withoutRelations()->toArray(),
            'stage' => $stageName,
            'salutation' => $storedDeal->salutation ?: $contact?->salutation,
            'first_name' => $storedDeal->first_name ?: $contact?->first_name,
            'middle_initial' => $storedDeal->middle_initial ?? $contact?->middle_initial,
            'middle_name' => $storedDeal->middle_name ?: $contact?->middle_name,
            'last_name' => $storedDeal->last_name ?: $contact?->last_name,
            'name_extension' => $storedDeal->name_extension ?? $contact?->name_extension,
            'sex' => $storedDeal->sex ?: $contact?->sex,
            'date_of_birth' => filled($storedDeal->date_of_birth)
                ? optional($storedDeal->date_of_birth)->format('Y-m-d')
                : optional($contact?->date_of_birth)->format('Y-m-d'),
            'email' => $storedDeal->email ?: $contact?->email,
            'mobile' => $storedDeal->mobile ?: $contact?->phone,
            'address' => $storedDeal->address ?: $contact?->contact_address,
            'company_name' => $storedDeal->company_name ?: $contact?->company_name,
            'company_address' => $storedDeal->company_address ?: $contact?->company_address,
            'position' => $storedDeal->position ?: $contact?->position,
            'assigned_finance_user_id' => $storedDeal->assigned_finance_user_id,
            'internal_finance' => $storedDeal->assignedFinance?->name,
        ]);
    }

    private function buildDealPdfPayload(int $id): array
    {
        if (Schema::hasTable('deals')) {
            $storedDeal = Deal::query()->with(['contact', 'stage', 'assignedFinance'])->find($id);
            if ($storedDeal) {
                $this->ensureDealCodeAssigned($storedDeal);
                $stages = collect($this->dealStages());
                $currentStage = $stages->firstWhere('id', $storedDeal->stage_id);
                if (! $currentStage && filled($storedDeal->stage)) {
                    $currentStage = $stages->firstWhere('name', $storedDeal->stage);
                }
                $currentStage ??= $stages->first();

                $contact = $storedDeal->contact;
                $contactName = trim(collect([
                    $storedDeal->first_name ?: $contact?->first_name,
                    $storedDeal->last_name ?: $contact?->last_name,
                ])->filter()->implode(' '));

                $stageName = (string) ($currentStage['name'] ?? $storedDeal->stage ?? 'Inquiry');
                $dealFormData = $this->storedDealFormData($storedDeal);

                return [
                    'deal' => [
                        'id' => $storedDeal->id,
                        'deal_code' => $storedDeal->deal_code,
                        'deal_name' => $storedDeal->deal_name ?: 'Deal Form',
                        'stage' => $stageName,
                        'value' => $dealFormData['total_estimated_engagement_value'] ?? 'P0.00',
                        'owner_name' => $storedDeal->assigned_consultant ?: 'Unassigned',
                        'contact_name' => $contactName ?: 'Linked Contact',
                        'company_name' => $dealFormData['company_name'] ?? '-',
                    ],
                    'detail' => [
                        'contact_person_position' => $dealFormData['position'] ?? '-',
                        'email_address' => $dealFormData['email'] ?? '-',
                        'contact_number' => $dealFormData['mobile'] ?? '-',
                        'client_type' => $storedDeal->customer_type ?: '-',
                        'industry' => $storedDeal->service_area ?: '-',
                        'expected_close_date' => optional($storedDeal->estimated_completion_date)->format('M d, Y') ?: 'TBD',
                        'deal_status' => $this->displayDealApprovalStatus($storedDeal),
                        'qualification_result' => $this->displayQualificationResult($storedDeal->qualification_result),
                        'qualification_notes' => $storedDeal->qualification_notes ?: '-',
                    ],
                    'dealFormData' => $dealFormData,
                    'generatedAt' => now(),
                ];
            }
        }

        $mockDeal = collect($this->mockDeals())->firstWhere('id', $id);
        abort_unless($mockDeal, 404);

        $currentStage = $this->resolveCurrentStageForDeal((string) ($mockDeal['stage'] ?? 'Inquiry'), null);
        $dealFormData = $this->normalizeDealFormData([
            ...$mockDeal,
            'stage' => $currentStage['name'] ?? ($mockDeal['stage'] ?? 'Inquiry'),
            'contact_id' => 101,
            'first_name' => data_get($this->mockContactRecord(), 'first_name'),
            'last_name' => data_get($this->mockContactRecord(), 'last_name'),
            'email' => strtolower(str_replace(' ', '.', (string) ($mockDeal['contact_name'] ?? 'contact'))).'@consulting.com',
            'mobile' => '09331234567',
            'company_name' => $mockDeal['company_name'] ?? '-',
            'position' => 'CEO',
            'estimated_completion_date' => '2026-06-10',
        ]);

        return [
            'deal' => [
                'id' => $mockDeal['id'],
                'deal_code' => $mockDeal['deal_code'] ?? 'DEAL-'.$mockDeal['id'],
                'deal_name' => $mockDeal['deal_name'] ?? 'Deal Form',
                'stage' => $currentStage['name'] ?? ($mockDeal['stage'] ?? 'Inquiry'),
                'value' => $dealFormData['total_estimated_engagement_value'] ?? 'P0.00',
                'owner_name' => $mockDeal['owner_name'] ?? 'Unassigned',
                'contact_name' => $mockDeal['contact_name'] ?? 'Linked Contact',
                'company_name' => $mockDeal['company_name'] ?? '-',
            ],
            'detail' => [
                'contact_person_position' => 'CEO',
                'email_address' => $dealFormData['email'] ?? '-',
                'contact_number' => $dealFormData['mobile'] ?? '-',
                'client_type' => 'Corporation',
                'industry' => 'Consulting',
                'expected_close_date' => 'Jun 10, 2026',
                'deal_status' => 'Pending',
                'qualification_result' => 'Qualified',
                'qualification_notes' => '-',
            ],
            'dealFormData' => $dealFormData,
            'generatedAt' => now(),
        ];
    }

    private function normalizeListValue(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($item) => trim((string) $item))->filter()->values()->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeRequirementStatusMap(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->mapWithKeys(fn ($status, $key) => [(string) $key => strtolower((string) $status)])
                ->filter(fn ($status) => in_array($status, ['provided', 'pending'], true))
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $parsed = [];
        foreach (explode(';', $value) as $pair) {
            $parts = explode(':', $pair, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = str_replace(' ', '_', trim(strtolower($parts[0])));
            $status = trim(strtolower($parts[1]));
            if (in_array($status, ['provided', 'pending'], true)) {
                $parsed[$key] = $status;
            }
        }

        return $parsed;
    }

    private function redirectToDealFormWithError(Request $request, string $message, ?int $dealId = null): RedirectResponse
    {
        $redirect = $dealId
            ? redirect()->route('deals.show', ['id' => $dealId, 'edit_deal' => 1])
            : redirect()->route('deals.index', ['open_deal_modal' => 1]);

        return $redirect
            ->withInput()
            ->withErrors(['deal_form' => $message])
            ->with('error', $message);
    }

    private function dealStageErrorResponse(Request $request, int $dealId, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()
            ->route('deals.show', $dealId)
            ->with('error', $message);
    }

    private function validateDealPayload(Request $request): array
    {
        $this->normalizeFeeRequestKeys($request);

        $validated = $request->validate([
            'contact_id' => ['required', 'integer', 'min:1'],
            'deal_name' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', 'string', 'max:100'],
            'owner_id' => ['nullable', 'integer'],
            'service_area' => ['nullable', 'string', 'max:4000'],
            'services' => ['nullable', 'string', 'max:4000'],
            'products' => ['nullable', 'string', 'max:4000'],
            'service_area_options' => ['nullable', 'array'],
            'service_area_options.*' => ['string', 'max:255'],
            'service_area_other' => ['nullable'],
            'service_area_other.*' => ['nullable', 'string', 'max:255'],
            'service_options' => ['nullable', 'array'],
            'service_options.*' => ['string', 'max:255'],
            'total_service_fee' => ['nullable', 'numeric'],
            'services_other' => ['nullable'],
            'services_other.*' => ['nullable', 'string', 'max:255'],
            'service_identification_custom' => ['nullable', 'array'],
            'service_identification_custom.*' => ['nullable', 'string', 'max:255'],
            'product_options' => ['nullable', 'array'],
            'product_options.*' => ['string', 'max:255'],
            'total_product_fee' => ['nullable', 'numeric'],
            'products_other' => ['nullable', 'string', 'max:255'],
            'products_other_entries' => ['nullable', 'array'],
            'products_other_entries.*' => ['nullable', 'string', 'max:255'],
            'scope_of_work' => ['nullable', 'string'],
            'engagement_type' => ['nullable', 'string', 'max:255'],
            'requirements_status' => ['nullable'],
            'requirements_status.*' => ['nullable', 'in:provided,pending'],
            'requirements_status_map' => ['nullable', 'array'],
            'requirements_status_map.*' => ['nullable', 'in:provided,pending'],
            'client_requirements_custom' => ['nullable', 'array'],
            'client_requirements_custom.*' => ['nullable', 'string', 'max:255'],
            'required_actions' => ['nullable', 'string'],
            'required_actions_options' => ['nullable', 'array'],
            'required_actions_options.*' => ['string', 'max:255'],
            'required_actions_other' => ['nullable', 'string'],
            'required_actions_custom' => ['nullable', 'array'],
            'required_actions_custom.*' => ['nullable', 'string', 'max:255'],
            'estimated_professional_fee' => ['nullable', 'numeric'],
            'estimated_government_fees' => ['nullable', 'numeric'],
            'estimated_government_fee' => ['nullable', 'numeric'],
            'estimated_service_support_fee' => ['nullable', 'numeric'],
            'deal_discount' => ['nullable', 'numeric', 'min:0'],
            'total_estimated_engagement_value' => ['nullable', 'numeric'],
            'total_estimated_value' => ['nullable', 'numeric'],
            'other_fees_titles' => ['nullable', 'array'],
            'other_fees_titles.*' => ['nullable', 'string', 'max:255'],
            'other_fees_amounts' => ['nullable', 'array'],
            'other_fees_amounts.*' => ['nullable', 'numeric'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'payment_terms_other' => ['nullable', 'string', 'max:255'],
            'payment_terms_custom' => ['nullable', 'array'],
            'payment_terms_custom.*' => ['nullable', 'string', 'max:255'],
            'planned_start_date' => ['nullable', 'date'],
            'estimated_duration' => ['nullable', 'string', 'max:255'],
            'estimated_completion_date' => ['nullable', 'date'],
            'client_preferred_completion_date' => ['nullable', 'date'],
            'confirmed_delivery_date' => ['nullable', 'date'],
            'timeline_notes' => ['nullable', 'string'],
            'service_complexity' => ['nullable', 'string', 'max:255'],
            'service_complexity_custom' => ['nullable', 'array'],
            'service_complexity_custom.*' => ['nullable', 'string', 'max:255'],
            'support_required' => ['nullable', 'string', 'max:255'],
            'support_required_options' => ['nullable', 'array'],
            'support_required_options.*' => ['string', 'max:255'],
            'support_required_custom' => ['nullable', 'array'],
            'support_required_custom.*' => ['nullable', 'string', 'max:255'],
            'complexity_notes' => ['nullable', 'string'],
            'proposal_decision' => ['nullable', 'string', 'max:255'],
            'decline_reason' => ['nullable', 'string'],
            'qualification_notes' => ['nullable', 'string'],
            'assigned_consultant' => ['nullable', 'string', 'max:255'],
            'assigned_associate' => ['nullable', 'string', 'max:255'],
            'assigned_finance_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'service_department_unit' => ['nullable', 'string', 'max:255'],
            'consultant_notes' => ['nullable', 'string'],
            'associate_notes' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'string', 'max:255'],
            'client_status' => ['nullable', 'in:new,existing'],
            'salutation' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'name_extension' => ['nullable', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:255'],
            'optional_remarks' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:100'],
            'deal_reference_number' => ['nullable', 'string', 'max:100'],
            'selected_owner' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'reviewed_by' => ['nullable', 'string', 'max:255'],
            'internal_name' => ['nullable', 'string', 'max:255'],
            'internal_date' => ['nullable', 'date'],
            'client_fullname_signature' => ['nullable', 'string', 'max:255'],
            'referred_closed_by' => ['nullable', 'string', 'max:255'],
            'internal_sales_marketing' => ['nullable', 'string', 'max:255'],
            'lead_consultant' => ['nullable', 'string', 'max:255'],
            'lead_associate_assigned' => ['nullable', 'string', 'max:255'],
            'internal_finance' => ['nullable', 'string', 'max:255'],
            'internal_president' => ['nullable', 'string', 'max:255'],
            'created_date' => ['nullable', 'string', 'max:100'],
            'lead_source' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'referral_type' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['estimated_government_fees'] = $validated['estimated_government_fees']
            ?? $validated['estimated_government_fee']
            ?? null;
        $validated['total_estimated_engagement_value'] = $validated['total_estimated_engagement_value']
            ?? $validated['total_estimated_value']
            ?? null;

        foreach ([
            'estimated_professional_fee',
            'estimated_government_fees',
            'estimated_service_support_fee',
            'total_service_fee',
            'total_product_fee',
            'deal_discount',
            'total_estimated_engagement_value',
        ] as $moneyField) {
            if (blank($validated[$moneyField] ?? null)) {
                $validated[$moneyField] = null;
                continue;
            }

            $normalized = str_replace(',', '', (string) $validated[$moneyField]);
            $validated[$moneyField] = is_numeric($normalized) ? (float) $normalized : null;
        }

        $validated['other_fees'] = $this->normalizeOtherFees(null, $validated);
        $validated['total_estimated_engagement_value'] = $this->computeTotalEstimatedEngagementValue($validated);

        if (blank($validated['deal_name'] ?? null)) {
            $validated['deal_code'] = Deal::hasValidDealCode($validated['deal_code'] ?? null)
                ? $validated['deal_code']
                : Deal::generateNextDealCode();
            $validated['deal_name'] = $validated['deal_code'];
        }

        $serviceAreaSelections = collect($validated['service_area_options'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim((string) $value) !== '' && trim((string) $value) !== 'Others')
            ->map(fn ($value): string => trim((string) $value))
            ->values();
        $serviceAreaOtherEntries = collect(is_array($validated['service_area_other'] ?? null) ? $validated['service_area_other'] : [$validated['service_area_other'] ?? null])
            ->filter(fn ($value): bool => is_string($value) && trim((string) $value) !== '')
            ->map(fn ($value): string => 'Others: '.trim((string) $value))
            ->values();
        if (($validated['service_area_options'] ?? []) && in_array('Others', $validated['service_area_options'], true) && $serviceAreaOtherEntries->isEmpty()) {
            $serviceAreaSelections->push('Others');
        }
        $validated['service_area'] = $this->truncateStringForColumn(
            $serviceAreaSelections->merge($serviceAreaOtherEntries)->filter()->values()->implode(', ')
        );
        $serviceSelections = collect($validated['service_options'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->values();
        $serviceCustomEntries = collect($validated['service_identification_custom'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => 'Custom: '.trim((string) $value))
            ->values();
        $legacyServicesOtherEntries = collect(is_array($validated['services_other'] ?? null) ? $validated['services_other'] : [$validated['services_other'] ?? null])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => 'Custom: '.trim((string) $value))
            ->values();
        $serviceCustomEntries = $legacyServicesOtherEntries->merge($serviceCustomEntries)->unique()->values();
        $validated['services'] = $this->truncateStringForColumn(
            $serviceSelections->merge($serviceCustomEntries)->filter()->values()->implode(', ')
        );
        $productSelections = collect($validated['product_options'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '' && trim($value) !== 'Others')
            ->map(fn ($value): string => trim((string) $value))
            ->values();
        $customProductEntries = collect($validated['products_other_entries'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => 'Custom: '.trim((string) $value))
            ->values();
        if (filled($validated['products_other'] ?? null)) {
            $customProductEntries->prepend('Custom: '.trim((string) $validated['products_other']));
        }
        $validated['products'] = $this->truncateStringForColumn(
            $productSelections->merge($customProductEntries)->filter()->values()->implode(', ')
        );
        $requirementsStatusInput = $validated['requirements_status_map'] ?? ($validated['requirements_status'] ?? []);
        if (! is_array($requirementsStatusInput)) {
            $requirementsStatusInput = [];
        }
        $requirementsStatusInput['deal_form'] = 'provided';
        $validated['requirements_status_map'] = $requirementsStatusInput;
        $clientRequirementsCustom = $this->parseClientRequirementCustomEntries($validated['client_requirements_custom'] ?? []);
        $validated['requirements_status'] = $this->truncateStringForColumn(
            $this->stringifyRequirements($requirementsStatusInput, $clientRequirementsCustom)
        );
        $requiredActionsSelections = collect($validated['required_actions_options'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->values();
        $requiredActionsCustom = collect($validated['required_actions_custom'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => 'Custom: '.trim((string) $value))
            ->values();
        if (filled($validated['required_actions_other'] ?? null)) {
            $requiredActionsCustom->prepend('Custom: '.trim((string) $validated['required_actions_other']));
        }
        $validated['required_actions'] = $this->truncateStringForColumn(
            $requiredActionsSelections->merge($requiredActionsCustom)->filter()->values()->implode(', ')
        );
        $supportRequiredSelections = collect($validated['support_required_options'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->values();
        $supportRequiredCustom = collect($validated['support_required_custom'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => 'Custom: '.trim((string) $value))
            ->values();
        $validated['support_required'] = $this->truncateStringForColumn(
            $supportRequiredSelections->merge($supportRequiredCustom)->filter()->unique()->values()->implode(', ')
        );

        if (blank($validated['middle_name'] ?? null) && filled($validated['middle_initial'] ?? null)) {
            $validated['middle_name'] = $validated['middle_initial'];
        }

        $paymentTermsCustom = collect($validated['payment_terms_custom'] ?? [])
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn ($value): string => 'Custom: '.trim((string) $value))
            ->values();
        if (filled($validated['payment_terms_other'] ?? null)) {
            $legacyPaymentTermOther = trim((string) $validated['payment_terms_other']);
            $paymentTermsCustom->prepend(
                Str::startsWith($legacyPaymentTermOther, 'Custom: ')
                    ? $legacyPaymentTermOther
                    : 'Custom: '.$legacyPaymentTermOther
            );
        }
        $validated['payment_terms_other'] = $paymentTermsCustom->isEmpty()
            ? null
            : $this->truncateStringForColumn($paymentTermsCustom->unique()->values()->implode(', '), 255);
        if (! $paymentTermsCustom->isEmpty()) {
            $validated['payment_terms'] = 'Others';
        } elseif (($validated['payment_terms'] ?? null) !== 'Others') {
            $validated['payment_terms_other'] = null;
        }

        $validated['qualification_result'] = 'pending_review';
        $validated['qualification_notes'] = $this->truncateStringForColumn($validated['qualification_notes'] ?? null, 2000);
        $validated['deal_status'] = 'pending';
        $validated['approved_at'] = null;
        $validated['approved_by_name'] = null;
        $validated['rejected_at'] = null;
        $validated['rejected_by_name'] = null;
        $validated['rejection_reason'] = null;
        $validated['stage'] = trim((string) ($validated['stage'] ?? 'Qualification')) ?: 'Qualification';
        $validated['internal_finance'] = $this->financeNameForUserId((int) ($validated['assigned_finance_user_id'] ?? 0))
            ?: $this->truncateStringForColumn($validated['internal_finance'] ?? null);

        return $validated;
    }

    private function applyInternalApprovalDefaults(array $validated, Request $request, ?Deal $existingDeal = null): array
    {
        $creatorName = $this->truncateStringForColumn(
            $existingDeal?->created_by
                ?: ($request->user()?->name ?: 'System')
        );
        $createdDate = optional($existingDeal?->created_at)->format('Y-m-d') ?: now()->toDateString();
        $clientName = $this->truncateStringForColumn($this->resolveClientSignatureName($validated), 255);
        $reviewedBy = $this->truncateStringForColumn(
            $validated['reviewed_by']
                ?? $existingDeal?->approved_by_name
                ?? null
        );

        $validated['prepared_by'] = $this->truncateStringForColumn(
            $validated['prepared_by']
                ?? $existingDeal?->prepared_by
                ?? $creatorName
        );
        $validated['reviewed_by'] = $reviewedBy;
        $validated['internal_date'] = $validated['internal_date']
            ?? optional($existingDeal?->internal_date)->format('Y-m-d')
            ?? $createdDate;
        $validated['client_fullname_signature'] = $this->truncateStringForColumn(
            $validated['client_fullname_signature']
                ?? $existingDeal?->client_fullname_signature
                ?? $clientName
        );
        $validated['internal_president'] = $this->truncateStringForColumn(
            $validated['internal_president']
                ?? $existingDeal?->internal_president
                ?? 'John Kelly'
        );
        $validated['lead_consultant'] = $this->truncateStringForColumn(
            $validated['lead_consultant']
                ?? $existingDeal?->lead_consultant
                ?? ($validated['assigned_consultant'] ?? null)
        );
        $validated['lead_associate_assigned'] = $this->truncateStringForColumn(
            $validated['lead_associate_assigned']
                ?? $existingDeal?->lead_associate_assigned
                ?? ($validated['assigned_associate'] ?? null)
        );

        return $validated;
    }

    private function resolveClientSignatureName(array $validated): ?string
    {
        $fullName = trim(collect([
            $validated['salutation'] ?? null,
            $validated['first_name'] ?? null,
            $validated['middle_name'] ?? ($validated['middle_initial'] ?? null),
            $validated['last_name'] ?? null,
            $validated['name_extension'] ?? null,
        ])->filter(fn ($value) => filled($value))->implode(' '));

        if ($fullName !== '') {
            return $fullName;
        }

        return $this->truncateStringForColumn($validated['company_name'] ?? null);
    }

    private function normalizeOtherFees(mixed $storedOtherFees = null, array $payload = []): array
    {
        $titles = $payload['other_fees_titles'] ?? null;
        $amounts = $payload['other_fees_amounts'] ?? null;

        if (is_array($titles) || is_array($amounts)) {
            $titles = is_array($titles) ? array_values($titles) : [];
            $amounts = is_array($amounts) ? array_values($amounts) : [];
            $count = max(count($titles), count($amounts));
            $otherFees = [];

            for ($index = 0; $index < $count; $index++) {
                $title = trim((string) ($titles[$index] ?? ''));
                $rawAmount = str_replace(',', '', trim((string) ($amounts[$index] ?? '')));

                if ($title === '' || $rawAmount === '' || ! is_numeric($rawAmount)) {
                    continue;
                }

                $otherFees[] = [
                    'title' => $this->truncateStringForColumn($title, 255),
                    'amount' => (float) $rawAmount,
                ];
            }

            return $otherFees;
        }

        if (is_string($storedOtherFees)) {
            $decoded = json_decode($storedOtherFees, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $storedOtherFees = $decoded;
            }
        }

        if (! is_array($storedOtherFees)) {
            return [];
        }

        return collect($storedOtherFees)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): ?array {
                $title = trim((string) ($entry['title'] ?? ''));
                $rawAmount = str_replace(',', '', trim((string) ($entry['amount'] ?? '')));

                if ($title === '' || $rawAmount === '' || ! is_numeric($rawAmount)) {
                    return null;
                }

                return [
                    'title' => $this->truncateStringForColumn($title, 255),
                    'amount' => (float) $rawAmount,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function computeTotalEstimatedEngagementValue(array $validated): ?float
    {
        $baseTotal = collect([
            $validated['estimated_professional_fee'] ?? 0,
            $validated['estimated_government_fees'] ?? 0,
            $validated['estimated_service_support_fee'] ?? 0,
            $validated['total_service_fee'] ?? 0,
            $validated['total_product_fee'] ?? 0,
        ])->sum();
        $discount = (float) ($validated['deal_discount'] ?? 0);

        $otherFeesTotal = collect($validated['other_fees'] ?? [])
            ->sum(fn (array $fee): float => (float) ($fee['amount'] ?? 0));

        $total = max(((float) $baseTotal + (float) $otherFeesTotal) - $discount, 0);

        return $total > 0 ? round($total, 2) : null;
    }

    private function normalizeFeeRequestKeys(Request $request): void
    {
        $request->merge([
            'estimated_professional_fee' => $this->normalizeCurrencyInputValue($request->input('estimated_professional_fee')),
            'estimated_government_fees' => $this->normalizeCurrencyInputValue($request->input('estimated_government_fees', $request->input('estimated_government_fee'))),
            'estimated_government_fee' => $this->normalizeCurrencyInputValue($request->input('estimated_government_fee', $request->input('estimated_government_fees'))),
            'estimated_service_support_fee' => $this->normalizeCurrencyInputValue($request->input('estimated_service_support_fee')),
            'total_service_fee' => $this->normalizeCurrencyInputValue($request->input('total_service_fee')),
            'total_product_fee' => $this->normalizeCurrencyInputValue($request->input('total_product_fee')),
            'deal_discount' => $this->normalizeCurrencyInputValue($request->input('deal_discount')),
            'total_estimated_engagement_value' => $this->normalizeCurrencyInputValue($request->input('total_estimated_engagement_value', $request->input('total_estimated_value'))),
            'total_estimated_value' => $this->normalizeCurrencyInputValue($request->input('total_estimated_value', $request->input('total_estimated_engagement_value'))),
            'other_fees_amounts' => collect((array) $request->input('other_fees_amounts', []))
                ->map(fn ($value) => $this->normalizeCurrencyInputValue($value))
            ->all(),
        ]);
    }

    private function applyCalculatedTimeline(array $validated): array
    {
        $plannedStartDate = $validated['planned_start_date'] ?? null;
        $confirmedDeliveryDate = $validated['confirmed_delivery_date'] ?? null;

        if (blank($plannedStartDate) || blank($confirmedDeliveryDate)) {
            $validated['estimated_duration'] = null;
            return $validated;
        }

        try {
            $start = Carbon::parse((string) $plannedStartDate)->startOfDay();
            $end = Carbon::parse((string) $confirmedDeliveryDate)->startOfDay();
        } catch (\Throwable) {
            $validated['estimated_duration'] = null;
            return $validated;
        }

        if ($end->lt($start)) {
            $validated['estimated_duration'] = null;
            return $validated;
        }

        $validated['estimated_duration'] = (string) ($start->diffInDays($end) + 1);

        return $validated;
    }

    private function normalizeCurrencyInputValue(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
        $normalized = is_string($normalized) ? trim($normalized) : '';

        return $normalized === '' ? null : $normalized;
    }

    private function dealPersistencePayload(array $validated): array
    {
        if (! Schema::hasTable('deals')) {
            return $validated;
        }

        $dealColumns = collect(Schema::getColumnListing('deals'))
            ->filter(fn ($column): bool => is_string($column) && $column !== '')
            ->flip();

        $validated = collect($validated)
            ->filter(fn ($value, $key): bool => $dealColumns->has((string) $key))
            ->all();

        if (! Schema::hasColumn('deals', 'other_fees')) {
            unset($validated['other_fees']);
        }

        return $validated;
    }

    private function applyFeePersistence(Deal $deal, array $validated): void
    {
        $professional = (float) ($validated['estimated_professional_fee'] ?? 0);
        $government = (float) ($validated['estimated_government_fees'] ?? 0);
        $support = (float) ($validated['estimated_service_support_fee'] ?? 0);
        $totalServiceFee = (float) ($validated['total_service_fee'] ?? 0);
        $totalProductFee = (float) ($validated['total_product_fee'] ?? 0);
        $discount = (float) ($validated['deal_discount'] ?? 0);
        $otherFees = $validated['other_fees'] ?? [];
        $total = $this->computeTotalEstimatedEngagementValue([
            'estimated_professional_fee' => $professional,
            'estimated_government_fees' => $government,
            'estimated_service_support_fee' => $support,
            'total_service_fee' => $totalServiceFee,
            'total_product_fee' => $totalProductFee,
            'deal_discount' => $discount,
            'other_fees' => $otherFees,
        ]);

        $deal->estimated_professional_fee = $professional ?: null;
        $deal->estimated_government_fees = $government ?: null;
        $deal->estimated_service_support_fee = $support ?: null;
        $deal->total_estimated_engagement_value = $total;

        if (Schema::hasColumn('deals', 'total_service_fee')) {
            $deal->total_service_fee = $totalServiceFee ?: null;
        }

        if (Schema::hasColumn('deals', 'total_product_fee')) {
            $deal->total_product_fee = $totalProductFee ?: null;
        }

        if (Schema::hasColumn('deals', 'deal_discount')) {
            $deal->deal_discount = $discount ?: null;
        }

        if (Schema::hasColumn('deals', 'other_fees')) {
            $deal->other_fees = $otherFees;
        }

        if (Schema::hasColumn('deals', 'estimated_government_fee')) {
            $deal->setAttribute('estimated_government_fee', $government ?: null);
        }

        if (Schema::hasColumn('deals', 'total_estimated_value')) {
            $deal->setAttribute('total_estimated_value', $total);
        }
    }

    private function composeMultiSelectString(array $selected, ?string $other = null, string $otherPrefix = 'Others: '): ?string
    {
        $cleanSelected = collect($selected)
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values();

        if (filled($other)) {
            $cleanSelected->push($otherPrefix.trim((string) $other));
        }

        return $cleanSelected->isEmpty() ? null : $cleanSelected->implode(', ');
    }

    private function stringifyRequirements(array $requirements, array $customEntries = []): ?string
    {
        $pairs = collect($requirements)
            ->filter(fn ($status, $item): bool => filled($item) && in_array($status, ['provided', 'pending'], true))
            ->map(fn ($status, $item): string => str_replace('_', ' ', (string) $item).': '.$status)
            ->values();

        $custom = collect($customEntries)
            ->filter(fn ($entry): bool => is_string($entry) && trim($entry) !== '')
            ->map(fn ($entry): string => trim((string) $entry))
            ->values();

        $all = $pairs->merge($custom)->filter()->values();

        return $all->isEmpty() ? null : $all->implode('; ');
    }

    private function parseCustomEntries(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($entry): bool => is_string($entry) && trim($entry) !== '')
                ->map(fn ($entry): string => trim((string) $entry))
                ->map(function (string $entry): string {
                    if (Str::startsWith($entry, 'Custom: ')) {
                        return trim(Str::after($entry, 'Custom: '));
                    }
                    if (Str::startsWith($entry, 'Others: ')) {
                        return trim(Str::after($entry, 'Others: '));
                    }
                    return $entry;
                })
                ->filter(fn ($entry): bool => $entry !== '')
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/[,;]\s*/', $value))
            ->filter(fn ($entry): bool => is_string($entry) && trim($entry) !== '')
            ->map(fn ($entry): string => trim((string) $entry))
            ->filter(fn ($entry): bool => Str::startsWith($entry, ['Custom: ', 'Others: ']))
            ->map(function (string $entry): string {
                if (Str::startsWith($entry, 'Custom: ')) {
                    return trim(Str::after($entry, 'Custom: '));
                }
                return trim(Str::after($entry, 'Others: '));
            })
            ->filter(fn ($entry): bool => $entry !== '')
            ->values()
            ->all();
    }

    private function parseClientRequirementCustomEntries(mixed $value): array
    {
        $normalizeEntry = function (string $entry): ?string {
            $clean = trim($entry);
            if ($clean === '') {
                return null;
            }

            if (preg_match('/^Other:\s*(.*?)\s*\|\s*(Provided|Pending)$/i', $clean, $matches) === 1) {
                $label = trim((string) $matches[1]);
                if ($label === '') {
                    return null;
                }

                $status = strtolower((string) $matches[2]) === 'provided' ? 'Provided' : 'Pending';
                return 'Other: '.$label.' | '.$status;
            }

            if (Str::startsWith($clean, 'Custom: ')) {
                $clean = trim(Str::after($clean, 'Custom: '));
            } elseif (Str::startsWith($clean, 'Others: ')) {
                $clean = trim(Str::after($clean, 'Others: '));
            } elseif (Str::startsWith($clean, 'Other: ')) {
                $clean = trim(Str::after($clean, 'Other: '));
            } else {
                $clean = trim($clean);
            }

            return $clean === '' ? null : 'Other: '.$clean.' | Pending';
        };

        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($entry): bool => is_string($entry) && trim($entry) !== '')
                ->map(fn ($entry): ?string => $normalizeEntry((string) $entry))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(explode(';', $value))
            ->filter(fn ($entry): bool => is_string($entry) && trim($entry) !== '')
            ->map(fn ($entry): ?string => $normalizeEntry((string) $entry))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function truncateStringForColumn(?string $value, int $limit = 255): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::limit((string) $value, $limit, '');
    }

    private function resolveContact(int $contactId): ?Contact
    {
        if (Schema::hasTable('contacts')) {
            $contact = Contact::query()->find($contactId);
            if ($contact) {
                return $contact;
            }
        }

        if ($contactId === 101) {
            return new Contact([
                'customer_type' => 'Corporation',
                'client_status' => 'existing',
                'salutation' => 'Mr.',
                'first_name' => 'David',
                'middle_initial' => 'S',
                'middle_name' => '',
                'last_name' => 'Lee',
                'name_extension' => null,
                'sex' => 'Male',
                'date_of_birth' => '1990-01-01',
                'email' => 'david.lee@consulting.com',
                'phone' => '09331234567',
                'contact_address' => 'Makati City, Philippines',
                'company_name' => 'Consulting Group',
                'company_address' => 'Ayala Avenue, Makati City',
                'position' => 'CEO',
                'lead_source' => 'Website',
                'referred_by' => 'John Smith',
                'service_inquiry_type' => 'Partner Referral',
            ]);
        }

        return null;
    }

    private function buildPreviewPayload(array $validated, Contact $contact): array
    {
        $contactData = [
            'customer_type' => $validated['customer_type'] ?? $contact->customer_type,
            'client_status' => $validated['client_status'] ?? $contact->client_status,
            'salutation' => $validated['salutation'] ?? $contact->salutation,
            'first_name' => $validated['first_name'] ?? $contact->first_name,
            'middle_initial' => $validated['middle_initial'] ?? $contact->middle_initial,
            'middle_name' => $validated['middle_name'] ?? $contact->middle_name,
            'last_name' => $validated['last_name'] ?? $contact->last_name,
            'name_extension' => $validated['name_extension'] ?? $contact->name_extension,
            'sex' => $validated['sex'] ?? $contact->sex,
            'date_of_birth' => $validated['date_of_birth'] ?? optional($contact->date_of_birth)->format('Y-m-d'),
            'email' => $validated['email'] ?? $contact->email,
            'mobile' => $validated['mobile'] ?? $contact->phone,
            'address' => $validated['address'] ?? $contact->contact_address,
            'company_name' => $validated['company_name'] ?? $contact->company_name,
            'company_address' => $validated['company_address'] ?? $contact->company_address,
            'position' => $validated['position'] ?? $contact->position,
        ];

        $estimatedTotal = collect([
            $validated['estimated_professional_fee'] ?? 0,
            $validated['estimated_government_fees'] ?? 0,
            $validated['estimated_service_support_fee'] ?? 0,
        ])->sum();

        $dealValue = $validated['total_estimated_engagement_value'] ?? null;
        if (blank($dealValue) && $estimatedTotal > 0) {
            $dealValue = $estimatedTotal;
        }

        return [
            ...$validated,
            ...$contactData,
            'total_estimated_engagement_value' => $dealValue,
            'lead_source' => $validated['lead_source'] ?? $contact->lead_source,
            'referred_by' => $validated['referred_by'] ?? $contact->referred_by,
            'referral_type' => $validated['referral_type'] ?? $contact->service_inquiry_type,
            'deal_reference_number' => $validated['deal_reference_number'] ?? ('DEAL-'.now()->format('Ymd-His')),
            'selected_owner' => $validated['selected_owner']
                ?? $this->resolveOwnerName((int) ($validated['owner_id'] ?? 0))
                ?? ($validated['assigned_consultant'] ?? 'Unassigned'),
            'prepared_by' => $validated['prepared_by'] ?? ($validated['assigned_consultant'] ?? 'System'),
            'created_date' => $validated['created_date'] ?? now()->format('Y-m-d'),
            'status' => $validated['status'] ?? 'Draft',
            'contact_id' => (int) ($validated['contact_id'] ?? 0),
        ];
    }

    private function hiddenDraftFields(array $draft): array
    {
        $hidden = [];
        $this->flattenHiddenDraftFields($draft, $hidden);

        return $hidden;
    }

    private function flattenHiddenDraftFields(array $source, array &$destination, ?string $prefix = null): void
    {
        foreach ($source as $name => $value) {
            $fieldName = $prefix === null ? (string) $name : $prefix.'['.$name.']';

            if (is_array($value)) {
                $this->flattenHiddenDraftFields($value, $destination, $fieldName);
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $destination[$fieldName] = is_bool($value) ? (int) $value : (string) ($value ?? '');
        }
    }

    private function buildMockSavedDeal(array $validated, Contact $contact): array
    {
        $contactName = trim(collect([
            $validated['first_name'] ?? $contact->first_name,
            $validated['last_name'] ?? $contact->last_name,
        ])->filter()->implode(' '));

        $amount = $validated['total_estimated_engagement_value'] ?? collect([
            $validated['estimated_professional_fee'] ?? 0,
            $validated['estimated_government_fees'] ?? 0,
            $validated['estimated_service_support_fee'] ?? 0,
        ])->sum();

        $dealCode = Deal::generateNextDealCode();

        return [
            'id' => (int) now()->format('His'),
            'deal_code' => $dealCode,
            'deal_name' => $dealCode,
            'contact_name' => $contactName !== '' ? $contactName : 'Linked Contact',
            'company_name' => $validated['company_name'] ?? $contact->company_name ?? '-',
            'amount' => (int) round((float) $amount),
            'expected_close' => filled($validated['estimated_completion_date'] ?? null)
                ? Carbon::parse($validated['estimated_completion_date'])->format('M d, Y')
                : 'TBD',
            'owner_name' => $validated['assigned_consultant'] ?? 'Unassigned',
            'stage' => $validated['stage'] ?? 'Inquiry',
            'deal_status' => $validated['deal_status'] ?? 'pending',
            'qualification_result' => $validated['qualification_result'] ?? 'qualified',
            'qualification_notes' => $validated['qualification_notes'] ?? null,
            'created_by' => optional(Auth::user())->name ?: 'System',
            'created_at_label' => now()->format('F d, Y • h:i:s A'),
        ];
    }

    private function generateDealCode(?Contact $contact, ?int $existingDealId = null): string
    {
        return Deal::generateNextDealCode(ignoreDealId: $existingDealId);
    }

    private function ensureDealCodeAssigned(Deal $deal, ?Contact $contact = null): void
    {
        if (! Schema::hasColumn('deals', 'deal_code') || Deal::hasValidDealCode($deal->deal_code)) {
            return;
        }

        $deal->deal_code = Deal::generateNextDealCode(
            year: optional($deal->created_at)->year ?: (int) now()->format('Y'),
            ignoreDealId: $deal->id
        );
        $deal->save();
        $deal->refresh();
    }

    private function syncDealNameToCode(Deal $deal): void
    {
        if ($deal->deal_name !== $deal->deal_code) {
            $deal->deal_name = $deal->deal_code;
        }
    }

    private function backfillMissingDealCodes(): void
    {
        if (! Schema::hasTable('deals') || ! Schema::hasColumn('deals', 'deal_code')) {
            return;
        }

        Deal::query()
            ->with('contact')
            ->where(function ($query) {
                $query->whereNull('deal_code')
                    ->orWhere('deal_code', '');
            })
            ->orderBy('id')
            ->get()
            ->each(function (Deal $deal): void {
                $this->ensureDealCodeAssigned($deal, $deal->contact);
                $this->syncDealNameToCode($deal);
                $deal->save();
            });

        Deal::query()
            ->with('contact')
            ->whereNotNull('deal_code')
            ->where('deal_code', '!=', '')
            ->orderBy('id')
            ->get()
            ->filter(fn (Deal $deal): bool => ! Deal::hasValidDealCode($deal->deal_code))
            ->each(function (Deal $deal): void {
                $this->ensureDealCodeAssigned($deal, $deal->contact);
                $this->syncDealNameToCode($deal);
                $deal->save();
            });

        Deal::query()
            ->whereNotNull('deal_code')
            ->whereColumn('deal_name', '!=', 'deal_code')
            ->orderBy('id')
            ->get()
            ->each(function (Deal $deal): void {
                $this->syncDealNameToCode($deal);
                $deal->save();
            });
    }

    private function generateDealCodeFromNames(string $firstName, string $lastName, int $seed): string
    {
        return 'CONDEAL-'.now()->format('Y').'-'.str_pad((string) max($seed, 1), 3, '0', STR_PAD_LEFT);
    }

    private function dealCodeInitials(string $firstName, string $lastName): string
    {
        $letters = strtoupper(mb_substr(trim($firstName), 0, 1).mb_substr(trim($lastName), 0, 1));

        return str_pad($letters !== '' ? $letters : 'DL', 2, 'X');
    }

    private function resolveCurrentStageForDeal(?string $stageName, ?int $stageId): array
    {
        $stages = collect($this->dealStages());

        if ($stageId) {
            $stage = $stages->firstWhere('id', $stageId);
            if ($stage) {
                return $stage;
            }
        }

        if (filled($stageName)) {
            $stage = $stages->firstWhere('name', $stageName);
            if ($stage) {
                return $stage;
            }
        }

        return $stages->first() ?: [
            'id' => null,
            'name' => $stageName ?: 'Inquiry',
            'order' => 1,
            'color' => null,
        ];
    }

    private function resolveStageIdByName(string $stageName): ?int
    {
        if (! Schema::hasTable('deal_stages') || trim($stageName) === '') {
            return null;
        }

        return DealStage::query()->where('name', trim($stageName))->value('id');
    }

    private function mockContactRecord(): array
    {
        return [
            'id' => 101,
            'label' => 'David Lee',
            'search_blob' => 'david lee consulting group david.lee@consulting.com 09331234567',
            'customer_type' => 'Corporation',
            'client_status' => 'existing',
            'salutation' => 'Mr.',
            'first_name' => 'David',
            'middle_initial' => 'S',
            'middle_name' => '',
            'last_name' => 'Lee',
            'name_extension' => null,
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'email' => 'david.lee@consulting.com',
            'mobile' => '09331234567',
            'address' => 'Makati City, Philippines',
            'company_name' => 'Consulting Group',
            'company_address' => 'Ayala Avenue, Makati City',
            'position' => 'CEO',
        ];
    }

    private function ownerOptions(): array
    {
        if (! Schema::hasTable('users')) {
            return [
                ['id' => 1001, 'name' => 'Shine Florence Padillo', 'email' => 'shinepadi@gmail.com'],
                ['id' => 1002, 'name' => 'John Admin', 'email' => 'john.admin@example.com'],
                ['id' => 1003, 'name' => 'Maria Santos', 'email' => 'maria.santos@example.com'],
                ['id' => 1004, 'name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com'],
            ];
        }

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email ?: strtolower(str_replace(' ', '.', $user->name)).'@example.com',
            ])
            ->values()
            ->all();

        if (! empty($users)) {
            return $users;
        }

        return [
            ['id' => 1001, 'name' => 'Shine Florence Padillo', 'email' => 'shinepadi@gmail.com'],
            ['id' => 1002, 'name' => 'John Admin', 'email' => 'john.admin@example.com'],
            ['id' => 1003, 'name' => 'Maria Santos', 'email' => 'maria.santos@example.com'],
            ['id' => 1004, 'name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@example.com'],
        ];
    }

    private function resolveOwnerName(int $ownerId): ?string
    {
        if ($ownerId <= 0) {
            return null;
        }

        $owner = collect($this->ownerOptions())->firstWhere('id', $ownerId);

        return is_array($owner) ? ($owner['name'] ?? null) : null;
    }

    private function financeUserOptions(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        return User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->whereIn('role', ['Admin', 'Employee', 'SuperAdmin'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ])
            ->values()
            ->all();
    }

    private function employeeOptions(): array
    {
        if (! Schema::hasTable('employees')) {
            return collect($this->ownerOptions())
                ->map(fn (array $owner): array => [
                    'id' => $owner['id'] ?? null,
                    'name' => $owner['name'] ?? '',
                    'employee_code' => null,
                    'email' => $owner['email'] ?? null,
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

    private function financeNameForUserId(int $userId): ?string
    {
        if ($userId <= 0 || ! Schema::hasTable('users')) {
            return null;
        }

        return User::query()->whereKey($userId)->value('name');
    }

    private function isDealReviewer(Request $request): bool
    {
        return in_array((string) ($request->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true);
    }

    private function displayDealApprovalStatus(Deal $deal): string
    {
        return match (strtolower((string) ($deal->deal_status ?? 'pending'))) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Pending',
        };
    }

    private function displayQualificationResult(?string $value): string
    {
        return match (strtolower((string) $value)) {
            'qualified' => 'Qualified',
            'not_qualified' => 'Not Qualified / Archived',
            default => 'Pending Review',
        };
    }

    private function resolveQualifiedStage(string $qualificationResult, string $currentStage): string
    {
        if ($qualificationResult === 'not_qualified') {
            return 'Closed Lost';
        }

        $stage = trim($currentStage);

        if ($stage === '' || $stage === 'Qualification') {
            return 'Proposal';
        }

        return $stage;
    }

    private function buildClientRequirementStatusMap(bool $hasApprovedCif, bool $hasApprovedBif): array
    {
        return [
            'client_contact_form' => $hasApprovedCif ? 'provided' : 'pending',
            'deal_form' => 'pending',
            'business_information_form' => $hasApprovedBif ? 'provided' : 'pending',
            'client_information_form' => $hasApprovedCif ? 'provided' : 'pending',
            'service_task_activation_routing_tracker' => 'pending',
        ];
    }

    private function buildLinkedProjectStartContext(Project $project): array
    {
        $project->loadMissing([
            'deal:id,deal_code,engagement_type',
            'contact:id,first_name,last_name,email,phone,company_name',
            'company.primaryContact:id,first_name,middle_name,last_name,email,phone,company_name,cif_status,organization_type,business_type_organization,ownership_flag,foreign_business_nature',
            'company.latestBif',
            'starts' => fn ($query) => $query->latest(),
            'sows' => fn ($query) => $query->latest(),
        ]);

        $start = $project->starts
            ->sort(function ($left, $right) {
                $rank = fn ($item) => match (strtolower((string) ($item->status ?? ''))) {
                    'approved' => 1,
                    'pending_approval' => 2,
                    'rejected' => 3,
                    default => 4,
                };

                $leftRank = $rank($left);
                $rightRank = $rank($right);
                if ($leftRank !== $rightRank) {
                    return $leftRank <=> $rightRank;
                }

                $leftTime = optional($left->updated_at)->getTimestamp() ?? 0;
                $rightTime = optional($right->updated_at)->getTimestamp() ?? 0;
                if ($leftTime !== $rightTime) {
                    return $rightTime <=> $leftTime;
                }

                return ((int) $right->id) <=> ((int) $left->id);
            })
            ->first();
        $sow = $project->sows->first();
        $startChecklist = collect($start?->checklist ?? [])->whenEmpty(fn () => collect([
            ['label' => 'Client Contact Form', 'status' => 'pending'],
            ['label' => 'Deal Form', 'status' => 'pending'],
            ['label' => 'Business Information Form', 'status' => 'pending'],
            ['label' => 'Client Information Form', 'status' => 'pending'],
            ['label' => 'Service Task Activation & Routing Tracker (Start)', 'status' => 'pending'],
            ['label' => 'Others', 'status' => 'pending'],
        ]));
        $startKyc = (array) ($start?->kyc_requirements ?? []);
        $hasVisibleKycRows = collect($startKyc['sole'] ?? [])->isNotEmpty() || collect($startKyc['juridical'] ?? [])->isNotEmpty();
        if (! $hasVisibleKycRows) {
            $startKyc = $this->projectProvisioner->resolveDealWorkspaceStartKyc($project);
        }
        $startReqs = collect($start?->engagement_requirements ?? [])->whenEmpty(fn () => collect([[
            'number' => 1,
            'requirement' => '',
            'notes' => '',
            'purpose' => '',
            'provided_by' => '',
            'submitted_to' => '',
            'assigned_to' => '',
            'timeline' => '',
        ]]));
        $startApprovalSteps = collect($start?->approval_steps ?? [])->whenEmpty(fn () => collect([
            ['requirement' => 'Client Contact Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Deal Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Business Information Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Client Information Form', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Service Task Activation & Routing Tracker (Start)', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Engagement-Specific Requirement', 'responsible_person' => 'Sales & Marketing/Consultant/Associate', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Proposal/Contract', 'responsible_person' => 'Sales & Marketing/Lead Consultant/Lead Associate', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Final Quote', 'responsible_person' => 'Lead Consultant/Lead Associate', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Invoice-Downpayment/Advance', 'responsible_person' => 'Finance', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Clearance', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
            ['requirement' => 'Turn Over', 'responsible_person' => 'Sales & Marketing', 'name_and_signature' => '', 'date_time_done' => ''],
        ]));
        $routing = collect($start?->routing ?? [])->whenEmpty(fn () => collect([
            ['role' => 'Admin', 'status' => 'pending'],
            ['role' => 'Lead Consultant', 'status' => 'pending'],
            ['role' => 'Lead Associate', 'status' => 'pending'],
            ['role' => 'Sales & Marketing', 'status' => 'pending'],
        ]));

        return [
            'project' => $project,
            'start' => $start,
            'startChecklist' => $startChecklist,
            'startKycOrganization' => (string) ($startKyc['organization_type'] ?? 'unknown'),
            'startKycSole' => collect($startKyc['sole'] ?? []),
            'startKycJuridical' => collect($startKyc['juridical'] ?? []),
            'startReqs' => $startReqs,
            'startApprovalSteps' => $startApprovalSteps,
            'startClearance' => (array) ($start?->clearance ?? []),
            'routing' => $routing,
            'serviceMemo' => $start ? $this->buildDealServiceMemoPayload($project, $start, $sow) : null,
        ];
    }

    private function buildDealServiceMemoPayload(Project $project, $start, $sow): array
    {
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
}
