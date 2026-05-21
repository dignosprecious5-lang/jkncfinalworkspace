<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyBirTaxController extends Controller
{
    use ResolvesCompanyRecords;

    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $searchTin = trim((string) $request->query('search_tin', ''));
        $searchTaxpayer = trim((string) $request->query('search_taxpayer', ''));

        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()))
            ->where('company_id', $company)
            ->when($searchTin !== '', function (Collection $items) use ($searchTin) {
                $term = strtolower($searchTin);

                return $items->filter(fn (array $item) => str_contains(strtolower((string) ($item['tin'] ?? '')), $term));
            })
            ->when($searchTaxpayer !== '', function (Collection $items) use ($searchTaxpayer) {
                $term = strtolower($searchTaxpayer);

                return $items->filter(fn (array $item) => str_contains(strtolower((string) ($item['tax_payer'] ?? '')), $term));
            })
            ->values();

        return view('company.bir-tax', [
            'company' => (object) $companyData,
            'records' => $records,
            'searchTin' => $searchTin,
            'searchTaxpayer' => $searchTaxpayer,
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
            'tin' => $validated['tin'],
            'tax_payer' => $validated['tax_payer'],
            'registering_office' => $validated['registering_office'],
            'registered_address' => $validated['registered_address'],
            'tax_types' => $validated['tax_types'],
            'form_type' => $validated['form_type'],
            'filing_frequency' => $validated['filing_frequency'],
            'due_date' => $validated['due_date'],
            'uploaded_by' => $validated['uploaded_by'],
            'date_uploaded' => $validated['date_uploaded'],
            'uploaded_file' => $validated['uploaded_file'] ?? '',
            'company_name' => $companyData['company_name'],
        ]);

        $request->session()->put($this->sessionKey(), $records->values()->all());

        return redirect()
            ->route('company.bir-tax', $company)
            ->with('bir_tax_success', 'BIR & Tax record added successfully.');
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
                'tin' => $validated['tin'],
                'tax_payer' => $validated['tax_payer'],
                'registering_office' => $validated['registering_office'],
                'registered_address' => $validated['registered_address'],
                'tax_types' => $validated['tax_types'],
                'form_type' => $validated['form_type'],
                'filing_frequency' => $validated['filing_frequency'],
                'due_date' => $validated['due_date'],
                'uploaded_by' => $validated['uploaded_by'],
                'date_uploaded' => $validated['date_uploaded'],
                'uploaded_file' => $validated['uploaded_file'] ?? ($item['uploaded_file'] ?? ''),
                'company_name' => $companyData['company_name'],
            ];
        });

        $request->session()->put($this->sessionKey(), $updated->values()->all());

        return redirect()
            ->route('company.bir-tax', $company)
            ->with('bir_tax_success', 'BIR & Tax record updated successfully.');
    }

    public function destroy(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompany($request, $company);
        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()));
        $existing = $records->firstWhere('id', $record);

        abort_unless($existing && (int) $existing['company_id'] === $company, 404);

        $request->session()->put(
            $this->sessionKey(),
            $records->reject(fn (array $item) => (int) $item['id'] === $record)->values()->all()
        );

        return redirect()
            ->route('company.bir-tax', $company)
            ->with('bir_tax_success', 'BIR & Tax record removed successfully.');
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'tin' => ['required', 'string', 'max:255'],
            'tax_payer' => ['required', 'string', 'max:255'],
            'registering_office' => ['required', 'string', 'max:255'],
            'registered_address' => ['required', 'string', 'max:255'],
            'tax_types' => ['required', 'string', 'max:255'],
            'form_type' => ['required', 'string', 'max:255'],
            'filing_frequency' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'uploaded_by' => ['required', 'string', 'max:255'],
            'date_uploaded' => ['required', 'date'],
            'uploaded_file' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function sessionKey(): string
    {
        return 'mock_company_bir_tax_records';
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
        return [
            ['id' => 1, 'company_id' => 1, 'tin' => '123-456-789-000', 'tax_payer' => 'Company 1', 'registering_office' => 'BIR RDO 44', 'registered_address' => 'Makati City, PH', 'tax_types' => 'VAT, WHT', 'form_type' => '1701Q', 'filing_frequency' => 'Quarterly', 'due_date' => '2024-04-30', 'uploaded_by' => 'Admin User', 'date_uploaded' => '2024-02-06', 'uploaded_file' => 'bir-company-1-q1.pdf', 'company_name' => 'Company 1'],
            ['id' => 2, 'company_id' => 1, 'tin' => '123-456-789-000', 'tax_payer' => 'Company 1', 'registering_office' => 'BIR RDO 44', 'registered_address' => 'Makati City, PH', 'tax_types' => 'Income Tax', 'form_type' => '1702', 'filing_frequency' => 'Annual', 'due_date' => '2024-04-15', 'uploaded_by' => 'Compliance Officer', 'date_uploaded' => '2024-02-18', 'uploaded_file' => 'bir-company-1-annual.pdf', 'company_name' => 'Company 1'],
            ['id' => 3, 'company_id' => 2, 'tin' => '987-654-321-000', 'tax_payer' => 'Company 2', 'registering_office' => 'BIR RDO 51', 'registered_address' => 'Quezon City, PH', 'tax_types' => 'Percentage Tax', 'form_type' => '2551M', 'filing_frequency' => 'Monthly', 'due_date' => '2024-03-20', 'uploaded_by' => 'Finance Manager', 'date_uploaded' => '2024-02-12', 'uploaded_file' => 'bir-company-2-monthly.pdf', 'company_name' => 'Company 2'],
            ['id' => 4, 'company_id' => 3, 'tin' => '555-222-333-000', 'tax_payer' => 'Company 3', 'registering_office' => 'BIR RDO 38', 'registered_address' => 'Cebu City, PH', 'tax_types' => 'VAT', 'form_type' => '2550Q', 'filing_frequency' => 'Quarterly', 'due_date' => '2024-05-15', 'uploaded_by' => 'Tax Specialist', 'date_uploaded' => '2024-03-12', 'uploaded_file' => 'bir-company-3-vat.pdf', 'company_name' => 'Company 3'],
        ];
    }
}
