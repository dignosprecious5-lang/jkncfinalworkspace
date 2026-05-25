<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Models\Company;
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

    public function companyGeneralInformation(Request $request, int $company): View
    {
        $companyData = $this->findCompanyOrAbort($request, $company);

        $latestAcceptedGis = GisRecord::query()
            ->when(
                Schema::hasColumn('gis_records', 'company_id'),
                fn ($query) => $query->where('company_id', $company)
            )
            ->where(function ($query) {
                $query->where('workflow_status', 'Accepted')
                    ->orWhere('approval_status', 'Approved');
            })
            ->latest('updated_at')
            ->latest('created_at')
            ->first();

        $latestGis = $latestAcceptedGis ?: GisRecord::query()
            ->when(
                Schema::hasColumn('gis_records', 'company_id'),
                fn ($query) => $query->where('company_id', $company)
            )
            ->latest('updated_at')
            ->latest('created_at')
            ->first();

        // IMPORTANT:
        // Company General Information must be based on an actual GIS record only.
        // Do not auto-fill this page from Company/BIF defaults, because it should stay blank
        // until the user saves a GIS for this specific company.
        $gis = $latestGis ?: new GisRecord([
            'company_id' => $company,
        ]);

        return view('company.corporate-formation-company-general-information', [
            'company' => (object) $companyData,
            'gis' => $gis,
            'sourceGis' => $latestGis,
            'activeTab' => 'company-info',
        ]);
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

        $defaults = $this->companyCorporateFormationDefaults($companyData, $company);
        $payload['corporate_name'] = $payload['corporate_name'] ?: ($defaults['corporate_name'] ?? null);
        $payload['company_reg_no'] = $payload['company_reg_no'] ?: ($defaults['company_reg_no'] ?? null);

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

        $defaults = $this->companyCorporateFormationDefaults($companyData, $company);
        $payload['corporation_name'] = $payload['corporation_name'] ?: ($defaults['corporation_name'] ?? null);
        $payload['company_reg_no'] = $payload['company_reg_no'] ?: ($defaults['company_reg_no'] ?? null);
        $payload['principal_address'] = $payload['principal_address'] ?: ($defaults['principal_address'] ?? null);

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

        $defaults = $this->companyCorporateFormationDefaults($companyData, $company);
        $payload['corporation_name'] = $payload['corporation_name'] ?: ($defaults['corporation_name'] ?? null);
        $payload['company_reg_no'] = $payload['company_reg_no'] ?: ($defaults['company_reg_no'] ?? null);
        $payload['type_of_formation'] = $payload['type_of_formation'] ?: ($defaults['type_of_formation'] ?? null);
        $payload['aoi_version'] = $payload['aoi_version'] ?: ($defaults['aoi_version'] ?? null);
        $payload['aoi_type'] = $payload['aoi_type'] ?: ($defaults['aoi_type'] ?? null);

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

        $defaults = $this->companyCorporateFormationDefaults($companyData, $company);
        $payload['corporation_name'] = $payload['corporation_name'] ?: ($defaults['corporation_name'] ?? null);
        $payload['company_reg_no'] = $payload['company_reg_no'] ?: ($defaults['company_reg_no'] ?? null);

        foreach ([
            'principal_address',
            'business_address',
            'email',
            'official_mobile',
            'tin',
            'website',
            'date_registered',
            'industry',
        ] as $defaultField) {
            if (Schema::hasColumn('gis_records', $defaultField) && blank($payload[$defaultField] ?? null)) {
                $payload[$defaultField] = $defaults[$defaultField] ?? null;
            }
        }

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

        $formationDefaults = $this->companyCorporateFormationDefaults($companyData, $company);

        $records = $this->companyScopedListQuery($model, $table, $company, $companyData, $formationDefaults)
            ->latest('id')
            ->get();

        return view('company.corporate-formation', [
            'company'           => (object) $companyData,
            'records'           => $records,
            'activeTab'         => $tab,
            'formationDefaults' => $formationDefaults,
        ]);
    }

    private function companyScopedListQuery(Model $model, string $table, int $company, array $companyData, array $formationDefaults)
    {
        $query = $model::query();

        if (! Schema::hasColumn($table, 'company_id')) {
            return $query;
        }

        $companyNames = array_values(array_unique(array_filter([
            $formationDefaults['corporation_name'] ?? null,
            $formationDefaults['corporate_name'] ?? null,
            $formationDefaults['company_name'] ?? null,
            $companyData['company_name'] ?? null,
            $companyData['business_name'] ?? null,
            $companyData['name'] ?? null,
        ])));

        $companyRegNos = array_values(array_unique(array_filter([
            $formationDefaults['company_reg_no'] ?? null,
            $companyData['company_reg_no'] ?? null,
            $companyData['sec_registration_no'] ?? null,
            $companyData['sec_reg_no'] ?? null,
            $companyData['registration_no'] ?? null,
            $companyData['bif_no'] ?? null,
        ])));

        $nameColumns = array_values(array_filter([
            Schema::hasColumn($table, 'corporation_name') ? 'corporation_name' : null,
            Schema::hasColumn($table, 'corporate_name') ? 'corporate_name' : null,
        ]));

        return $query->where(function ($outer) use ($company, $table, $companyNames, $companyRegNos, $nameColumns) {
            $outer->where('company_id', $company);

            // Safety fallback for old records saved before company_id was properly attached.
            // This still keeps the list company-specific by matching the company name or registration number.
            $outer->orWhere(function ($fallback) use ($table, $companyNames, $companyRegNos, $nameColumns) {
                $fallback->whereNull('company_id')
                    ->where(function ($match) use ($table, $companyNames, $companyRegNos, $nameColumns) {
                        foreach ($nameColumns as $column) {
                            if (! empty($companyNames)) {
                                $match->orWhereIn($column, $companyNames);
                            }
                        }

                        if (Schema::hasColumn($table, 'company_reg_no') && ! empty($companyRegNos)) {
                            $match->orWhereIn('company_reg_no', $companyRegNos);
                        }
                    });
            });
        });
    }


    private function companyCorporateFormationDefaults(array $companyData, int $company): array
    {
        $companyName = $companyData['company_name']
            ?? $companyData['business_name']
            ?? $companyData['name']
            ?? '';

        $companyAddress = $companyData['address']
            ?? $companyData['business_address']
            ?? $companyData['company_address']
            ?? '';

        $companyEmail = $companyData['email']
            ?? $companyData['authorized_contact_person_email']
            ?? '';

        $companyPhone = $companyData['phone']
            ?? $companyData['mobile_no']
            ?? $companyData['business_phone']
            ?? '';

        $bif = null;
        if (Schema::hasTable('company_bifs') && Schema::hasColumn('company_bifs', 'company_id')) {
            $bif = \Illuminate\Support\Facades\DB::table('company_bifs')
                ->where('company_id', $company)
                ->latest('id')
                ->first();
        }

        $latestGis = null;
        if (Schema::hasTable('gis_records') && Schema::hasColumn('gis_records', 'company_id')) {
            $latestGis = GisRecord::query()
                ->where('company_id', $company)
                ->latest('id')
                ->first();
        }

        $latestAoi = null;
        if (Schema::hasTable('sec_aois') && Schema::hasColumn('sec_aois', 'company_id')) {
            $latestAoi = SecAoi::query()
                ->where('company_id', $company)
                ->latest('id')
                ->first();
        }

        $latestCoi = null;
        if (Schema::hasTable('sec_coi') && Schema::hasColumn('sec_coi', 'company_id')) {
            $latestCoi = SecCoi::query()
                ->where('company_id', $company)
                ->latest('id')
                ->first();
        }

        $valueFromBif = function (array $keys) use ($bif): ?string {
            if (! $bif) {
                return null;
            }

            foreach ($keys as $key) {
                if (property_exists($bif, $key) && filled($bif->{$key})) {
                    return (string) $bif->{$key};
                }
            }

            return null;
        };

        $companyRegNo = $latestGis?->company_reg_no
            ?: $latestAoi?->company_reg_no
            ?: $latestCoi?->company_reg_no
            ?: $valueFromBif(['company_reg_no', 'sec_registration_no', 'sec_reg_no', 'registration_no', 'business_registration_no', 'bif_no'])
            ?: ($companyData['company_reg_no'] ?? null)
            ?: ($companyData['sec_registration_no'] ?? null)
            ?: ($companyData['bif_no'] ?? null)
            ?: '';

        $principalAddress = $latestGis?->principal_address
            ?: $latestAoi?->principal_address
            ?: $valueFromBif(['principal_address', 'business_address', 'company_address', 'office_address'])
            ?: $companyAddress;

        $businessAddress = $latestGis?->business_address
            ?: $valueFromBif(['business_address', 'company_address'])
            ?: $companyAddress;

        $corporationName = $latestGis?->corporation_name
            ?: $latestAoi?->corporation_name
            ?: $latestCoi?->corporate_name
            ?: $companyName;

        return [
            'company_name' => $companyName,
            'corporate_name' => $corporationName,
            'corporation_name' => $corporationName,
            'company_reg_no' => $companyRegNo,
            'principal_address' => $principalAddress,
            'business_address' => $businessAddress,
            'email' => $latestGis?->email ?: $companyEmail,
            'official_mobile' => $latestGis?->official_mobile ?: $companyPhone,
            'alternate_mobile' => $latestGis?->alternate_mobile ?: '',
            'tin' => $latestGis?->tin ?: $valueFromBif(['tin_no', 'tin']) ?: ($companyData['tin_no'] ?? ''),
            'trade_name' => $latestGis?->trade_name ?: $valueFromBif(['business_name', 'trade_name']) ?: ($companyData['company_name'] ?? ''),
            'date_registered' => optional($latestGis?->date_registered)->format('Y-m-d') ?: '',
            'fiscal_year_end' => $latestGis?->fiscal_year_end ?: '',
            'website' => $latestGis?->website ?: '',
            'auditor' => $latestGis?->auditor ?: '',
            'industry' => $latestGis?->industry ?: '',
            'geo_code' => $latestGis?->geo_code ?: '',
            'parent_company_name' => $latestGis?->parent_company_name ?: '',
            'parent_company_sec_no' => $latestGis?->parent_company_sec_no ?: '',
            'parent_company_address' => $latestGis?->parent_company_address ?: '',
            'subsidiary_name' => $latestGis?->subsidiary_name ?: '',
            'subsidiary_sec_no' => $latestGis?->subsidiary_sec_no ?: '',
            'subsidiary_address' => $latestGis?->subsidiary_address ?: '',
            'type_of_formation' => $latestAoi?->type_of_formation ?: 'Stock Corporation',
            'aoi_version' => $latestAoi?->aoi_version ?: 'Original',
            'aoi_type' => $latestAoi?->aoi_type ?: 'Original',
            'aoi_date' => optional($latestAoi?->date_upload)->format('Y-m-d') ?: '',
            'par_value' => $latestAoi?->par_value ?: '',
            'authorized_capital_stock' => $latestAoi?->authorized_capital_stock ?: '',
            'directors' => $latestAoi?->directors ?: '',
        ];
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
            'annual_meeting'      => ['nullable', 'date'],
            'meeting_type'        => ['nullable', 'string', 'max:255'],
            'date_registered'     => ['nullable', 'date'],
            'trade_name'          => ['nullable', 'string', 'max:255'],
            'fiscal_year_end'     => ['nullable', 'string', 'max:255'],
            'tin'                 => ['nullable', 'string', 'max:255'],
            'website'             => ['nullable', 'string', 'max:255'],
            'email'               => ['nullable', 'email', 'max:255'],
            'principal_address'   => ['nullable', 'string'],
            'business_address'    => ['nullable', 'string'],
            'official_mobile'     => ['nullable', 'string', 'max:255'],
            'alternate_mobile'    => ['nullable', 'string', 'max:255'],
            'auditor'             => ['nullable', 'string', 'max:255'],
            'industry'            => ['nullable', 'string', 'max:255'],
            'geo_code'            => ['nullable', 'string', 'max:255'],
            'parent_company_name' => ['nullable', 'string', 'max:255'],
            'parent_company_sec_no' => ['nullable', 'string', 'max:255'],
            'parent_company_address' => ['nullable', 'string', 'max:255'],
            'subsidiary_name' => ['nullable', 'string', 'max:255'],
            'subsidiary_sec_no' => ['nullable', 'string', 'max:255'],
            'subsidiary_address' => ['nullable', 'string', 'max:255'],
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

    private function createCompanyScopedRecord(Model $model, array $payload, int $company): Model
    {
        $record = $model::create($this->attachCompanyId($model, $payload, $company));

        // Force the company_id after create too, so it still works even if the model fillable/guarded
        // setting does not save company_id during mass assignment.
        if (Schema::hasColumn($model->getTable(), 'company_id') && (int) ($record->company_id ?? 0) !== (int) $company) {
            \Illuminate\Support\Facades\DB::table($model->getTable())
                ->where('id', $record->id)
                ->update(['company_id' => $company]);

            $record->company_id = $company;
        }

        return $record;
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
        $table = $model->getTable();

        if (! Schema::hasColumn($table, 'company_id')) {
            return $query->findOrFail($record);
        }

        return $query->where('id', $record)
            ->where(function ($scope) use ($company) {
                $scope->where('company_id', $company)
                    ->orWhereNull('company_id');
            })
            ->firstOrFail();
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
