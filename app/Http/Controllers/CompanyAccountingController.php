<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyAccountingController extends Controller
{
    use ResolvesCompanyRecords;

    private const CATEGORIES = ['PNL', 'Balance Sheet', 'Cash Flow', 'Income Statement', 'AFS'];
    private const STATUSES = ['Open', 'Completed', 'Overdue'];

    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $category = trim((string) $request->query('category', self::CATEGORIES[0]));

        if (! in_array($category, self::CATEGORIES, true)) {
            $category = self::CATEGORIES[0];
        }

        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()))
            ->where('company_id', $company)
            ->where('category', $category)
            ->values();

        return view('company.accounting', [
            'company' => (object) $companyData,
            'companyTin' => $this->companyTin($companyData),
            'currentUserName' => $this->currentUserName($request),
            'categories' => self::CATEGORIES,
            'selectedCategory' => $category,
            'records' => $records,
            'stats' => [
                'total' => $records->count(),
                'open' => $records->where('status', 'Open')->count(),
                'completed' => $records->where('status', 'Completed')->count(),
                'overdue' => $records->where('status', 'Overdue')->count(),
            ],
        ]);
    }

    public function store(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validateRecord($request);
        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()));
        $nextId = (int) ($records->max('id') ?? 0) + 1;

        $records->push([
            'id' => $nextId,
            'company_id' => $company,
            'category' => $validated['category'],
            'date_uploaded' => $validated['date_uploaded'],
            'uploaded_by' => $this->currentUserName($request),
            'client' => $companyData['company_name'],
            'tin' => $validated['tin'] ?: $this->companyTin($companyData),
            'status' => $validated['status'],
        ]);

        $request->session()->put($this->sessionKey(), $records->values()->all());

        return redirect()
            ->route('company.accounting', ['company' => $company, 'category' => $validated['category']])
            ->with('accounting_success', 'Accounting entry added successfully.');
    }

    public function update(Request $request, int $company, int $record): RedirectResponse
    {
        $companyData = $this->findCompany($request, $company);
        $validated = $this->validateRecord($request);
        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()));
        $existing = $records->firstWhere('id', $record);

        abort_unless($existing && (int) $existing['company_id'] === $company, 404);

        $updated = $records->map(function (array $item) use ($record, $company, $companyData, $validated) {
            if ((int) $item['id'] !== $record) {
                return $item;
            }

            return [
                ...$item,
                'company_id' => $company,
                'category' => $validated['category'],
                'date_uploaded' => $validated['date_uploaded'],
                'uploaded_by' => $this->currentUserName($request),
                'client' => $companyData['company_name'],
                'tin' => $validated['tin'] ?: $this->companyTin($companyData),
                'status' => $validated['status'],
            ];
        });

        $request->session()->put($this->sessionKey(), $updated->values()->all());

        return redirect()
            ->route('company.accounting', ['company' => $company, 'category' => $validated['category']])
            ->with('accounting_success', 'Accounting entry updated successfully.');
    }

    public function destroy(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompany($request, $company);
        $category = trim((string) $request->input('category', self::CATEGORIES[0]));
        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()));
        $existing = $records->firstWhere('id', $record);

        abort_unless($existing && (int) $existing['company_id'] === $company, 404);

        $request->session()->put(
            $this->sessionKey(),
            $records->reject(fn (array $item) => (int) $item['id'] === $record)->values()->all()
        );

        return redirect()
            ->route('company.accounting', ['company' => $company, 'category' => $category])
            ->with('accounting_success', 'Accounting entry removed successfully.');
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', self::CATEGORIES)],
            'date_uploaded' => ['required', 'date'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:' . implode(',', self::STATUSES)],
        ]);
    }

    private function sessionKey(): string
    {
        return 'company_accounting_records_v2';
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, $this->defaultCompanies());
    }

    private function defaultCompanies(): array
    {
        return [
            ['id' => 1, 'company_name' => 'Company 1', 'company_type' => 'Corporation', 'email' => 'company1@example.com', 'phone' => '09012345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Makati City', 'owner_name' => 'Owner 1', 'created_at' => '2026-03-01 10:00:00'],
            ['id' => 2, 'company_name' => 'Company 2', 'company_type' => 'Corporation', 'email' => 'company2@example.com', 'phone' => '09000345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Taguig City', 'owner_name' => 'Owner 2', 'created_at' => '2026-03-02 10:00:00'],
            ['id' => 3, 'company_name' => 'Company 3', 'company_type' => 'Corporation', 'email' => 'company3@example.com', 'phone' => '09777345678', 'website' => 'https://bigin.example', 'description' => 'Sample company record', 'address' => 'Pasig City', 'owner_name' => 'Owner 3', 'created_at' => '2026-03-03 10:00:00'],
        ];
    }

    private function defaultRecords(): array
    {
        return [];
    }

    private function currentUserName(Request $request): string
    {
        $user = $request->user();

        return trim((string) (
            $user?->name
            ?? $user?->full_name
            ?? $user?->employee_name
            ?? $user?->username
            ?? $user?->email
            ?? 'System User'
        ));
    }

    private function companyTin(array $companyData): string
    {
        return trim((string) ($companyData['tin_no'] ?? $companyData['tin'] ?? $companyData['tin_number'] ?? $companyData['company_tin'] ?? $companyData['tax_identification_number'] ?? ''));
    }
}
