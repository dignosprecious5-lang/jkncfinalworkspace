<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyDealController extends Controller
{
    private const STAGES = ['Qualification', 'Consultation', 'Proposal', 'Negotiation', 'Won', 'Lost'];

    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $search = trim((string) $request->query('search', ''));
        $stage = trim((string) $request->query('stage', 'all'));
        $deals = $this->companyDeals($request, $company, $companyData, $search, $stage);

        $summary = [
            'total' => $deals->count(),
            'open' => $deals->whereNotIn('stage', ['Won', 'Lost'])->count(),
            'won' => $deals->where('stage', 'Won')->count(),
            'pipeline_value' => $deals->whereNotIn('stage', ['Lost'])->sum('amount'),
        ];

        return view('company.deals', [
            'company' => (object) $companyData,
            'deals' => $deals,
            'search' => $search,
            'stage' => $stage,
            'stages' => self::STAGES,
            'summary' => $summary,
            'availableDeals' => collect($this->availableDeals($companyData))
                ->sortBy('name')
                ->values(),
        ]);
    }

    public function store(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validateDeal($request);
        $linkedDealId = (int) ($validated['linked_deal_id'] ?? 0);
        $linkedDeal = collect($this->availableDeals($companyData))->firstWhere('id', $linkedDealId);

        if ($linkedDeal) {
            $validated = [
                ...$validated,
                'name' => $validated['name'] ?: $linkedDeal['name'],
                'stage' => $validated['stage'] ?: $linkedDeal['stage'],
                'amount' => $validated['amount'] !== null && $validated['amount'] !== '' ? $validated['amount'] : $linkedDeal['amount'],
                'expected_close_date' => $validated['expected_close_date'] ?: $linkedDeal['expected_close_date'],
                'owner' => $validated['owner'] ?: $linkedDeal['owner'],
                'deal_source' => $validated['deal_source'] ?: $linkedDeal['deal_source'],
                'priority' => $validated['priority'] ?: $linkedDeal['priority'],
                'notes' => $validated['notes'] ?: $linkedDeal['notes'],
            ];
        }

        $deals = collect($request->session()->get($this->dealsKey(), $this->defaultDeals()));
        $nextId = (int) ($deals->max('id') ?? 800) + 1;

        $deals->push($this->makeDealPayload(
            $nextId,
            $company,
            $companyData['company_name'],
            $validated
        ));

        $request->session()->put($this->dealsKey(), $deals->values()->all());

        return redirect()
            ->route('company.deals', $company)
            ->with('deals_success', 'Deal added successfully.');
    }

    public function show(Request $request, int $company, int $deal): View
    {
        $companyData = $this->findCompany($request, $company);
        $dealData = collect($request->session()->get($this->dealsKey(), $this->defaultDeals()))
            ->firstWhere(fn (array $item) => (int) $item['id'] === $deal && (int) $item['company_id'] === $company);

        abort_unless($dealData, 404);

        return view('company.deal-show', [
            'company' => (object) $companyData,
            'deal' => (object) $dealData,
            'stages' => self::STAGES,
        ]);
    }

    public function update(Request $request, int $company, int $deal): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validateDeal($request);

        $deals = collect($request->session()->get($this->dealsKey(), $this->defaultDeals()));
        abort_unless($deals->contains(fn (array $item) => (int) $item['id'] === $deal && (int) $item['company_id'] === $company), 404);

        $deals = $deals->map(function (array $item) use ($deal, $company, $companyData, $validated) {
            if ((int) $item['id'] !== $deal || (int) $item['company_id'] !== $company) {
                return $item;
            }

            return $this->makeDealPayload($deal, $company, $companyData['company_name'], $validated, $item);
        })->values();

        $request->session()->put($this->dealsKey(), $deals->all());

        return redirect()
            ->route('company.deals', $company)
            ->with('deals_success', 'Deal updated successfully.');
    }

    public function destroy(Request $request, int $company, int $deal): RedirectResponse
    {
        $this->findCompany($request, $company);

        $deals = collect($request->session()->get($this->dealsKey(), $this->defaultDeals()));
        abort_unless($deals->contains(fn (array $item) => (int) $item['id'] === $deal && (int) $item['company_id'] === $company), 404);

        $request->session()->put(
            $this->dealsKey(),
            $deals->reject(fn (array $item) => (int) $item['id'] === $deal && (int) $item['company_id'] === $company)->values()->all()
        );

        return redirect()
            ->route('company.deals', $company)
            ->with('deals_success', 'Deal removed successfully.');
    }

    private function validateDeal(Request $request): array
    {
        return $request->validate([
            'linked_deal_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'stage' => ['required', 'in:' . implode(',', self::STAGES)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'owner' => ['nullable', 'string', 'max:255'],
            'deal_source' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'stage.required' => 'Stage is required.',
        ]);
    }

    private function makeDealPayload(int $id, int $companyId, string $companyName, array $validated, array $existing = []): array
    {
        $owner = trim((string) $validated['owner']);
        $initials = collect(explode(' ', $owner))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return [
            'id' => $id,
            'company_id' => $companyId,
            'company_name' => $companyName,
            'name' => $validated['name'],
            'stage' => $validated['stage'],
            'amount' => (float) $validated['amount'],
            'expected_close_date' => $validated['expected_close_date'] ?? null,
            'owner' => $owner,
            'owner_initials' => $initials ?: 'NA',
            'deal_source' => $validated['deal_source'] ?? '',
            'priority' => $validated['priority'] ?? 'Normal',
            'notes' => $validated['notes'] ?? '',
            'created_at' => $existing['created_at'] ?? now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('M d, Y h:i A'),
        ];
    }

    private function companyDeals(Request $request, int $companyId, array $companyData, string $search, string $stage): Collection
    {
        $deals = Schema::hasTable('deals')
            ? $this->databaseDealsForCompany($companyId, $companyData)
            : collect($request->session()->get($this->dealsKey(), $this->defaultDeals()))->where('company_id', $companyId);

        return $deals
            ->when($search !== '', function (Collection $collection) use ($search) {
                $term = strtolower($search);

                return $collection->filter(function (array $deal) use ($term) {
                    return collect([
                        $deal['name'] ?? '',
                        $deal['stage'] ?? '',
                        $deal['owner'] ?? '',
                        $deal['deal_source'] ?? '',
                        $deal['priority'] ?? '',
                    ])->contains(fn (?string $value) => str_contains(strtolower((string) $value), $term));
                });
            })
            ->when($stage !== 'all', fn (Collection $collection) => $collection->where('stage', $stage))
            ->sortByDesc(fn (array $deal) => strtotime((string) ($deal['updated_at'] ?? 'now')))
            ->values();
    }

    private function databaseDealsForCompany(int $companyId, array $companyData): Collection
    {
        $companyName = trim((string) ($companyData['company_name'] ?? ''));

        return Deal::query()
            ->when($companyName !== '', function ($query) use ($companyName) {
                $query->where(function ($nested) use ($companyName) {
                    $nested
                        ->where('company_name', $companyName)
                        ->orWhere('company_name', 'like', $companyName);
                });
            })
            ->latest('updated_at')
            ->get()
            ->map(function (Deal $deal) use ($companyId, $companyName): array {
                $owner = $deal->assigned_consultant ?: $deal->lead_consultant ?: $deal->created_by ?: 'Unassigned';
                $stage = trim((string) ($deal->stage ?: 'Qualification'));

                return [
                    'id' => $deal->id,
                    'company_id' => $companyId,
                    'company_name' => $companyName,
                    'name' => $deal->deal_name ?: ($deal->deal_code ?: 'Untitled deal'),
                    'stage' => $stage,
                    'amount' => (float) ($deal->total_estimated_engagement_value ?? 0),
                    'expected_close_date' => optional($deal->confirmed_delivery_date ?: $deal->estimated_completion_date ?: $deal->client_preferred_completion_date)->format('Y-m-d'),
                    'owner' => $owner,
                    'owner_initials' => collect(explode(' ', $owner))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
                        ->implode('') ?: 'NA',
                    'deal_source' => $deal->service_area ?: '',
                    'priority' => 'Normal',
                    'notes' => $deal->consultant_notes ?: '',
                    'created_at' => optional($deal->created_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s'),
                    'updated_at' => optional($deal->updated_at)->format('M d, Y h:i A') ?: now()->format('M d, Y h:i A'),
                    'show_url' => route('deals.show', $deal->id),
                    'readonly' => true,
                ];
            })
            ->values();
    }

    private function dealsKey(): string
    {
        return 'mock_deals_catalog';
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
                    'owner_name' => $record->owner_name,
                    'created_at' => optional($record->created_at)->toDateTimeString(),
                ];
            }
        }

        $companyData = collect($request->session()->get('mock_companies', $this->defaultCompanies()))
            ->firstWhere('id', $company);

        abort_unless($companyData, 404);

        return $companyData;
    }

    private function defaultDeals(): array
    {
        return [
            [
                'id' => 801,
                'company_id' => 1,
                'company_name' => 'Company 1',
                'name' => 'Cloud Migration Services',
                'stage' => 'Qualification',
                'amount' => 800000,
                'expected_close_date' => '2026-04-30',
                'owner' => 'Maria Santos',
                'owner_initials' => 'MS',
                'deal_source' => 'Referral',
                'priority' => 'High',
                'notes' => 'Needs technical scoping session.',
                'created_at' => '2026-03-01 10:00:00',
                'updated_at' => 'Apr 10, 2026 05:33 PM',
            ],
            [
                'id' => 802,
                'company_id' => 1,
                'company_name' => 'Company 1',
                'name' => 'Security Audit Package',
                'stage' => 'Consultation',
                'amount' => 120000,
                'expected_close_date' => '2026-03-25',
                'owner' => 'Sarah Williams',
                'owner_initials' => 'SW',
                'deal_source' => 'Website Inquiry',
                'priority' => 'Normal',
                'notes' => 'Waiting on final scope approval.',
                'created_at' => '2026-03-02 11:00:00',
                'updated_at' => 'Mar 10, 2026 11:25 AM',
            ],
            [
                'id' => 803,
                'company_id' => 2,
                'company_name' => 'Company 2',
                'name' => 'Payroll System Integration',
                'stage' => 'Proposal',
                'amount' => 500000,
                'expected_close_date' => '2026-06-15',
                'owner' => 'David Lee',
                'owner_initials' => 'DL',
                'deal_source' => 'Upsell',
                'priority' => 'High',
                'notes' => 'Proposal shared with client CFO.',
                'created_at' => '2026-03-03 01:00:00',
                'updated_at' => 'Feb 26, 2026 02:23 PM',
            ],
            [
                'id' => 804,
                'company_id' => 2,
                'company_name' => 'Company 2',
                'name' => 'IT Infrastructure Setup',
                'stage' => 'Negotiation',
                'amount' => 1500000,
                'expected_close_date' => '2026-05-30',
                'owner' => 'John Admin',
                'owner_initials' => 'JA',
                'deal_source' => 'Partner Referral',
                'priority' => 'High',
                'notes' => 'Client negotiating payment schedule.',
                'created_at' => '2026-03-04 02:00:00',
                'updated_at' => 'Mar 06, 2026 02:50 PM',
            ],
        ];
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

    private function availableDeals(array $companyData = []): array
    {
        if (Schema::hasTable('deals')) {
            $companyName = trim((string) ($companyData['company_name'] ?? ''));

            return Deal::query()
                ->when($companyName !== '', fn ($query) => $query->where('company_name', $companyName))
                ->latest('updated_at')
                ->get()
                ->map(fn (Deal $deal): array => [
                    'id' => $deal->id,
                    'name' => $deal->deal_name ?: ($deal->deal_code ?: 'Untitled deal'),
                    'company_name' => $deal->company_name ?: $companyName,
                    'amount' => (float) ($deal->total_estimated_engagement_value ?? 0),
                    'expected_close_date' => optional($deal->confirmed_delivery_date ?: $deal->estimated_completion_date ?: $deal->client_preferred_completion_date)->format('Y-m-d'),
                    'owner' => $deal->assigned_consultant ?: $deal->lead_consultant ?: $deal->created_by ?: 'Unassigned',
                    'stage' => $deal->stage ?: 'Qualification',
                    'deal_source' => $deal->service_area ?: '',
                    'priority' => 'Normal',
                    'notes' => $deal->consultant_notes ?: '',
                ])
                ->values()
                ->all();
        }

        return [
            [
                'id' => 1,
                'name' => 'Tax Advisory Compliance Audit Regular Retainer',
                'company_name' => 'Consulting Group',
                'amount' => 920000,
                'expected_close_date' => '2026-06-10',
                'owner' => 'Admin User',
                'stage' => 'Qualification',
                'deal_source' => 'Referral',
                'priority' => 'High',
                'notes' => '',
            ],
            [
                'id' => 2,
                'name' => 'Data Analytics Platform',
                'company_name' => 'Consulting Group',
                'amount' => 920000,
                'expected_close_date' => '2026-06-10',
                'owner' => 'Admin User',
                'stage' => 'Consultation',
                'deal_source' => 'Website Inquiry',
                'priority' => 'Normal',
                'notes' => '',
            ],
            [
                'id' => 3,
                'name' => 'Cloud Migration Program',
                'company_name' => 'ABC Company',
                'amount' => 540000,
                'expected_close_date' => '2026-05-20',
                'owner' => 'Maria Santos',
                'stage' => 'Proposal',
                'deal_source' => 'Upsell',
                'priority' => 'High',
                'notes' => '',
            ],
            [
                'id' => 4,
                'name' => 'Security Audit Package',
                'company_name' => 'XYZ Company',
                'amount' => 120000,
                'expected_close_date' => '2026-04-15',
                'owner' => 'Sarah Williams',
                'stage' => 'Negotiation',
                'deal_source' => 'Partner Referral',
                'priority' => 'Normal',
                'notes' => '',
            ],
        ];
    }
}
