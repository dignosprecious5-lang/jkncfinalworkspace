<?php

namespace App\Http\Controllers;

use App\Models\Bylaw;
use App\Models\GisRecord;
use App\Models\SecAoi;
use App\Models\SecCoi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyCorporateFormationController extends Controller
{
    public function index(int $company): RedirectResponse
    {
        return redirect()->route('company.corporate-formation.sec-coi', $company);
    }

    public function secCoi(Request $request, int $company): View
    {
        return $this->renderTab($request, $company, 'sec-coi');
    }

    public function secAoi(Request $request, int $company): View
    {
        return $this->renderTab($request, $company, 'sec-aoi');
    }

    public function bylaws(Request $request, int $company): View
    {
        return $this->renderTab($request, $company, 'bylaws');
    }

    public function gis(Request $request, int $company): View
    {
        return $this->renderTab($request, $company, 'gis');
    }

    public function storeSecCoi(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $validated = $request->validate([
            'corporate_name' => ['required', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'issued_by' => ['required', 'string', 'max:255'],
            'issued_on' => ['required', 'date'],
            'date_upload' => ['required', 'date'],
            'file_upload' => ['nullable', 'file'],
        ]);

        $payload = [
            'corporate_name' => $validated['corporate_name'],
            'company_reg_no' => $validated['company_reg_no'],
            'issued_by' => $validated['issued_by'],
            'issued_on' => $validated['issued_on'],
            'date_upload' => $validated['date_upload'],
            'file_path' => $this->storeUploadedFile($request, 'file_upload', 'uploads/sec-coi'),
        ];

        $this->createCompanyScopedRecord(new SecCoi(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.sec-coi', $company)
            ->with('corporate_formation_success', 'SEC-COI record added for ' . $companyData['company_name'] . '.');
    }

    public function updateSecCoi(Request $request, int $company, int $record): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $validated = $request->validate([
            'corporate_name' => ['required', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'issued_by' => ['required', 'string', 'max:255'],
            'issued_on' => ['required', 'date'],
            'date_upload' => ['required', 'date'],
            'file_upload' => ['nullable', 'file'],
        ]);

        $model = $this->resolveCompanyRecord(SecCoi::query(), new SecCoi(), $record, $company);
        $payload = [
            'corporate_name' => $validated['corporate_name'],
            'company_reg_no' => $validated['company_reg_no'],
            'issued_by' => $validated['issued_by'],
            'issued_on' => $validated['issued_on'],
            'date_upload' => $validated['date_upload'],
        ];

        if ($filePath = $this->storeUploadedFile($request, 'file_upload', 'uploads/sec-coi')) {
            $payload['file_path'] = $filePath;
        }

        $model->update($this->attachCompanyId(new SecCoi(), $payload, $company));

        return redirect()
            ->route('company.corporate-formation.sec-coi', $company)
            ->with('corporate_formation_success', 'SEC-COI record updated for ' . $companyData['company_name'] . '.');
    }

    public function storeSecAoi(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $validated = $request->validate([
            'corporation_name' => ['required', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'principal_address' => ['nullable', 'string', 'max:255'],
            'par_value' => ['nullable', 'string', 'max:255'],
            'authorized_capital_stock' => ['nullable', 'string', 'max:255'],
            'directors' => ['nullable', 'integer', 'min:0'],
            'type_of_formation' => ['nullable', 'string', 'max:255'],
            'aoi_version' => ['nullable', 'string', 'max:255'],
            'aoi_type' => ['nullable', 'string', 'max:255'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'date_upload' => ['required', 'date'],
            'file_upload' => ['nullable', 'file'],
        ]);

        $payload = [
            'corporation_name' => $validated['corporation_name'],
            'company_reg_no' => $validated['company_reg_no'],
            'principal_address' => $validated['principal_address'] ?? null,
            'par_value' => $validated['par_value'] ?? null,
            'authorized_capital_stock' => $validated['authorized_capital_stock'] ?? null,
            'directors' => $validated['directors'] ?? null,
            'type_of_formation' => $validated['type_of_formation'] ?? null,
            'aoi_version' => $validated['aoi_version'] ?? null,
            'aoi_type' => $validated['aoi_type'] ?? null,
            'uploaded_by' => $validated['uploaded_by'] ?? null,
            'date_upload' => $validated['date_upload'],
            'file_path' => $request->hasFile('file_upload') ? $request->file('file_upload')->store('sec_aoi', 'public') : null,
        ];

        $this->createCompanyScopedRecord(new SecAoi(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.sec-aoi', $company)
            ->with('corporate_formation_success', 'SEC-AOI record added for ' . $companyData['company_name'] . '.');
    }

    public function updateSecAoi(Request $request, int $company, int $record): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $validated = $request->validate([
            'corporation_name' => ['required', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'principal_address' => ['nullable', 'string', 'max:255'],
            'par_value' => ['nullable', 'string', 'max:255'],
            'authorized_capital_stock' => ['nullable', 'string', 'max:255'],
            'directors' => ['nullable', 'integer', 'min:0'],
            'type_of_formation' => ['nullable', 'string', 'max:255'],
            'aoi_version' => ['nullable', 'string', 'max:255'],
            'aoi_type' => ['nullable', 'string', 'max:255'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'date_upload' => ['required', 'date'],
            'file_upload' => ['nullable', 'file'],
        ]);

        $model = $this->resolveCompanyRecord(SecAoi::query(), new SecAoi(), $record, $company);
        $payload = [
            'corporation_name' => $validated['corporation_name'],
            'company_reg_no' => $validated['company_reg_no'],
            'principal_address' => $validated['principal_address'] ?? null,
            'par_value' => $validated['par_value'] ?? null,
            'authorized_capital_stock' => $validated['authorized_capital_stock'] ?? null,
            'directors' => $validated['directors'] ?? null,
            'type_of_formation' => $validated['type_of_formation'] ?? null,
            'aoi_version' => $validated['aoi_version'] ?? null,
            'aoi_type' => $validated['aoi_type'] ?? null,
            'uploaded_by' => $validated['uploaded_by'] ?? null,
            'date_upload' => $validated['date_upload'],
        ];

        if ($request->hasFile('file_upload')) {
            $payload['file_path'] = $request->file('file_upload')->store('sec_aoi', 'public');
        }

        $model->update($this->attachCompanyId(new SecAoi(), $payload, $company));

        return redirect()
            ->route('company.corporate-formation.sec-aoi', $company)
            ->with('corporate_formation_success', 'SEC-AOI record updated for ' . $companyData['company_name'] . '.');
    }

    public function storeBylaw(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $payload = $this->validateBylaw($request);
        $payload['file_path'] = $request->hasFile('file_upload') ? $request->file('file_upload')->store('bylaws', 'public') : null;

        $this->createCompanyScopedRecord(new Bylaw(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.bylaws', $company)
            ->with('corporate_formation_success', 'Bylaws record added for ' . $companyData['company_name'] . '.');
    }

    public function updateBylaw(Request $request, int $company, int $record): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $model = $this->resolveCompanyRecord(Bylaw::query(), new Bylaw(), $record, $company);
        $payload = $this->validateBylaw($request);

        if ($request->hasFile('file_upload')) {
            $payload['file_path'] = $request->file('file_upload')->store('bylaws', 'public');
        }

        $model->update($this->attachCompanyId(new Bylaw(), $payload, $company));

        return redirect()
            ->route('company.corporate-formation.bylaws', $company)
            ->with('corporate_formation_success', 'Bylaws record updated for ' . $companyData['company_name'] . '.');
    }

    public function storeGis(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $payload = $this->validateGis($request);
        $payload['file'] = $request->hasFile('file') ? $request->file('file')->store('gis_files', 'public') : null;

        $this->createCompanyScopedRecord(new GisRecord(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.gis', $company)
            ->with('corporate_formation_success', 'GIS record added for ' . $companyData['company_name'] . '.');
    }

    public function updateGis(Request $request, int $company, int $record): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $model = $this->resolveCompanyRecord(GisRecord::query(), new GisRecord(), $record, $company);
        $payload = $this->validateGis($request);

        if ($request->hasFile('file')) {
            $payload['file'] = $request->file('file')->store('gis_files', 'public');
        }

        $model->update($this->attachCompanyId(new GisRecord(), $payload, $company));

        return redirect()
            ->route('company.corporate-formation.gis', $company)
            ->with('corporate_formation_success', 'GIS record updated for ' . $companyData['company_name'] . '.');
    }

    private function renderTab(Request $request, int $company, string $tab): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        [$model, $table] = match ($tab) {
            'sec-coi' => [new SecCoi(), 'sec_coi'],
            'sec-aoi' => [new SecAoi(), 'sec_aois'],
            'bylaws' => [new Bylaw(), 'bylaws'],
            'gis' => [new GisRecord(), 'gis_records'],
        };

        $records = $model::query()
            ->when(
                Schema::hasColumn($table, 'company_id'),
                fn ($query) => $query->where('company_id', $company)
            )
            ->latest()
            ->get();

        return view('company.corporate-formation', [
            'company' => (object) $companyData,
            'records' => $records,
            'activeTab' => $tab,
        ]);
    }

    private function validateBylaw(Request $request): array
    {
        return $request->validate([
            'corporation_name' => ['required', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'type_of_formation' => ['nullable', 'string', 'max:255'],
            'aoi_version' => ['nullable', 'string', 'max:255'],
            'aoi_type' => ['nullable', 'string', 'max:255'],
            'aoi_date' => ['nullable', 'date'],
            'regular_asm' => ['nullable', 'string', 'max:255'],
            'asm_notice' => ['nullable', 'string', 'max:255'],
            'regular_bodm' => ['nullable', 'string', 'max:255'],
            'bodm_notice' => ['nullable', 'string', 'max:255'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'date_upload' => ['required', 'date'],
            'file_upload' => ['nullable', 'file'],
        ]);
    }

    private function validateGis(Request $request): array
    {
        return $request->validate([
            'uploaded_by' => ['nullable', 'string', 'max:255'],
            'submission_status' => ['nullable', 'string', 'max:255'],
            'receive_on' => ['nullable', 'date'],
            'period_date' => ['nullable', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'corporation_name' => ['required', 'string', 'max:255'],
            'annual_meeting' => ['nullable', 'date'],
            'meeting_type' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file'],
        ]);
    }

    private function createCompanyScopedRecord(Model $model, array $payload, int $company): void
    {
        $model::create($this->attachCompanyId($model, $payload, $company));
    }

    private function attachCompanyId(Model $model, array $payload, int $company): array
    {
        if (Schema::hasColumn($model->getTable(), 'company_id')) {
            $payload['company_id'] = $company;
        }

        return $payload;
    }

    private function resolveCompanyRecord($query, Model $model, int $record, int $company): Model
    {
        $scopedQuery = Schema::hasColumn($model->getTable(), 'company_id')
            ? $query->where('company_id', $company)
            : $query;

        return $scopedQuery->findOrFail($record);
    }

    private function storeUploadedFile(Request $request, string $key, string $directory): ?string
    {
        if (! $request->hasFile($key)) {
            return null;
        }

        $file = $request->file($key);
        $fileName = time() . '_' . $this->sanitizeFileName($file->getClientOriginalName());

        return $file->storeAs($directory, $fileName, 'public');
    }

    private function sanitizeFileName(string $fileName): string
    {
        return preg_replace('/[^A-Za-z0-9.\-_]/', '_', $fileName) ?: 'uploaded_file';
    }

    private function findCompanyOrAbort(Request $request, int $company): array
    {
        $companyData = collect($request->session()->get('mock_companies', $this->defaultCompanies()))
            ->firstWhere('id', $company);

        abort_unless($companyData, 404);

        return $companyData;
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
}
