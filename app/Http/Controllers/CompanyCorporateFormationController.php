<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Bylaw;
use App\Models\GisRecord;
use App\Models\SecAoi;
use App\Models\SecCoi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyCorporateFormationController extends Controller
{
    use ResolvesCompanyRecords;

    public function index(int $company): RedirectResponse
    {
        return redirect()->route('company.corporate-formation.sec-coi', $company);
    }

    // -------------------------------------------------------------------------
    // List views
    // -------------------------------------------------------------------------

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

    public function notices(Request $request, int $company): View
    {
        return $this->renderComingSoonTab($request, $company, 'notices', 'Notices of Meeting');
    }

    public function minutes(Request $request, int $company): View
    {
        return $this->renderComingSoonTab($request, $company, 'minutes', 'Minutes of Meeting');
    }

    public function resolutions(Request $request, int $company): View
    {
        return $this->renderComingSoonTab($request, $company, 'resolution', 'Resolution');
    }

    public function secretaryCertificates(Request $request, int $company): View
    {
        return $this->renderComingSoonTab($request, $company, 'secretary', 'Secretary Certificates');
    }

    // -------------------------------------------------------------------------
    // Preview / show views
    // -------------------------------------------------------------------------

    public function showSecCoi(Request $request, int $company, int $record): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $model = $this->scopeModelRecord(SecCoi::query(), new SecCoi(), $record, $company);

        return view('company.corporate-formation-sec-coi-preview', [
            'company' => (object) $companyData,
            'record'  => $model,
        ]);
    }

    public function showSecAoi(Request $request, int $company, int $record): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $model = $this->scopeModelRecord(SecAoi::query(), new SecAoi(), $record, $company);

        return view('company.corporate-formation-sec-aoi-preview', [
            'company' => (object) $companyData,
            'record'  => $model,
        ]);
    }

    public function showBylaw(Request $request, int $company, int $record): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $model = $this->scopeModelRecord(Bylaw::query(), new Bylaw(), $record, $company);

        return view('company.corporate-formation-bylaws-preview', [
            'company' => (object) $companyData,
            'record'  => $model,
        ]);
    }

    public function showGis(Request $request, int $company, int $record): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $model = $this->scopeModelRecord(GisRecord::query(), new GisRecord(), $record, $company);

        return view('company.corporate-formation-gis-show', [
            'company' => (object) $companyData,
            'gis'     => $model,
        ]);
    }

    // -------------------------------------------------------------------------
    // SEC-COI store / update / upload / submit
    // -------------------------------------------------------------------------

    public function storeSecCoi(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $request->validate([
            'corporate_name'      => ['required', 'string', 'max:255'],
            'company_reg_no'      => ['required', 'string', 'max:255'],
            'issued_on'           => ['required', 'date'],
            'date_upload'         => ['required', 'date'],
            'draft_file_upload'   => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'notary_file_upload'  => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $payload = [
            'corporate_name'   => $request->corporate_name,
            'company_reg_no'   => $request->company_reg_no,
            'issued_by'        => $this->employeeName(),
            'issued_on'        => $request->issued_on,
            'date_upload'      => $request->date_upload,
            'file_path'        => $this->storeUploadedFile($request, 'draft_file_upload', 'uploads/sec-coi'),
            'notary_file_path' => $this->storeUploadedFile($request, 'notary_file_upload', 'uploads/sec-coi'),
            'workflow_status'  => 'Uploaded',
            'approval_status'  => 'Pending',
            'submitted_by'     => Auth::id(),
        ];

        $this->createCompanyScopedRecord(new SecCoi(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.sec-coi', $company)
            ->with('corporate_formation_success', 'SEC-COI record added for ' . $companyData['company_name'] . '.');
    }

    public function updateSecCoi(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $request->validate([
            'corporate_name' => ['required', 'string', 'max:255'],
            'company_reg_no' => ['required', 'string', 'max:255'],
            'issued_on'      => ['required', 'date'],
            'date_upload'    => ['required', 'date'],
        ]);

        $model = $this->scopeModelRecord(SecCoi::query(), new SecCoi(), $record, $company);
        $model->update([
            'corporate_name' => $request->corporate_name,
            'company_reg_no' => $request->company_reg_no,
            'issued_on'      => $request->issued_on,
            'date_upload'    => $request->date_upload,
        ]);

        return back()->with('corporate_formation_success', 'SEC-COI details updated successfully.');
    }

    public function uploadDraftSecCoi(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['draft_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(SecCoi::query(), new SecCoi(), $record, $company);
        $this->abortIfNotEditable($model);

        $model->update(['file_path' => $this->storeUploadedFile($request, 'draft_file', 'uploads/sec-coi')]);

        return back()->with('corporate_formation_success', 'Draft file attached successfully.');
    }

    public function uploadNotarySecCoi(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['notary_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(SecCoi::query(), new SecCoi(), $record, $company);
        $this->abortIfNotEditable($model);

        $model->update(['notary_file_path' => $this->storeUploadedFile($request, 'notary_file', 'uploads/sec-coi')]);

        return back()->with('corporate_formation_success', 'Notary file attached successfully.');
    }

    public function submitSecCoi(Request $request, int $company, int $record): RedirectResponse
    {
        $model = $this->scopeModelRecord(SecCoi::query(), new SecCoi(), $record, $company);
        $this->abortIfNotEditable($model);

        $model->update(['workflow_status' => 'Submitted', 'approval_status' => 'Pending', 'review_note' => null]);

        return back()->with('corporate_formation_success', 'SEC-COI submitted for approval.');
    }

    // -------------------------------------------------------------------------
    // SEC-AOI store / update / upload / submit
    // -------------------------------------------------------------------------

    public function storeSecAoi(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $request->validate([
            'corporation_name'         => ['required', 'string', 'max:255'],
            'company_reg_no'           => ['required', 'string', 'max:255'],
            'principal_address'        => ['nullable', 'string', 'max:255'],
            'par_value'                => ['nullable', 'string', 'max:255'],
            'authorized_capital_stock' => ['nullable', 'string', 'max:255'],
            'directors'                => ['nullable', 'integer', 'min:0'],
            'type_of_formation'        => ['nullable', 'string', 'max:255'],
            'aoi_version'              => ['nullable', 'string', 'max:255'],
            'aoi_type'                 => ['nullable', 'string', 'max:255'],
            'date_upload'              => ['required', 'date'],
            'draft_file_upload'        => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'notary_file_upload'       => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $payload = [
            'corporation_name'         => $request->corporation_name,
            'company_reg_no'           => $request->company_reg_no,
            'principal_address'        => $request->principal_address,
            'par_value'                => $request->par_value,
            'authorized_capital_stock' => $request->authorized_capital_stock,
            'directors'                => $request->directors,
            'type_of_formation'        => $request->type_of_formation,
            'aoi_version'              => $request->aoi_version,
            'aoi_type'                 => $request->aoi_type,
            'uploaded_by'              => $this->employeeName(),
            'date_upload'              => $request->date_upload,
            'file_path'                => $this->storeFileAs($request, 'draft_file_upload', 'sec_aoi', 'draft'),
            'notary_file_path'         => $this->storeFileAs($request, 'notary_file_upload', 'sec_aoi', 'notary'),
            'workflow_status'          => 'Uploaded',
            'approval_status'          => 'Pending',
            'submitted_by'             => Auth::id(),
        ];

        $this->createCompanyScopedRecord(new SecAoi(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.sec-aoi', $company)
            ->with('corporate_formation_success', 'SEC-AOI record added for ' . $companyData['company_name'] . '.');
    }

    public function updateSecAoi(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $request->validate([
            'corporation_name'         => ['required', 'string', 'max:255'],
            'company_reg_no'           => ['required', 'string', 'max:255'],
            'principal_address'        => ['nullable', 'string', 'max:255'],
            'par_value'                => ['nullable', 'string', 'max:255'],
            'authorized_capital_stock' => ['nullable', 'string', 'max:255'],
            'directors'                => ['nullable', 'integer', 'min:0'],
            'type_of_formation'        => ['nullable', 'string', 'max:255'],
            'aoi_version'              => ['nullable', 'string', 'max:255'],
            'aoi_type'                 => ['nullable', 'string', 'max:255'],
            'date_upload'              => ['required', 'date'],
        ]);

        $model = $this->scopeModelRecord(SecAoi::query(), new SecAoi(), $record, $company);
        $model->update([
            'corporation_name'         => $request->corporation_name,
            'company_reg_no'           => $request->company_reg_no,
            'principal_address'        => $request->principal_address,
            'par_value'                => $request->par_value,
            'authorized_capital_stock' => $request->authorized_capital_stock,
            'directors'                => $request->directors,
            'type_of_formation'        => $request->type_of_formation,
            'aoi_version'              => $request->aoi_version,
            'aoi_type'                 => $request->aoi_type,
            'date_upload'              => $request->date_upload,
        ]);

        return back()->with('corporate_formation_success', 'SEC-AOI details updated successfully.');
    }

    public function uploadDraftSecAoi(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['draft_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(SecAoi::query(), new SecAoi(), $record, $company);
        $this->abortIfNotEditable($model);

        $file = $request->file('draft_file');
        $fileName = time() . '_draft_' . $file->getClientOriginalName();
        $file->storeAs('sec_aoi', $fileName, 'public');
        $model->update(['file_path' => 'sec_aoi/' . $fileName]);

        return back()->with('corporate_formation_success', 'Draft file attached successfully.');
    }

    public function uploadNotarySecAoi(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['notary_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(SecAoi::query(), new SecAoi(), $record, $company);
        $this->abortIfNotEditable($model);

        $file = $request->file('notary_file');
        $fileName = time() . '_notary_' . $file->getClientOriginalName();
        $file->storeAs('sec_aoi', $fileName, 'public');
        $model->update(['notary_file_path' => 'sec_aoi/' . $fileName]);

        return back()->with('corporate_formation_success', 'Notary file attached successfully.');
    }

    public function submitSecAoi(Request $request, int $company, int $record): RedirectResponse
    {
        $model = $this->scopeModelRecord(SecAoi::query(), new SecAoi(), $record, $company);
        $this->abortIfNotEditable($model);

        $model->update(['workflow_status' => 'Submitted', 'approval_status' => 'Pending', 'review_note' => null]);

        return back()->with('corporate_formation_success', 'SEC-AOI submitted for approval.');
    }

    // -------------------------------------------------------------------------
    // Bylaws store / update / upload / submit
    // -------------------------------------------------------------------------

    public function storeBylaw(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $payload = $this->validateBylaw($request);

        $payload['file_path']        = $this->storeFileAs($request, 'draft_file_upload', 'bylaws', 'draft');
        $payload['notary_file_path'] = $this->storeFileAs($request, 'notary_file_upload', 'bylaws', 'notary');
        $payload['uploaded_by']      = $this->employeeName();
        $payload['workflow_status']  = 'Uploaded';
        $payload['approval_status']  = 'Pending';
        $payload['submitted_by']     = Auth::id();

        $this->createCompanyScopedRecord(new Bylaw(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.bylaws', $company)
            ->with('corporate_formation_success', 'Bylaws record added for ' . $companyData['company_name'] . '.');
    }

    public function updateBylaw(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $model = $this->scopeModelRecord(Bylaw::query(), new Bylaw(), $record, $company);
        $model->update($this->validateBylaw($request));

        return back()->with('corporate_formation_success', 'Bylaws details updated successfully.');
    }

    public function uploadDraftBylaw(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['draft_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(Bylaw::query(), new Bylaw(), $record, $company);
        $this->abortIfNotEditable($model);

        $file = $request->file('draft_file');
        $fileName = time() . '_draft_' . $file->getClientOriginalName();
        $file->storeAs('bylaws', $fileName, 'public');
        $model->update(['file_path' => 'bylaws/' . $fileName]);

        return back()->with('corporate_formation_success', 'Draft file attached successfully.');
    }

    public function uploadNotaryBylaw(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['notary_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(Bylaw::query(), new Bylaw(), $record, $company);
        $this->abortIfNotEditable($model);

        $file = $request->file('notary_file');
        $fileName = time() . '_notary_' . $file->getClientOriginalName();
        $file->storeAs('bylaws', $fileName, 'public');
        $model->update(['notary_file_path' => 'bylaws/' . $fileName]);

        return back()->with('corporate_formation_success', 'Notary file attached successfully.');
    }

    public function submitBylaw(Request $request, int $company, int $record): RedirectResponse
    {
        $model = $this->scopeModelRecord(Bylaw::query(), new Bylaw(), $record, $company);
        $this->abortIfNotEditable($model);

        $model->update(['workflow_status' => 'Submitted', 'approval_status' => 'Pending', 'review_note' => null]);

        return back()->with('corporate_formation_success', 'Bylaws submitted for approval.');
    }

    // -------------------------------------------------------------------------
    // GIS store / update / upload / submit
    // -------------------------------------------------------------------------

    public function storeGis(Request $request, int $company): RedirectResponse
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        $payload = $this->validateGis($request);

        if ($request->hasFile('draft_file_upload')) {
            $file = $request->file('draft_file_upload');
            $fileName = time() . '_draft_' . $file->getClientOriginalName();
            $file->storeAs('gis_files', $fileName, 'public');
            $payload['file'] = 'gis_files/' . $fileName;
        }

        if ($request->hasFile('notary_file_upload')) {
            $file = $request->file('notary_file_upload');
            $fileName = time() . '_notary_' . $file->getClientOriginalName();
            $file->storeAs('gis_files', $fileName, 'public');
            $payload['notary_file_path'] = 'gis_files/' . $fileName;
        }

        $payload['uploaded_by']     = $this->employeeName();
        $payload['workflow_status'] = 'Uploaded';
        $payload['approval_status'] = 'Pending';
        $payload['submitted_by']    = Auth::id();

        $this->createCompanyScopedRecord(new GisRecord(), $payload, $company);

        return redirect()
            ->route('company.corporate-formation.gis', $company)
            ->with('corporate_formation_success', 'GIS record added for ' . $companyData['company_name'] . '.');
    }

    public function updateGis(Request $request, int $company, int $record): RedirectResponse
    {
        $this->findCompanyOrAbort($request, $company);
        $model = $this->scopeModelRecord(GisRecord::query(), new GisRecord(), $record, $company);
        $model->update($this->validateGis($request));

        return back()->with('corporate_formation_success', 'GIS details updated successfully.');
    }

    public function uploadDraftGis(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['draft_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(GisRecord::query(), new GisRecord(), $record, $company);
        $this->abortIfNotEditable($model);

        $file = $request->file('draft_file');
        $fileName = time() . '_draft_' . $file->getClientOriginalName();
        $file->storeAs('gis_files', $fileName, 'public');
        $model->update(['file' => 'gis_files/' . $fileName]);

        return back()->with('corporate_formation_success', 'Draft file attached successfully.');
    }

    public function uploadNotaryGis(Request $request, int $company, int $record): RedirectResponse
    {
        $request->validate(['notary_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240']]);
        $model = $this->scopeModelRecord(GisRecord::query(), new GisRecord(), $record, $company);
        $this->abortIfNotEditable($model);

        $file = $request->file('notary_file');
        $fileName = time() . '_notary_' . $file->getClientOriginalName();
        $file->storeAs('gis_files', $fileName, 'public');
        $model->update(['notary_file_path' => 'gis_files/' . $fileName]);

        return back()->with('corporate_formation_success', 'Notary file attached successfully.');
    }

    public function submitGis(Request $request, int $company, int $record): RedirectResponse
    {
        $model = $this->scopeModelRecord(GisRecord::query(), new GisRecord(), $record, $company);
        $this->abortIfNotEditable($model);

        $model->update(['workflow_status' => 'Submitted', 'approval_status' => 'Pending', 'review_note' => null]);

        return back()->with('corporate_formation_success', 'GIS submitted for approval.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function renderTab(Request $request, int $company, string $tab): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);
        [$model, $table] = match ($tab) {
            'sec-coi' => [new SecCoi(), 'sec_coi'],
            'sec-aoi' => [new SecAoi(), 'sec_aois'],
            'bylaws'  => [new Bylaw(), 'bylaws'],
            'gis'     => [new GisRecord(), 'gis_records'],
        };

        $records = $model::query()
            ->when(
                Schema::hasColumn($table, 'company_id'),
                fn ($q) => $q->where('company_id', $company)
            )
            ->latest()
            ->get();

        return view('company.corporate-formation', [
            'company'   => (object) $companyData,
            'records'   => $records,
            'activeTab' => $tab,
        ]);
    }

    private function validateBylaw(Request $request): array
    {
        return $request->validate([
            'corporation_name' => ['required', 'string', 'max:255'],
            'company_reg_no'   => ['required', 'string', 'max:255'],
            'type_of_formation' => ['nullable', 'string', 'max:255'],
            'aoi_version'       => ['nullable', 'string', 'max:255'],
            'aoi_type'          => ['nullable', 'string', 'max:255'],
            'aoi_date'          => ['nullable', 'date'],
            'regular_asm'       => ['nullable', 'string', 'max:255'],
            'asm_notice'        => ['nullable', 'string', 'max:255'],
            'regular_bodm'      => ['nullable', 'string', 'max:255'],
            'bodm_notice'       => ['nullable', 'string', 'max:255'],
            'date_upload'       => ['required', 'date'],
        ]);
    }

    private function validateGis(Request $request): array
    {
        return $request->validate([
            'submission_status' => ['nullable', 'string', 'max:255'],
            'receive_on'        => ['nullable', 'date'],
            'period_date'       => ['nullable', 'string', 'max:255'],
            'company_reg_no'    => ['required', 'string', 'max:255'],
            'corporation_name'  => ['required', 'string', 'max:255'],
            'annual_meeting'    => ['nullable', 'date'],
            'meeting_type'      => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function renderComingSoonTab(Request $request, int $company, string $activeTab, string $title): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);

        return view('company.corporate-formation-coming-soon', [
            'company' => (object) $companyData,
            'activeTab' => $activeTab,
            'title' => $title,
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

    private function scopeModelRecord($query, Model $model, int $record, int $company): Model
    {
        $scopedQuery = Schema::hasColumn($model->getTable(), 'company_id')
            ? $query->where('company_id', $company)
            : $query;

        return $scopedQuery->findOrFail($record);
    }

    private function abortIfNotEditable(Model $model): void
    {
        $canApprove = Auth::user()?->hasPermission('approve_corporate');
        $workflow   = $model->workflow_status ?? 'Uploaded';

        if (! $canApprove && ! in_array($workflow, ['Uploaded', 'Reverted'])) {
            abort(403, 'This record can no longer be edited.');
        }
    }

    private function storeUploadedFile(Request $request, string $key, string $directory): ?string
    {
        if (! $request->hasFile($key)) {
            return null;
        }

        $file     = $request->file($key);
        $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9.\-_]/', '_', $file->getClientOriginalName());

        return $file->storeAs($directory, $fileName, 'public');
    }

    private function storeFileAs(Request $request, string $key, string $directory, string $prefix): ?string
    {
        if (! $request->hasFile($key)) {
            return null;
        }

        $file     = $request->file($key);
        $fileName = time() . '_' . $prefix . '_' . $file->getClientOriginalName();
        $file->storeAs($directory, $fileName, 'public');

        return $directory . '/' . $fileName;
    }

    private function employeeName(): string
    {
        $user = Auth::user();

        return $user->name
            ?? $user->full_name
            ?? $user->employee_name
            ?? $user->username
            ?? $user->email
            ?? 'Unknown Employee';
    }

    private function findCompanyOrAbort(Request $request, int $company): array
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
}
