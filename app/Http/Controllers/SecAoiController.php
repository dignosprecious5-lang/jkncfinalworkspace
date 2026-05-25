<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SecAoi;
use App\Models\GisRecord;

class SecAoiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Internal Corporate Scope Helpers
    |--------------------------------------------------------------------------
    | Internal Corporate module records must NOT use company-specific records.
    |
    | Rule:
    | - Internal Corporate records = company_id is NULL or 0
    | - Company Corporate Formation records = company_id has selected company id
    |--------------------------------------------------------------------------
    */

    private function internalGisQuery()
    {
        $query = GisRecord::query();

        if (Schema::hasColumn('gis_records', 'company_id')) {
            $query->where(function ($q) {
                $q->whereNull('company_id')
                    ->orWhere('company_id', 0);
            });
        }

        return $this->excludeCompanyModuleMatches($query, 'gis_records', 'corporation_name');
    }

    private function internalSecAoiQuery()
    {
        $query = SecAoi::query();

        if (Schema::hasColumn('sec_aois', 'company_id')) {
            $query->where(function ($q) {
                $q->whereNull('company_id')
                    ->orWhere('company_id', 0);
            });
        }

        return $this->excludeCompanyModuleMatches($query, 'sec_aois', 'corporation_name');
    }

    private function latestAcceptedGis(): ?GisRecord
    {
        return $this->internalGisQuery()
            ->where(function ($query) {
                $query->where('workflow_status', 'Accepted')
                    ->orWhere('approval_status', 'Approved');
            })
            ->latest('updated_at')
            ->latest('created_at')
            ->first();
    }

    private function excludeCompanyModuleMatches($query, string $table, string $nameColumn)
    {
        $identifiers = $this->companyModuleIdentifiers();

        if (! empty($identifiers['names']) && Schema::hasColumn($table, $nameColumn)) {
            $query->where(function ($q) use ($identifiers, $nameColumn) {
                $q->whereNull($nameColumn)
                    ->orWhereNotIn($nameColumn, $identifiers['names']);
            });
        }

        if (! empty($identifiers['regNos']) && Schema::hasColumn($table, 'company_reg_no')) {
            $query->where(function ($q) use ($identifiers) {
                $q->whereNull('company_reg_no')
                    ->orWhereNotIn('company_reg_no', $identifiers['regNos']);
            });
        }

        return $query;
    }

    private function companyModuleIdentifiers(): array
    {
        $names = [];
        $regNos = [];

        foreach (['companies', 'company_bifs'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->orderBy('id')->chunk(200, function ($rows) use (&$names, &$regNos) {
                foreach ($rows as $row) {
                    foreach ((array) $row as $key => $value) {
                        if (! is_string($value) && ! is_numeric($value)) {
                            continue;
                        }

                        $value = trim((string) $value);

                        if ($value === '') {
                            continue;
                        }

                        $lowerKey = strtolower((string) $key);

                        if (str_contains($lowerKey, 'name')) {
                            $names[] = $value;
                        }

                        if (
                            str_contains($lowerKey, 'reg') ||
                            str_contains($lowerKey, 'bif') ||
                            str_contains($lowerKey, 'sec')
                        ) {
                            $regNos[] = $value;
                        }
                    }
                }
            });
        }

        return [
            'names' => array_values(array_unique(array_filter($names))),
            'regNos' => array_values(array_unique(array_filter($regNos))),
        ];
    }

    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(SecAoi $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status, ['Uploaded', 'Reverted']);
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

    private function cleanFileName(string $fileName): string
    {
        return preg_replace('/[^A-Za-z0-9.\-_]/', '_', $fileName);
    }

    private function storeSecAoiFile($file, string $prefix): string
    {
        $fileName = time() . '_' . $prefix . '_' . $this->cleanFileName($file->getClientOriginalName());

        $file->storeAs('sec_aoi', $fileName, 'public');

        return 'sec_aoi/' . $fileName;
    }

    public function index()
    {
        $query = $this->internalSecAoiQuery();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        $records = $query->latest()->get();

        $latestAcceptedGis = $this->latestAcceptedGis();

        return view('corporate.sec-aoi', compact('records', 'latestAcceptedGis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'corporation_name'         => 'required',
            'company_reg_no'           => 'required',
            'principal_address'        => 'required',
            'par_value'                => 'nullable|string',
            'authorized_capital_stock' => 'nullable|string',
            'directors'                => 'nullable|integer',
            'type_of_formation'        => 'nullable|string',
            'aoi_version'              => 'nullable|string',
            'aoi_type'                 => 'nullable|string',
            'date_upload'              => 'nullable|date',
            'draft_file_upload'        => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'notary_file_upload'       => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $sourceGis = $this->latestAcceptedGis();

        if (! $sourceGis) {
            return redirect()->route('corporate.sec_aoi')
                ->withErrors(['gis_required' => 'Please complete and accept an internal Corporate GIS record first before creating SEC-AOI.']);
        }

        $draftPath = null;
        $notaryPath = null;

        if ($request->hasFile('draft_file_upload')) {
            $draftPath = $this->storeSecAoiFile($request->file('draft_file_upload'), 'draft');
        }

        if ($request->hasFile('notary_file_upload')) {
            $notaryPath = $this->storeSecAoiFile($request->file('notary_file_upload'), 'notary');
        }

        $isApprover = $this->canApproveCorporate();

        $payload = [
            'corporation_name'         => $sourceGis->corporation_name ?: $request->corporation_name,
            'company_reg_no'           => $sourceGis->company_reg_no ?: $request->company_reg_no,
            'principal_address'        => $sourceGis->principal_address ?: $sourceGis->business_address ?: $request->principal_address,
            'par_value'                => $request->par_value,
            'authorized_capital_stock' => $request->authorized_capital_stock,
            'directors'                => $request->directors,
            'type_of_formation'        => $request->type_of_formation,
            'aoi_version'              => $request->aoi_version,
            'aoi_type'                 => $request->aoi_type,
            'uploaded_by'              => $this->employeeName(),
            'date_upload'              => $request->date_upload,
            'file_path'                => $draftPath,
            'notary_file_path'         => $notaryPath,
            'approval_status'          => $isApprover ? 'Approved' : 'Pending',
            'workflow_status'          => $isApprover ? 'Accepted' : 'Uploaded',
            'submitted_by'             => Auth::id(),
            'approved_by'              => $isApprover ? Auth::id() : null,
            'approved_at'              => $isApprover ? now() : null,
        ];

        // Important:
        // Internal Corporate SEC-AOI must stay internal, so company_id must be NULL.
        // Company-specific SEC-AOI is handled by CompanyCorporateFormationController.
        if (Schema::hasColumn('sec_aois', 'company_id')) {
            $payload['company_id'] = null;
        }

        SecAoi::create($payload);

        return redirect()->route('corporate.sec_aoi')
            ->with('success', $isApprover ? 'SEC-AOI saved successfully.' : 'SEC-AOI saved as uploaded record.');
    }

    public function show($id)
    {
        $record = $this->internalSecAoiQuery()->where('id', $id)->firstOrFail();

        if (! $this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.sec-aoi-preview', compact('record'));
    }

    public function uploadDraftFile(Request $request, $id)
    {
        $request->validate([
            'draft_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = $this->internalSecAoiQuery()->where('id', $id)->firstOrFail();

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $record->update([
            'file_path' => $this->storeSecAoiFile($request->file('draft_file'), 'draft'),
        ]);

        return back()->with('success', 'Draft file attached successfully.');
    }

    public function uploadNotaryFile(Request $request, $id)
    {
        $request->validate([
            'notary_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = $this->internalSecAoiQuery()->where('id', $id)->firstOrFail();

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $record->update([
            'notary_file_path' => $this->storeSecAoiFile($request->file('notary_file'), 'notary'),
        ]);

        return back()->with('success', 'Notary file attached successfully.');
    }

    public function submit($id)
    {
        $record = $this->internalSecAoiQuery()->where('id', $id)->firstOrFail();

        if (! $this->canEditRecord($record)) {
            abort(403, 'This record cannot be submitted.');
        }

        if (empty($record->file_path) || empty($record->notary_file_path)) {
            return back()->with('success', 'You must upload both Draft and Notary files before submitting.');
        }

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note'     => null,
        ]);

        return back()->with('success', 'SEC-AOI submitted for approval.');
    }
}