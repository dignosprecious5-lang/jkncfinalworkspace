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
    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($company);
        $search = trim((string) $request->query('search', ''));
        $stage = trim((string) $request->query('stage', 'all'));
        $deals = $this->companyDeals($companyData, $search, $stage);
        $stages = $deals->pluck('stage')->filter()->unique()->sort()->values()->all();

        return view('company.deals', [
            'company' => (object) $companyData,
            'deals' => $deals,
            'search' => $search,
            'stage' => $stage,
            'stages' => $stages,
            'summary' => [
                'total' => $deals->count(),
                'open' => $deals->where('status', 'Open')->count(),
                'won' => $deals->where('status', 'Won')->count(),
                'pipeline_value' => $deals
                    ->filter(fn (array $deal): bool => $deal['status'] !== 'Lost')
                    ->sum('amount_value'),
            ],
            'dataNotice' => $this->companyDealsNotice($companyData, $deals),
        ]);
    }

    public function store(Request $request, int $company): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.deals', $company)
            ->with('deals_warning', 'This company deals page now mirrors the main Deals module. Create or update deals from the main Deals page and they will appear here automatically.');
    }

    public function show(Request $request, int $company, int $deal): RedirectResponse
    {
        $companyData = $this->findCompany($company);
        $dealRecord = $this->findLinkedDeal($companyData, $deal);

        if (! $dealRecord) {
            return redirect()
                ->route('company.deals', $company)
                ->with('deals_warning', 'That deal is no longer linked to this company or could not be found.');
        }

        return redirect()->route('deals.show', $dealRecord->id);
    }

    public function update(Request $request, int $company, int $deal): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.deals', $company)
            ->with('deals_warning', 'Edit deals from the main Deals module so the same live record stays consistent everywhere.');
    }

    public function destroy(Request $request, int $company, int $deal): RedirectResponse
    {
        $this->findCompany($company);

        return redirect()
            ->route('company.deals', $company)
            ->with('deals_warning', 'Delete or update deals from the main Deals module. This company view is read-only and automatically reflects linked deal records.');
    }

    private function companyDeals(array $companyData, string $search, string $stage): Collection
    {
        if (! Schema::hasTable('deals')) {
            return collect();
        }

        $companyName = trim((string) ($companyData['company_name'] ?? ''));

        if ($companyName === '') {
            return collect();
        }

        return Deal::query()
            ->where('company_name', $companyName)
            ->latest('updated_at')
            ->get()
            ->map(function (Deal $deal): array {
                $stageName = trim((string) ($deal->stage ?: 'Qualification'));
                $owner = $deal->assigned_consultant ?: $deal->lead_consultant ?: $deal->created_by ?: 'Unassigned';
                $amount = (float) ($deal->total_estimated_engagement_value ?? 0);

                return [
                    'id' => $deal->id,
                    'name' => $deal->deal_name ?: ($deal->deal_code ?: 'Untitled deal'),
                    'stage' => $stageName,
                    'amount' => 'P'.number_format($amount, 2),
                    'amount_value' => $amount,
                    'closing_date' => optional($deal->confirmed_delivery_date ?: $deal->estimated_completion_date ?: $deal->client_preferred_completion_date)->format('M d, Y') ?: '-',
                    'owner' => $owner,
                    'status' => match ($stageName) {
                        'Won', 'Closed Won' => 'Won',
                        'Lost', 'Closed Lost' => 'Lost',
                        default => 'Open',
                    },
                    'show_url' => route('deals.show', $deal->id),
                ];
            })
            ->when($search !== '', function (Collection $collection) use ($search) {
                $term = strtolower($search);

                return $collection->filter(function (array $deal) use ($term): bool {
                    return collect([
                        $deal['name'] ?? '',
                        $deal['stage'] ?? '',
                        $deal['owner'] ?? '',
                        $deal['status'] ?? '',
                    ])->contains(fn (?string $value): bool => str_contains(strtolower((string) $value), $term));
                });
            })
            ->when($stage !== 'all', fn (Collection $collection) => $collection->where('stage', $stage))
            ->values();
    }

    private function companyDealsNotice(array $companyData, Collection $deals): ?string
    {
        if (! Schema::hasTable('deals')) {
            return 'Deals data is currently unavailable because the deals table is missing. Open the main Deals module after the migration is restored.';
        }

        if ($deals->isEmpty()) {
            return 'No live deals are linked to this company yet. Create or update a deal from the main Deals module and assign this company name to have it appear here.';
        }

        return null;
    }

    private function findLinkedDeal(array $companyData, int $deal): ?Deal
    {
        if (! Schema::hasTable('deals')) {
            return null;
        }

        $companyName = trim((string) ($companyData['company_name'] ?? ''));

        if ($companyName === '') {
            return null;
        }

        return Deal::query()
            ->where('id', $deal)
            ->where('company_name', $companyName)
            ->first();
    }

    private function findCompany(int $company): array
    {
        abort_unless(Schema::hasTable('companies'), 404, 'Company data is unavailable right now.');

        $record = Company::query()->findOrFail($company);

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
