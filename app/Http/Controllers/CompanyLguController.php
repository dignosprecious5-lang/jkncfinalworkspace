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
            'user' => $validated['user'],
            'client' => $companyData['company_name'],
            'tin' => $validated['tin'],
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
                'user' => $validated['user'],
                'client' => $companyData['company_name'],
                'tin' => $validated['tin'],
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
            'user' => ['required', 'string', 'max:255'],
            'tin' => ['required', 'string', 'max:255'],
            'reg' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:Active,For Review,Overdue'],
        ]);
    }

    private function sessionKey(): string
    {
        return 'mock_company_lgu_records';
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
        return [
            ['id' => 1, 'company_id' => 1, 'permit_type' => "Mayor's Permit", 'date' => '2023-10-24', 'user' => 'Admin_Sarah', 'client' => 'Company 1', 'tin' => '009-123', 'reg' => 'Renewed', 'status' => 'Active'],
            ['id' => 2, 'company_id' => 1, 'permit_type' => "Mayor's Permit", 'date' => '2023-11-02', 'user' => 'User_John', 'client' => 'Company 1', 'tin' => '112-987', 'reg' => 'Pending', 'status' => 'For Review'],
            ['id' => 3, 'company_id' => 1, 'permit_type' => "Fire Permit", 'date' => '2024-02-10', 'user' => 'Admin_Mark', 'client' => 'Company 1', 'tin' => '999-000', 'reg' => 'Active', 'status' => 'Active'],
            ['id' => 4, 'company_id' => 2, 'permit_type' => "Mayor's Permit", 'date' => '2024-03-01', 'user' => 'Admin_Sarah', 'client' => 'Company 2', 'tin' => '222-333', 'reg' => 'Pending', 'status' => 'For Review'],
            ['id' => 5, 'company_id' => 2, 'permit_type' => 'Barangay Business Permit', 'date' => '2022-12-15', 'user' => 'User_John', 'client' => 'Company 2', 'tin' => '777-888', 'reg' => 'Expired', 'status' => 'Overdue'],
            ['id' => 6, 'company_id' => 3, 'permit_type' => "Mayor's Permit", 'date' => '2025-05-20', 'user' => 'Admin_Mark', 'client' => 'Company 3', 'tin' => '123-456', 'reg' => 'Active', 'status' => 'Active'],
            ['id' => 7, 'company_id' => 3, 'permit_type' => 'Sanitary Permit', 'date' => '2024-08-14', 'user' => 'User_Anna', 'client' => 'Company 3', 'tin' => '555-111', 'reg' => 'Pending', 'status' => 'For Review'],
            ['id' => 8, 'company_id' => 3, 'permit_type' => "Mayor's Permit", 'date' => '2023-06-30', 'user' => 'Admin_Sarah', 'client' => 'Company 3', 'tin' => '998-776', 'reg' => 'Expired', 'status' => 'Overdue'],
        ];
    }
}
