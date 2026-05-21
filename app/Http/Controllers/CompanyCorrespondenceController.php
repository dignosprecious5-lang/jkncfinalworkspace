<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyCorrespondenceController extends Controller
{
    use ResolvesCompanyRecords;

    private const STATUSES = ['Open', 'Completed', 'Overdue'];

    public function index(Request $request, int $company): View
    {
        $companyData = $this->findCompany($request, $company);
        $records = collect($request->session()->get($this->sessionKey(), $this->defaultRecords()))
            ->where('company_id', $company)
            ->values();

        return view('company.correspondence', [
            'company' => (object) $companyData,
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
            'date_uploaded' => $validated['date_uploaded'],
            'uploaded_by' => $validated['uploaded_by'],
            'client' => $companyData['company_name'],
            'tin' => $validated['tin'],
            'correspondence_type' => $validated['correspondence_type'],
            'document_title' => $validated['document_title'],
            'status' => $validated['status'],
        ]);

        $request->session()->put($this->sessionKey(), $records->values()->all());

        return redirect()
            ->route('company.correspondence', $company)
            ->with('correspondence_success', 'Correspondence entry added successfully.');
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
                'date_uploaded' => $validated['date_uploaded'],
                'uploaded_by' => $validated['uploaded_by'],
                'client' => $companyData['company_name'],
                'tin' => $validated['tin'],
                'correspondence_type' => $validated['correspondence_type'],
                'document_title' => $validated['document_title'],
                'status' => $validated['status'],
            ];
        });

        $request->session()->put($this->sessionKey(), $updated->values()->all());

        return redirect()
            ->route('company.correspondence', $company)
            ->with('correspondence_success', 'Correspondence entry updated successfully.');
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
            ->route('company.correspondence', $company)
            ->with('correspondence_success', 'Correspondence entry removed successfully.');
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'date_uploaded' => ['required', 'date'],
            'uploaded_by' => ['required', 'string', 'max:255'],
            'tin' => ['required', 'string', 'max:255'],
            'correspondence_type' => ['required', 'string', 'max:255'],
            'document_title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:' . implode(',', self::STATUSES)],
        ]);
    }

    private function sessionKey(): string
    {
        return 'mock_company_correspondence_records';
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
            ['id' => 1, 'company_id' => 1, 'date_uploaded' => '2024-04-22', 'uploaded_by' => 'Jasper Bulac', 'client' => 'Company 1', 'tin' => '123-456-756', 'correspondence_type' => 'Internal Memo', 'document_title' => 'Policy Update', 'status' => 'Completed'],
            ['id' => 2, 'company_id' => 1, 'date_uploaded' => '2024-05-03', 'uploaded_by' => 'Lara Cruz', 'client' => 'Company 1', 'tin' => '123-456-756', 'correspondence_type' => 'Letter', 'document_title' => 'Vendor Notice', 'status' => 'Open'],
            ['id' => 3, 'company_id' => 2, 'date_uploaded' => '2024-06-12', 'uploaded_by' => 'Jasper Bulac', 'client' => 'Company 2', 'tin' => '222-333-444', 'correspondence_type' => 'Email', 'document_title' => 'Renewal Reminder', 'status' => 'Overdue'],
            ['id' => 4, 'company_id' => 3, 'date_uploaded' => '2024-07-18', 'uploaded_by' => 'Ana Reyes', 'client' => 'Company 3', 'tin' => '555-111-000', 'correspondence_type' => 'Transmittal', 'document_title' => 'Contract Package', 'status' => 'Open'],
        ];
    }
}
