<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Deal;
use App\Models\DealContact;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DealHistory;
use App\Services\DealHistoryService;
use Carbon\Carbon;

class DealController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Deals Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Deal::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);
            $tokens = preg_split('/\s+/', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY);
            $firstToken = $tokens[0] ?? $search;

            $query->where(function ($q) use ($firstToken) {
                $q->where('first_name', 'like', "{$firstToken}%")
                    ->orWhere('company_name', 'like', "{$firstToken}%")
                    ->orWhere('company', 'like', "{$firstToken}%")
                    ->orWhere('primary_contact_name', 'like', "{$firstToken}%")
                    ->orWhere('client_search', 'like', "{$firstToken}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["{$firstToken}%"])
                    ->orWhereHas('account', function ($accQ) use ($firstToken) {
                        $accQ->where('account_name', 'like', "{$firstToken}%");
                    });
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
                $query->where('pipeline_stage', $request->deal_filter);
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
            ->where('status', 'Active')
            ->orderBy('company_name')
            ->get();

        $contacts = Contact::query()
            ->where('status', 'Active')
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
        $deal = $request->filled('deal')
            ? Deal::findOrFail($request->integer('deal'))
            : null;

        $owners = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        $companies = Company::query()
            ->where('status', 'Active')
            ->orderBy('company_name')
            ->get();

        $contacts = Contact::query()
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();


        $accounts = Account::query()
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
                'userPositions.position',
                'userPositions.department',
            ])
            ->orderBy('name')
            ->get();

        return view(
            'deals.create',
            compact('deal', 'owners', 'employees', 'clients', 'accounts', 'companies', 'contacts')
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

        $accounts = Account::query()
            ->with([
                'company',
                'individualContact',
                'deals' => function ($q) {
                    $q->latest()->limit(5);
                }
            ])
            ->where('status', 'Active')
            ->orderBy('account_name')
            ->get();

        $companies = Company::query()
            ->where('status', 'Active')
            ->orderBy('company_name')
            ->get();

        $contacts = Contact::query()
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        $owners = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $employees = User::query()
            ->with([
                'userPositions.position',
                'userPositions.department',
            ])
            ->orderBy('name')
            ->get();

        return view(
            'deals.quick-purchase',
            compact('accounts', 'companies', 'contacts', 'owners', 'employees', 'selectedAccountId')
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

        $firstToken = $tokens[0];

        // Search Priority:
        // 1. Customer / Client Name (first_name, last_name, primary_contact_name, client_search)
        // 2. Company Name (if business)
        // 3. Contact Name (if applicable)
        // Do NOT search by deal title, service name, scope of work, etc.
        $query = Deal::with('account')
            ->where(function ($q) use ($firstToken) {
                $q->where('first_name', 'like', "{$firstToken}%")
                  ->orWhere('company_name', 'like', "{$firstToken}%")
                  ->orWhere('company', 'like', "{$firstToken}%")
                  ->orWhere('primary_contact_name', 'like', "{$firstToken}%")
                  ->orWhere('client_search', 'like', "{$firstToken}%")
                  ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["{$firstToken}%"])
                  ->orWhereHas('account', function ($accQ) use ($firstToken) {
                      $accQ->where('account_name', 'like', "{$firstToken}%");
                  });
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

            // Candidate targets for client name matching
            $searchableClientTargets = array_unique(array_filter([
                $mainClientName,
                $primaryCompany,
                $contactName,
                $deal->account?->account_name,
            ]));

            $matched = false;
            $dealScore = 999999;

            foreach ($searchableClientTargets as $targetText) {
                $targetLower = mb_strtolower(trim((string)$targetText));
                if ($targetLower === '') continue;

                // 1. Full search string prefix match against client name (highest priority)
                if (str_starts_with($targetLower, $searchLower)) {
                    $score = strlen($targetLower) - strlen($searchLower);
                    if ($score < $dealScore) {
                        $dealScore = $score;
                        $matched = true;
                    }
                } elseif (count($tokens) > 1 && str_starts_with($targetLower, $firstToken)) {
                    // Multi-token: client name starts with first token, and other tokens match word starts
                    $words = preg_split('/[\s\-_,.:;|\/\\\\()\[\]@]+/u', $targetLower, -1, PREG_SPLIT_NO_EMPTY);
                    $allTokensFound = true;
                    foreach ($tokens as $tok) {
                        $found = false;
                        foreach ($words as $w) {
                            if (str_starts_with($w, $tok)) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $allTokensFound = false;
                            break;
                        }
                    }
                    if ($allTokensFound) {
                        $score = 50 + (strlen($targetLower) - strlen($searchLower));
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
        
        // Service Name & Deal Title
        $serviceName = !empty($services) ? implode(', ', $services) : ($request->input('service') ?: 'Consulting Service');
        $deal->deal_title = $serviceName . ' — ' . $account->account_name;

        // Owner & Creator
        $deal->owner_name = $request->input('owner_name') ?: (auth()->user()?->name ?: null);
        $deal->created_by = auth()->user()?->name ?: ($deal->owner_name ?: 'System');

        // Contact Information - populated directly from selected verified record and form input
        $deal->salutation = $contact?->salutation;
        $deal->sex = $contact?->sex;
        $deal->first_name = $request->input('first_name', $contact?->first_name ?: $account->account_name);
        $deal->middle_initial = $contact?->middle_name;
        $deal->last_name = $request->input('last_name', $contact?->last_name ?: '');
        $deal->name_extension = $contact?->name_extension;
        $deal->date_of_birth = $contact?->date_of_birth;
        $deal->email = $request->input('email', $contact?->email);
        $deal->mobile_number = $request->input('mobile_number', $contact?->mobile_number);
        $deal->address = $request->input('address', $contact?->address);
        $deal->position = $request->input('position', $contact?->position);

        if ($company) {
            $deal->company = $company->company_name;
            $deal->company_name = $company->company_name;
            $deal->company_address = $company->address;
        }

        $deal->primary_contact_name = trim(collect([
            $deal->salutation,
            $deal->first_name,
            $deal->middle_initial,
            $deal->last_name,
            $deal->name_extension,
        ])->filter()->implode(' '));

        // Service Details
        $deal->service_areas = $serviceAreas;
        $deal->services_products = $services;
        $deal->engagement_type = null; // Decided later in Deal lifecycle
        $deal->scope_of_work = $request->input('scope_of_work');

        // Commercials (optional)
        $baseFee = (float) ($request->input('estimated_professional_fee') ?? $request->input('amount') ?? 0);
        $discount = (float) ($request->input('discount') ?? 0);
        $totalVal = max(0, $baseFee - $discount);

        $deal->est_professional_fee = $baseFee > 0 ? $baseFee : null;
        $deal->total_service_fee = $baseFee > 0 ? $baseFee : null;
        $deal->discount = $discount > 0 ? $discount : null;
        $deal->amount = $totalVal > 0 ? $totalVal : null;
        $deal->total_estimated_engagement_value = $totalVal > 0 ? $totalVal : null;
        $deal->payment_terms = $request->input('payment_terms');

        // Stage assignment: Pipeline Stage is automatically "Inquiry"
        $deal->pipeline_stage = 'Inquiry';
        $deal->inquiry_source = 'Add Inquiry';
        $deal->inquiry_date = now()->toDateString();
        $deal->inquiry_details = $request->input('scope_of_work') ?: $serviceName;

        $deal->save();

        // Generate Standard Deal Code: CONDEAL-YYYY-###
        $deal->deal_code = 'CONDEAL-' . now()->format('Y') . '-' . str_pad($deal->id, 3, '0', STR_PAD_LEFT);
        $deal->save();

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

        $count = Contact::count() + 1;
        $contactCode = 'CON-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (Contact::where('contact_code', $contactCode)->exists()) {
            $count++;
            $contactCode = 'CON-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        $contact = Contact::create([
            'contact_code' => $contactCode,
            'contact_type' => $validated['contact_type'] ?? ($request->filled('company_id') ? 'Business' : 'Individual'),
            'salutation' => $validated['salutation'] ?? null,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name_extension' => $validated['name_extension'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'email' => $validated['email'] ?? null,
            'mobile_number' => $validated['mobile_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'company_id' => $validated['company_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'status' => 'Active',
        ]);

        return response()->json([
            'success' => true,
            'contact' => [
                'id' => $contact->id,
                'contact_code' => $contact->contact_code,
                'contact_type' => $contact->contact_type,
                'salutation' => $contact->salutation,
                'first_name' => $contact->first_name,
                'middle_initial' => $contact->middle_name ?? '',
                'last_name' => $contact->last_name,
                'name_extension' => $contact->name_extension,
                'date_of_birth' => optional($contact->date_of_birth)->format('Y-m-d'),
                'sex' => $contact->sex,
                'email' => $contact->email,
                'mobile_number' => $contact->mobile_number,
                'address' => $contact->address,
                'company_id' => $contact->company_id,
                'position' => $contact->position,
                'status' => $contact->status,
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
                $compCount = Company::count() + 1;
                $companyCode = 'COM-' . str_pad($compCount, 4, '0', STR_PAD_LEFT);
                while (Company::where('company_code', $companyCode)->exists()) {
                    $compCount++;
                    $companyCode = 'COM-' . str_pad($compCount, 4, '0', STR_PAD_LEFT);
                }

                $company = Company::create([
                    'company_code' => $companyCode,
                    'company_name' => $validated['company_name'],
                    'industry' => $validated['industry'] ?? null,
                    'email' => $validated['company_email'] ?? null,
                    'phone' => $validated['company_phone'] ?? null,
                    'address' => $validated['company_address'] ?? null,
                    'status' => 'Active',
                ]);
            }

            // Create Contact if provided
            $contact = null;
            if (!empty($validated['first_name']) || !empty($validated['last_name'])) {
                $contactCount = Contact::count() + 1;
                $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
                while (Contact::where('contact_code', $contactCode)->exists()) {
                    $contactCount++;
                    $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
                }

                $contact = Contact::create([
                    'contact_code' => $contactCode,
                    'contact_type' => 'Business',
                    'company_id' => $company->id,
                    'salutation' => $validated['salutation'] ?? null,
                    'first_name' => $validated['first_name'] ?: $company->company_name,
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => $validated['last_name'] ?: '',
                    'name_extension' => $validated['name_extension'] ?? null,
                    'email' => $validated['contact_email'] ?? null,
                    'mobile_number' => $validated['contact_mobile'] ?? null,
                    'position' => $validated['contact_position'] ?? null,
                    'status' => 'Active',
                ]);
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
                    'individual_contact_id' => null,
                    'status' => $account->status,
                ],
                'company' => [
                    'id' => $company->id,
                    'company_code' => $company->company_code,
                    'company_name' => $company->company_name,
                    'industry' => $company->industry,
                    'address' => $company->address,
                    'email' => $company->email,
                    'phone' => $company->phone,
                ],
                'contact' => $contact ? [
                    'id' => $contact->id,
                    'contact_code' => $contact->contact_code,
                    'contact_type' => $contact->contact_type,
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_initial' => $contact->middle_name ?? '',
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'email' => $contact->email,
                    'mobile_number' => $contact->mobile_number,
                    'address' => $contact->address,
                    'company_id' => $contact->company_id,
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

            $contactCount = Contact::count() + 1;
            $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
            while (Contact::where('contact_code', $contactCode)->exists()) {
                $contactCount++;
                $contactCode = 'CON-' . str_pad($contactCount, 4, '0', STR_PAD_LEFT);
            }

            $contact = Contact::create([
                'contact_code' => $contactCode,
                'contact_type' => 'Individual',
                'salutation' => $validated['salutation'] ?? null,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'name_extension' => $validated['name_extension'] ?? null,
                'email' => $validated['contact_email'] ?? null,
                'mobile_number' => $validated['contact_mobile'] ?? null,
                'address' => $validated['contact_address'] ?? null,
                'status' => 'Active',
            ]);

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

            $account = Account::create([
                'account_code' => $accountCode,
                'account_type' => 'Individual',
                'account_name' => $fullName ?: ($contact->first_name . ' ' . $contact->last_name),
                'individual_contact_id' => $contact->id,
                'status' => 'Active',
            ]);

            return response()->json([
                'success' => true,
                'account' => [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_type' => $account->account_type,
                    'account_name' => $account->account_name,
                    'company_id' => null,
                    'individual_contact_id' => $account->individual_contact_id,
                    'status' => $account->status,
                ],
                'company' => null,
                'contact' => [
                    'id' => $contact->id,
                    'contact_code' => $contact->contact_code,
                    'contact_type' => $contact->contact_type,
                    'salutation' => $contact->salutation,
                    'first_name' => $contact->first_name,
                    'middle_initial' => $contact->middle_name ?? '',
                    'last_name' => $contact->last_name,
                    'name_extension' => $contact->name_extension,
                    'email' => $contact->email,
                    'mobile_number' => $contact->mobile_number,
                    'address' => $contact->address,
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

    public function store(Request $request)
    {
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

        $services = (array) ($request->services ?? []);
        if ($request->filled('other_service')) {
            $services[] = $request->other_service;
        }

        $products = (array) ($request->products ?? []);
        if ($request->filled('other_product')) {
            $products[] = $request->other_product;
        }

        $deal->services_products = array_values(array_filter(
            array_unique(array_merge($services, $products)),
            fn ($item) => filled($item) && $item !== 'Others'
        ));

        $deal->scope_of_work =
            $request->scope_of_work;

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
            $request->planned_start_date;

        $deal->estimated_duration_days =
            $request->estimated_duration;

        $deal->estimated_completion_date =
            $request->estimated_completion_date;

        $deal->client_preferred_completion_date =
            $request->client_preferred_completion_date;

        $deal->confirmed_delivery_date =
            $request->confirmed_delivery_date;

        $deal->timeline_notes =
            $request->timeline_notes;

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
            $request->expected_close
            ?? $request->confirmed_delivery_date;

        /*
        |--------------------------------------------------------------------------
        | Inquiry Details
        |--------------------------------------------------------------------------
        */

        $deal->inquiry_date = now();
        $deal->inquiry_source = in_array($request->engagement_type, ['Product', 'Hybrid']) ? 'Product' : 'Service';
        $deal->inquiry_details = $deal->scope_of_work 
            ?: (filled($deal->client_requirements) ? (is_array($deal->client_requirements) ? implode(', ', $deal->client_requirements) : $deal->client_requirements)
                : (filled($deal->services_products) ? (is_array($deal->services_products) ? implode(', ', $deal->services_products) : $deal->services_products)
                    : ($deal->consultant_notes ?: ('Initial Client Inquiry for ' . ($deal->company_name ?: ($deal->primary_contact_name ?: 'services'))))));

        $listValInit = function ($val) {
            if (is_array($val)) return implode(', ', array_filter($val));
            if (is_string($val)) {
                $d = json_decode($val, true);
                if (is_array($d)) return implode(', ', array_filter($d));
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

        $deal->deal_code =
            'CONDEAL-' .
            now()->format('Y') .
            '-' .
            str_pad(
                $deal->id,
                3,
                '0',
                STR_PAD_LEFT
            );

        if (empty($deal->deal_title) || $deal->deal_title === 'CONDEAL-YYYY-###' || str_contains($deal->deal_title, 'YYYY-###')) {
            $deal->deal_title = $deal->deal_code;
        }

        $deal->save();

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
        $deal = Deal::with([
            'startRecords.engagementGroups.assignments',
            'startRecords.assignments',
            'projectScopeItems',
            'dealContacts',
            'proposal',
            'company',
            'account',
            'histories',
            'clientActionRequests.createdByUser',
            'clientActionRequests.recordedByUser',
            'clientActionRequests.verifiedByUser',
        ])->findOrFail($id);

        $users = User::with([
            'userPositions.position',
            'userPositions.department',
        ])->orderBy('name')->get();

        $proposal = Proposal::firstOrNew([
            'deal_id' => $deal->id,
        ]);

        $historyService = app(DealHistoryService::class);
        $stageProgression = $historyService->getStageProgression($deal);
        $activityCounts = $historyService->getActivityCounts($deal);

        return view(
            'deals.show',
            compact('deal', 'users', 'proposal', 'stageProgression', 'activityCounts')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Deal
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);

        $validated = $request->validate([
            'deal_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pipeline_stage' => [
                'required',
                'in:Inquiry,Qualification,Consultation,Proposal,Negotiation,Payment,Activation,Closed Won,Closed Lost',
            ],

            'company' => [
                'nullable',
                'string',
                'max:255',
            ],

            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'expected_close' => [
                'nullable',
                'date',
            ],

            'owner_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $original = $deal->getOriginal();
        $oldStage = $deal->pipeline_stage;
        $deal->update($validated);
        $changes = $deal->getChanges();

        if (!empty($changes)) {
            if (isset($changes['pipeline_stage']) && $changes['pipeline_stage'] !== $oldStage) {
                app(DealHistoryService::class)->logStageChanged($deal, $oldStage, $changes['pipeline_stage'], $request->input('notes'));
            }
            app(DealHistoryService::class)->logDealUpdated($deal, $changes, $original);
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
    | Update Deal Stage
    |--------------------------------------------------------------------------
    */

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
        if ($newStage === 'Closed Won' && empty($deal->closed_won_date)) {
            $deal->closed_won_date = now()->toDateString();
        }
        if ($newStage === 'Closed Lost' && empty($deal->closed_lost_date)) {
            $deal->closed_lost_date = now()->toDateString();
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
        $isFinalOutcome = in_array($stage, ['Closed Won', 'Closed Lost'], true);
        if ($isFinalOutcome) {
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

        $original = $deal->getOriginal();
        $oldStage = $deal->pipeline_stage ?: 'Inquiry';

        // Update fields on deal
        foreach ($validated as $key => $val) {
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
                    $nextStage = 'Activation';
                    break;
                case 'Activation':
                    // If outcome is chosen in same action
                    if ($outcomeType === 'Closed Won' || $outcomeType === 'Closed Lost') {
                        $nextStage = $outcomeType;
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
        } elseif ($stage === 'Closed Won' || $stage === 'Closed Lost') {
            $deal->pipeline_stage = $stage;
            $nextStage = $stage;
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
                if (is_array($val)) return implode(', ', array_filter($val));
                if (is_string($val)) {
                    $d = json_decode($val, true);
                    if (is_array($d)) return implode(', ', array_filter($d));
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
            'consultant' => $validated['consultant'] ?: ($deal->lead_consultant ?: ($deal->assigned_consultant ?: ($deal->owner_name ?: 'Consultant'))),
            'associate' => $validated['associate'] ?: '',
            'preparedBy' => $validated['prepared_by'] ?: (auth()->user()?->name ?: ($deal->owner_name ?: 'Consultant')),
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
            'type' => 'nullable|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'qty' => 'nullable|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'billing' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:50',
        ]);

        $items = is_array($deal->services_products) ? $deal->services_products : [];
        $editId = $request->input('id');

        $type = strtoupper($validated['type'] ?? 'SERVICE');
        $qty = (float)($validated['qty'] ?? 1);
        $unitPrice = (float)($validated['unit_price'] ?? 0);
        $discount = (float)($validated['discount'] ?? 0);
        $tax = (float)($validated['tax'] ?? 0);
        $unit = $validated['unit'] ?? ($type === 'PRODUCT' ? 'document' : 'lot');
        $billing = $validated['billing'] ?? ($deal->payment_terms ?: 'Full Payment Before Service');
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
            ];
            $items[] = $newRecord;
            $targetItem = $newRecord;
        }

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
        $deal = Deal::findOrFail($id);
        $items = is_array($deal->services_products) ? $deal->services_products : [];
        $filtered = array_values(array_filter($items, fn($it) => is_array($it) && (string)($it['id'] ?? '') !== (string)$itemId));
        $deal->services_products = $filtered;

        // Recalculate totals
        $servicesFee = 0;
        $productsFee = 0;
        $totalDiscount = 0;
        $commercialTotal = 0;

        foreach ($filtered as $it) {
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
        $deal->save();

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Line item deleted successfully.',
                'items' => $filtered,
                'totals' => [
                    'services_fee' => $servicesFee,
                    'products_fee' => $productsFee,
                    'total_discount' => $totalDiscount,
                    'commercial_total' => $commercialTotal,
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Line item deleted successfully.');
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

        $proposal = Proposal::updateOrCreate(
            [
                'deal_id' => $deal->id,
            ],
            $validated
        );

        app(DealHistoryService::class)->logProposalGenerated($deal, $proposal);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Proposal saved successfully.',
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
                'Proposal saved successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Send Proposal
    |--------------------------------------------------------------------------
    */

    public function sendProposal(
        Request $request,
        $id
    ) {
        $deal = Deal::findOrFail($id);

        $proposal = Proposal::firstOrCreate([
            'deal_id' => $deal->id,
        ]);

        $email = $request->validate([
            'recipient_email' => [
                'required',
                'email',
            ],
        ])['recipient_email'];

        $proposal->update([
            'recipient_email' => $email,
        ]);

        Mail::raw(
            'Proposal ' .
            ($deal->deal_code ?: 'for your engagement') .
            ' is ready for review.',
            function ($message) use (
                $email,
                $deal
            ) {
                $message
                    ->to($email)
                    ->subject(
                        'Proposal: ' .
                        (
                            $deal->deal_title
                            ?: $deal->deal_code
                        )
                    );
            }
        );

        app(DealHistoryService::class)->logActivity(
            $deal,
            DealHistory::TYPE_PROPOSAL_SENT,
            "Proposal sent to {$email}.",
            [
                'proposal_id' => $proposal->id,
                'document_name' => "Proposal " . ($deal->deal_code ?: "#{$deal->id}"),
                'document_type' => 'Email',
                'new_values' => ['recipient_email' => $email]
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Proposal sent successfully.',
            ]);
        }

        return redirect()
            ->route(
                'deals.show',
                ['id' => $deal->id]
            )
            ->with(
                'success',
                'Proposal sent successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Regular Workspace
    |--------------------------------------------------------------------------
    */

    public function regular($id)
    {
        $deal = Deal::findOrFail($id);

        $regularIndex = Deal::where(
            'engagement_type',
            'Regular (Retainer) Engagement'
        )
            ->latest()
            ->pluck('id')
            ->search($deal->id);

        $regularNumber =
            'REG-' .
            date('Y') .
            '-' .
            str_pad(
                ($regularIndex === false
                    ? 0
                    : $regularIndex) + 1,
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

        $clientName =
            $deal->primary_contact_name
            ?: trim(
                collect([
                    $deal->first_name,
                    $deal->middle_initial,
                    $deal->last_name,
                    $deal->name_extension
                ])
                    ->filter()
                    ->implode(' ')
            );

        return view(
            'deals.regular',
            compact(
                'deal',
                'regularNumber',
                'dealCode',
                'clientName'
            )
        );
    }
    

    /*
    |--------------------------------------------------------------------------
    | Project Workspace
    |--------------------------------------------------------------------------
    */

    public function project($id)
    {
        $deal = Deal::findOrFail($id);
        $scopeItems = $deal->projectScopeItems()->orderBy('id')->get();

        $projectIndex =
            Deal::latest()
                ->pluck('id')
                ->search($deal->id);

        $projectNumber =
            'PROJ-' .
            date('Y') .
            '-' .
            str_pad(
                ($projectIndex === false
                    ? 0
                    : $projectIndex) + 101,
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

        $clientName =
            $deal->primary_contact_name
            ?: trim(
                collect([
                    $deal->first_name,
                    $deal->middle_initial,
                    $deal->last_name,
                    $deal->name_extension
                ])
                    ->filter()
                    ->implode(' ')
            );

        return view(
            'deals.project',
            compact(
                'deal',
                'projectNumber',
                'dealCode',
                'clientName',
                'scopeItems'
            )
        );
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

}