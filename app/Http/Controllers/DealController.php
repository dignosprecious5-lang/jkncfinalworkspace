<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Deal;
use App\Models\DealContact;
use App\Models\DealProposal;
use App\Models\DealProposalItemResponse;
use App\Models\DealStage;
use App\Models\User;
use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Product;
use App\Models\Service;
use App\Models\DealHistory;
use App\Models\DealStageHistory;
use App\Services\DealHistoryService;
use App\Services\ProjectProvisioner;
use App\Services\DealProposalAggregationService;

class DealController extends Controller
{
    public function __construct(private readonly ?ProjectProvisioner $projectProvisioner = null)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Deals Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Deal::with(['account', 'company']);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);
            $tokens = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($q) use ($search, $tokens) {
                $q->where('deal_code', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('deal_title', 'like', "%{$search}%")
                    ->orWhere('deal_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('primary_contact_name', 'like', "%{$search}%")
                    ->orWhere('client_search', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('services_products', 'like', "%{$search}%")
                    ->orWhere('services', 'like', "%{$search}%")
                    ->orWhere('products', 'like', "%{$search}%")
                    ->orWhere('service_area', 'like', "%{$search}%")
                    ->orWhere('scope_of_work', 'like', "%{$search}%")
                    ->orWhere('pipeline_stage', 'like', "%{$search}%")
                    ->orWhere('stage', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($accQ) use ($search) {
                        $accQ->where('account_name', 'like', "%{$search}%");
                    });

                if (count($tokens) > 1) {
                    $q->orWhere(function ($multiQ) use ($tokens) {
                        foreach ($tokens as $token) {
                            $multiQ->where(function ($subQ) use ($token) {
                                $subQ->where('deal_code', 'like', "%{$token}%")
                                    ->orWhere('id', 'like', "%{$token}%")
                                    ->orWhere('deal_title', 'like', "%{$token}%")
                                    ->orWhere('deal_name', 'like', "%{$token}%")
                                    ->orWhere('first_name', 'like', "%{$token}%")
                                    ->orWhere('last_name', 'like', "%{$token}%")
                                    ->orWhere('company_name', 'like', "%{$token}%")
                                    ->orWhere('company', 'like', "%{$token}%")
                                    ->orWhere('primary_contact_name', 'like', "%{$token}%")
                                    ->orWhere('client_search', 'like', "%{$token}%")
                                    ->orWhere('email', 'like', "%{$token}%")
                                    ->orWhere('services_products', 'like', "%{$token}%")
                                    ->orWhere('services', 'like', "%{$token}%")
                                    ->orWhere('products', 'like', "%{$token}%")
                                    ->orWhere('service_area', 'like', "%{$token}%")
                                    ->orWhere('pipeline_stage', 'like', "%{$token}%")
                                    ->orWhere('stage', 'like', "%{$token}%")
                                    ->orWhereHas('account', function ($accQ) use ($token) {
                                        $accQ->where('account_name', 'like', "%{$token}%");
                                    });
                            });
                        }
                    });
                }
            });
        }

        $stages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
            'Closed Won',
            'Closed Lost',
        ];

        /*
        |--------------------------------------------------------------------------
        | Deal Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('deal_filter')) {
            if ($request->deal_filter === 'my_deals') {
                $currentUser = auth()->user();
                $userName = $currentUser ? $currentUser->name : null;
                $userEmail = $currentUser ? $currentUser->email : null;
                $userId = $currentUser ? $currentUser->id : null;

                $query->where(function ($q) use ($userName, $userEmail, $userId) {
                    if ($userName) {
                        $q->where('owner_name', 'like', "%{$userName}%")
                            ->orWhere('created_by', 'like', "%{$userName}%")
                            ->orWhere('assigned_consultant', 'like', "%{$userName}%")
                            ->orWhere('assigned_associate', 'like', "%{$userName}%")
                            ->orWhere('lead_consultant', 'like', "%{$userName}%")
                            ->orWhere('lead_associate_assigned', 'like', "%{$userName}%")
                            ->orWhere('prepared_by', 'like', "%{$userName}%");
                    }
                    if ($userEmail) {
                        $q->orWhere('owner_name', 'like', "%{$userEmail}%")
                            ->orWhere('created_by', 'like', "%{$userEmail}%");
                    }
                    if ($userId) {
                        $q->orWhere('user_id', $userId)
                            ->orWhere('created_by_id', $userId)
                            ->orWhere('owner_id', $userId);
                    }
                    if (!$userName) {
                        $q->where('owner_name', 'John Kelly Abalde');
                    }
                });
            } elseif (in_array($request->deal_filter, $stages)) {
                $selectedStage = $request->deal_filter;
                $stageAliases = match($selectedStage) {
                    'Inquiry' => ['Inquiry', 'Inquiry / Consultation', 'Intake'],
                    'Proposal' => ['Proposal', 'Solutioning / Proposal'],
                    'Negotiation' => ['Negotiation', 'Negotiation / Finalizing'],
                    'Activation' => ['Activation', 'Activated'],
                    'Closed Won' => ['Closed Won', 'approved'],
                    'Closed Lost' => ['Closed Lost', 'rejected'],
                    default => [$selectedStage],
                };

                $query->where(function ($q) use ($stageAliases) {
                    $q->whereIn('pipeline_stage', $stageAliases)
                      ->orWhereIn('stage', $stageAliases);
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_filter')) {
            if ($request->date_filter === 'created_date') {
                $query->latest('created_at');
            } elseif ($request->date_filter === 'updated_date') {
                $query->latest('updated_at');
            } elseif ($request->date_filter === 'today') {
                $query->whereDate('created_at', Carbon::today())->latest('created_at');
            } elseif ($request->date_filter === 'this_week') {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->latest('created_at');
            } elseif ($request->date_filter === 'this_month') {
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->latest('created_at');
            } elseif ($request->date_filter === 'this_year') {
                $query->whereYear('created_at', Carbon::now()->year)->latest('created_at');
            } else {
                $query->latest('created_at');
            }
        } else {
            $query->latest('created_at');
        }

        /*
        |--------------------------------------------------------------------------
        | Get Deals
        |--------------------------------------------------------------------------
        */

        $deals = $query->get();

        $accounts = Account::query()
            ->with(['company', 'individualContact'])
            ->where('status', 'Active')
            ->orderBy('account_name')
            ->get();

        $companies = Company::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('companies', 'status'), fn($q) => $q->where('status', 'Active'))
            ->orderBy('company_name')
            ->get();

        $contacts = Contact::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'status'), fn($q) => $q->where('status', 'Active'))
            ->orderBy('first_name')
            ->get();

        return view('deals.index', [
            'deals' => $deals,
            'stages' => $stages,
            'accounts' => $accounts,
            'companies' => $companies,
            'contacts' => $contacts,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Deal Page
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        app(\App\Services\DealCatalogSelection::class)->authorize($request->user());
        if ($request->user() && ($request->user()->isClient() || $request->user()->isDisabled())) {
            abort(403, 'Unauthorized to create or edit deals.');
        }

        $deal = $request->filled('deal')
            ? Deal::with([
                'account.company.primaryContact',
                'account.individualContact',
                'company.primaryContact',
                'company.contacts',
                'contact.companies',
            ])->findOrFail($request->integer('deal'))
            : null;

        if ($deal) {
            // 1. Automatically resolve Account if not already explicitly set
            if (empty($deal->account_id)) {
                if (!empty($deal->company_id)) {
                    $deal->account_id = Account::where('company_id', $deal->company_id)->value('id');
                }
                if (empty($deal->account_id) && !empty($deal->contact_id)) {
                    $deal->account_id = Account::where('individual_contact_id', $deal->contact_id)->value('id');
                }
                if (empty($deal->account_id) && !empty($deal->company_name ?: $deal->company)) {
                    $cName = $deal->company_name ?: $deal->company;
                    $comp = Company::where('company_name', $cName)->first();
                    if ($comp) {
                        $deal->company_id = $deal->company_id ?: $comp->id;
                        $deal->account_id = Account::where('company_id', $comp->id)->value('id');
                    }
                }
            }

            // 2. Automatically resolve Company if missing but Account has Company
            if (empty($deal->company_id) && !empty($deal->account_id)) {
                $acc = $deal->account ?: Account::find($deal->account_id);
                if ($acc && $acc->company_id) {
                    $deal->company_id = $acc->company_id;
                }
            }
            if (empty($deal->company_id) && !empty($deal->company_name ?: $deal->company)) {
                $cName = $deal->company_name ?: $deal->company;
                $comp = Company::where('company_name', $cName)->first();
                if ($comp) {
                    $deal->company_id = $comp->id;
                }
            }

            // 3. Automatically resolve Contact if missing but Account/Company is present
            if (empty($deal->contact_id) && !empty($deal->account_id)) {
                $acc = $deal->account ?: Account::with(['company', 'individualContact'])->find($deal->account_id);
                if ($acc) {
                    if ($acc->individual_contact_id) {
                        $deal->contact_id = $acc->individual_contact_id;
                    } elseif ($acc->company && $acc->company->primary_contact_id) {
                        $deal->contact_id = $acc->company->primary_contact_id;
                    }
                }
            }
            if (empty($deal->contact_id) && !empty($deal->company_id)) {
                $comp = $deal->company ?: Company::find($deal->company_id);
                if ($comp && $comp->primary_contact_id) {
                    $deal->contact_id = $comp->primary_contact_id;
                }
            }

            // 4. Automatically resolve customer_type if missing
            if (empty($deal->customer_type)) {
                if (!empty($deal->account_id)) {
                    $acc = $deal->account ?: Account::find($deal->account_id);
                    if ($acc) {
                        $type = strtolower((string) $acc->account_type);
                        if ($type === 'individual' || !empty($acc->individual_contact_id)) {
                            $deal->customer_type = 'Individual';
                        } else {
                            $deal->customer_type = 'Business';
                        }
                    }
                } elseif (!empty($deal->company_id) || !empty($deal->company) || !empty($deal->company_name)) {
                    $deal->customer_type = 'Business';
                } else {
                    $deal->customer_type = 'Individual';
                }
            }

        }

        $owners = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        $companies = Company::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('companies', 'status'), fn($q) => $q->where('status', 'Active'))
            ->orderBy('company_name')
            ->get();

        $contacts = Contact::query()
            ->with(['companies'])
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'status'), fn($q) => $q->where('status', 'Active'))
            ->orderBy('first_name')
            ->get();

        $accounts = Account::query()
            ->with(['company.primaryContact', 'individualContact'])
            ->where('status', 'Active')
            ->orderBy('account_name')
            ->get();

        $clients = Deal::query()
            ->where(function ($query) {
                $query->whereNotNull('primary_contact_name')
                    ->orWhereNotNull('company')
                    ->orWhereNotNull('email')
                    ->orWhereNotNull('mobile_number');
            })
            ->latest()
            ->get([
                'id',
                'primary_contact_name',
                'company',
                'email',
                'mobile_number'
            ]);

        $employees = User::query()
            ->with([
                'employeeProfile'
            ])
            ->orderBy('name')
            ->get();

        $catalogChoices = app(\App\Services\DealCatalogSelection::class)->choices($deal);
        $regularDealServiceMap = [];
        $unassignedDealServices = [];
        foreach ($catalogChoices['services'] as $service) {
            if ($service['service_area'] === []) { $unassignedDealServices[] = $service; }
            foreach ($service['service_area'] as $area) { $regularDealServiceMap[$area][] = $service; }
        }
        ksort($regularDealServiceMap);
        $regularServicePriceMap = $regularDealServiceMap;
        $serviceAreaOptions = array_values(array_unique(array_merge(array_keys($regularDealServiceMap),
            app(\App\Services\DealCatalogSelection::class)->areas($deal?->service_areas ?: $deal?->service_area))));
        $dealProductCatalog = $catalogChoices['products'];
        $legacyDealServices = $catalogChoices['legacy_services'];
        $legacyDealProducts = $catalogChoices['legacy_products'];

        return view(
            'deals.create',
            compact('deal', 'owners', 'employees', 'clients', 'accounts', 'companies', 'contacts', 'regularDealServiceMap', 'regularServicePriceMap', 'serviceAreaOptions', 'dealProductCatalog', 'unassignedDealServices', 'legacyDealServices', 'legacyDealProducts')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Quick Purchase for Existing Clients Page
    |--------------------------------------------------------------------------
    */

    public function quickPurchase(Request $request)
    {
        $selectedAccountId = $request->input('account_id');
        if (!$selectedAccountId && $request->filled('deal')) {
            $prevDeal = Deal::find($request->deal);
            if ($prevDeal) {
                $selectedAccountId = $prevDeal->account_id;
            }
        }

        $selectedContactId = $request->input('contact_id');

        $accounts = Account::query()
            ->with([
                'company.primaryContact',
                'individualContact',
                'deals' => function ($q) {
                    $q->latest()->limit(5);
                }
            ])
            ->where('status', 'Active')
            ->orderBy('account_name')
            ->get();

        $companies = Company::query()
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('companies', 'status'), fn($q) => $q->where('status', 'Active'))
            ->orderBy('company_name')
            ->get();

        $contacts = Contact::query()
            ->with(['companies'])
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'status'), fn($q) => $q->where('status', 'Active'))
            ->orderBy('first_name')
            ->get();

        $owners = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $employees = User::query()
            ->with([
                'employeeProfile'
            ])
            ->orderBy('name')
            ->get();

        return view(
            'deals.quick-purchase',
            compact('accounts', 'companies', 'contacts', 'owners', 'employees', 'selectedAccountId', 'selectedContactId')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Search Autocomplete (Typeahead) - Customer / Client Name Prefix Search
    |--------------------------------------------------------------------------
    */
    public function autocomplete(Request $request)
    {
        $search = trim($request->query('q', ''));
        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $searchLower = mb_strtolower($search);
        $tokens = preg_split('/\s+/', $searchLower, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            return response()->json([]);
        }

        $query = Deal::with('account')
            ->where(function ($q) use ($search, $tokens) {
                $q->where('deal_code', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('deal_title', 'like', "%{$search}%")
                  ->orWhere('deal_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('primary_contact_name', 'like', "%{$search}%")
                  ->orWhere('client_search', 'like', "%{$search}%")
                  ->orWhereHas('account', function ($accQ) use ($search) {
                      $accQ->where('account_name', 'like', "%{$search}%");
                  });

                foreach ($tokens as $tok) {
                    $q->orWhere('deal_code', 'like', "%{$tok}%")
                      ->orWhere('deal_title', 'like', "%{$tok}%")
                      ->orWhere('first_name', 'like', "%{$tok}%")
                      ->orWhere('last_name', 'like', "%{$tok}%")
                      ->orWhere('company_name', 'like', "%{$tok}%");
                }
            });

        $candidates = $query->latest('created_at')->limit(50)->get();

        $scoredDeals = [];

        foreach ($candidates as $deal) {
            // Primary resolved Customer / Client Name
            $primaryCompany = trim((string)($deal->company_name ?: $deal->company));
            $contactName = trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? ''));
            if (!$contactName) {
                $contactName = trim((string)($deal->primary_contact_name ?: ($deal->client_search ?: '')));
            }

            $mainClientName = $primaryCompany ?: ($contactName ?: ($deal->account?->account_name ?: 'Unnamed Client'));

            // Candidate targets for code, deal, and client matching
            $searchableTargets = array_unique(array_filter([
                $deal->deal_code,
                $deal->deal_title,
                $deal->deal_name,
                $mainClientName,
                $primaryCompany,
                $contactName,
                $deal->account?->account_name,
            ]));

            $matched = false;
            $dealScore = 999999;

            foreach ($searchableTargets as $targetText) {
                $targetLower = mb_strtolower(trim((string)$targetText));
                if ($targetLower === '') continue;

                // 1. Full search string prefix match (highest priority)
                if (str_starts_with($targetLower, $searchLower)) {
                    $score = strlen($targetLower) - strlen($searchLower);
                    if ($score < $dealScore) {
                        $dealScore = $score;
                        $matched = true;
                    }
                } elseif (str_contains($targetLower, $searchLower)) {
                    // Full substring match
                    $score = 50 + (strlen($targetLower) - strlen($searchLower));
                    if ($score < $dealScore) {
                        $dealScore = $score;
                        $matched = true;
                    }
                } else {
                    // Multi-token match
                    $allTokensFound = true;
                    foreach ($tokens as $tok) {
                        if (!str_contains($targetLower, $tok)) {
                            $allTokensFound = false;
                            break;
                        }
                    }
                    if ($allTokensFound) {
                        $score = 100 + strlen($targetLower);
                        if ($score < $dealScore) {
                            $dealScore = $score;
                            $matched = true;
                        }
                    }
                }
            }

            if ($matched) {
                $scoredDeals[] = [
                    'deal' => $deal,
                    'client_name' => $mainClientName,
                    'score' => $dealScore,
                ];
            }
        }

        // Sort by Client Name Match Score (Exact Prefix > Longer Prefix)
        usort($scoredDeals, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $a['score'] <=> $b['score'];
            }
            return ($b['deal']->created_at?->timestamp ?? 0) <=> ($a['deal']->created_at?->timestamp ?? 0);
        });

        $rankedDeals = collect($scoredDeals)->slice(0, 8);

        $stageColors = [
            'Inquiry'       => '#2458d7',
            'Qualification' => '#5d43d7',
            'Consultation'  => '#0d8797',
            'Proposal'      => '#d87b10',
            'Negotiation'   => '#dd5b21',
            'Payment'       => '#c43d71',
            'Activation'    => '#23834b',
            'Closed Won'    => '#16805b',
            'Closed Lost'   => '#64748b',
        ];

        $results = $rankedDeals->map(function ($item) use ($stageColors) {
            $deal = $item['deal'];
            $client = $item['client_name'];
            $stage = $deal->stage ?? $deal->pipeline_stage ?? 'Inquiry';

            return [
                'id' => $deal->id,
                'deal_code' => $deal->deal_code ?: ('DEAL-' . $deal->id),
                'deal_title' => $deal->deal_title ?: 'Untitled Deal',
                'client_name' => $client,
                'stage' => $stage,
                'stage_color' => $stageColors[$stage] ?? '#2458d7',
                'url' => route('deals.show', $deal->id),
            ];
        });

        return response()->json($results->values());
    }


    /*
    |--------------------------------------------------------------------------
    | Store Inquiry / Quick Purchase
    |--------------------------------------------------------------------------
    */
    public function storeQuickPurchase(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'customer_type' => 'nullable|in:Business,Individual',
            'contact_id' => 'nullable|exists:contacts,id',
            'service_area' => 'nullable',
            'services' => 'nullable',
            'service' => 'nullable|string|max:255',
            'scope_of_work' => 'nullable|string',
            'owner_name' => 'nullable|string|max:255',
            'estimated_professional_fee' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'expected_close' => 'nullable|date',
        ], [
            'account_id.required' => 'Please select an account.',
        ]);

        $account = Account::with(['company', 'individualContact'])->findOrFail($validated['account_id']);
        $company = $account->company;
        $contact = $account->individualContact;

        if ($request->filled('contact_id')) {
            $specificContact = Contact::find($request->input('contact_id'));
            if ($specificContact) {
                $contact = $specificContact;
            }
        } elseif (!$contact && $company) {
            $contact = $company->contacts()->where('status', 'Active')->first();
        }

        // Prevent duplicate deal creation (rapid re-submission or identical deal within 60s)
        $recentDuplicate = Deal::where('account_id', $account->id)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            $returnTo = $request->input(
                'return_to',
                route('deals.show', ['id' => $recentDuplicate->id])
            );
            return redirect()->to($returnTo)->with(
                'info',
                'An inquiry for this client was just created. Prevented duplicate creation.'
            );
        }

        $deal = new Deal();
        $deal->account_id = $account->id;
        $deal->company_id = $company?->id;
        $deal->contact_id = $contact?->id;
        $deal->customer_type = $request->input('customer_type', $account->account_type ?: ($company ? 'Business' : 'Individual'));

        // Handle service areas and services (arrays or strings)
        $rawServiceAreas = $request->input('service_area', []);
        $serviceAreas = is_array($rawServiceAreas) ? $rawServiceAreas : (array) $rawServiceAreas;
        
        $rawServices = $request->input('services', []);
        $services = is_array($rawServices) ? $rawServices : ($request->filled('service') ? [$request->input('service')] : (array) $rawServices);
        
        // Service Name & Deal Title / Deal Name
        $serviceName = !empty($services) ? implode(', ', $services) : ($request->input('service') ?: 'Consulting Service');
        $dealTitle = $serviceName . ' — ' . $account->account_name;
        $deal->deal_name = $dealTitle;
        $deal->deal_title = $dealTitle;

        // Owner & Creator
        $deal->owner_name = $request->input('owner_name') ?: (auth()->user()?->name ?: null);
        $deal->created_by = auth()->user()?->name ?: ($deal->owner_name ?: 'System');

        // Contact Information - populated directly from selected verified record and form input
        $deal->salutation = $contact?->salutation;
        $deal->sex = $contact?->sex;
        $deal->first_name = $request->input('first_name', $contact?->first_name ?: $account->account_name);
        $deal->middle_name = $contact?->middle_name;
        $deal->middle_initial = $contact?->middle_name;
        $deal->last_name = $request->input('last_name', $contact?->last_name ?: '');
        $deal->name_extension = $contact?->name_extension;
        $deal->date_of_birth = $contact?->date_of_birth;
        $contactPhone = $contact?->phone ?: ($contact?->phone_number ?: ($contact?->mobile_number ?? null));
        $deal->mobile = $request->input('mobile_number') ?: ($request->input('mobile') ?: $contactPhone);
        $deal->mobile_number = $deal->mobile;
        $deal->address = $request->input('address', $contact?->contact_address ?: ($contact?->company_address ?: $contact?->address));
        $deal->position = $request->input('position', $contact?->position ?: ($contact?->job_title ?? null));
        $deal->qualification_result = null;

        if ($company) {
            $deal->company = $company->company_name;
            $deal->company_name = $company->company_name;
            $deal->company_address = $company->address;
        }

        $deal->primary_contact_name = trim(collect([
            $deal->salutation,
            $deal->first_name,
            $deal->middle_name,
            $deal->last_name,
            $deal->name_extension,
        ])->filter()->implode(' '));

        // Service Details
        $deal->service_area = is_array($serviceAreas) ? implode(', ', $serviceAreas) : $serviceAreas;
        $deal->service_areas = $serviceAreas;
        $deal->services = is_array($services) ? implode(', ', $services) : $services;
        $deal->services_products = $this->buildSavedDealLineItems($services);
        $deal->engagement_type = $request->input('engagement_type') ?: null;
        $deal->scope_of_work = $request->input('scope_of_work');

        // Commercials (optional)
        $baseFee = (float) ($request->input('estimated_professional_fee') ?? $request->input('amount') ?? 0);
        $discount = (float) ($request->input('discount') ?? 0);
        $totalVal = max(0, $baseFee - $discount);

        $deal->estimated_professional_fee = $baseFee > 0 ? $baseFee : null;
        $deal->est_professional_fee = $baseFee > 0 ? $baseFee : null;
        $deal->total_service_fee = $baseFee > 0 ? $baseFee : null;
        $deal->deal_discount = $discount > 0 ? $discount : null;
        $deal->discount = $discount > 0 ? $discount : null;
        $deal->amount = $totalVal > 0 ? $totalVal : null;
        $deal->total_estimated_engagement_value = $totalVal > 0 ? $totalVal : null;
        $deal->payment_terms = $request->input('payment_terms');

        // Stage assignment: Pipeline Stage is automatically "Inquiry"
        $deal->pipeline_stage = 'Inquiry';
        $deal->inquiry_source = 'Add Inquiry';
        $deal->inquiry_date = now()->toDateString();
        $deal->inquiry_details = $request->input('scope_of_work') ?: $serviceName;
        $deal->expected_close = $request->filled('expected_close') ? $request->input('expected_close') : null;

        // User Assignments & Responsibilities
        if ($request->filled('assigned_user_id')) {
            $deal->assigned_user_id = $request->input('assigned_user_id');
        }
        if ($request->filled('responsible_user_id')) {
            $deal->responsible_user_id = $request->input('responsible_user_id');
        }
        if ($request->filled('reviewer_id')) {
            $deal->reviewer_id = $request->input('reviewer_id');
        }
        if ($request->filled('approver_id')) {
            $deal->approver_id = $request->input('approver_id');
        }
        if ($request->has('assigned_user_ids')) {
            $deal->assigned_user_ids = (array) $request->input('assigned_user_ids');
        }
        $deal->assigned_by = auth()->id();
        $deal->assigned_at = now();

        $deal->save();

        // Generate Standard Deal Code: CONDEAL-YYYY-### (Collision Safe)
        if (!Deal::hasValidDealCode($deal->deal_code)) {
            $deal->deal_code = Deal::generateNextDealCode(null, $deal->id);
            $deal->save();
        }

        // Dispatch Targeted Notifications to Assigned User(s) ONLY
        $recipients = array_filter([
            $deal->assigned_user_id,
            $deal->responsible_user_id,
            $deal->reviewer_id,
            $deal->approver_id,
            ...((array) ($deal->assigned_user_ids ?? [])),
            $deal->assigned_consultant,
            $deal->assigned_associate,
        ]);
        if (!empty($recipients)) {
            \App\Services\TargetedNotificationService::notifyAssignedUsers(
                $recipients,
                "New Deal Assignment: {$deal->deal_code}",
                "You have been assigned responsibility for Deal {$deal->deal_code} ({$deal->deal_title}).",
                route('deals.show', ['id' => $deal->id]),
                'Deals',
                'fa-briefcase',
                'Open Deal',
                auth()->id()
            );
        }

        // Create Primary Deal Contact Record
        if ($deal->first_name || $deal->last_name || $deal->email) {
            $deal->dealContacts()->create([
                'contact_type' => 'Primary',
                'salutation' => $deal->salutation,
                'first_name' => $deal->first_name ?: $account->account_name,
                'middle_name' => $deal->middle_initial,
                'last_name' => $deal->last_name ?: '',
                'name_extension' => $deal->name_extension,
                'email' => $deal->email,
                'mobile_number' => $deal->mobile_number,
                'address' => $deal->address,
                'position' => $deal->position,
                'is_primary' => true,
            ]);
        }

        // Audit Trail History
        app(DealHistoryService::class)->logActivity(
            $deal,
            DealHistory::TYPE_DEAL_CREATED,
            "Inquiry created for client \"{$account->account_name}\" ({$account->account_code}). Service: {$serviceName}.",
            [
                'account_id' => $account->id,
                'client_code' => $account->account_code,
                'service' => $serviceName,
                'amount' => $totalVal,
                'pipeline_stage' => 'Inquiry',
            ]
        );

        $returnTo = $request->input(
            'return_to',
            route('deals.show', ['id' => $deal->id])
        );

        return redirect()->to($returnTo)->with(
            'success',
            'Inquiry created successfully. Deal initialized at Inquiry stage.'
        );
    }


        /*
    |--------------------------------------------------------------------------
    | Quick Store Contact (AJAX)
    |--------------------------------------------------------------------------
    */

    public function quickStoreContact(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'salutation' => 'nullable|string|max:50',
            'middle_name' => 'nullable|string|max:255',
            'name_extension' => 'nullable|string|max:50',
            'sex' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'mobile_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'company_id' => 'nullable|exists:companies,id',
            'position' => 'nullable|string|max:255',
            'contact_type' => 'nullable|string|in:Business,Individual',
        ]);

        $contactData = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'salutation' => $validated['salutation'] ?? null,
            'name_extension' => $validated['name_extension'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['mobile_number'] ?? null,
            'contact_address' => $validated['address'] ?? null,
            'position' => $validated['position'] ?? null,
            'customer_type' => $validated['contact_type'] ?? ($request->filled('company_id') ? 'Business' : 'Individual'),
        ];

        if (Schema::hasColumn('contacts', 'contact_code')) {
            $count = Contact::count() + 1;
            $contactCode = 'CON-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            while (Contact::where('contact_code', $contactCode)->exists()) {
                $count++;
                $contactCode = 'CON-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            $contactData['contact_code'] = $contactCode;
        }

        if (Schema::hasColumn('contacts', 'mobile_number')) {
            $contactData['mobile_number'] = $validated['mobile_number'] ?? null;
        }

        if (Schema::hasColumn('contacts', 'address')) {
            $contactData['address'] = $validated['address'] ?? null;
        }

        if (Schema::hasColumn('contacts', 'company_id') && !empty($validated['company_id'])) {
            $contactData['company_id'] = $validated['company_id'];
        }

        if (!empty($validated['company_id'])) {
            $comp = Company::find($validated['company_id']);
            if ($comp) {
                $contactData['company_name'] = $comp->company_name;
            }
        }

        $contact = Contact::create($contactData);

        if (!empty($validated['company_id']) && isset($comp)) {
            if (Schema::hasColumn('companies', 'primary_contact_id') && empty($comp->primary_contact_id)) {
                $comp->primary_contact_id = $contact->id;
                $comp->save();
            }
        }

        return response()->json([
            'success' => true,
            'contact' => [
                'id' => $contact->id,
                'contact_code' => $contact->contact_code ?? ('CON-' . $contact->id),
                'contact_type' => $contact->customer_type ?? 'Individual',
                'salutation' => $contact->salutation,
                'first_name' => $contact->first_name,
                'middle_initial' => $contact->middle_name ?? $contact->middle_initial ?? '',
                'last_name' => $contact->last_name,
                'name_extension' => $contact->name_extension,
                'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                'sex' => $contact->sex,
                'email' => $contact->email,
                'mobile_number' => $contact->phone ?? $contact->mobile_number ?? '',
                'address' => $contact->contact_address ?? $contact->address ?? '',
                'company_id' => $contact->company_id ?? null,
                'company_name' => $contact->company_name ?? ($comp->company_name ?? null),
                'position' => $contact->position,
                'status' => $contact->client_status ?? $contact->status ?? 'Active',
            ],
            'message' => 'Contact added successfully.'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Quick Store Account (AJAX)
    |--------------------------------------------------------------------------
    */
    public function quickStoreAccount(Request $request)
    {
        $accountType = $request->input('account_type', 'Business');

        if ($accountType === 'Business') {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'industry' => 'nullable|string|max:255',
                'company_email' => 'nullable|email|max:255',
                'company_phone' => 'nullable|string|max:50',
                'company_address' => 'nullable|string',
                // Optional Primary Contact info
                'salutation' => 'nullable|string|max:50',
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'name_extension' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:255',
                'contact_mobile' => 'nullable|string|max:50',
                'contact_position' => 'nullable|string|max:255',
            ]);

            // Find or create Company
            $company = Company::where('company_name', $validated['company_name'])->first();
            if (!$company) {
                $compData = [
                    'company_name' => $validated['company_name'],
                    'email' => $validated['company_email'] ?? null,
                    'phone' => $validated['company_phone'] ?? null,
                    'address' => $validated['company_address'] ?? null,
                ];

                if (Schema::hasColumn('companies', 'company_code')) {
                    $compCount = Company::count() + 1;
                    $companyCode = 'COM-' . str_pad($compCount, 4, '0', STR_PAD_LEFT);
                    while (Company::where('company_code', $companyCode)->exists()) {
                        $compCount++;
                        $companyCode = 'COM-' . str_pad($compCount, 4, '0', STR_PAD_LEFT);
                    }
                    $compData['company_code'] = $companyCode;
                }

                if (Schema::hasColumn('companies', 'industry')) {
                    $compData['industry'] = $validated['industry'] ?? null;
                }

                $company = Company::create($compData);
            }

            // Create Contact if provided
            $contact = null;
            if (!empty($validated['first_name']) || !empty($validated['last_name'])) {
                $contactData = [
                    'salutation' => $validated['salutation'] ?? null,
                    'first_name' => $validated['first_name'] ?: $company->company_name,
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => $validated['last_name'] ?: '',
                    'name_extension' => $validated['name_extension'] ?? null,
                    'email' => $validated['contact_email'] ?? null,
                    'phone' => $validated['contact_mobile'] ?? null,
                    'position' => $validated['contact_position'] ?? null,
                    'company_name' => $company->company_name,
                    'customer_type' => 'Business',
                ];

                if (Schema::hasColumn('contacts', 'company_id')) {
                    $contactData['company_id'] = $company->id;
                }
                if (Schema::hasColumn('contacts', 'contact_code')) {
                    $contactCount = Contact::count() + 1;
                    $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
                    while (Contact::where('contact_code', $contactCode)->exists()) {
                        $contactCount++;
                        $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
                    }
                    $contactData['contact_code'] = $contactCode;
                }

                $contact = Contact::create($contactData);
            }

            // Generate Account Code: ACC-BUS-YYYY-###
            $year = now()->format('Y');
            $busCount = Account::where('account_type', 'Business')->count() + 1;
            $accountCode = 'ACC-BUS-' . $year . '-' . str_pad($busCount, 3, '0', STR_PAD_LEFT);
            while (Account::where('account_code', $accountCode)->exists()) {
                $busCount++;
                $accountCode = 'ACC-BUS-' . $year . '-' . str_pad($busCount, 3, '0', STR_PAD_LEFT);
            }

            $account = Account::create([
                'account_code' => $accountCode,
                'account_type' => 'Business',
                'account_name' => $company->company_name,
                'company_id' => $company->id,
                'contact_id' => $contact?->id,
                'email' => $company->email ?: $contact?->email,
                'phone' => $company->phone ?: $contact?->phone,
                'address' => $company->address,
                'status' => 'Active',
            ]);

            return response()->json([
                'success' => true,
                'account' => [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_type' => $account->account_type,
                    'account_name' => $account->account_name,
                    'company_id' => $account->company_id,
                    'contact_id' => $account->contact_id,
                    'individual_contact_id' => null,
                    'status' => $account->status,
                ],
                'company' => [
                    'id' => $company->id,
                    'company_code' => $company->company_code ?? ('COM-' . $company->id),
                    'company_name' => $company->company_name,
                    'industry' => $company->industry ?? null,
                    'address' => $company->address,
                    'email' => $company->email,
                    'phone' => $company->phone,
                ],
                'contact' => $contact ? [
                    'id' => $contact->id,
                    'contact_code' => $contact->contact_code ?? ('CON-' . $contact->id),
                    'contact_type' => $contact->customer_type ?? 'Business',
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_initial' => $contact->middle_name ?? '',
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'email' => $contact->email,
                    'mobile_number' => $contact->phone ?? '',
                    'address' => $contact->contact_address ?? '',
                    'company_id' => $contact->company_id ?? $company->id,
                    'position' => $contact->position,
                ] : null,
                'message' => 'Account created successfully.'
            ]);
        } else {
            // Individual Account
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'salutation' => 'nullable|string|max:50',
                'middle_name' => 'nullable|string|max:255',
                'name_extension' => 'nullable|string|max:50',
                'contact_email' => 'nullable|email|max:255',
                'contact_mobile' => 'nullable|string|max:50',
                'contact_address' => 'nullable|string',
            ]);

            $contactData = [
                'salutation' => $validated['salutation'] ?? null,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'name_extension' => $validated['name_extension'] ?? null,
                'email' => $validated['contact_email'] ?? null,
                'phone' => $validated['contact_mobile'] ?? null,
                'contact_address' => $validated['contact_address'] ?? null,
                'customer_type' => 'Individual',
            ];

            if (Schema::hasColumn('contacts', 'contact_code')) {
                $contactCount = Contact::count() + 1;
                $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
                while (Contact::where('contact_code', $contactCode)->exists()) {
                    $contactCount++;
                    $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
                }
                $contactData['contact_code'] = $contactCode;
            }

            $contact = Contact::create($contactData);

            $fullName = trim(collect([
                $contact->salutation,
                $contact->first_name,
                $contact->middle_name,
                $contact->last_name,
                $contact->name_extension,
            ])->filter()->implode(' '));

            $year = now()->format('Y');
            $indCount = Account::where('account_type', 'Individual')->count() + 1;
            $accountCode = 'ACC-IND-' . $year . '-' . str_pad($indCount, 3, '0', STR_PAD_LEFT);
            while (Account::where('account_code', $accountCode)->exists()) {
                $indCount++;
                $accountCode = 'ACC-IND-' . $year . '-' . str_pad($indCount, 3, '0', STR_PAD_LEFT);
            }

            $accountData = [
                'account_code' => $accountCode,
                'account_type' => 'Individual',
                'account_name' => $fullName ?: ($contact->first_name . ' ' . $contact->last_name),
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'address' => $contact->contact_address,
                'status' => 'Active',
            ];

            if (Schema::hasColumn('accounts', 'individual_contact_id')) {
                $accountData['individual_contact_id'] = $contact->id;
            }

            $account = Account::create($accountData);

            return response()->json([
                'success' => true,
                'account' => [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_type' => $account->account_type,
                    'account_name' => $account->account_name,
                    'company_id' => null,
                    'contact_id' => $account->contact_id,
                    'individual_contact_id' => $account->individual_contact_id ?? $contact->id,
                    'status' => $account->status,
                ],
                'company' => null,
                'contact' => [
                    'id' => $contact->id,
                    'contact_code' => $contact->contact_code ?? ('CON-' . $contact->id),
                    'contact_type' => $contact->customer_type ?? 'Individual',
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_initial' => $contact->middle_name ?? '',
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'email' => $contact->email,
                    'mobile_number' => $contact->phone ?? '',
                    'address' => $contact->contact_address ?? '',
                    'company_id' => null,
                    'position' => $contact->position,
                ],
                'message' => 'Account created successfully.'
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Deal
    |--------------------------------------------------------------------------
    */

    public function catalogConfiguration(Request $request): JsonResponse
    {
        $selection = app(\App\Services\DealCatalogSelection::class);
        $selection->authorize($request->user());
        $validated = $request->validate([
            'deal_id' => ['nullable', 'integer', 'exists:deals,id'],
            'customer_type' => ['required', 'in:Business,Individual'],
        ]);
        $deal = ! empty($validated['deal_id']) ? Deal::findOrFail($validated['deal_id']) : null;
        return response()->json($selection->configuration($selection->fromRequest($request, $deal), $validated['customer_type']));
    }

    public function store(Request $request)
    {
        app(\App\Services\DealCatalogSelection::class)->authorize($request->user());
        return DB::transaction(fn () => $this->persistNewDeal($request));
    }

    private function persistNewDeal(Request $request)
    {
        $catalogSelections = app(\App\Services\DealCatalogSelection::class)->fromRequest($request);
        /*
        |--------------------------------------------------------------------------
        | Validate Required Deal Information
        |--------------------------------------------------------------------------
        */

        $request->validate([
                'account_id' => [
                    'required',
                    'exists:accounts,id',
                ],

                'customer_type' => [
                    'required',
                    'in:Business,Individual',
                ],

                'pipeline_stage' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'deal_title' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Deals
        |--------------------------------------------------------------------------
        */

        // Check for rapid re-submission (within 60 seconds for same account)
        $recentDuplicate = Deal::where('account_id', $request->account_id)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            $returnTo = $request->input(
                'return_to',
                route('deals.show', ['id' => $recentDuplicate->id])
            );
            return redirect()->to($returnTo)->with(
                'info',
                'A deal for this client was just created. Prevented duplicate creation.'
            );
        }

        // Check for identical services within 5 minutes for the same account
        $checkServices = (array) ($request->services ?? []);
        if ($request->filled('other_service')) {
            $checkServices[] = $request->other_service;
        }
        $checkProducts = (array) ($request->products ?? []);
        if ($request->filled('other_product')) {
            $checkProducts[] = $request->other_product;
        }
        $incomingServices = array_values(array_filter(
            array_unique(array_merge($checkServices, $checkProducts)),
            fn ($item) => filled($item) && $item !== 'Others'
        ));

        if (!empty($incomingServices)) {
            $sameServiceDuplicate = Deal::where('account_id', $request->account_id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->latest()
                ->get()
                ->first(function($existingDeal) use ($incomingServices) {
                    $existingServices = (array) ($existingDeal->services_products ?? []);
                    sort($existingServices);
                    $sortedIncoming = $incomingServices;
                    sort($sortedIncoming);
                    return $existingServices === $sortedIncoming;
                });

            if ($sameServiceDuplicate) {
                return redirect()->to(
                    $request->input('return_to', route('deals.show', ['id' => $sameServiceDuplicate->id]))
                )->with('info', 'A deal with identical services for this client was recently created. Prevented duplicate creation.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Deal
        |--------------------------------------------------------------------------
        */

        $deal = new Deal();

        $deal->account_id = $request->account_id;
        $deal->company_id = $request->company_id;
        $deal->contact_id = $request->contact_id;
        

        /*
        |--------------------------------------------------------------------------
        | Basic Deal Information
        |--------------------------------------------------------------------------
        */

        $deal->customer_type = $request->customer_type;
        $deal->pipeline_stage = $request->pipeline_stage;
        $deal->deal_title = $request->deal_title;

        $deal->amount = $request->amount;
        $deal->client_search = $request->client_search;

        $deal->owner_name =
            $request->owner_name
            ?: optional(auth()->user())->name;

        $deal->created_by =
            $request->created_by
            ?: optional(auth()->user())->name;

        /*
        |--------------------------------------------------------------------------
        | Contact Information
        |--------------------------------------------------------------------------
        */

        $deal->salutation = $request->salutation;
        $deal->sex = $request->sex;
        $deal->first_name = $request->first_name;
        $deal->middle_initial = $request->middle_initial;
        $deal->last_name = $request->last_name;
        $deal->name_extension = $request->name_extension;
        $deal->date_of_birth = $request->date_of_birth;
        $deal->email = $request->email;
        $deal->mobile_number = $request->mobile_number;
        $deal->address = $request->address;

        /*
        |--------------------------------------------------------------------------
        | Company Information
        |--------------------------------------------------------------------------
        */

        $deal->company = $request->company;
        $deal->company_name = $request->company;
        $deal->company_address = $request->company_address;
        $deal->position = $request->position;

        /*
        |--------------------------------------------------------------------------
        | Primary Contact Name
        |--------------------------------------------------------------------------
        */

        $deal->primary_contact_name = trim(
            collect([
                $request->first_name,
                $request->middle_initial,
                $request->last_name,
                $request->name_extension,
            ])
                ->filter(function ($value) {
                    return filled($value);
                })
                ->implode(' ')
        );

        /*
        |--------------------------------------------------------------------------
        | Services
        |--------------------------------------------------------------------------
        */

        $deal->service_areas =
            $request->service_area ?? [];

        $services = $catalogSelections['services'];
        $products = $catalogSelections['products'];

        $deal->services_products = $this->buildSavedDealLineItems($services, $products);
        $deal->services = json_encode(array_column($services, 'name'));
        $deal->products = json_encode(array_column($products, 'name'));

        $deal->scope_of_work =
            $request->scope_of_work;
        if (blank($deal->scope_of_work)) {
            $defaults = app(\App\Services\ProductCatalogConfiguration::class)->forDeal($deal);
            $deal->scope_of_work = \App\Services\ProductCatalogConfiguration::summarize($defaults, 'scope_of_work');
        }

        $deal->engagement_type =
            $request->engagement_type;

        /*
        |--------------------------------------------------------------------------
        | Requirements / Actions
        |--------------------------------------------------------------------------
        */

        $requirements = (array) ($request->requirements ?? []);
        if ($request->filled('other_requirement')) {
            $requirements[] = $request->other_requirement;
        }
        $deal->client_requirements = array_values(array_filter(
            array_unique($requirements),
            fn ($item) => filled($item) && $item !== 'Others'
        ));

        $actions = (array) ($request->required_actions ?? []);
        if ($request->filled('other_required_action')) {
            $actions[] = $request->other_required_action;
        }
        $deal->required_actions = array_values(array_filter(
            array_unique($actions),
            fn ($item) => filled($item) && $item !== 'Others'
        ));

        /*
        |--------------------------------------------------------------------------
        | Fees
        |--------------------------------------------------------------------------
        */

        $deal->est_professional_fee =
            $request->estimated_professional_fee;

        $deal->est_government_fee =
            $request->estimated_government_fees;

        $deal->est_service_support_fee =
            $request->estimated_service_support_fee;

        $deal->total_service_fee =
            $request->total_service_fee;

        $deal->total_product_fee =
            $request->total_product_fee;

        $deal->discount =
            $request->discount;

        $deal->total_estimated_engagement_value =
            $request->total_estimated_engagement_value;

        /*
        |--------------------------------------------------------------------------
        | Pricing / Other Fees
        |--------------------------------------------------------------------------
        */

        $deal->services_pricing_guide = [];
        $deal->products_pricing_guide = [];
        $deal->other_fees = [];

        if ($request->has('other_fee_description')) {

            $otherFees = [];

            foreach (
                $request->other_fee_description as $index => $description
            ) {

                $amount =
                    $request->other_fee_amount[$index]
                    ?? null;

                if (
                    blank($description) &&
                    blank($amount)
                ) {
                    continue;
                }

                $otherFees[] = [
                    'description' => $description,
                    'amount' => $amount ?? 0,
                ];
            }

            $deal->other_fees = $otherFees;
        }

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        $deal->payment_terms =
            $request->payment_terms;

        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */

        $deal->planned_start_date =
            $request->filled('planned_start_date') ? $request->planned_start_date : null;

        $deal->estimated_duration =
            $request->filled('estimated_duration') ? $request->estimated_duration : null;

        $deal->estimated_duration_days =
            $request->filled('estimated_duration') ? $request->estimated_duration : null;

        $deal->estimated_completion_date =
            $request->filled('estimated_completion_date') ? $request->estimated_completion_date : null;

        $deal->client_preferred_completion_date =
            $request->filled('client_preferred_completion_date') ? $request->client_preferred_completion_date : null;

        $deal->confirmed_delivery_date =
            $request->filled('confirmed_delivery_date') ? $request->confirmed_delivery_date : null;

        $deal->timeline_notes =
            $request->timeline_notes ?: null;

        /*
        |--------------------------------------------------------------------------
        | Complexity
        |--------------------------------------------------------------------------
        */

        $deal->service_complexity =
            $request->complexity;

        $support = (array) ($request->professional_support ?? []);
        if ($request->filled('other_professional_support')) {
            $support[] = $request->other_professional_support;
        }
        $deal->professional_support_required = array_values(array_filter(
            array_unique($support),
            fn ($item) => filled($item) && $item !== 'Others'
        ));

        $deal->complexity_notes =
            $request->complexity_notes;

        /*
        |--------------------------------------------------------------------------
        | Proposal
        |--------------------------------------------------------------------------
        */

        $deal->proposal_decision =
            $request->proposal_decision;

        /*
        |--------------------------------------------------------------------------
        | Assignment
        |--------------------------------------------------------------------------
        */

        $deal->assigned_consultant =
            $request->assigned_consultant;

        $deal->assigned_associate =
            $request->assigned_associate;

        $deal->service_department =
            $request->service_department;

        if ($request->filled('assigned_user_id')) {
            $deal->assigned_user_id = $request->assigned_user_id;
        }
        if ($request->filled('responsible_user_id')) {
            $deal->responsible_user_id = $request->responsible_user_id;
        }
        if ($request->filled('reviewer_id')) {
            $deal->reviewer_id = $request->reviewer_id;
        }
        if ($request->filled('approver_id')) {
            $deal->approver_id = $request->approver_id;
        }
        if ($request->has('assigned_user_ids')) {
            $deal->assigned_user_ids = (array) $request->assigned_user_ids;
        }
        $deal->assigned_by = auth()->id();
        $deal->assigned_at = now();

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        $deal->consultant_notes =
            $request->consultant_notes;

        $deal->associate_notes =
            $request->associate_notes;

        /*
        |--------------------------------------------------------------------------
        | Approval
        |--------------------------------------------------------------------------
        */

        $deal->prepared_by =
            $request->owner_name ?: optional(auth()->user())->name;

        $deal->reviewed_by =
            $request->reviewed_by;

        $deal->approval_name =
            $request->approval_name;

        $deal->approval_date =
            $request->approval_date;

        $deal->client_fullname_signature =
            $request->client_fullname_signature;

        /*
        |--------------------------------------------------------------------------
        | Referral / Team
        |--------------------------------------------------------------------------
        */

        $deal->referred_by =
            $request->referred_by;

        $deal->sales_marketing =
            $request->sales_marketing;

        $deal->lead_consultant =
            $request->lead_consultant;

        $deal->lead_associate =
            $request->lead_associate;

        $deal->finance =
            $request->finance;

        $deal->president =
            $request->president;

        /*
        |--------------------------------------------------------------------------
        | Dashboard Expected Close
        |--------------------------------------------------------------------------
        */

        $deal->expected_close =
            $request->filled('expected_close')
                ? $request->expected_close
                : null;

        /*
        |--------------------------------------------------------------------------
        | Inquiry Details
        |--------------------------------------------------------------------------
        */

        $deal->inquiry_date = now();
        $deal->inquiry_source = in_array($request->engagement_type, ['Product', 'Hybrid']) ? 'Product' : 'Service';
        $deal->inquiry_details = $deal->scope_of_work 
            ?: (filled($deal->client_requirements) ? (is_array($deal->client_requirements) ? implode(', ', $deal->client_requirements) : $deal->client_requirements)
                : (filled($deal->services_products) ? (is_array($deal->services_products) ? collect($deal->services_products)->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : $item)->filter()->implode(', ') : $deal->services_products)
                    : ($deal->consultant_notes ?: ('Initial Client Inquiry for ' . ($deal->company_name ?: ($deal->primary_contact_name ?: 'services'))))));

        $listValInit = function ($val) {
            if (is_array($val)) return collect($val)->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : $item)->filter()->implode(', ');
            if (is_string($val)) {
                $d = json_decode($val, true);
                if (is_array($d)) return collect($d)->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : $item)->filter()->implode(', ');
            }
            return (string)$val;
        };

        $inquirySubject = $deal->deal_title;
        if (blank($inquirySubject)) {
            $inquirySubject = filled($listValInit($deal->services_products))
                ? $listValInit($deal->services_products)
                : (filled($listValInit($deal->service_areas))
                    ? $listValInit($deal->service_areas)
                    : (filled($deal->company_name) ? ($deal->company_name . ' Service') : 'Initial Client Inquiry'));
        }

        $deal->inquiry_records = [
            [
                'id' => 1,
                'subject' => $inquirySubject,
                'type' => $deal->inquiry_source,
                'clientInquiry' => $deal->inquiry_details,
                'budget' => (string) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: '')),
                'targetDate' => $deal->expected_close ? Carbon::parse($deal->expected_close)->format('Y-m-d') : ($deal->estimated_completion_date ? Carbon::parse($deal->estimated_completion_date)->format('Y-m-d') : ''),
                'notes' => !empty($deal->client_requirements) ? ('Requirements: ' . $listValInit($deal->client_requirements)) : ($deal->consultant_notes ?: ''),
                'createdAt' => now()->format('M d, Y'),
                'createdBy' => $deal->owner_name ?: (optional(auth()->user())->name ?: 'System'),
            ]
        ];

        /*
        |--------------------------------------------------------------------------
        | Save Deal
        |--------------------------------------------------------------------------
        */

        $deal->save();

        /*
        |--------------------------------------------------------------------------
        | Save Primary Contact
        |--------------------------------------------------------------------------
        |
        | The Deal is saved first so that its ID exists.
        | The contact is then stored in deal_contacts and linked
        | to this Deal through deal_id.
        |
        */

        $deal->dealContacts()->create([
            'contact_type' => 'Primary',
            'salutation' => $request->salutation,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_initial,
            'last_name' => $request->last_name,
            'name_extension' => $request->name_extension,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'address' => $request->address,
            'position' => $request->position,
            'is_primary' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Generate Deal Code
        |--------------------------------------------------------------------------
        |
        | Example:
        | CONDEAL-2026-001
        | CONDEAL-2026-002
        |
        */

        if (!Deal::hasValidDealCode($deal->deal_code)) {
            $deal->deal_code = Deal::generateNextDealCode(null, $deal->id);
        }

        if (empty($deal->deal_title) || $deal->deal_title === 'CONDEAL-YYYY-###' || str_contains($deal->deal_title, 'YYYY-###')) {
            $deal->deal_title = $deal->deal_code;
        }

        $deal->save();

        // Dispatch Targeted Notifications to Assigned User(s) ONLY
        $recipients = array_filter([
            $deal->assigned_user_id,
            $deal->responsible_user_id,
            $deal->reviewer_id,
            $deal->approver_id,
            ...((array) ($deal->assigned_user_ids ?? [])),
            $deal->assigned_consultant,
            $deal->assigned_associate,
            $deal->reviewed_by,
        ]);
        if (!empty($recipients)) {
            \App\Services\TargetedNotificationService::notifyAssignedUsers(
                $recipients,
                "New Deal Assignment: {$deal->deal_code}",
                "You have been assigned responsibility for Deal {$deal->deal_code} ({$deal->deal_title}).",
                route('deals.show', ['id' => $deal->id]),
                'Deals',
                'fa-briefcase',
                'Open Deal',
                auth()->id()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Log Deal Creation History
        |--------------------------------------------------------------------------
        */
        app(DealHistoryService::class)->logDealCreated(
            $deal,
            auth()->id(),
            auth()->user()?->name ?? $deal->owner_name
        );

        /*
            |--------------------------------------------------------------------------
            | Create Primary Deal Contact
            |--------------------------------------------------------------------------
            */


        /*
        |--------------------------------------------------------------------------
        | Redirect To Newly Created Deal
        |--------------------------------------------------------------------------
        */

        return redirect()->to(
            $request->input(
                'return_to',
                route(
                    'deals.show',
                    ['id' => $deal->id]
                )
            )
        )->with(
            'success',
            'Deal successfully created.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | View Single Deal
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $dealId = $id instanceof Deal ? $id->id : $id;
        $deal = Deal::with([
            'dealContacts',
            'proposal',
            'company',
            'companyRecord',
            'companyRecord.primaryContact',
            'companyRecord.contacts',
            'contact',
            'primaryContact',
            'account',
            'account.company',
            'account.individualContact',
            'histories',
            'stageHistories',
            'project',
            'project.starts',
            'projects',
        ])->find($dealId);

        if (! $deal) {
            return redirect()->route('deals.index')->with('info', 'The requested deal record is no longer available.');
        }

        if ($deal->isStartCompleted() && ! in_array($deal->pipeline_stage, ['Closed Won', 'Closed Lost'], true)) {
            $previousStage = (string) ($deal->pipeline_stage ?: 'Inquiry');
            $pipelineNeedsAdvance = $deal->pipeline_stage !== 'Activation';
            if ($pipelineNeedsAdvance || $deal->stage !== 'Activation') {
                $deal->pipeline_stage = 'Activation';
                $deal->stage = 'Activation';
                $deal->save();
                if ($pipelineNeedsAdvance) {
                    app(DealHistoryService::class)->logStageChanged($deal, $previousStage, 'Activation', 'START completion verified from persisted assignments and downloadable Service Memo.');
                }
                $deal->refresh();
            }
        }

        $users = User::with([
            'employeeProfile'
        ])->orderBy('name')->get();

        $proposal = $deal->proposal ?: new DealProposal([
            'deal_id' => $deal->id,
            'proposal_version' => 1,
            'is_current' => true,
            'status' => 'Draft',
        ]);
        $proposalVersions = $deal->proposals()
            ->orderBy('proposal_version')
            ->get();
        $proposalItemResponseHistory = $proposalVersions->isNotEmpty()
            ? DealProposalItemResponse::query()
                ->whereIn('deal_proposal_id', $proposalVersions->pluck('id'))
                ->orderBy('responded_at')
                ->orderBy('id')
                ->get()
                ->groupBy('deal_proposal_id')
            : collect();
        $dealResolvedLineItems = app(DealProposalAggregationService::class)->resolveDealLineItems($deal);
        $clientKycData = app(ProjectProvisioner::class)->resolveDealStartKyc($deal);
        $clientKycOrganization = (string) ($clientKycData['organization_type'] ?? 'unknown');
        $clientKycGroup = match ($clientKycOrganization) {
            'sole_proprietorship' => 'sole',
            'individual' => 'individual',
            default => 'juridical',
        };
        $clientKycCatalog = collect($clientKycData[$clientKycGroup] ?? []);
        $clientKycAssignedLabels = $clientKycCatalog
            ->filter(fn ($item) => is_array($item) && (bool) ($item['required'] ?? false))
            ->pluck('label')
            ->all();
        $canConfigureKyc = auth()->user() !== null
            && method_exists(auth()->user(), 'hasPermission')
            && (auth()->user()->hasPermission('access_admin_dashboard') || auth()->user()->hasPermission('approve_townhall'));

        $historyService = app(DealHistoryService::class);
        $stageProgression = $historyService->getStageProgression($deal);
        $activityCounts = $historyService->getActivityCounts($deal);

        return view(
            'deals.show',
            compact('deal', 'users', 'proposal', 'proposalVersions', 'proposalItemResponseHistory', 'dealResolvedLineItems', 'stageProgression', 'activityCounts', 'clientKycOrganization', 'clientKycGroup', 'clientKycCatalog', 'clientKycAssignedLabels', 'canConfigureKyc')
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Update Deal
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        app(\App\Services\DealCatalogSelection::class)->authorize($request->user());
        return DB::transaction(fn () => $this->persistDealUpdate($request, $id));
    }

    private function persistDealUpdate(Request $request, $id)
    {
        if ($request->user() && ($request->user()->isClient() || $request->user()->isDisabled())) {
            abort(403, 'Unauthorized to edit this deal.');
        }

        $deal = Deal::query()->lockForUpdate()->findOrFail($id);
        $selection = app(\App\Services\DealCatalogSelection::class);
        $catalogSelections = $selection->hasInput($request) ? $selection->fromRequest($request, $deal) : null;

        $validated = $request->validate([
            'account_id' => ['nullable', 'exists:accounts,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'deal_title' => ['nullable', 'string', 'max:255'],
            'pipeline_stage' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', 'in:Business,Individual'],
            'company' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'position' => ['nullable', 'string', 'max:255'],
            'salutation' => ['nullable', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'name_extension' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'expected_close' => ['nullable', 'date'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'approver_id' => ['nullable', 'exists:users,id'],
            'assigned_user_ids' => ['nullable', 'array'],
            'estimated_professional_fee' => ['nullable', 'numeric', 'min:0'],
            'estimated_government_fees' => ['nullable', 'numeric', 'min:0'],
            'estimated_service_support_fee' => ['nullable', 'numeric', 'min:0'],
            'total_service_fee' => ['nullable', 'numeric', 'min:0'],
            'total_product_fee' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total_estimated_engagement_value' => ['nullable', 'numeric', 'min:0'],
            'scope_of_work' => ['nullable', 'string'],
            'engagement_type' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'planned_start_date' => ['nullable', 'date'],
            'estimated_duration' => ['nullable', 'numeric', 'min:0'],
            'estimated_completion_date' => ['nullable', 'date'],
            'client_preferred_completion_date' => ['nullable', 'date'],
            'confirmed_delivery_date' => ['nullable', 'date'],
            'timeline_notes' => ['nullable', 'string'],
            'complexity' => ['nullable', 'string', 'max:255'],
            'complexity_notes' => ['nullable', 'string'],
            'proposal_decision' => ['nullable', 'string', 'max:255'],
            'assigned_consultant' => ['nullable', 'string', 'max:255'],
            'assigned_associate' => ['nullable', 'string', 'max:255'],
            'service_department' => ['nullable', 'string', 'max:255'],
            'consultant_notes' => ['nullable', 'string'],
            'associate_notes' => ['nullable', 'string'],
            'reviewed_by' => ['nullable', 'string', 'max:255'],
            'approval_name' => ['nullable', 'string', 'max:255'],
            'approval_date' => ['nullable', 'date'],
            'client_fullname_signature' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'sales_marketing' => ['nullable', 'string', 'max:255'],
            'lead_consultant' => ['nullable', 'string', 'max:255'],
            'lead_associate' => ['nullable', 'string', 'max:255'],
            'finance' => ['nullable', 'string', 'max:255'],
            'president' => ['nullable', 'string', 'max:255'],
        ]);

        $original = $deal->getOriginal();
        $oldStage = $deal->pipeline_stage;

        if ($request->filled('deal_title')) {
            $deal->deal_title = $request->deal_title;
            $deal->deal_name = $request->deal_title;
        }

        if ($request->filled('pipeline_stage')) {
            $deal->pipeline_stage = $request->pipeline_stage;
            $deal->stage = $request->pipeline_stage;
        }

        if ($request->filled('customer_type')) {
            $deal->customer_type = $request->customer_type;
        }

        if ($request->has('account_id') && $request->filled('account_id')) {
            $deal->account_id = $request->account_id;
        }
        if ($request->has('company_id')) {
            $deal->company_id = $request->company_id;
        }
        if ($request->has('contact_id')) {
            $deal->contact_id = $request->contact_id;
        }

        if ($request->has('company')) {
            $deal->company = $request->company;
            $deal->company_name = $request->company;
        }
        if ($request->has('company_address')) {
            $deal->company_address = $request->company_address;
        }
        if ($request->has('position')) {
            $deal->position = $request->position;
        }

        if ($request->has('amount')) {
            $deal->amount = $request->amount;
        }

        if ($request->has('expected_close')) {
            $deal->expected_close = $request->filled('expected_close') ? $request->expected_close : null;
        }

        if ($request->filled('owner_name')) {
            $deal->owner_name = $request->owner_name;
        }

        // Contact details
        if ($request->has('salutation')) {
            $deal->salutation = $request->salutation;
        }
        if ($request->has('sex')) {
            $deal->sex = $request->sex;
        }
        if ($request->has('date_of_birth')) {
            $deal->date_of_birth = $request->date_of_birth;
        }
        if ($request->has('email')) {
            $deal->email = $request->email;
        }
        if ($request->has('mobile_number')) {
            $deal->mobile_number = $request->mobile_number;
            $deal->mobile = $request->mobile_number;
        }
        if ($request->has('address')) {
            $deal->address = $request->address;
        }

        if ($request->has('first_name') || $request->has('last_name')) {
            $deal->first_name = $request->first_name;
            $deal->middle_initial = $request->middle_initial;
            $deal->last_name = $request->last_name;
            $deal->name_extension = $request->name_extension;

            $fullName = trim(
                collect([
                    $request->first_name,
                    $request->middle_initial,
                    $request->last_name,
                    $request->name_extension,
                ])
                    ->filter(fn ($value) => filled($value))
                    ->implode(' ')
            );
            if (filled($fullName)) {
                $deal->primary_contact_name = $fullName;
            }
        }

        // Services & Products
        if ($request->has('service_area')) {
            $deal->service_areas = $request->service_area ?? [];
            $deal->service_area = is_array($request->service_area) ? implode(', ', $request->service_area) : $request->service_area;
        }

        if ($catalogSelections !== null) {
            $services = $catalogSelections['services'];
            $products = $catalogSelections['products'];
            $deal->services = json_encode(array_column($services, 'name'));
            $deal->products = json_encode(array_column($products, 'name'));
            $existingItems = is_array($deal->services_products) ? $deal->services_products : [];
            $deal->services_products = $selection->preserveExistingInstances(
                $this->buildSavedDealLineItems($services, $products, $existingItems), $existingItems
            );
        }

        if ($request->has('scope_of_work')) {
            $deal->scope_of_work = $request->scope_of_work;
        }

        if ($request->has('engagement_type')) {
            $deal->engagement_type = $request->engagement_type;
        }

        // Requirements & Actions
        if ($request->has('requirements') || $request->filled('other_requirement')) {
            $requirements = (array) ($request->requirements ?? []);
            if ($request->filled('other_requirement')) {
                $requirements[] = $request->other_requirement;
            }
            $deal->client_requirements = array_values(array_filter(
                array_unique($requirements),
                fn ($item) => filled($item) && $item !== 'Others'
            ));
        }

        if ($request->has('required_actions') || $request->filled('other_required_action')) {
            $actions = (array) ($request->required_actions ?? []);
            if ($request->filled('other_required_action')) {
                $actions[] = $request->other_required_action;
            }
            $deal->required_actions = array_values(array_filter(
                array_unique($actions),
                fn ($item) => filled($item) && $item !== 'Others'
            ));
        }

        // Fees
        if ($request->has('estimated_professional_fee')) {
            $deal->est_professional_fee = $request->estimated_professional_fee;
            $deal->estimated_professional_fee = $request->estimated_professional_fee;
        }
        if ($request->has('estimated_government_fees')) {
            $deal->est_government_fee = $request->estimated_government_fees;
            $deal->estimated_government_fees = $request->estimated_government_fees;
        }
        if ($request->has('estimated_service_support_fee')) {
            $deal->est_service_support_fee = $request->estimated_service_support_fee;
            $deal->estimated_service_support_fee = $request->estimated_service_support_fee;
        }
        if ($request->has('total_service_fee')) {
            $deal->total_service_fee = $request->total_service_fee;
        }
        if ($request->has('total_product_fee')) {
            $deal->total_product_fee = $request->total_product_fee;
        }
        if ($request->has('discount')) {
            $deal->discount = $request->discount;
            $deal->deal_discount = $request->discount;
        }
        if ($request->has('total_estimated_engagement_value')) {
            $deal->total_estimated_engagement_value = $request->total_estimated_engagement_value;
        }
        if ($request->has('other_fee_description')) {
            $otherFees = [];
            foreach ((array) $request->other_fee_description as $index => $description) {
                $feeAmount = $request->other_fee_amount[$index] ?? null;
                if (blank($description) && blank($feeAmount)) {
                    continue;
                }
                $otherFees[] = [
                    'description' => $description,
                    'amount' => $feeAmount ?? 0,
                ];
            }
            $deal->other_fees = $otherFees;
        }

        // Timeline
        if ($request->has('payment_terms')) {
            $deal->payment_terms = $request->payment_terms;
        }
        if ($request->has('planned_start_date')) {
            $deal->planned_start_date = $request->filled('planned_start_date') ? $request->planned_start_date : null;
        }
        if ($request->has('estimated_duration')) {
            $deal->estimated_duration = $request->filled('estimated_duration') ? $request->estimated_duration : null;
            $deal->estimated_duration_days = $request->filled('estimated_duration') ? $request->estimated_duration : null;
        }
        if ($request->has('estimated_completion_date')) {
            $deal->estimated_completion_date = $request->filled('estimated_completion_date') ? $request->estimated_completion_date : null;
        }
        if ($request->has('client_preferred_completion_date')) {
            $deal->client_preferred_completion_date = $request->filled('client_preferred_completion_date') ? $request->client_preferred_completion_date : null;
        }
        if ($request->has('confirmed_delivery_date')) {
            $deal->confirmed_delivery_date = $request->filled('confirmed_delivery_date') ? $request->confirmed_delivery_date : null;
        }
        if ($request->has('timeline_notes')) {
            $deal->timeline_notes = $request->timeline_notes ?: null;
        }

        // Complexity
        if ($request->has('complexity')) {
            $deal->service_complexity = $request->complexity;
        }
        if ($request->has('complexity_notes')) {
            $deal->complexity_notes = $request->complexity_notes;
        }
        if ($request->has('professional_support') || $request->filled('other_professional_support')) {
            $support = (array) ($request->professional_support ?? []);
            if ($request->filled('other_professional_support')) {
                $support[] = $request->other_professional_support;
            }
            $deal->professional_support_required = array_values(array_filter(
                array_unique($support),
                fn ($item) => filled($item) && $item !== 'Others'
            ));
        }

        // Decision, Assignments, Notes, Sign-offs
        if ($request->has('proposal_decision')) {
            $deal->proposal_decision = $request->proposal_decision;
        }
        if ($request->has('assigned_consultant')) {
            $deal->assigned_consultant = $request->assigned_consultant;
        }
        if ($request->has('assigned_associate')) {
            $deal->assigned_associate = $request->assigned_associate;
        }
        if ($request->has('service_department')) {
            $deal->service_department = $request->service_department;
        }
        if ($request->has('consultant_notes')) {
            $deal->consultant_notes = $request->consultant_notes;
        }
        if ($request->has('associate_notes')) {
            $deal->associate_notes = $request->associate_notes;
        }
        if ($request->has('reviewed_by')) {
            $deal->reviewed_by = $request->reviewed_by;
        }
        if ($request->has('approval_name')) {
            $deal->approval_name = $request->approval_name;
        }
        if ($request->has('approval_date')) {
            $deal->approval_date = $request->approval_date;
        }
        if ($request->has('client_fullname_signature')) {
            $deal->client_fullname_signature = $request->client_fullname_signature;
        }
        if ($request->has('referred_by')) {
            $deal->referred_by = $request->referred_by;
        }
        if ($request->has('sales_marketing')) {
            $deal->sales_marketing = $request->sales_marketing;
        }
        if ($request->has('lead_consultant')) {
            $deal->lead_consultant = $request->lead_consultant;
        }
        if ($request->has('lead_associate')) {
            $deal->lead_associate = $request->lead_associate;
        }
        if ($request->has('finance')) {
            $deal->finance = $request->finance;
        }
        if ($request->has('president')) {
            $deal->president = $request->president;
        }

        // User assignments
        if ($request->has('assigned_user_id')) {
            $deal->assigned_user_id = $request->assigned_user_id;
        }
        if ($request->has('responsible_user_id')) {
            $deal->responsible_user_id = $request->responsible_user_id;
        }
        if ($request->has('reviewer_id')) {
            $deal->reviewer_id = $request->reviewer_id;
        }
        if ($request->has('approver_id')) {
            $deal->approver_id = $request->approver_id;
        }
        if ($request->has('assigned_user_ids')) {
            $deal->assigned_user_ids = (array) $request->assigned_user_ids;
        }
        $deal->assigned_by = auth()->id();
        $deal->assigned_at = now();

        $deal->save();
        $changes = $deal->getChanges();

        if (!empty($changes)) {
            if (isset($changes['pipeline_stage']) && $changes['pipeline_stage'] !== $oldStage) {
                app(DealHistoryService::class)->logStageChanged($deal, $oldStage, $changes['pipeline_stage'], $request->input('notes'));
            }
            app(DealHistoryService::class)->logDealUpdated($deal, $changes, $original);

            // Notify newly assigned users
            $newAssigned = array_filter([
                $changes['assigned_user_id'] ?? null,
                $changes['responsible_user_id'] ?? null,
                $changes['reviewer_id'] ?? null,
                $changes['approver_id'] ?? null,
            ]);
            if (!empty($newAssigned)) {
                \App\Services\TargetedNotificationService::notifyAssignedUsers(
                    $newAssigned,
                    "Reassigned Deal: {$deal->deal_code}",
                    "You have been assigned responsibility for Deal {$deal->deal_code} ({$deal->deal_title}).",
                    route('deals.show', ['id' => $deal->id]),
                    'Deals',
                    'fa-user-check',
                    'Open Deal',
                    auth()->id()
                );
            }
        }

        return redirect()->to(
            $request->input(
                'return_to',
                route(
                    'deals.show',
                    ['id' => $deal->id]
                )
            )
        )->with(
            'success',
            'Deal updated successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Send Finance Notice (Payment / Quotation Notice)
    |--------------------------------------------------------------------------
    | Strictly dispatches targeted notification to the designated Finance user ONLY.
    */
    public function sendFinanceNotice(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:payment,quotation'],
        ]);

        $deal = Deal::findOrFail($id);
        $type = $validated['type'];

        // Determine the Finance user specifically for this deal
        $financeUser = null;

        if ($deal->assigned_finance_user_id) {
            $financeUser = User::find($deal->assigned_finance_user_id);
        }

        if (!$financeUser && !empty($deal->internal_finance)) {
            $financeUser = User::where('name', $deal->internal_finance)
                ->orWhere('email', $deal->internal_finance)
                ->first();
        }

        if (!$financeUser && !empty($deal->finance)) {
            $financeUser = User::where('name', $deal->finance)
                ->orWhere('email', $deal->finance)
                ->first();
        }

        if (!$financeUser) {
            // Find user explicitly holding Finance role or permission
            $financeUser = User::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(role) = ?', ['finance'])
                      ->orWhereHas('userPermission', function ($pq) {
                          $pq->where('access_finance', true)
                             ->orWhere('finance_treasurer', true)
                             ->orWhere('finance_approver', true)
                             ->orWhere('finance_president', true);
                      })
                      ->orWhereHas('employeeProfile', function ($eq) {
                          $eq->where('department', 'like', '%Finance%')
                             ->orWhere('position', 'like', '%Finance%')
                             ->orWhere('position', 'like', '%Accountant%');
                      });
                })
                ->first();
        }

        if (!$financeUser) {
            $financeUser = User::where('email', 'like', '%finance%')->first();
        }

        if (!$financeUser) {
            return response()->json([
                'success' => false,
                'message' => 'Assign a Finance user with an email address before sending this notice.',
            ], 422);
        }

        if (!\App\Services\EmailNotificationService::isValidRecipient($financeUser->email)) {
            return response()->json([
                'success' => false,
                'message' => 'The assigned Finance user does not have a valid email address. Update the Finance email before sending.',
            ], 422);
        }

        $dealValue = (float) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: 0));
        $dealTitle = $deal->deal_title ?: ($deal->deal_name ?: 'Untitled Deal');
        $senderName = auth()->user()?->name ?? 'System';

        if ($type === 'payment') {
            $title = "Payment Notice: Deal {$deal->deal_code}";
            $message = "Payment collection notice dispatched for Deal {$deal->deal_code} ({$dealTitle}). Total Value: ₱" . number_format($dealValue, 2) . ". Please record and process collection.";
            $icon = 'fa-money-bill-wave';
            $actionLabel = 'Open Finance Desk';
        } else {
            $title = "Quotation Notice: Deal {$deal->deal_code}";
            $message = "Quotation notice dispatched for Deal {$deal->deal_code} ({$dealTitle}). Please review quotation and prepare terms.";
            $icon = 'fa-file-invoice-dollar';
            $actionLabel = 'Review Quotation';
        }

        $emailHtml = view('emails.deals.finance-notice', [
            'deal' => $deal,
            'financeName' => $financeUser->name,
            'noticeTitle' => $type === 'payment' ? 'Payment Notice' : 'Quotation Notice',
            'noticeMessage' => $type === 'payment'
                ? "A payment collection notice has been issued for {$dealTitle}. Please record and process the collection."
                : "A quotation notice has been issued for {$dealTitle}. Please review the quotation and prepare the terms.",
            'dealUrl' => route('deals.show', ['id' => $deal->id]) . '?tab=finance',
            'senderName' => $senderName,
            'dealValue' => $dealValue,
        ])->render();

        $emailResult = app(\App\Services\EmailNotificationService::class)->sendHtml(
            $financeUser->email,
            "{$title} - {$dealTitle}",
            $emailHtml
        );

        if (!$emailResult['success']) {
            return response()->json([
                'success' => false,
                'message' => "Could not email the notice to Finance ({$financeUser->email}): {$emailResult['error']}",
            ], 502);
        }

        // Permanently bind this Finance user to the deal only after delivery succeeds.
        if (!$deal->assigned_finance_user_id) {
            $deal->assigned_finance_user_id = $financeUser->id;
            if (empty($deal->finance)) {
                $deal->finance = $financeUser->name;
            }
            $deal->save();
        }

        if ($deal->proposal) {
            if ($type === 'payment') {
                $deal->proposal->update(['invoice_status' => 'payment_notice_sent']);
            } else {
                $deal->proposal->update(['quotation_status' => 'quotation_notice_sent']);
            }
        }

        // GUARANTEE: Dispatch ONLY to this specific Finance user
        \App\Services\TargetedNotificationService::notifyAssignedUsers(
            $financeUser,
            $title,
            $message,
            route('deals.show', ['id' => $deal->id]) . '?tab=finance',
            'Finance',
            $icon,
            $actionLabel,
            auth()->id()
        );

        // Audit Trail / History Log
        try {
            app(\App\Services\DealHistoryService::class)->logActivity(
                $deal,
                $type === 'payment' ? 'Payment Notice Dispatched' : 'Quotation Notice Dispatched',
                "{$title} dispatched strictly to Finance Desk ({$financeUser->name}) by {$senderName}.",
                [
                    'notice_type' => $type,
                    'finance_user_id' => $financeUser->id,
                    'finance_user_name' => $financeUser->name,
                    'finance_user_email' => $financeUser->email,
                    'sender' => $senderName,
                ]
            );
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => ($type === 'payment' ? 'Payment Notice' : 'Quotation Notice') . " emailed to Finance ({$financeUser->email}).",
            'finance_user' => [
                'id' => $financeUser->id,
                'name' => $financeUser->name,
                'email' => $financeUser->email,
            ],
            'status' => $type === 'payment' ? 'payment_sent' : 'quotation_sent',
            'sent_at' => now()->format('M d, Y h:i A'),
            'sent_by' => $senderName,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Deal Stage
    |--------------------------------------------------------------------------
    */

    public function finalizeOutcome(Request $request, $id)
    {
        $validated = $request->validate([
            'outcome' => ['required', 'in:Closed Won,Closed Lost'],
            'lost_reason' => ['required_if:outcome,Closed Lost', 'nullable', 'string', 'max:2000'],
            'closing_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $validated) {
        $deal = Deal::query()->lockForUpdate()->findOrFail($id);
        $outcome = $validated['outcome'];
        if (in_array($deal->pipeline_stage, ['Closed Won', 'Closed Lost'], true)) {
            return response()->json(['success' => false, 'message' => 'A final outcome has already been recorded.'], 409);
        }
        if ($deal->pipeline_stage !== 'Activation' || ! $deal->isStartCompleted()) {
            return response()->json(['success' => false, 'message' => 'Complete START and reach Activation before recording an outcome.'], 422);
        }
        if (! $deal->areEngagementWorkspacesComplete()) {
            return response()->json(['success' => false, 'message' => 'Complete the required engagement workspace(s) before closing this deal.'], 422);
        }
        if ($outcome === 'Closed Won') {
            foreach (['activation_date', 'assigned_team', 'assigned_person', 'service_start_date', 'activation_notes'] as $field) {
                if (blank($deal->{$field})) {
                    return response()->json(['success' => false, 'message' => 'Complete the Activation requirements before moving this deal to Closed Won.'], 422);
                }
            }
        }

        $oldStage = $deal->pipeline_stage;
        $deal->pipeline_stage = $outcome;
        $deal->stage = $outcome;
        $deal->{$outcome === 'Closed Won' ? 'closed_won_date' : 'closed_lost_date'} = now()->toDateString();
        if ($outcome === 'Closed Lost') {
            $deal->lost_reason = trim($validated['lost_reason']);
        }
        if (filled($validated['closing_notes'] ?? null)) {
            $deal->closing_notes = $validated['closing_notes'];
        }
        $deal->save();
        app(DealHistoryService::class)->logStageChanged($deal, $oldStage, $outcome, $validated['closing_notes'] ?? $validated['lost_reason'] ?? 'Final engagement outcome recorded.');

        return response()->json(['success' => true, 'message' => "Deal recorded as {$outcome}.", 'stage' => $outcome]);
        });
    }

    public function updateStage(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);

        $newStage = $request->input('pipeline_stage') ?: $request->input('stage');

        $validStages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
            'Closed Won',
            'Closed Lost',
        ];

        if (!$newStage || !in_array($newStage, $validStages, true)) {
            $msg = 'Invalid pipeline stage specified.';
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $oldStage = $deal->pipeline_stage ?: 'Inquiry';
        if (in_array($oldStage, ['Closed Won', 'Closed Lost'], true) && $newStage !== $oldStage) {
            return response()->json(['success' => false, 'message' => 'A final outcome is already recorded for this deal.'], 409);
        }
        if ($newStage === 'Activation' && ! $deal->isStartCompleted()) {
            $msg = 'Complete the required START assignments and downloadable Service Memo before moving to Activation.';
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        // Helper to compile stages summary
        $getStagesData = function () {
            $allStages = [
                'Inquiry',
                'Qualification',
                'Consultation',
                'Proposal',
                'Negotiation',
                'Payment',
                'Activation',
                'Closed Won',
                'Closed Lost',
            ];
            $allDeals = Deal::all();
            $stagesData = [];
            foreach ($allStages as $st) {
                $stDeals = $allDeals->where('pipeline_stage', $st);
                $count = $stDeals->count();
                $total = (float)$stDeals->sum(fn($d) => (float)($d->total_estimated_engagement_value ?? $d->amount ?? 0));
                $stagesData[$st] = [
                    'count' => $count,
                    'total' => $total,
                    'formatted_total' => 'P' . number_format($total, 0),
                    'label' => 'P' . number_format($total, 0) . ' • ' . $count . ' ' . ($count === 1 ? 'Deal' : 'Deals'),
                ];
            }
            return $stagesData;
        };

        // If stage is identical, no-op
        if ($oldStage === $newStage) {
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Deal is already in {$newStage}.",
                    'deal' => $deal,
                    'old_stage' => $oldStage,
                    'new_stage' => $newStage,
                    'stages' => $getStagesData(),
                ]);
            }
            return redirect()->back()->with('info', "Deal is already in {$newStage}.");
        }

        $sequentialStages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
        ];

        $currentIdx = array_search($oldStage, $sequentialStages, true);
        if ($currentIdx === false) {
            $currentIdx = count($sequentialStages);
        }

        $targetIdx = array_search($newStage, $sequentialStages, true);
        if ($targetIdx === false) {
            $targetIdx = count($sequentialStages);
        }

        $isTargetFinal = in_array($newStage, ['Closed Won', 'Closed Lost'], true);

        // 1. Moving to Final Stages (Closed Won / Closed Lost)
        if ($isTargetFinal) {
            if (! $deal->isStartCompleted() || ! $deal->areEngagementWorkspacesComplete()) {
                $msg = 'Complete START and the required engagement workspace(s) before closing this deal.';
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            if ($currentIdx < 6) {
                $msg = "Please complete all previous stages through Activation before moving to {$newStage}.";
                if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                    ], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            if ($newStage === 'Closed Won') {
                $activationFields = [
                    'activation_date',
                    'assigned_team',
                    'assigned_person',
                    'service_start_date',
                    'activation_notes',
                ];
                foreach ($activationFields as $f) {
                    if (empty($deal->{$f})) {
                        $msg = 'Complete the Activation requirements before moving this deal to Closed Won.';
                        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $msg,
                            ], 422);
                        }
                        return redirect()->back()->with('error', $msg);
                    }
                }
            } elseif ($newStage === 'Closed Lost') {
                if (empty($deal->lost_reason) && empty($request->input('notes')) && empty($request->input('lost_reason'))) {
                    $msg = 'Please specify a lost reason or complete closing requirements before moving this deal to Closed Lost.';
                    if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $msg,
                        ], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }
                if (!empty($request->input('notes')) && empty($deal->lost_reason)) {
                    $deal->lost_reason = $request->input('notes');
                    $deal->closing_notes = $request->input('notes');
                }
            }
        }
        // 2. Forward Skipping (more than 1 step ahead)
        elseif ($targetIdx > $currentIdx + 1) {
            $msg = "Stage skipping is not permitted. Complete intermediate stages sequentially before moving to {$newStage}.";
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }
        // 3. Advancing 1 Step: Validate current stage requirements
        elseif ($targetIdx === $currentIdx + 1) {
            $requiredFieldsMap = [
                'Inquiry' => [
                    'inquiry_source',
                    'inquiry_date',
                    'first_name',
                    'last_name',
                    'email',
                    'mobile_number',
                    'deal_title',
                    'inquiry_details',
                ],
                'Qualification' => [
                    'qualification_result',
                    'client_need',
                    'amount',
                    'decision_maker',
                    'expected_close',
                    'qualification_notes',
                ],
                'Consultation' => [
                    'consultation_date',
                    'consultation_type',
                    'requirements_confirmed',
                    'scope_of_work',
                    'consultant_notes',
                ],
                'Proposal' => [
                    'proposal_number',
                    'proposal_date',
                    'proposal_value',
                    'proposal_valid_until',
                    'proposal_status',
                    'proposal_notes',
                ],
                'Negotiation' => [
                    'negotiation_status',
                    'final_deal_value',
                    'payment_terms',
                    'pricing_model',
                    'negotiation_notes',
                ],
                'Payment' => [
                    'payment_method',
                    'payment_amount',
                    'payment_date',
                    'payment_status',
                    'payment_reference',
                ],
            ];

            if (isset($requiredFieldsMap[$oldStage])) {
                foreach ($requiredFieldsMap[$oldStage] as $field) {
                    $val = $deal->{$field};
                    if ($val === null || $val === '' || (is_numeric($val) && $val <= 0 && in_array($field, ['amount', 'payment_amount'], true))) {
                        $msg = "Complete the {$oldStage} requirements before moving this deal to {$newStage}.";
                        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $msg,
                            ], 422);
                        }
                        return redirect()->back()->with('error', $msg);
                    }
                }
            }
        }
        // 4. Moving backward ($targetIdx < $currentIdx): allowed without losing data.

        // Perform stage update
        $deal->pipeline_stage = $newStage;
        $deal->stage = $newStage;
        if (in_array($newStage, ['Consultation', 'Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won'], true)) {
            if (empty($deal->qualification_result) || in_array(strtolower((string)$deal->qualification_result), ['not_started', 'pending', 'in_progress'], true)) {
                $deal->qualification_result = 'qualified';
            }
            if (empty($deal->qualification_notes)) {
                $deal->qualification_notes = 'Client qualification verified.';
            }
        }
        if ($newStage === 'Closed Won' && empty($deal->closed_won_date)) {
            $deal->closed_won_date = now()->toDateString();
        }
        if ($newStage === 'Closed Lost' && empty($deal->closed_lost_date)) {
            $deal->closed_lost_date = now()->toDateString();
        }
        if (in_array($newStage, ['Activation', 'Closed Won'], true)) {
            if (empty($deal->casa_stage)) {
                $deal->casa_stage = 'Activation';
            }
        }
        $deal->save();

        // Audit Trail
        $historyNote = $request->input('notes') ?: ($targetIdx < $currentIdx ? "Moved backward from {$oldStage} to {$newStage}." : "Moved to {$newStage}.");
        app(DealHistoryService::class)->logStageChanged(
            $deal,
            $oldStage,
            $newStage,
            $historyNote
        );

        $deal->refresh();
        $historyService = app(DealHistoryService::class);
        $stageProgression = $historyService->getStageProgression($deal);
        $stageDurations = $deal->getStageDurationsMap();
        $counts = $historyService->getActivityCounts($deal);
        $stageStartedAt = $deal->current_stage_started_at->copy()->timezone('Asia/Manila');

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => "Deal stage updated to {$newStage}.",
                'deal' => $deal,
                'old_stage' => $oldStage,
                'new_stage' => $newStage,
                'current_stage' => $newStage,
                'stage_progression' => $stageProgression,
                'stage_durations' => $stageDurations,
                'stage_start_ms' => $stageStartedAt->getTimestamp() * 1000,
                'stage_started_at_formatted' => $stageStartedAt->format('M d, Y · g:i A'),
                'counts' => $counts,
                'stages' => $getStagesData(),
                'statuses' => [
                    'kyc' => $deal->getResolvedKycStatus(),
                    'payment' => $deal->getResolvedPaymentStatus(),
                    'start' => $deal->getResolvedStartStatus(),
                    'proposal' => $deal->getResolvedProposalStatus(),
                    'commercial' => $deal->getResolvedCommercialStatus(),
                    'casa' => $deal->getResolvedCasaStatus(),
                ],
            ]);
        }

        return redirect()->to(
            $request->input(
                'return_to',
                url()->previous()
            )
        )->with(
            'success',
            "Deal stage updated to {$newStage} successfully."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Save Stage Workflow & Enforce Sequential Progression
    |--------------------------------------------------------------------------
    */

    public function saveStageWorkflow(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);

        $stage = $request->input('stage');
        $action = $request->input('action', 'complete_and_advance');
        $outcomeType = $request->input('outcome_type'); // 'Closed Won' or 'Closed Lost'

        $validStages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
            'Closed Won',
            'Closed Lost',
        ];

        if (!in_array($stage, $validStages, true)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid stage specified.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Invalid stage specified.');
        }

        $sequentialStages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
        ];

        $currentStage = $deal->pipeline_stage ?: 'Inquiry';
        if (in_array($currentStage, ['Closed Won', 'Closed Lost'], true) && $stage !== $currentStage) {
            return response()->json(['success' => false, 'message' => 'A final outcome is already recorded for this deal.'], 409);
        }
        $currentIdx = array_search($currentStage, $sequentialStages, true);
        if ($currentIdx === false) {
            // Already Closed Won or Closed Lost
            $currentIdx = count($sequentialStages);
        }

        $submittedIdx = array_search($stage, $sequentialStages, true);
        if ($submittedIdx === false) {
            $submittedIdx = count($sequentialStages);
        }

        // Enforce No Skipping: Cannot submit future stages ahead of current stage
        // Note: Closed Won / Closed Lost are valid outcomes once Activation (index 6) is reached.
        $outcomeToRecord = in_array($stage, ['Closed Won', 'Closed Lost'], true)
            ? $stage
            : (($stage === 'Activation' && in_array($outcomeType, ['Closed Won', 'Closed Lost'], true)) ? $outcomeType : null);
        $isFinalOutcome = $outcomeToRecord !== null;
        if ($isFinalOutcome) {
            if (! $deal->isStartCompleted() || ! $deal->areEngagementWorkspacesComplete()) {
                $msg = 'Complete START and the required engagement workspace(s) before finalizing the deal outcome.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg, 'errors' => ['stage' => [$msg]]], 422);
                }
                return redirect()->back()->with('error', $msg);
            }
            if ($currentIdx < 6) {
                $msg = 'Please complete all previous stages through Activation before finalizing the deal outcome.';
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                        'errors' => ['stage' => [$msg]],
                    ], 422);
                }
                return redirect()->back()->with('error', $msg);
            }
        } elseif ($submittedIdx > $currentIdx) {
            $msg = 'Please complete the current stage first. Stage skipping is not permitted.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => ['stage' => [$msg]],
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Stage-specific Validation Rules
        $rules = [];
        $messages = [];

        switch ($stage) {
            case 'Inquiry':
                $rules = [
                    'inquiry_source' => 'required|string|max:255',
                    'inquiry_date' => 'required|date',
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'nullable|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'mobile_number' => 'nullable|string|max:50',
                    'deal_title' => 'required|string|max:255',
                    'inquiry_details' => 'required|string',
                ];
                break;

            case 'Qualification':
                $rules = [
                    'qualification_result' => 'nullable|string|max:255',
                    'client_need' => 'nullable|string',
                    'amount' => 'nullable|numeric|min:0',
                    'decision_maker' => 'nullable|string|max:255',
                    'expected_close' => 'nullable|date',
                    'qualification_notes' => 'nullable|string',
                ];
                break;

            case 'Consultation':
                $rules = [
                    'consultation_date' => 'nullable|date',
                    'consultation_type' => 'nullable|string|max:255',
                    'requirements_confirmed' => 'nullable|string|max:255',
                    'scope_of_work' => 'nullable|string',
                    'consultant_notes' => 'nullable|string',
                ];
                break;

            case 'Proposal':
                $rules = [
                    'proposal_number' => 'nullable|string|max:255',
                    'proposal_date' => 'nullable|date',
                    'proposal_value' => 'nullable|numeric|min:0',
                    'proposal_valid_until' => 'nullable|date',
                    'proposal_status' => 'nullable|string|max:255',
                    'proposal_notes' => 'nullable|string',
                ];
                break;

            case 'Negotiation':
                $rules = [
                    'negotiation_status' => 'nullable|string|max:255',
                    'final_deal_value' => 'nullable|numeric|min:0',
                    'payment_terms' => 'nullable|string|max:255',
                    'pricing_model' => 'nullable|string|max:255',
                    'negotiation_notes' => 'nullable|string',
                ];
                break;

            case 'Payment':
                $rules = [
                    'payment_method' => 'nullable|string|max:255',
                    'payment_amount' => 'nullable|numeric|min:0',
                    'payment_date' => 'nullable|date',
                    'payment_status' => 'nullable|string|max:255',
                    'payment_reference' => 'nullable|string|max:255',
                ];
                break;

            case 'Activation':
                $rules = [
                    'activation_date' => 'nullable|date',
                    'assigned_team' => 'nullable|string|max:255',
                    'assigned_person' => 'nullable|string|max:255',
                    'service_start_date' => 'nullable|date',
                    'activation_notes' => 'nullable|string',
                ];
                break;

            case 'Closed Won':
                $rules = [
                    'closed_won_date' => 'nullable|date',
                    'final_deal_value' => 'nullable|numeric|min:0',
                    'closing_notes' => 'nullable|string',
                ];
                break;

            case 'Closed Lost':
                $rules = [
                    'closed_lost_date' => 'nullable|date',
                    'lost_reason' => 'nullable|string|max:255',
                    'closing_notes' => 'nullable|string',
                ];
                break;
        }

        if ($outcomeToRecord === 'Closed Lost') {
            $rules['lost_reason'] = 'required|string|max:2000';
        }

        try {
            $validated = $request->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete all required fields before proceeding.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        if ($isFinalOutcome && $outcomeToRecord === 'Closed Won') {
            foreach (['activation_date', 'assigned_team', 'assigned_person', 'service_start_date', 'activation_notes'] as $field) {
                if (blank($validated[$field] ?? $deal->{$field})) {
                    return response()->json(['success' => false, 'message' => 'Complete the Activation requirements before moving this deal to Closed Won.'], 422);
                }
            }
        }
        if ($isFinalOutcome && $outcomeToRecord === 'Closed Lost' && blank($validated['lost_reason'] ?? $deal->lost_reason)) {
            return response()->json(['success' => false, 'message' => 'A reason is required before moving this deal to Closed Lost.'], 422);
        }

        $original = $deal->getOriginal();
        $oldStage = $deal->pipeline_stage ?: 'Inquiry';

        // Update fields on deal
        foreach ($validated as $key => $val) {
            if ($key === 'expected_close') {
                if ($request->filled('expected_close')) {
                    $deal->expected_close = $val;
                }
                // Do not overwrite existing user-specified date if stage update has empty expected_close
                continue;
            }
            $deal->{$key} = $val;
        }

        // Keep synced fields consistent
        if ($request->filled('first_name') || $request->filled('last_name')) {
            $deal->primary_contact_name = trim(
                implode(' ', array_filter([
                    $deal->salutation,
                    $deal->first_name,
                    $deal->middle_initial,
                    $deal->last_name,
                    $deal->name_extension,
                ]))
            );
        }
        if ($request->filled('final_deal_value')) {
            $deal->amount = $deal->final_deal_value;
            $deal->total_estimated_engagement_value = $deal->final_deal_value;
        } elseif ($request->filled('amount')) {
            $deal->total_estimated_engagement_value = $deal->amount;
        }
        if ($request->filled('assigned_team')) {
            $deal->service_department = $deal->assigned_team;
        }
        if ($request->filled('assigned_person')) {
            $deal->assigned_consultant = $deal->assigned_person;
        }
        if ($request->filled('service_start_date')) {
            $deal->planned_start_date = $deal->service_start_date;
        }

        // Determine Next Stage
        $nextStage = $currentStage;
        $isAdvancing = ($action === 'complete_and_advance');

        if ($isAdvancing && $submittedIdx === $currentIdx) {
            switch ($stage) {
                case 'Inquiry':
                    $nextStage = 'Qualification';
                    break;
                case 'Qualification':
                    $nextStage = 'Consultation';
                    break;
                case 'Consultation':
                    $nextStage = 'Proposal';
                    break;
                case 'Proposal':
                    $nextStage = 'Negotiation';
                    break;
                case 'Negotiation':
                    $nextStage = 'Payment';
                    break;
                case 'Payment':
                    $nextStage = $deal->isStartCompleted() ? 'Activation' : 'Payment';
                    break;
                case 'Activation':
                    // If outcome is chosen in same action
                    if ($outcomeToRecord !== null) {
                        $nextStage = $outcomeToRecord;
                    } else {
                        $nextStage = 'Activation'; // Stays at Activation until Won/Lost submitted
                    }
                    break;
                case 'Closed Won':
                    $nextStage = 'Closed Won';
                    break;
                case 'Closed Lost':
                    $nextStage = 'Closed Lost';
                    break;
            }

            $deal->pipeline_stage = $nextStage;
            $deal->stage = $nextStage;
        } elseif ($stage === 'Closed Won' || $stage === 'Closed Lost') {
            $deal->pipeline_stage = $stage;
            $deal->stage = $stage;
            $nextStage = $stage;
        }

        if ($stage === 'Qualification' || in_array($deal->pipeline_stage, ['Consultation', 'Proposal', 'Negotiation', 'Payment', 'Activation', 'Closed Won'], true)) {
            if (empty($deal->qualification_result) || in_array(strtolower((string)$deal->qualification_result), ['not_started', 'pending', 'in_progress'], true)) {
                $deal->qualification_result = $request->input('qualification_result') ?: 'qualified';
            }
            if (empty($deal->qualification_notes)) {
                $deal->qualification_notes = $request->input('qualification_notes') ?: 'Client qualification verified.';
            }
        }

        if (in_array($deal->pipeline_stage, ['Activation', 'Closed Won'], true)) {
            if (empty($deal->casa_stage)) {
                $deal->casa_stage = 'Activation';
            }
        }

        if ($deal->pipeline_stage === 'Closed Won' && empty($deal->closed_won_date)) {
            $deal->closed_won_date = now()->toDateString();
        } elseif ($deal->pipeline_stage === 'Closed Lost' && empty($deal->closed_lost_date)) {
            $deal->closed_lost_date = now()->toDateString();
        }

        $deal->save();

        // Log Stage Transition in Audit Trail if stage changed
        if ($oldStage !== $deal->pipeline_stage) {
            app(DealHistoryService::class)->logStageChanged(
                $deal,
                $oldStage,
                $deal->pipeline_stage,
                "Stage completed and progressed to {$deal->pipeline_stage} via workflow."
            );
        }

        // Log general field updates
        $changes = $deal->getChanges();
        if (!empty($changes)) {
            app(DealHistoryService::class)->logDealUpdated($deal, $changes, $original);
        }

        $deal->refresh();
        $historyService = app(DealHistoryService::class);
        $stageProgression = $historyService->getStageProgression($deal);
        $stageDurations = $deal->getStageDurationsMap();
        $counts = $historyService->getActivityCounts($deal);
        $stageStartedAt = $deal->current_stage_started_at->copy()->timezone('Asia/Manila');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$stage} requirements completed successfully.",
                'current_stage' => $deal->pipeline_stage,
                'completed_stage' => $stage,
                'next_stage' => $nextStage,
                'deal' => $deal,
                'stage_progression' => $stageProgression,
                'stage_durations' => $stageDurations,
                'stage_start_ms' => $stageStartedAt->getTimestamp() * 1000,
                'stage_started_at_formatted' => $stageStartedAt->format('M d, Y · g:i A'),
                'counts' => $counts,
                'statuses' => [
                    'kyc' => $deal->getResolvedKycStatus(),
                    'payment' => $deal->getResolvedPaymentStatus(),
                    'start' => $deal->getResolvedStartStatus(),
                    'proposal' => $deal->getResolvedProposalStatus(),
                    'commercial' => $deal->getResolvedCommercialStatus(),
                    'casa' => $deal->getResolvedCasaStatus(),
                ],
            ]);
        }

        return redirect()->to(
            route('deals.show', ['id' => $deal->id])
        )->with('success', "{$stage} completed successfully.");
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Deal
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $deal = Deal::findOrFail($id);

        $deal->delete();

        return redirect()
            ->route('deals.index')
            ->with(
                'success',
                'Deal deleted successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | History & Traceability AJAX Endpoint
    |--------------------------------------------------------------------------
    */

    public function getHistories(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $query = DealHistory::where('deal_id', $deal->id)->latestFirst();

        if ($request->filled('activity_type') && $request->activity_type !== 'all') {
            $query->filterByType($request->activity_type);
        }

        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->filterByUser((int)$request->user_id);
        }

        if ($request->filled('date_range') && $request->date_range !== 'all') {
            $query->filterByDateRange(
                $request->date_range,
                $request->start_date,
                $request->end_date
            );
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('user_name', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%")
                    ->orWhere('document_name', 'like', "%{$s}%");
            });
        }

        $totalFiltered = (clone $query)->count();
        $limit = max(1, min((int)$request->input('limit', 20), 100));
        $page = max(1, (int)$request->input('page', 1));
        $offset = ($page - 1) * $limit;

        $histories = $query->skip($offset)->take($limit)->get();
        $historyService = app(DealHistoryService::class);
        $counts = $historyService->getActivityCounts($deal);
        $stageProgression = $historyService->getStageProgression($deal);

        return response()->json([
            'success' => true,
            'count' => $histories->count(),
            'total_count' => $totalFiltered,
            'page' => $page,
            'limit' => $limit,
            'has_more' => ($offset + $histories->count()) < $totalFiltered,
            'counts' => $counts,
            'stage_progression' => $stageProgression,
            'histories' => $histories->map(function ($h, $index) use ($page) {
                return [
                    'id' => $h->id,
                    'is_latest' => ($page === 1 && $index === 0),
                    'activity_type' => $h->activity_type,
                    'type_label' => $h->type_label,
                    'badge_class' => $h->badge_class,
                    'title' => $h->title,
                    'description' => $h->description,
                    'user_name' => $h->user_name,
                    'from_stage' => $h->from_stage,
                    'to_stage' => $h->to_stage,
                    'field_name' => $h->field_name,
                    'from_value' => $h->from_value,
                    'to_value' => $h->to_value,
                    'document_name' => $h->document_name,
                    'document_type' => $h->document_type,
                    'proposal_id' => $h->proposal_id,
                    'old_values' => $h->old_values,
                    'new_values' => $h->new_values,
                    'notes' => $h->notes,
                    'ip_address' => $h->ip_address,
                    'created_at_human' => $h->created_at ? $h->created_at->diffForHumans() : '',
                    'created_at_formatted' => $h->created_at ? $h->created_at->format('M d, Y · h:i A') : '',
                    'created_at_date' => $h->created_at ? $h->created_at->format('M d, Y') : '',
                    'created_at_time' => $h->created_at ? $h->created_at->format('h:i A') : '',
                    'created_at_iso' => $h->created_at ? $h->created_at->toISOString() : '',
                ];
            }),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Note
    |--------------------------------------------------------------------------
    */

    public function storeNote(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
            'category' => 'nullable|string|max:50',
        ]);

        $history = app(DealHistoryService::class)->logNoteAdded(
            $deal,
            $validated['notes'],
            $validated['category'] ?? 'General'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Note added to history.',
                'history' => $history,
            ]);
        }

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Inquiry Records Endpoints (Multiple Inquiries)
    |--------------------------------------------------------------------------
    */
    public function storeInquiry(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $validated = $request->validate([
            'id' => 'nullable',
            'subject' => 'required|string|max:255',
            'type' => 'required|string|in:Product,Service',
            'client_inquiry' => 'required|string',
            'budget' => 'nullable|string|max:255',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $records = is_array($deal->inquiry_records) ? $deal->inquiry_records : [];
        if (empty($records)) {
            $listVal = function ($val) {
                if (is_array($val)) return collect($val)->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : $item)->filter()->implode(', ');
                if (is_string($val)) {
                    $d = json_decode($val, true);
                    if (is_array($d)) return collect($d)->map(fn ($item) => is_array($item) ? ($item['name'] ?? '') : $item)->filter()->implode(', ');
                }
                return (string)$val;
            };

            $title = $deal->deal_title;
            if (blank($title)) {
                $title = filled($listVal($deal->services_products))
                    ? $listVal($deal->services_products)
                    : (filled($listVal($deal->service_areas))
                        ? $listVal($deal->service_areas)
                        : (filled($deal->company_name) ? ($deal->company_name . ' Service') : 'Initial Client Inquiry'));
            }

            $details = $deal->inquiry_details 
                ?: ($deal->scope_of_work 
                    ?: (filled($listVal($deal->client_requirements)) ? $listVal($deal->client_requirements)
                        : ($deal->consultant_notes ?: ('Initial Client Inquiry for ' . ($deal->company_name ?: ($deal->primary_contact_name ?: 'deal'))))));

            $records[] = [
                'id' => 1,
                'subject' => $title,
                'type' => (in_array($deal->inquiry_source, ['Service', 'Product']) ? $deal->inquiry_source : (in_array($deal->engagement_type, ['Product', 'Hybrid']) ? 'Product' : 'Service')),
                'clientInquiry' => $details,
                'budget' => (string) ($deal->total_estimated_engagement_value ?: ($deal->amount ?: '')),
                'targetDate' => $deal->expected_close ? $deal->expected_close->format('Y-m-d') : ($deal->estimated_completion_date ? $deal->estimated_completion_date->format('Y-m-d') : ''),
                'notes' => !empty($deal->client_requirements) ? ('Requirements: ' . $listVal($deal->client_requirements)) : ($deal->consultant_notes ?: ''),
                'createdAt' => $deal->inquiry_date ? $deal->inquiry_date->format('M d, Y') : ($deal->created_at ? $deal->created_at->format('M d, Y') : now()->format('M d, Y')),
                'createdBy' => $deal->owner_name ?: ($deal->created_by ?: optional(auth()->user())->name ?: 'System'),
            ];
        }

        $editId = $request->input('id');

        if ($editId) {
            $updatedRecord = null;
            foreach ($records as &$rec) {
                if ((string)($rec['id'] ?? '') === (string)$editId) {
                    $rec['subject'] = $validated['subject'];
                    $rec['type'] = $validated['type'];
                    $rec['clientInquiry'] = $validated['client_inquiry'];
                    $rec['budget'] = $validated['budget'] ?? '';
                    $rec['targetDate'] = $validated['target_date'] ?? '';
                    $rec['notes'] = $validated['notes'] ?? '';
                    $updatedRecord = $rec;
                    break;
                }
            }
            unset($rec);

            if (!$updatedRecord) {
                $maxId = 0;
                foreach ($records as $r) {
                    if (isset($r['id']) && is_numeric($r['id']) && (int)$r['id'] > $maxId) {
                        $maxId = (int)$r['id'];
                    }
                }
                $newId = $maxId + 1;
                $updatedRecord = [
                    'id' => $newId,
                    'subject' => $validated['subject'],
                    'type' => $validated['type'],
                    'clientInquiry' => $validated['client_inquiry'],
                    'budget' => $validated['budget'] ?? '',
                    'targetDate' => $validated['target_date'] ?? '',
                    'notes' => $validated['notes'] ?? '',
                    'createdAt' => now()->format('M d, Y'),
                    'createdBy' => auth()->user()?->name ?: ($deal->owner_name ?: 'System'),
                ];
                array_unshift($records, $updatedRecord);
            }
            $targetRecord = $updatedRecord;
        } else {
            $maxId = 0;
            foreach ($records as $r) {
                if (isset($r['id']) && is_numeric($r['id']) && (int)$r['id'] > $maxId) {
                    $maxId = (int)$r['id'];
                }
            }
            $newId = $maxId + 1;
            $newRecord = [
                'id' => $newId,
                'subject' => $validated['subject'],
                'type' => $validated['type'],
                'clientInquiry' => $validated['client_inquiry'],
                'budget' => $validated['budget'] ?? '',
                'targetDate' => $validated['target_date'] ?? '',
                'notes' => $validated['notes'] ?? '',
                'createdAt' => now()->format('M d, Y'),
                'createdBy' => auth()->user()?->name ?: ($deal->owner_name ?: 'System'),
            ];
            array_unshift($records, $newRecord);
            $targetRecord = $newRecord;
        }

        $deal->inquiry_records = $records;
        if (!empty($validated['client_inquiry'])) {
            $deal->inquiry_details = $validated['client_inquiry'];
        }
        $deal->save();

        // Audit Trail log
        app(DealHistoryService::class)->logNoteAdded(
            $deal,
            "Inquiry recorded: {$validated['subject']} ({$validated['type']}). Details: {$validated['client_inquiry']}",
            'Inquiry'
        );

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inquiry record saved successfully.',
                'record' => $targetRecord,
                'records' => $records,
            ]);
        }

        return redirect()->back()->with('success', 'Inquiry record saved successfully.');
    }

    public function destroyInquiry(Request $request, $id, $inquiryId)
    {
        $deal = Deal::findOrFail($id);
        $records = is_array($deal->inquiry_records) ? $deal->inquiry_records : [];
        $filtered = array_values(array_filter($records, fn($r) => (string)($r['id'] ?? '') !== (string)$inquiryId));
        $deal->inquiry_records = $filtered;
        $deal->save();

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inquiry record deleted successfully.',
                'records' => $filtered,
            ]);
        }

        return redirect()->back()->with('success', 'Inquiry record deleted successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | Consultation Records Endpoints
    |--------------------------------------------------------------------------
    */
    public function storeConsultation(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'consultant' => 'nullable|string|max:255',
            'associate' => 'nullable|string|max:255',
            'prepared_by' => 'nullable|string|max:255',
            'notes' => 'required|string',
            'attachments' => 'nullable|array',
        ]);

        $records = is_array($deal->consultation_records) ? $deal->consultation_records : [];
        if (empty($records) && (filled($deal->consultant_notes) || filled($deal->consultation_date))) {
            $baseTitle = $deal->deal_title ?: 'Initial Consultation';
            if (!str_contains(strtolower($baseTitle), 'consultation')) {
                $baseTitle .= ' Consultation';
            }
            $records[] = [
                'id' => 1,
                'title' => $baseTitle,
                'date' => $deal->consultation_date ? Carbon::parse($deal->consultation_date)->format('Y-m-d') : ($deal->planned_start_date ? Carbon::parse($deal->planned_start_date)->format('Y-m-d') : now()->format('Y-m-d')),
                'consultant' => $deal->assigned_consultant ?: ($deal->lead_consultant ?: ($deal->owner_name ?: 'Consultant')),
                'associate' => $deal->assigned_associate ?: '',
                'preparedBy' => $deal->prepared_by ?: ($deal->owner_name ?: 'Consultant'),
                'notes' => $deal->consultant_notes ?: 'Initial Deal consultation notes.',
                'createdAt' => $deal->created_at ? $deal->created_at->format('M d, Y') : now()->format('M d, Y'),
                'createdBy' => $deal->owner_name ?: ($deal->created_by ?: 'Consultant'),
                'attachments' => []
            ];
        }

        $maxId = 0;
        foreach ($records as $r) {
            if (isset($r['id']) && is_numeric($r['id']) && (int)$r['id'] > $maxId) {
                $maxId = (int)$r['id'];
            }
        }
        $newId = $maxId + 1;

        $newRecord = [
            'id' => $newId,
            'title' => $validated['title'],
            'date' => $validated['date'],
            'consultant' => ($validated['consultant'] ?? '') ?: ($deal->lead_consultant ?: ($deal->assigned_consultant ?: ($deal->owner_name ?: 'Consultant'))),
            'associate' => $validated['associate'] ?? '',
            'preparedBy' => ($validated['prepared_by'] ?? '') ?: (auth()->user()?->name ?: ($deal->owner_name ?: 'Consultant')),
            'notes' => $validated['notes'],
            'createdAt' => now()->format('M d, Y'),
            'createdBy' => auth()->user()?->name ?: ($deal->owner_name ?: 'Consultant'),
            'attachments' => $validated['attachments'] ?? [],
        ];

        array_unshift($records, $newRecord);
        $deal->consultation_records = $records;

        // Keep core deal consultation attributes in sync
        $deal->consultation_date = $validated['date'];
        $deal->consultant_notes = $validated['notes'];
        if (!empty($validated['consultant'])) {
            $deal->assigned_consultant = $validated['consultant'];
        }
        if (!empty($validated['associate'])) {
            $deal->assigned_associate = $validated['associate'];
        }
        $deal->save();

        // Audit Trail log
        app(DealHistoryService::class)->logNoteAdded(
            $deal,
            "Consultation recorded: {$validated['title']}. Notes: {$validated['notes']}",
            'Consultation'
        );

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Consultation record saved successfully.',
                'record' => $newRecord,
                'records' => $records,
            ]);
        }

        return redirect()->back()->with('success', 'Consultation record saved successfully.');
    }

    public function destroyConsultation(Request $request, $id, $consultationId)
    {
        $deal = Deal::findOrFail($id);
        $records = is_array($deal->consultation_records) ? $deal->consultation_records : [];
        $filtered = array_values(array_filter($records, fn($r) => (int)($r['id'] ?? 0) !== (int)$consultationId));
        $deal->consultation_records = $filtered;
        $deal->save();

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Consultation record deleted successfully.',
                'records' => $filtered,
            ]);
        }

        return redirect()->back()->with('success', 'Consultation record deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Deal Line Items (Services & Pricing) Endpoints
    |--------------------------------------------------------------------------
    */
    public function storeLineItem(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $validated = $request->validate([
            'id' => 'nullable',
            'custom' => 'nullable|boolean',
            'service_id' => 'nullable|integer|min:1',
            'product_id' => 'nullable|integer|min:1',
            'associated_service_id' => 'nullable|integer|min:1',
            'type' => 'nullable|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'billing' => 'required|string|max:255',
            'route' => 'nullable|string|max:50',
        ]);

        $items = $this->normalizeDealLineItems(is_array($deal->services_products) ? $deal->services_products : []);
        $editId = $request->input('id');

        $type = strtoupper($validated['type'] ?? 'SERVICE');
        $catalogIdentity = null;
        if (! empty($validated['service_id']) || ! empty($validated['product_id'])) {
            $idKey = $type === 'PRODUCT' ? 'product_id' : 'service_id';
            $otherKey = $type === 'PRODUCT' ? 'service_id' : 'product_id';
            if (! in_array($type, ['SERVICE', 'PRODUCT'], true) || ! empty($validated[$otherKey]) || ! empty($validated['custom'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Select one matching catalog item or enter a custom name.']);
            }
            $choices = app(\App\Services\DealCatalogSelection::class)->choices($deal);
            $catalogIdentity = collect($choices[$type === 'PRODUCT' ? 'products' : 'services'])
                ->firstWhere('id', $validated[$idKey]);
            $existingItem = collect($items)->first(fn ($item) => (string) ($item['id'] ?? '') === (string) $editId);
            $retained = $existingItem && empty($existingItem['custom'])
                && (string) ($existingItem[$idKey] ?? '') === (string) $validated[$idKey];
            if (! $catalogIdentity || (! $catalogIdentity['selectable'] && ! $retained)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Select an active catalog item.']);
            }
            $validated['name'] = $catalogIdentity['name'];
        }
        $associatedServiceId = $validated['associated_service_id'] ?? null;
        if ($associatedServiceId) {
            $associatedService = isset($choices) ? collect($choices['services'])->firstWhere('id', $associatedServiceId) : null;
            if ($type !== 'PRODUCT' || ! $catalogIdentity || ! empty($validated['custom'])
                || ! in_array((int) $associatedServiceId, $catalogIdentity['linked_service_ids'], true)
                || ! $associatedService || (! $associatedService['selectable'] && ! $associatedService['retained'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['associated_service_id' => 'Select a Service actually linked to this Product.']);
            }
        }
        $qty = (float)($validated['qty'] ?? 1);
        $unitPrice = (float)($validated['unit_price'] ?? 0);
        $discount = (float)($validated['discount'] ?? 0);
        $tax = (float)($validated['tax'] ?? 0);
        $unit = $validated['unit'] ?? ($type === 'PRODUCT' ? 'document' : 'lot');
        $billing = $validated['billing'];
        $route = $validated['route'] ?? 'Regular';

        if ($editId) {
            $updatedItem = null;
            foreach ($items as &$it) {
                if (is_array($it) && (string)($it['id'] ?? '') === (string)$editId) {
                    $it['type'] = $type;
                    $it['name'] = $validated['name'];
                    $it['description'] = $validated['description'] ?? '';
                    $it['qty'] = $qty;
                    $it['unit'] = $unit;
                    $it['unitPrice'] = $unitPrice;
                    $it['price'] = $unitPrice;
                    $it['discount'] = $discount;
                    $it['tax'] = $tax;
                    $it['billing'] = $billing;
                    $it['route'] = $route;
                    $it['custom'] = (bool) ($validated['custom'] ?? false);
                    $updatedItem = $it;
                    break;
                }
            }
            unset($it);

            if (!$updatedItem) {
                $maxId = 0;
                foreach ($items as $it) {
                    if (is_array($it) && isset($it['id']) && is_numeric($it['id']) && (int)$it['id'] > $maxId) {
                        $maxId = (int)$it['id'];
                    }
                }
                $newId = $maxId + 1;
                $updatedItem = [
                    'id' => $newId,
                    'type' => $type,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? '',
                    'qty' => $qty,
                    'unit' => $unit,
                    'unitPrice' => $unitPrice,
                    'price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $tax,
                    'billing' => $billing,
                    'route' => $route,
                    'custom' => (bool) ($validated['custom'] ?? false),
                ];
                $items[] = $updatedItem;
            }
            $targetItem = $updatedItem;
        } else {
            $maxId = 0;
            foreach ($items as $it) {
                if (is_array($it) && isset($it['id']) && is_numeric($it['id']) && (int)$it['id'] > $maxId) {
                    $maxId = (int)$it['id'];
                }
            }
            $newId = $maxId + 1;
            $newRecord = [
                'id' => $newId,
                'type' => $type,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'qty' => $qty,
                'unit' => $unit,
                'unitPrice' => $unitPrice,
                'price' => $unitPrice,
                'discount' => $discount,
                'tax' => $tax,
                'billing' => $billing,
                'route' => $route,
                'custom' => (bool) ($validated['custom'] ?? false),
            ];
            $items[] = $newRecord;
            $targetItem = $newRecord;
        }

        foreach ($items as &$item) {
            if ((string) $item['id'] !== (string) $targetItem['id']) { continue; }
            if (! empty($validated['custom']) || $catalogIdentity) {
                $previousServiceId = $item['service_id'] ?? null;
                $previousVersionId = $item['service_version_id'] ?? null;
                unset($item['associated_service_id'], $item['service_identifications'], $item['service_area_id']);
                unset($item['service_id'], $item['service_version_id'], $item['product_id'], $item['product_version_id']);
                if ($catalogIdentity) {
                    $item[$type === 'PRODUCT' ? 'product_id' : 'service_id'] = $catalogIdentity['id'];
                    if ($type === 'PRODUCT' && $associatedServiceId) { $item['associated_service_id'] = (int) $associatedServiceId; }
                    if ($type === 'SERVICE') {
                        $item['service_identifications'] = $catalogIdentity['service_identifications'];
                        $item['service_area_id'] = Service::find($catalogIdentity['id'])?->service_area_id;
                        $item['service_version_id'] = (string) $previousServiceId === (string) $catalogIdentity['id']
                            ? ($previousVersionId ?? $catalogIdentity['service_version_id']) : $catalogIdentity['service_version_id'];
                    }
                }
            }
            $targetItem = $item;
            break;
        }
        unset($item);

        $deal->services_products = $items;

        // Recalculate totals
        $servicesFee = 0;
        $productsFee = 0;
        $totalDiscount = 0;
        $commercialTotal = 0;

        foreach ($items as $it) {
            $iQty = (float)($it['qty'] ?? 1);
            $iPrice = (float)($it['unitPrice'] ?? ($it['price'] ?? 0));
            $iDisc = (float)($it['discount'] ?? 0);
            $iTax = (float)($it['tax'] ?? 0);
            $base = $iQty * $iPrice;
            $tot = $base - $iDisc + $iTax;

            if (strtoupper($it['type'] ?? 'SERVICE') === 'PRODUCT') {
                $productsFee += $base;
            } else {
                $servicesFee += $base;
            }
            $totalDiscount += $iDisc;
            $commercialTotal += ($tot > 0 ? $tot : 0);
        }

        $deal->total_service_fee = $servicesFee;
        $deal->total_product_fee = $productsFee;
        $deal->discount = $totalDiscount;
        $deal->amount = $commercialTotal;
        $deal->total_estimated_engagement_value = $commercialTotal;
        $deal->payment_terms = $billing;
        $deal->save();

        app(DealHistoryService::class)->logNoteAdded(
            $deal,
            "Line item saved in Services & Pricing: {$validated['name']} ({$type}) - ₱" . number_format($unitPrice, 2),
            'Pricing'
        );

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Line item saved successfully.',
                'item' => $targetItem,
                'items' => $items,
                'paymentTerms' => $deal->payment_terms,
                'totals' => [
                    'services_fee' => $servicesFee,
                    'products_fee' => $productsFee,
                    'total_discount' => $totalDiscount,
                    'commercial_total' => $commercialTotal,
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Line item saved successfully.');
    }

    public function destroyLineItem(Request $request, $id, $itemId)
    {
        $result = DB::transaction(function () use ($id, $itemId): array {
            $deal = Deal::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $items = $this->normalizeDealLineItems(is_array($deal->services_products) ? $deal->services_products : []);
            $deletedItem = collect($items)->first(
                fn ($item): bool => (string) ($item['id'] ?? '') === (string) $itemId
            );
            abort_if($deletedItem === null, 404, 'The selected Deal line item no longer exists.');

            $filtered = array_values(array_filter(
                $items,
                fn ($item): bool => (string) ($item['id'] ?? '') !== (string) $itemId
            ));
            $deal->services_products = $filtered;

            $servicesFee = 0.0;
            $productsFee = 0.0;
            $totalDiscount = 0.0;
            $commercialTotal = 0.0;

            foreach ($filtered as $item) {
                $item = is_array($item) ? $item : [];
                $quantity = (float) ($item['qty'] ?? 1);
                $unitPrice = (float) ($item['unitPrice'] ?? ($item['price'] ?? 0));
                $discount = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $baseAmount = $quantity * $unitPrice;
                $lineTotal = $baseAmount - $discount + $tax;

                if (strtoupper($item['type'] ?? 'SERVICE') === 'PRODUCT') {
                    $productsFee += $baseAmount;
                } else {
                    $servicesFee += $baseAmount;
                }

                $totalDiscount += $discount;
                $commercialTotal += max($lineTotal, 0);
            }

            $deal->total_service_fee = $servicesFee;
            $deal->total_product_fee = $productsFee;
            $deal->discount = $totalDiscount;
            $deal->amount = $commercialTotal;
            $deal->total_estimated_engagement_value = $commercialTotal;
            $paymentTerms = null;
            foreach (array_reverse($filtered) as $remainingItem) {
                if (filled($remainingItem['billing'] ?? null)) {
                    $paymentTerms = $remainingItem['billing'];
                    break;
                }
            }
            $deal->payment_terms = $paymentTerms;
            $deal->save();

            $itemName = (string) ($deletedItem['name'] ?? 'Commercial Scope Item');
            $itemType = strtoupper((string) ($deletedItem['type'] ?? 'SERVICE'));
            app(DealHistoryService::class)->logNoteAdded(
                $deal,
                "Line item removed from active Deal pricing: {$itemName} ({$itemType}, ID {$itemId}). Issued proposal snapshots and client decision history were retained.",
                'Pricing'
            );

            return [
                'items' => $filtered,
                'payment_terms' => $paymentTerms,
                'totals' => [
                    'services_fee' => $servicesFee,
                    'products_fee' => $productsFee,
                    'total_discount' => $totalDiscount,
                    'commercial_total' => $commercialTotal,
                ],
                'item_name' => $itemName,
            ];
        });

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Line item deleted successfully. Issued proposal versions and client decision history were retained.',
                'items' => $result['items'],
                'paymentTerms' => $result['payment_terms'],
                'totals' => $result['totals'],
            ]);
        }

        return redirect()->back()->with('success', 'Line item deleted successfully. Issued proposal versions and client decision history were retained.');
    }

    private function normalizeDealLineItems(array $items): array
    {
        $normalized = [];
        $usedIds = [];

        foreach (array_values($items) as $index => $item) {
            $item = is_array($item) ? $item : ['name' => (string) $item];
            $itemId = filled($item['id'] ?? null) ? (string) $item['id'] : 'legacy-'.($index + 1);
            $baseId = $itemId;
            $suffix = 1;

            while (isset($usedIds[$itemId])) {
                $itemId = $baseId.'-'.$suffix++;
            }

            $usedIds[$itemId] = true;
            $item['id'] = $itemId;
            $normalized[] = $item;
        }

        return $normalized;
    }

    private function serviceEngagementBehavior(Service $service): ?string
    {
        $behavior = strtolower(trim((string) ($service->engagement_behavior ?: $service->engagement_type)));
        if ($behavior !== '') {
            return $behavior;
        }

        $structure = $service->engagement_structure;
        if (is_string($structure)) {
            $decoded = json_decode($structure, true);
            $structure = is_array($decoded) ? $decoded : [$structure];
        }
        $structure = array_map(static fn ($value) => strtolower((string) $value), is_array($structure) ? $structure : []);
        $hasRegular = collect($structure)->contains(fn ($value) => str_contains($value, 'regular') || str_contains($value, 'retainer'));
        $hasProject = collect($structure)->contains(fn ($value) => str_contains($value, 'project'));

        if ($hasRegular && $hasProject) {
            return 'hybrid';
        }

        return $hasRegular ? 'regular' : ($hasProject ? 'project' : null);
    }

    private function buildSavedDealLineItems(
        array $services,
        array $products = [],
        array $existingItems = []
    ): array {
        $selections = [];
        foreach (['SERVICE' => $services, 'PRODUCT' => $products] as $type => $items) {
            foreach ($items as $item) {
                $name = trim((string) (is_array($item) ? ($item['name'] ?? '') : $item));
                if ($name === '' || $name === 'Others') {
                    continue;
                }

                $selections[] = [
                    'type' => $type,
                    'id' => is_array($item) ? ($item['id'] ?? $item['service_id'] ?? $item['product_id'] ?? null) : null,
                    'name' => $name,
                    'custom' => is_array($item) && !empty($item['custom']),
                    'legacy' => is_array($item) && !empty($item['legacy']),
                ];
            }
        }

        $names = collect($selections)->pluck('name')->unique()->values()->all();
        $serviceIds = collect($selections)->where('type', 'SERVICE')->pluck('id')->filter()->unique()->values()->all();
        $productIds = collect($selections)->where('type', 'PRODUCT')->pluck('id')->filter()->unique()->values()->all();
        $servicesById = $serviceIds === [] ? collect() : Service::query()->with('activeVersion')->whereIn('id', $serviceIds)->get()->keyBy('id');
        $productsById = $productIds === [] ? collect() : Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
        $servicesByName = $names === []
            ? collect()
            : Service::query()
                ->with('activeVersion')
                ->where(function ($query) use ($names): void {
                    $query->whereIn('service_name', $names)->orWhereIn('name', $names);
                })
                ->get()
                ->keyBy(fn (Service $service): string => mb_strtolower(trim((string) ($service->service_name ?: $service->name))));
        $productsByName = $names === []
            ? collect()
            : Product::query()
                ->where(function ($query) use ($names): void {
                    $query->whereIn('product_name', $names)->orWhereIn('name', $names);
                })
                ->get()
                ->keyBy(fn (Product $product): string => mb_strtolower(trim((string) ($product->product_name ?: $product->name))));

        $existingByKey = [];
        foreach ($existingItems as $item) {
            if (!is_array($item) || blank($item['name'] ?? null)) {
                continue;
            }

            $itemType = strtoupper((string) ($item['type'] ?? 'SERVICE'));
            $catalogId = $itemType === 'PRODUCT' ? ($item['product_id'] ?? null) : ($item['service_id'] ?? null);
            $key = $itemType.'|'.($catalogId ? 'catalog:'.$catalogId : mb_strtolower(trim((string) $item['name'])));
            $existingByKey[$key][] = $item;
        }

        return array_map(function (array $selection) use ($servicesByName, $productsByName, $servicesById, $productsById, &$existingByKey, $existingItems): array {
            $type = $selection['type'];
            $name = $selection['name'];
            $catalogItem = $selection['custom'] ? null : ($type === 'PRODUCT'
                ? ($productsById->get($selection['id']) ?: (! $selection['custom'] ? $productsByName->get(mb_strtolower($name)) : null))
                : ($servicesById->get($selection['id']) ?: (! $selection['custom'] ? $servicesByName->get(mb_strtolower($name)) : null)));
            $resolvedId = $selection['id'] ?: $catalogItem?->id;
            $key = $type.'|'.($resolvedId ? 'catalog:'.$resolvedId : mb_strtolower($name));
            $legacyKey = $type.'|'.mb_strtolower($name);
            $existing = isset($existingByKey[$key]) ? array_shift($existingByKey[$key]) : [];
            if ($existing === [] && isset($existingByKey[$legacyKey])) {
                foreach ($existingByKey[$legacyKey] as $offset => $candidate) {
                    if ($selection['legacy'] || (! $selection['custom'] && empty($candidate['custom']))) {
                        $existing = $candidate;
                        unset($existingByKey[$legacyKey][$offset]);
                        break;
                    }
                }
            }

            if ($existing === [] && $selection['legacy']) {
                $existing = collect($existingItems)->first(fn ($row) => is_array($row)
                    && strtoupper((string) ($row['type'] ?? 'SERVICE')) === $type && ($row['name'] ?? '') === $name) ?? [];
            }

            $savedPrice = null;
            foreach (['unitPrice', 'unit_price', 'price', 'amount'] as $priceKey) {
                if (array_key_exists($priceKey, $existing) && is_numeric($existing[$priceKey])) {
                    $savedPrice = (float) $existing[$priceKey];
                    break;
                }
            }

            $catalogPrice = null;
            if ($catalogItem instanceof Service && ! $selection['custom']) {
                $catalogPrice = $catalogItem->activeVersion?->standard_price;
                if ($catalogPrice === null) {
                    $catalogPrice = $catalogItem->price_fee;
                }
            } elseif ($catalogItem instanceof Product && ! $selection['custom']) {
                $catalogPrice = $catalogItem->price;
            }

            $price = $savedPrice ?? (is_numeric($catalogPrice) ? (float) $catalogPrice : null);
            $catalogDescription = $catalogItem instanceof Service
                ? ($catalogItem->activeVersion?->scope_of_work ?: ($catalogItem->service_description ?: $catalogItem->short_description))
                : ($catalogItem instanceof Product
                    ? ($catalogItem->scope_of_work ?: ($catalogItem->product_description ?: $catalogItem->description))
                    : '');
            $lineItem = array_merge($existing, [
                'id' => $existing['id'] ?? (string) Str::uuid(),
                'type' => $type,
                'name' => $name,
                'custom' => $selection['custom'],
                'description' => filled($existing['description'] ?? null)
                    ? $existing['description']
                    : ($catalogDescription ?: ''),
                'qty' => $existing['qty'] ?? 1,
                'unit' => $existing['unit'] ?? ($catalogItem->unit ?? ($type === 'PRODUCT' ? 'document' : 'lot')),
                'unitPrice' => $price,
                'price' => $price,
            ]);

            if ($selection['custom'] && ! $selection['legacy']) {
                unset($lineItem['service_id'], $lineItem['service_version_id'], $lineItem['product_id'], $lineItem['product_version_id']);
            }

            if ($catalogItem instanceof Service && ! $selection['custom']) {
                $serviceAreas = $catalogItem->service_area;
                if (is_string($serviceAreas)) {
                    $decodedAreas = json_decode($serviceAreas, true);
                    $serviceAreas = is_array($decodedAreas) ? $decodedAreas : [$serviceAreas];
                }
                $lineItem['service_identifications'] = app(\App\Services\DealCatalogSelection::class)->serviceIdentifications($catalogItem);
                $lineItem['service_area_id'] = $catalogItem->service_area_id;
                $lineItem['service_id'] = $existing['service_id'] ?? $catalogItem->id;
                $lineItem['service_version_id'] = $existing['service_version_id'] ?? $catalogItem->active_version_id;
                $lineItem['service_area'] = $existing['service_area'] ?? (is_array($serviceAreas) ? $serviceAreas : []);
                $lineItem['engagement_behavior'] = $existing['engagement_behavior'] ?? $this->serviceEngagementBehavior($catalogItem);
                $lineItem['tax_treatment'] = $existing['tax_treatment'] ?? ($catalogItem->activeVersion?->tax_treatment ?: $catalogItem->tax_type);
                $lineItem['short_description'] = $existing['short_description'] ?? ($catalogItem->activeVersion?->client_description ?: ($catalogItem->activeVersion?->short_name ?: $catalogItem->short_description));
            } elseif ($catalogItem instanceof Product && ! $selection['custom']) {
                $lineItem['product_id'] = $existing['product_id'] ?? $catalogItem->id;
                $lineItem['product_version_id'] = $existing['product_version_id'] ?? ($catalogItem->active_version_id ?? null);
                $lineItem['tax_treatment'] = $existing['tax_treatment'] ?? ($catalogItem->tax_treatment ?: $catalogItem->tax_type);
                $lineItem['short_description'] = $existing['short_description'] ?? ($catalogItem->short_description ?: $catalogItem->product_description);
            }

            return $lineItem;
        }, $selections);
    }


    /*
    |--------------------------------------------------------------------------
    | Save Proposal
    |--------------------------------------------------------------------------
    */

    public function storeProposal(
        Request $request,
        $id
    ) {
        $deal = Deal::findOrFail($id);

        $validated = $request->validate([
            'recipient_email' => [
                'nullable',
                'email',
            ],

            'subject' => [
                'nullable',
                'string',
                'max:255',
            ],

            'introduction' => [
                'nullable',
                'string',
            ],

            'scope' => [
                'nullable',
                'string',
            ],

            'terms' => [
                'nullable',
                'string',
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'tax' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $proposal = DB::transaction(function () use ($deal, $validated): DealProposal {
            $currentProposal = $deal->proposals()
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($currentProposal && ! in_array(strtolower((string) $currentProposal->status), ['draft', 'saved'], true)) {
                $currentProposal->forceFill(['is_current' => false])->save();
                $nextVersion = (int) $deal->proposals()->max('proposal_version') + 1;
                $revision = $currentProposal->replicate([
                    'status',
                    'is_current',
                    'line_items_snapshot',
                    'catalog_snapshot',
                    'revision_request_key',
                    'client_access_token',
                    'client_access_expires_at',
                    'client_form_sent_to_email',
                    'client_form_sent_at',
                    'client_approved_at',
                    'client_approved_by_name',
                    'client_approval_note',
                    'quotation_status',
                    'quotation_finance_file_path',
                    'quotation_client_file_path',
                    'quotation_finance_started_at',
                    'quotation_approved_at',
                    'quotation_approved_by_name',
                    'invoice_status',
                    'invoice_file_path',
                    'invoice_generated_at',
                    'invoice_uploaded_at',
                    'payment_confirmed_at',
                    'payment_confirmed_by_name',
                ]);
                $revision->fill($validated);
                $revision->forceFill([
                    'proposal_version' => $nextVersion,
                    'is_current' => true,
                    'status' => 'Draft',
                ]);
                $revision->save();

                return $revision;
            }

            if ($currentProposal) {
                $currentProposal->fill($validated);
                $currentProposal->save();

                return $currentProposal;
            }

            return $deal->proposals()->create([
                ...$validated,
                'proposal_version' => 1,
                'is_current' => true,
                'status' => 'Draft',
            ]);
        });

        app(DealHistoryService::class)->logProposalGenerated($deal, $proposal);

        $msg = $proposal->proposal_version > 1
            ? "Proposal revision (v{$proposal->proposal_version}) created and saved successfully."
            : 'Proposal saved successfully.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'proposal' => $proposal,
            ]);
        }

        return redirect()
            ->route(
                'deals.show',
                ['id' => $deal->id]
            )
            ->with(
                'success',
                $msg
            );
    }

    public function storeProposalRevision(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'revision_request_key' => ['required', 'uuid'],
            'subject' => ['nullable', 'string', 'max:255'],
            'introduction' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
        ]);

        $revision = DB::transaction(function () use ($id, $validated): DealProposal {
            $existingRevision = DealProposal::query()
                ->where('revision_request_key', $validated['revision_request_key'])
                ->first();

            if ($existingRevision) {
                abort_unless((int) $existingRevision->deal_id === $id, 409, 'This revision request was already used for another deal.');

                return $existingRevision;
            }

            $deal = Deal::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $currentProposal = $deal->proposals()
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if (! $currentProposal) {
                return $deal->proposals()->create([
                    ...collect($validated)->except('revision_request_key')->all(),
                    'proposal_version' => 1,
                    'is_current' => true,
                    'revision_request_key' => $validated['revision_request_key'],
                    'status' => 'Draft',
                ]);
            }

            if (in_array(strtolower((string) $currentProposal->status), ['draft', 'saved'], true)) {
                $currentProposal->fill(collect($validated)->except('revision_request_key')->all());
                $currentProposal->save();
            }

            $currentProposal->forceFill(['is_current' => false])->save();
            $nextVersion = (int) $deal->proposals()->max('proposal_version') + 1;
            $revision = $currentProposal->replicate([
                'status',
                'is_current',
                'line_items_snapshot',
                'catalog_snapshot',
                'revision_request_key',
                'client_access_token',
                'client_access_expires_at',
                'client_form_sent_to_email',
                'client_form_sent_at',
                'client_approved_at',
                'client_approved_by_name',
                'client_approval_note',
                'quotation_status',
                'quotation_finance_file_path',
                'quotation_client_file_path',
                'quotation_finance_started_at',
                'quotation_approved_at',
                'quotation_approved_by_name',
                'invoice_status',
                'invoice_file_path',
                'invoice_generated_at',
                'invoice_uploaded_at',
                'payment_confirmed_at',
                'payment_confirmed_by_name',
            ]);
            $revision->forceFill([
                'proposal_version' => $nextVersion,
                'is_current' => true,
                'revision_request_key' => $validated['revision_request_key'],
                'status' => 'Draft',
            ])->save();

            return $revision;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'proposal' => $revision,
            ], 201);
        }

        return redirect()
            ->route('deals.show', $id)
            ->with('success', 'Proposal revision V'.$revision->proposal_version.' created as a draft.');
    }


    /*
    |--------------------------------------------------------------------------
    | Send Proposal
    |--------------------------------------------------------------------------
    */

    public function sendProposal(Request $request, $id)
    {
        return app(DealProposalController::class)->sendClientProposal($request, Deal::findOrFail($id));
    }
    /*
    |--------------------------------------------------------------------------
    | Regular Workspace
    |--------------------------------------------------------------------------
    */

    public function regular($id)
    {
        $deal = Deal::find($id);
        if (! $deal) {
            return redirect()->route('deals.index')->with('info', 'The requested deal record is no longer available.');
        }

        if (! $deal->isStartCompleted()) {
            return redirect()
                ->to(route('deals.show', $deal->id) . '#start')
                ->with('error', 'Please complete the START workflow before accessing Engagement.');
        }

        if (! $deal->hasRegularEngagement()) {
            return redirect()->route('deals.show', $deal->id)->with('info', 'This deal is configured as a Project Engagement.');
        }

        $regular = \App\Models\Project::query()
            ->where('deal_id', $deal->id)
            ->where('engagement_type', 'like', '%regular%')
            ->first();

        if (! $regular) {
            $regular = app(\App\Services\ProjectProvisioner::class)->createOrSyncFromDeal($deal);
        }

        if ($regular) {
            return redirect()->route('regular.show', $regular->id);
        }

        return redirect()->route('deals.show', $deal->id)->with('info', 'No regular engagement provisioned for this deal yet.');
    }
    

    /*
    |--------------------------------------------------------------------------
    | Project Workspace
    |--------------------------------------------------------------------------
    */

    public function project($id)
    {
        $deal = Deal::find($id);
        if (! $deal) {
            return redirect()->route('deals.index')->with('info', 'The requested deal record is no longer available.');
        }

        if (! $deal->isStartCompleted()) {
            return redirect()
                ->to(route('deals.show', $deal->id) . '#start')
                ->with('error', 'Please complete the START workflow before accessing Engagement.');
        }

        if (! $deal->hasProjectEngagement()) {
            return redirect()->route('deals.show', $deal->id)->with('info', 'This deal is configured as a Regular Retainer Engagement.');
        }

        $project = \App\Models\Project::query()
            ->where('deal_id', $deal->id)
            ->where(function ($q) {
                $q->where('engagement_type', 'like', '%project%')
                  ->orWhere('engagement_type', 'like', '%hybrid%');
            })
            ->first();

        if (! $project) {
            $project = app(\App\Services\ProjectProvisioner::class)->createOrSyncFromDeal($deal);
        }

        if ($project) {
            return redirect()->route('project.show', $project->id);
        }

        return redirect()->route('deals.show', $deal->id)->with('info', 'No project workspace provisioned for this deal yet.');
    }


    /*
    |--------------------------------------------------------------------------
    | Project Registry
    |--------------------------------------------------------------------------
    */

    public function projects()
    {
        $deals = Deal::latest()->get();

        $projects = $deals->map(
            function ($deal, $index) {

                $projectNumber =
                    'PROJ-' .
                    date('Y') .
                    '-' .
                    str_pad(
                        $index + 101,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                $dealCode =
                    $deal->deal_code
                    ?: 'CONDEAL-' .
                    date('Y') .
                    '-' .
                    str_pad(
                        $deal->id,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                return [
                    'id' =>
                        $deal->id,

                    'project_number' =>
                        $projectNumber,

                    'project_name' =>
                        $deal->deal_title
                        ?: 'Business Compliance Project',

                    'deal_code' =>
                        $dealCode,

                    'company' =>
                        $deal->company,

                    'phase' =>
                        $deal->pipeline_stage
                        ?: 'In Progress',

                    'owner' =>
                        $deal->owner_name,

                    'target' =>
                        $deal->expected_close
                        ? Carbon::parse(
                            $deal->expected_close
                        )->format('M d, Y')
                        : null,
                ];
            }
        );

        return view(
            'projects.index',
            compact('projects')
        );
    }

     /*
|--------------------------------------------------------------------------
| Save Project Scope of Work
|--------------------------------------------------------------------------
*/

public function saveScope(Request $request, $id)
{
    $deal = Deal::findOrFail($id);

    $request->validate([
    'within_scope' => ['nullable', 'array'],
    'out_of_scope' => ['nullable', 'array'],
    'record_custodian' => ['nullable', 'string'],
    'date_recorded' => ['nullable', 'date'],
    'date_signed' => ['nullable', 'date'],
    ]);

    $deal->update([
    'record_custodian' => $request->record_custodian,
    'date_recorded' => $request->date_recorded,
    'date_signed' => $request->date_signed,
    ]);

    // Remove old scope items for this Deal
    $deal->projectScopeItems()->delete();

    // Save WITHIN SCOPE
    foreach ($request->input('within_scope', []) as $item) {
        if (
            blank($item['main_task'] ?? null) &&
            blank($item['sub_task'] ?? null)
        ) {
            continue;
        }

        $deal->projectScopeItems()->create([
            'scope_type' => 'within_scope',
            'main_task' => $item['main_task'] ?? null,
            'sub_task' => $item['sub_task'] ?? null,
            'responsible' => $item['responsible'] ?? null,
            'duration' => $item['duration'] ?? null,
            'start_date' => $item['start_date'] ?? null,
            'end_date' => $item['end_date'] ?? null,
            'status' => $item['status'] ?? null,
            'remarks' => $item['remarks'] ?? null,
        ]);
    }

    // Save OUT OF SCOPE
    foreach ($request->input('out_of_scope', []) as $item) {
        if (
            blank($item['main_task'] ?? null) &&
            blank($item['sub_task'] ?? null)
        ) {
            continue;
        }

            $deal->projectScopeItems()->create([
        'scope_type' => 'out_of_scope',
        'main_task' => $item['main_task'] ?? null,
        'sub_task' => $item['sub_task'] ?? null,
        'responsible' => $item['responsible'] ?? null,
        'duration' => $item['duration'] ?? null,
        'start_date' => $item['start_date'] ?? null,
        'end_date' => $item['end_date'] ?? null,
        'status' => $item['status'] ?? null,
        'remarks' => $item['remarks'] ?? null,
        ]);
        }

     $deal->update([
        'record_custodian' => $request->record_custodian,
        'date_recorded' => $request->date_recorded,
        'date_signed' => $request->date_signed,
    ]);


    return redirect()
        ->route('deals.project', ['id' => $deal->id])
        ->with('success', 'Scope of Work saved successfully.');
}



    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: approveDeletion
    |--------------------------------------------------------------------------
    */
    public function approveDeletion(Request $request, int $id): RedirectResponse
    {
        if (! $this->isDealReviewer($request)) {
            return redirect()
                ->route('admin.dashboard.section', 'deals')
                ->with('error', 'You do not have permission to approve deal deletions.');
        }

        $deal = Deal::find($id);
        if (! $deal) {
            return redirect()
                ->route('admin.dashboard.section', 'deals')
                ->with('error', 'Deal not found.');
        }

        $deal->delete();

        return redirect()
            ->route('admin.dashboard.section', 'deals')
            ->with('success', 'Deal permanently deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: rejectDeletion
    |--------------------------------------------------------------------------
    */
    public function rejectDeletion(Request $request, int $id): RedirectResponse
    {
        if (! $this->isDealReviewer($request)) {
            return redirect()
                ->route('admin.dashboard.section', 'deals')
                ->with('error', 'You do not have permission to reject deal deletions.');
        }

        $deal = Deal::find($id);
        if (! $deal) {
            return redirect()
                ->route('admin.dashboard.section', 'deals')
                ->with('error', 'Deal not found.');
        }

        $deal->forceFill([
            'delete_request_status' => 'rejected',
        ])->save();

        return redirect()
            ->route('admin.dashboard.section', 'deals')
            ->with('success', 'Deal deletion request rejected.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: approve
    |--------------------------------------------------------------------------
    */
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
            });

            // Create a shell workspace immediately so the START form is visible
            // in the deal show page right after approval. The START form starts
            // as 'pending' (editable by the team). It will be auto-submitted to
            // 'pending_approval' when the deal reaches the Closed Won stage.
            try {
                $freshDeal = $deal->fresh();
                if ($freshDeal && $freshDeal->projects()->doesntExist()) {
                    $this->projectProvisioner->createShellWorkspace($freshDeal);
                    // Reset to 'pending' so the team can fill it before Closed Won.
                    // If it's a Hybrid deal, two workspaces are created. Iterate over all.
                    foreach ($freshDeal->projects()->get() as $shell) {
                        $start = $shell->starts()->latest()->first();
                        if ($start && strtolower((string) $start->status) === 'pending_approval') {
                            $start->forceFill(['status' => 'pending'])->save();
                        }
                    }
                }
            } catch (Throwable) {
                // Non-critical — shell creation failure should not block deal approval
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('deals.show', $deal->id)
                ->with('error', 'We could not approve the deal right now. Please try again.');
        }

        return redirect()
            ->route('deals.show', $deal->id)
            ->with('success', 'Deal qualified and approved successfully. START form is now available.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: approveInternalReview
    |--------------------------------------------------------------------------
    */
    public function approveInternalReview(Request $request, int $id): RedirectResponse
    {
        if (! $this->isDealReviewer($request)) {
            return redirect()
                ->route('deals.show', $id)
                ->with('error', 'You do not have permission to approve this deal.');
        }

        $deal = Deal::query()->findOrFail($id);

        try {
            DB::transaction(function () use ($deal, $request): void {
                $deal->forceFill([
                    'stage' => 'Consultation',
                    'stage_id' => Schema::hasColumn('deals', 'stage_id') ? $this->resolveStageIdByName('Consultation') : $deal->stage_id,
                ])->save();
            });
        } catch (Throwable $exception) {
            report($exception);
            return redirect()
                ->route('deals.show', $deal->id)
                ->with('error', 'We could not approve the internal review right now. Please try again.');
        }

        return redirect()
            ->route('deals.show', $deal->id)
            ->with('success', 'Internal review approved. Deal moved to Consultation stage.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: reject
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: saveDraft
    |--------------------------------------------------------------------------
    */
    public function saveDraft(Request $request): RedirectResponse
    {
        $draft = $request->except('_token');
        $request->session()->put('deals.preview_payload', $draft);

        return redirect()->route('deals.index')->with('success', 'Deal draft saved to mock session.');
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: preview
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: previewPage
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: downloadPdf
    |--------------------------------------------------------------------------
    */
    public function downloadPdf(Request $request, int $id)
    {
        $deal = Deal::query()->with(['proposal', 'contact', 'company', 'account', 'assignedFinance'])->findOrFail($id);

        if (! $this->canViewDeal($deal)) {
            return redirect()
                ->route('deals.index')
                ->with('deal_access_denied', "You don't have access to this deal.");
        }

        if ($request->boolean('autoprint')) {
            $payload = $this->buildDealPdfPayload($deal);
            $payload['autoPrint'] = true;
            return view('pdf.deal', $payload);
        }

        if ($deal->proposal && $request->query('view') !== 'deal_form') {
            return app(DealProposalController::class)->download($deal);
        }

        $payload = $this->buildDealPdfPayload($deal);
        $payload['autoPrint'] = false;
        $filename = 'deal-' . ($deal->deal_code ? Str::slug($deal->deal_code) : $deal->id) . '.pdf';

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('pdf.deal', $payload)->setPaper('a4', 'portrait');
            return $pdf->download($filename);
        }

        return view('pdf.deal', $payload);
    }

    /**
     * Download Deal Form PDF
     */
    public function downloadDealForm(Request $request, int $id)
    {
        $deal = Deal::query()->with(['proposal', 'contact', 'company', 'account', 'assignedFinance'])->findOrFail($id);

        if (! $this->canViewDeal($deal)) {
            return redirect()
                ->route('deals.index')
                ->with('deal_access_denied', "You don't have access to this deal.");
        }

        $payload = $this->buildDealPdfPayload($deal);
        $payload['autoPrint'] = false;
        $filename = 'deal-form-' . ($deal->deal_code ? Str::slug($deal->deal_code) : $deal->id) . '.pdf';

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('pdf.deal', $payload)->setPaper('a4', 'portrait');
            return $pdf->download($filename);
        }

        return view('pdf.deal', $payload);
    }

    /**
     * Download Consulting Document / Form
     */
    public function downloadConsulting(Request $request, int $id)
    {
        $deal = Deal::query()->with(['contact', 'companyRecord', 'account'])->findOrFail($id);

        if (! $this->canViewDeal($deal)) {
            return redirect()
                ->route('deals.index')
                ->with('deal_access_denied', "You don't have access to this deal.");
        }

        $hasConsultations = (!empty($deal->consultation_records) && is_array($deal->consultation_records) && count($deal->consultation_records) > 0)
            || filled($deal->consultant_notes)
            || filled($deal->consultation_date);

        if (! $hasConsultations) {
            return redirect()
                ->route('deals.show', ['id' => $deal->id])
                ->with('warning', 'Consulting document is not available yet. Prerequisite required: A consultation meeting or consulting notes must be recorded in the Consultation tab before downloading the consulting document.');
        }

        $payload = [
            'deal' => $deal,
            'contact' => $deal->contact,
            'company' => $deal->companyRecord ?: $deal->company,
            'consultationRecords' => $deal->consultation_records ?? [],
            'consultantName' => $deal->assigned_consultant ?: ($deal->lead_consultant ?: ($deal->owner_name ?: 'Consultant')),
            'associateName' => $deal->assigned_associate ?: ($deal->lead_associate_assigned ?: '-'),
            'contactName' => $deal->contact ? trim($deal->contact->first_name . ' ' . $deal->contact->last_name) : ($deal->contact_name ?: '-'),
            'contactPosition' => $deal->getResolvedContactPosition(),
            'contactNumber' => $deal->getResolvedContactNumber(),
            'companyName' => $deal->companyRecord?->company_name ?: ($deal->company ?: ($deal->customer_type === 'Individual' ? 'Individual Client' : '-')),
            'date' => $deal->consultation_date ? $deal->consultation_date->format('F d, Y') : ($deal->created_at ? $deal->created_at->format('F d, Y') : date('F d, Y')),
        ];

        $filename = 'consulting-form-' . ($deal->deal_code ? Str::slug($deal->deal_code) : $deal->id) . '.pdf';

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('pdf.consulting', $payload)->setPaper('a4', 'portrait');
            return $pdf->download($filename);
        }

        return view('pdf.consulting', $payload);
    }

    /**
     * Download START Document
     */
    public function downloadStart(Request $request, int $id)
    {
        $deal = Deal::query()->with(['contact', 'companyRecord', 'project', 'projects'])->findOrFail($id);

        if (! $this->canViewDeal($deal)) {
            return redirect()
                ->route('deals.index')
                ->with('deal_access_denied', "You don't have access to this deal.");
        }

        $isStartCompleted = $deal->isStartCompleted();
        $hasActiveStart = ($deal->startRecords && $deal->startRecords->count() > 0) || !empty($deal->start_batches);

        if (! $isStartCompleted && ! $hasActiveStart && ! $deal->project) {
            return redirect()
                ->route('deals.show', ['id' => $deal->id])
                ->with('warning', 'START document is not available yet. Prerequisite required: Complete the START workflow and required verification steps in the START tab before downloading the START document.');
        }

        if ($deal->project) {
            return app(ProjectController::class)->downloadStartPdf($deal->project);
        }

        if ($deal->projects && $deal->projects->first()) {
            return app(ProjectController::class)->downloadStartPdf($deal->projects->first());
        }

        // If deal has start batches directly, render project.pdf.start
        $formatList = static function (mixed $value, array $itemKeys = ['name']): string {
            if (!is_array($value)) {
                return trim((string) ($value ?? ''));
            }

            return collect($value)
                ->map(function ($item) use ($itemKeys): string {
                    if (is_scalar($item)) {
                        return trim((string) $item);
                    }

                    foreach ($itemKeys as $key) {
                        if (is_array($item) && filled($item[$key] ?? null)) {
                            return trim((string) $item[$key]);
                        }
                    }

                    return '';
                })
                ->filter()
                ->implode(', ');
        };
        $servicesProducts = $formatList($deal->services_products, ['name', 'service_name', 'product_name']);
        $contactName = trim(
            implode(' ', array_filter([
                $deal->salutation,
                $deal->first_name,
                $deal->middle_initial,
                $deal->last_name,
                $deal->name_extension
            ]))
        ) ?: ($deal->primary_contact_name ?: ($deal->contact?->full_name ?: ($deal->contact ? trim($deal->contact->first_name . ' ' . $deal->contact->last_name) : '-')));

        $businessName = $deal->company ?: ($deal->companyRecord?->company_name ?: '-');
        $servicesProducts = $formatList($deal->services_products, ['name', 'service_name', 'product_name', 'title']);
        $servicesText = is_array($deal->services) ? implode(', ', array_filter($deal->services)) : ($deal->services ?: ($formatList($deal->services_needed, ['name', 'service_name']) ?: $servicesProducts));
        $productsText = is_array($deal->products) ? implode(', ', array_filter($deal->products)) : ($deal->products ?: ($formatList($deal->products_needed, ['name', 'product_name']) ?: ($deal->product_type ?: '—')));
        $serviceAreaText = is_array($deal->service_areas) ? implode(', ', array_filter($deal->service_areas)) : ($deal->service_area ?: $formatList($deal->service_areas, ['name', 'service_area']));

        $project = new \App\Models\Project([
            'deal_id' => $deal->id,
            'client_name' => $contactName,
            'business_name' => $businessName,
            'service_area' => $serviceAreaText ?: '—',
            'products' => $productsText ?: '—',
            'services' => $servicesText ?: '—',
            'engagement_type' => $deal->engagement_type ?: ($deal->hasProjectEngagement() ? 'Project' : ($deal->hasRegularEngagement() ? 'Retainer' : 'Project')),
            'project_name' => $deal->deal_title ?: $deal->deal_name,
            'project_code' => $deal->deal_code,
        ]);
        $project->setRelation('contact', $deal->contact);
        $project->setRelation('deal', $deal);

        // Dynamic KYC
        $kycData = app(\App\Services\ProjectProvisioner::class)->resolveDealStartKyc($deal);
        $startKycOrganization = $kycData['organization_type'] ?? 'juridical';
        $startKycSole = collect($kycData['sole'] ?? []);
        $startKycIndividual = collect($kycData['individual'] ?? []);
        $startKycJuridical = collect($kycData['juridical'] ?? []);

        // Dynamic Clearance
        $startClearance = [
            'assigned_team_lead' => $deal->team_lead ?? '',
            'assigned_team_lead_signature' => '',
            'lead_consultant_confirmed' => $deal->lead_consultant ?: ($deal->assigned_consultant ?: 'John Kelly Abalde'),
            'lead_consultant_signature' => '',
            'lead_associate_assigned' => $deal->lead_associate ?: ($deal->assigned_associate ?: 'John Kelly Abalde'),
            'lead_associate_signature' => '',
            'sales_marketing' => $deal->internal_sales_marketing ?: ($deal->sales_marketing ?: 'Mari Louise Chua'),
            'sales_marketing_signature' => '',
            'record_custodian_name' => 'Record Custodian',
            'record_custodian_signature' => '',
            'date_recorded' => optional($deal->created_at)->format('m/d/Y') ?: now()->format('m/d/Y'),
            'date_signed' => '',
        ];

        // Dynamic START requirements
        $dealStartBatches = collect($deal->startRecords ?? [])->whenEmpty(fn () => collect($deal->start_batches ?? []));
        $startReqs = collect();
        if ($dealStartBatches->isNotEmpty()) {
            foreach ($dealStartBatches as $batch) {
                $items = data_get($batch, 'activated_items', []);
                if (!empty($items) && (is_array($items) || $items instanceof \Traversable)) {
                    foreach ($items as $item) {
                        $startReqs->push([
                            'requirement' => data_get($item, 'name') ?: (data_get($item, 'requirement') ?: (is_string($item) ? $item : 'Requirement')),
                            'notes' => data_get($batch, 'notes') ?: (data_get($batch, 'status') ?: '—'),
                            'purpose' => 'START intake',
                            'provided_by' => 'Client',
                            'submitted_to' => 'Sales & Marketing',
                            'assigned_to' => data_get($batch, 'assignee') ?: ($deal->assigned_associate ?? 'Project Team'),
                            'timeline' => data_get($batch, 'opened_date') ? \Illuminate\Support\Carbon::parse(data_get($batch, 'opened_date'))->format('m/d/Y') : 'To be scheduled',
                        ]);
                    }
                } else {
                    $startReqs->push([
                        'requirement' => data_get($batch, 'batch_name') ?: (data_get($batch, 'start_code') ?: 'START intake'),
                        'notes' => data_get($batch, 'notes') ?: (data_get($batch, 'status') ?: '—'),
                        'purpose' => 'START intake',
                        'provided_by' => 'Client',
                        'submitted_to' => 'Sales & Marketing',
                        'assigned_to' => data_get($batch, 'assignee') ?: ($deal->assigned_associate ?? 'Project Team'),
                        'timeline' => data_get($batch, 'opened_date') ? \Illuminate\Support\Carbon::parse(data_get($batch, 'opened_date'))->format('m/d/Y') : 'To be scheduled',
                    ]);
                }
            }
        }
        if ($startReqs->isEmpty()) {
            $startReqs = collect([[
                'requirement' => 'Initial intake and supporting documents',
                'notes' => '',
                'purpose' => 'START intake',
                'provided_by' => 'Client',
                'submitted_to' => 'Sales & Marketing',
                'assigned_to' => $deal->lead_consultant ?: ($deal->assigned_consultant ?: 'John Kelly Abalde'),
                'timeline' => 'To be scheduled',
            ]]);
        }

        // Determine START status
        $currentStartStatus = 'Draft';
        if ($deal->isStartCompleted()) {
            $currentStartStatus = 'Completed';
        } elseif ($dealStartBatches->isNotEmpty()) {
            $latestBatch = $dealStartBatches->sortByDesc('id')->first();
            $currentStartStatus = $latestBatch->status ?: 'Draft';
        }

        $payload = [
            'project' => $project,
            'deal' => $deal,
            'start' => (object) [
                'form_date' => $deal->created_at ?: now(),
                'created_at' => $deal->created_at ?: now(),
                'date_started' => $deal->planned_start_date,
                'date_completed' => $deal->estimated_completion_date,
                'kyc_requirements' => $kycData,
                'engagement_requirements' => $startReqs->all(),
                'approval_steps' => [],
                'clearance' => $startClearance,
                'status' => $currentStartStatus,
            ],
            'contactName' => $contactName,
            'startKycOrganization' => $startKycOrganization,
            'startKycSole' => $startKycSole,
            'startKycIndividual' => $startKycIndividual,
            'startKycJuridical' => $startKycJuridical,
            'startReqs' => $startReqs->all(),
            'startApprovalSteps' => collect([]),
            'startClearance' => $startClearance,
            'currentStartStatus' => $currentStartStatus,
        ];

        $filename = 'start-form-' . ($deal->deal_code ? Str::slug($deal->deal_code) : $deal->id) . '.pdf';

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('project.pdf.start', $payload)->setPaper('a4', 'portrait');
            return $pdf->download($filename);
        }

        return view('project.pdf.start', $payload);
    }

    public function buildDealPdfPayload(int|Deal $deal): array
    {
        if (is_numeric($deal)) {
            $deal = Deal::query()->with(['proposal', 'contact', 'companyRecord', 'account', 'assignedFinance', 'dealContacts'])->findOrFail($deal);
        } else {
            $deal->loadMissing(['proposal', 'contact', 'companyRecord', 'account', 'assignedFinance', 'dealContacts']);
        }

        $contact = $deal->contact ?: ($deal->primaryContact ?: ($deal->dealContacts?->firstWhere('is_primary', true) ?: $deal->dealContacts?->first()));
        $companyRecord = $deal->companyRecord ?: $deal->account;

        // 1. Header
        $dealNo = $deal->deal_code ?: ('CONDEAL-' . sprintf('%04d', $deal->id));
        $headerDate = $deal->created_at ? $deal->created_at->format('m/d/Y') : date('m/d/Y');

        // 2. Deal Summary
        $dealName = $deal->deal_name ?: ($deal->deal_title ?: ($deal->deal_code ?: ('Deal #' . $deal->id)));
        $stage = $deal->stage ?: ($deal->pipeline_stage ?: 'Inquiry');
        $engagementType = $deal->engagement_type ?: ($deal->engagement_term ?: ($deal->billing_type ?: '-'));

        $totalVal = $deal->total_estimated_engagement_value ?? ($deal->amount ?? ($deal->deal_value ?? null));
        $totalValueFormatted = filled($totalVal) && is_numeric($totalVal)
            ? 'P' . number_format((float) $totalVal, 2)
            : (filled($totalVal) ? (string) $totalVal : 'P0.00');

        // 3. Contact Information
        $salutation = $deal->salutation ?: ($contact?->salutation ?: '-');
        $firstName = $deal->first_name ?: ($contact?->first_name ?: '-');

        $midInit = $deal->middle_initial;
        if (! filled($midInit) && filled($deal->middle_name)) {
            $midInit = strtoupper(substr(trim($deal->middle_name), 0, 1)) . '.';
        }
        if (! filled($midInit) && $contact) {
            $midInit = $contact->middle_initial;
            if (! filled($midInit) && filled($contact->middle_name)) {
                $midInit = strtoupper(substr(trim($contact->middle_name), 0, 1)) . '.';
            }
        }
        $middleInitial = filled($midInit) ? $midInit : '-';

        $lastName = $deal->last_name ?: ($contact?->last_name ?: '-');
        $nameExtension = $deal->name_extension ?: ($deal->suffix ?: ($contact?->name_extension ?: ($contact?->suffix ?: '-')));
        $sex = $deal->sex ?: ($deal->gender ?: ($contact?->sex ?: ($contact?->gender ?: '-')));

        $dob = $deal->date_of_birth ?: ($contact?->date_of_birth ?: null);
        $dobFormatted = filled($dob) ? \Illuminate\Support\Carbon::parse($dob)->format('M d, Y') : '-';

        $email = $deal->email ?: ($contact?->email ?: '-');
        $mobile = $deal->getResolvedContactNumber() ?: ($deal->mobile ?: ($deal->mobile_number ?: ($contact?->phone ?: '-')));
        $position = $deal->getResolvedContactPosition() ?: ($deal->position ?: ($contact?->position ?: '-'));
        $address = $deal->address ?: ($contact?->contact_address ?: ($contact?->address ?: '-'));
        $companyName = $deal->company ?: ($deal->company_name ?: ($companyRecord?->company_name ?: ($contact?->company_name ?: '-')));
        $companyAddress = $deal->company_address ?: ($companyRecord?->address ?: ($contact?->company_address ?: '-'));

        // 4. Service Identification
        $sa = $deal->service_area ?: ($deal->service_areas ?: ($deal->proposal?->service_type ?: null));
        if (is_array($sa)) {
            $sa = implode(', ', array_filter($sa));
        }
        $serviceArea = filled($sa) ? $sa : '-';

        $srv = $deal->services ?: ($deal->services_needed ?: ($deal->services_products ?: ($deal->proposal?->package_name ?: null)));
        if (is_array($srv)) {
            $srv = collect($srv)->map(fn ($item) => is_array($item) ? ($item['name'] ?? json_encode($item)) : $item)->filter()->implode(', ');
        }
        $services = filled($srv) ? $srv : '-';

        // 5. Products
        $prod = $deal->products ?: ($deal->products_needed ?: ($deal->proposal?->what_you_will_receive ?: null));
        if (is_array($prod)) {
            $prod = collect($prod)->map(fn ($item) => is_array($item) ? ($item['name'] ?? json_encode($item)) : $item)->filter()->implode(', ');
        }
        $products = filled($prod) ? $prod : '-';

        $scopeOfWork = $deal->scope_of_work ?: ($deal->proposal?->scope_of_service ?: '-');

        // 6. Client Requirements
        $clientRequirements = [
            [
                'requirement' => 'Client Contact Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Deal Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Business Information Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Client Information Form',
                'provided' => '-',
                'pending' => 'Yes',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Service Task Activation & Routing Tracker (Start)',
                'provided' => '-',
                'pending' => 'Yes',
                'required_action' => '-',
            ],
        ];

        // 7. Timeline & Assessment
        $plannedStart = $deal->planned_start_date ? \Illuminate\Support\Carbon::parse($deal->planned_start_date)->format('M d, Y') : ($deal->service_start_date ? \Illuminate\Support\Carbon::parse($deal->service_start_date)->format('M d, Y') : '-');
        $estDuration = $deal->estimated_duration ?: ($deal->estimated_duration_days ? (is_numeric($deal->estimated_duration_days) ? $deal->estimated_duration_days . ' days' : $deal->estimated_duration_days) : '-');
        $estCompletion = $deal->estimated_completion_date ? \Illuminate\Support\Carbon::parse($deal->estimated_completion_date)->format('M d, Y') : ($deal->expected_close ? \Illuminate\Support\Carbon::parse($deal->expected_close)->format('M d, Y') : '-');
        $confirmedDelivery = $deal->confirmed_delivery_date ? \Illuminate\Support\Carbon::parse($deal->confirmed_delivery_date)->format('M d, Y') : '-';
        $timelineNotes = $deal->timeline_notes ?: '-';
        $serviceComplexity = $deal->service_complexity ?: '-';

        $supp = $deal->support_required ?: ($deal->professional_support_required ?: null);
        if (is_array($supp)) {
            $supp = implode(', ', array_filter($supp));
        }
        $profSupportRequired = filled($supp) ? $supp : '-';

        // 8. Fees & Payment
        $pf = $deal->estimated_professional_fee ?? ($deal->est_professional_fee ?? ($deal->total_estimated_engagement_value ?? ($deal->amount ?? null)));
        $profFee = filled($pf) && is_numeric($pf) ? 'P' . number_format((float) $pf, 2) : (filled($pf) ? (string) $pf : '-');

        $gf = $deal->estimated_government_fees ?? ($deal->est_government_fee ?? null);
        $govFee = filled($gf) && is_numeric($gf) ? 'P' . number_format((float) $gf, 2) : (filled($gf) ? (string) $gf : 'P0.00');

        $ssf = $deal->estimated_service_support_fee ?? ($deal->est_service_support_fee ?? null);
        $supportFee = filled($ssf) && is_numeric($ssf) ? 'P' . number_format((float) $ssf, 2) : (filled($ssf) ? (string) $ssf : 'P0.00');

        $pt = $deal->payment_terms ?: ($deal->proposal?->payment_terms ?: ($deal->pricing_model ?: '-'));
        if (filled($deal->payment_terms_other)) {
            $pt .= ' / ' . $deal->payment_terms_other;
        }
        $paymentTerms = filled($pt) ? $pt : '-';

        // 9. Proposal & Internal Assignment
        $proposalDecision = $deal->proposal_decision ?: ($deal->proposal?->status ?: '-');
        $declineReason = $deal->decline_reason ?: ($deal->proposal?->rejection_reason ?: ($deal->rejection_reason ?: '-'));
        $department = $deal->service_department_unit ?: ($deal->service_department ?: ($deal->department ?: ($deal->assigned_team ?: '-')));
        $assignedConsultant = $deal->assigned_consultant ?: ($deal->lead_consultant ?: '-');
        $assignedAssociate = $deal->assigned_associate ?: ($deal->lead_associate_assigned ?: ($deal->lead_associate ?: '-'));

        // 10. Notes & Approval
        $consultantNotes = $deal->consultant_notes ?: '-';
        $associateNotes = $deal->associate_notes ?: '-';

        // 11. Signatures and Approval Records
        $preparedBy = $deal->prepared_by ?: ($deal->created_by ?: ($deal->owner_name ?: '-'));
        $reviewedBy = $deal->reviewed_by ?: ($deal->approved_by_name ?: '-');
        $internalDate = $deal->internal_date ? \Illuminate\Support\Carbon::parse($deal->internal_date)->format('M d, Y') : ($deal->approved_at ? \Illuminate\Support\Carbon::parse($deal->approved_at)->format('M d, Y') : ($deal->approval_date ? \Illuminate\Support\Carbon::parse($deal->approval_date)->format('M d, Y') : ($deal->created_at ? \Illuminate\Support\Carbon::parse($deal->created_at)->format('M d, Y') : date('M d, Y'))));
        $clientSignature = $deal->client_fullname_signature ?: ($contact ? trim($contact->first_name . ' ' . $contact->last_name) : ($deal->first_name ? trim($deal->first_name . ' ' . $deal->last_name) : '-'));
        $referredClosedBy = $deal->referred_closed_by ?: ($deal->referred_by ?: ($deal->owner_name ?: '-'));
        $salesMarketing = $deal->internal_sales_marketing ?: ($deal->sales_marketing ?: ($deal->sales_marketing_partner ?: '-'));
        $leadConsultant = $deal->lead_consultant ?: ($deal->assigned_consultant ?: '-');
        $leadAssociate = $deal->lead_associate_assigned ?: ($deal->lead_associate ?: ($deal->assigned_associate ?: '-'));
        $finance = $deal->internal_finance ?: ($deal->finance ?: ($deal->assignedFinance?->name ?: ($deal->finance_officer ?: '-')));
        $president = $deal->internal_president ?: ($deal->president ?: 'John Kelly Abalde');

        $logoPath = public_path('images/imaglogo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $dealFormData = [
            'deal_no' => $dealNo,
            'deal_form_no' => $dealNo,
            'reference_no' => $dealNo,
            'header_date' => $headerDate,
            'date' => $deal->created_at?->format('Y-m-d') ?: date('Y-m-d'),
            // Deal Summary
            'deal_name' => $dealName,
            'deal_title' => $dealName,
            'stage' => $stage,
            'engagement_type' => $engagementType,
            'engagement_term' => $engagementType,
            'billing_type' => $deal->billing_type ?: 'Milestone',
            'total_value' => $totalValueFormatted,
            'deal_value' => $totalVal ?: 0,
            // Contact Information
            'salutation' => $salutation,
            'first_name' => $firstName,
            'middle_initial' => $middleInitial,
            'last_name' => $lastName,
            'name_extension' => $nameExtension,
            'sex' => $sex,
            'date_of_birth' => $dobFormatted,
            'email' => $email,
            'email_address' => $email,
            'mobile' => $mobile,
            'mobile_number' => $mobile,
            'contact_number' => $mobile,
            'position' => $position,
            'contact_person_position' => $position,
            'address' => $address,
            'company' => $companyName,
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'contact_name' => $contact ? trim($contact->first_name . ' ' . $contact->last_name) : ($deal->first_name ? trim($deal->first_name . ' ' . $deal->last_name) : '-'),
            // Service Identification
            'service_area' => $serviceArea,
            'services' => $services,
            // Products
            'products' => $products,
            'products_deliverables' => $products,
            'scope_of_work' => $scopeOfWork,
            // Client Requirements
            'client_requirements' => $clientRequirements,
            'requirements_status_map' => [
                'client_contact_form' => $clientRequirements[0]['provided'] === 'Yes' ? 'provided' : 'pending',
                'deal_form' => 'provided',
                'business_information_form' => $clientRequirements[2]['provided'] === 'Yes' ? 'provided' : 'pending',
                'client_information_form' => $clientRequirements[3]['provided'] === 'Yes' ? 'provided' : 'pending',
                'service_task_activation_routing_tracker' => $clientRequirements[4]['provided'] === 'Yes' ? 'provided' : 'pending',
            ],
            // Timeline & Assessment
            'planned_start_date' => $plannedStart,
            'estimated_duration' => $estDuration,
            'estimated_completion_date' => $estCompletion,
            'confirmed_delivery_date' => $confirmedDelivery,
            'timeline_notes' => $timelineNotes,
            'service_complexity' => $serviceComplexity,
            'professional_support_required' => $profSupportRequired,
            'support_required' => $profSupportRequired,
            // Fees & Payment
            'professional_fee' => $profFee,
            'estimated_professional_fee' => $profFee,
            'government_fees' => $govFee,
            'estimated_government_fees' => $govFee,
            'service_support_fee' => $supportFee,
            'estimated_service_support_fee' => $supportFee,
            'payment_terms' => $paymentTerms,
            // Proposal & Internal Assignment
            'proposal_decision' => $proposalDecision,
            'decline_reason' => $declineReason,
            'service_department_unit' => $department,
            'assigned_consultant' => $assignedConsultant,
            'assigned_associate' => $assignedAssociate,
            // Notes & Approval
            'consultant_notes' => $consultantNotes,
            'associate_notes' => $associateNotes,
            // Signatures
            'prepared_by' => $preparedBy,
            'reviewed_by' => $reviewedBy,
            'internal_date' => $internalDate,
            'client_fullname_signature' => $clientSignature,
            'referred_closed_by' => $referredClosedBy,
            'internal_sales_marketing' => $salesMarketing,
            'sales_marketing' => $salesMarketing,
            'lead_consultant' => $leadConsultant,
            'lead_associate_assigned' => $leadAssociate,
            'lead_associate' => $leadAssociate,
            'internal_finance' => $finance,
            'finance' => $finance,
            'internal_president' => $president,
            'president' => $president,
            'logo_base64' => $logoBase64,
        ];

        return [
            'deal' => $deal,
            'dealFormData' => $dealFormData,
            'detail' => $dealFormData,
            'logoBase64' => $logoBase64,
        ];
    }

    public function normalizeDealFormData(array $draft): array
    {
        return array_merge([
            'deal_no' => $draft['deal_no'] ?? ($draft['deal_code'] ?? 'DRAFT'),
            'deal_form_no' => $draft['deal_form_no'] ?? ($draft['deal_code'] ?? 'DRAFT'),
            'reference_no' => $draft['reference_no'] ?? ($draft['deal_code'] ?? 'DRAFT'),
            'header_date' => $draft['header_date'] ?? date('m/d/Y'),
            'date' => $draft['date'] ?? date('Y-m-d'),
            'deal_name' => $draft['deal_name'] ?? ($draft['deal_title'] ?? '-'),
            'stage' => $draft['stage'] ?? 'Inquiry',
            'engagement_type' => $draft['engagement_type'] ?? '-',
            'total_value' => $draft['total_value'] ?? 'P0.00',
            'contact_name' => $draft['contact_name'] ?? '-',
            'company_name' => $draft['company_name'] ?? '-',
            'deal_title' => $draft['deal_title'] ?? '-',
            'service_area' => $draft['service_area'] ?? '-',
            'services' => $draft['services'] ?? '-',
            'products' => $draft['products'] ?? '-',
            'deal_value' => $draft['deal_value'] ?? 0,
            'billing_type' => $draft['billing_type'] ?? 'Milestone',
            'engagement_term' => $draft['engagement_term'] ?? 'Per Project',
            'service_department_unit' => $draft['service_department_unit'] ?? 'Operations',
            'assigned_consultant' => $draft['assigned_consultant'] ?? '-',
            'assigned_associate' => $draft['assigned_associate'] ?? '-',
            'consultant_notes' => $draft['consultant_notes'] ?? '-',
            'associate_notes' => $draft['associate_notes'] ?? '-',
            'prepared_by' => $draft['prepared_by'] ?? 'System User',
            'reviewed_by' => $draft['reviewed_by'] ?? '-',
            'internal_date' => $draft['internal_date'] ?? date('M d, Y'),
            'client_fullname_signature' => $draft['client_fullname_signature'] ?? '-',
            'referred_closed_by' => $draft['referred_closed_by'] ?? '-',
            'internal_sales_marketing' => $draft['internal_sales_marketing'] ?? '-',
            'lead_consultant' => $draft['lead_consultant'] ?? '-',
            'lead_associate_assigned' => $draft['lead_associate_assigned'] ?? '-',
            'internal_finance' => $draft['internal_finance'] ?? '-',
            'internal_president' => 'John Kelly Abalde',
        ], $draft);
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: storeStage
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: moveStage
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: destroyStage
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: updateDealStage
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: dealStages
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: ownerOptions
    |--------------------------------------------------------------------------
    */
    private function ownerOptions(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        return User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: financeUserOptions
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: employeeOptions
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | PRESERVED JKNC WORKFLOW METHOD: isDealReviewer
    |--------------------------------------------------------------------------
    */
    private function isDealReviewer(Request $request): bool
    {
        return in_array((string) ($request->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true);
    }

    public function canViewDeal(?Deal $deal = null): bool
    {
        return true;
    }
}
