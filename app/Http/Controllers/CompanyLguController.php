<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyLguController extends Controller
{
    use ResolvesCompanyRecords;

    private const PERMIT_TYPES = [
        "Mayor's Permit",
        'Barangay Business Permit',
        'Fire Permit',
        'Sanitary Permit',
        'OBO',
    ];

    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $permit = trim((string) $request->query('permit', self::PERMIT_TYPES[0]));
        $status = trim((string) $request->query('status', 'all'));

        if (! in_array($permit, self::PERMIT_TYPES, true)) {
            $permit = self::PERMIT_TYPES[0];
        }

        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()))
            ->where('company_id', $company)
            ->where('permit_type', $permit)
            ->when($status !== 'all', fn (Collection $items) => $items->where('status', $status))
            ->values();

        return view('company.lgu', [
            'company' => (object) $companyData,
            'companyTin' => $this->companyTin($companyData),
            'currentUserName' => $this->currentUserName($request),
            'permitTypes' => self::PERMIT_TYPES,
            'selectedPermit' => $permit,
            'selectedStatus' => $status,
            'records' => $records,
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
            'permit_type' => $validated['permit_type'],
            'date' => $validated['date'],
            'user' => $this->currentUserName($request),
            'client' => $companyData['company_name'],
            'tin' => $validated['tin'] ?: $this->companyTin($companyData),
            'reg' => $validated['reg'],
            'status' => $validated['status'],
        ]);

        $request->session()->put($this->sessionKey(), $records->values()->all());

        return redirect()
            ->route('company.lgu', ['company' => $company, 'permit' => $validated['permit_type']])
            ->with('lgu_success', 'LGU record added successfully.');
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
                'permit_type' => $validated['permit_type'],
                'date' => $validated['date'],
                'user' => $this->currentUserName($request),
                'client' => $companyData['company_name'],
                'tin' => $validated['tin'] ?: $this->companyTin($companyData),
                'reg' => $validated['reg'],
                'status' => $validated['status'],
            ];
        });

        $request->session()->put($this->sessionKey(), $updated->values()->all());

        return redirect()
            ->route('company.lgu', ['company' => $company, 'permit' => $validated['permit_type']])
            ->with('lgu_success', 'LGU record updated successfully.');
    }

    public function destroy(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompany($request, $company);
        $permit = trim((string) $request->input('permit', self::PERMIT_TYPES[0]));
        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()));
        $existing = $records->firstWhere('id', $record);

        abort_unless($existing && (int) $existing['company_id'] === $company, 404);

        $request->session()->put(
            $this->sessionKey(),
            $records->reject(fn (array $item) => (int) $item['id'] === $record)->values()->all()
        );

        return redirect()
            ->route('company.lgu', ['company' => $company, 'permit' => $permit])
            ->with('lgu_success', 'LGU record removed successfully.');
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'permit_type' => ['required', 'string', 'in:' . implode(',', self::PERMIT_TYPES)],
            'date' => ['required', 'date'],
            'user' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],
            'reg' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:Active,For Review,Overdue'],
        ]);
    }

    private function sessionKey(): string
    {
        return 'company_lgu_records_v2';
    }

    private function findCompany(Request $request, int $company): array
    {
        return $this->resolveCompanyRecord($request, $company, $this->defaultCompanies());
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
