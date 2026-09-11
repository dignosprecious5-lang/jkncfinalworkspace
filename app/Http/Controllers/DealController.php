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
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('deal_code', 'like', "%{$search}%")
                    ->orWhere('deal_title', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('primary_contact_name', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Deal Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('deal_filter')) {
            if ($request->deal_filter === 'my_deals') {
                $query->where('owner_name', 'John Kelly Abalde');
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

        /*
        |--------------------------------------------------------------------------
        | Pipeline Stages
        |--------------------------------------------------------------------------
        */

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

        return view('deals.index', [
            'deals' => $deals,
            'stages' => $stages,
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
            ->get(['id', 'name']);
        
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

        return view(
            'deals.create',
            compact('deal', 'owners', 'clients', 'accounts', 'companies', 'contacts')
        );
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

        $deal->services_products =
            $request->services ?? [];

        $deal->scope_of_work =
            $request->scope_of_work;

        $deal->engagement_type =
            $request->engagement_type;

        /*
        |--------------------------------------------------------------------------
        | Requirements / Actions
        |--------------------------------------------------------------------------
        */

        $deal->client_requirements =
            $request->requirements ?? [];

        $deal->required_actions =
            $request->required_actions ?? [];

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

        $deal->professional_support_required =
            $request->professional_support ?? [];

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

        $deal->save();

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
        $deal = Deal::findOrFail($id);

        return view(
            'deals.show',
            compact('deal')
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

        $deal->update($validated);

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

        $validated = $request->validate([
            'pipeline_stage' => [
                'required',
                'in:Inquiry,Qualification,Consultation,Proposal,Negotiation,Payment,Activation,Closed Won,Closed Lost',
            ],
        ]);

        $deal->update($validated);

        return redirect()->to(
            $request->input(
                'return_to',
                url()->previous()
            )
        )->with(
            'success',
            'Deal stage updated successfully.'
        );
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
    | Proposal
    |--------------------------------------------------------------------------
    */

    public function proposal($id)
    {
        $deal = Deal::findOrFail($id);

        $proposal = Proposal::firstOrNew([
            'deal_id' => $deal->id,
        ]);

        return view(
            'deals.proposal',
            compact('deal', 'proposal')
        );
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

        Proposal::updateOrCreate(
            [
                'deal_id' => $deal->id,
            ],
            $validated
        );

        return redirect()
            ->route(
                'deals.proposal',
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

        return redirect()
            ->route(
                'deals.proposal',
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