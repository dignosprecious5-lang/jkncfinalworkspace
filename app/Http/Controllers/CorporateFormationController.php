<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SecCoi;
use App\Models\GisRecord;

class CorporateFormationController extends Controller
{

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

    private function internalSecCoiQuery()
    {
        $query = SecCoi::query();

        if (Schema::hasColumn('sec_coi', 'company_id')) {
            $query->where(function ($q) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', 0);
            });
        }

        return $this->excludeCompanyModuleMatches($query, 'sec_coi', 'corporate_name');
    }

    private function excludeCompanyModuleMatches($query, string $table, string $nameColumn)
    {
        $identifiers = $this->companyModuleIdentifiers();

        if (! empty($identifiers['names']) && Schema::hasColumn($table, $nameColumn)) {
            $names = array_map(fn ($value) => mb_strtolower(trim((string) $value)), $identifiers['names']);

            $query->where(function ($q) use ($nameColumn, $names) {
                $q->whereNull($nameColumn)
                  ->orWhereRaw('LOWER(TRIM(' . $nameColumn . ')) NOT IN (' . implode(',', array_fill(0, count($names), '?')) . ')', $names);
            });
        }

        if (! empty($identifiers['regNos']) && Schema::hasColumn($table, 'company_reg_no')) {
            $regNos = array_map(fn ($value) => mb_strtolower(trim((string) $value)), $identifiers['regNos']);

            $query->where(function ($q) use ($regNos) {
                $q->whereNull('company_reg_no')
                  ->orWhereRaw('LOWER(TRIM(company_reg_no)) NOT IN (' . implode(',', array_fill(0, count($regNos), '?')) . ')', $regNos);
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

                        if (str_contains($key, 'name')) {
                            $names[] = $value;
                        }

                        if (str_contains($key, 'reg') || str_contains($key, 'bif') || str_contains($key, 'sec')) {
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

    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(SecCoi $record): bool
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

    private function storeSecCoiFile($file, string $prefix): string
    {
        $fileName = time() . '_' . $prefix . '_' . $this->cleanFileName($file->getClientOriginalName());

        return $file->storeAs('uploads/sec-coi', $fileName, 'public');
    }

    public function index()
    {
        $query = $this->internalSecCoiQuery();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        $records = $query->latest()->get();

        $latestAcceptedGis = $this->latestAcceptedGis();

        return view('corporate.corporate-formation', compact('records', 'latestAcceptedGis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'corporate_name'      => 'required',
            'company_reg_no'      => 'required',
            'issued_on'           => 'required',
            'date_upload'         => 'required',
            'draft_file_upload'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'notary_file_upload'  => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $sourceGis = $this->latestAcceptedGis();

        if (! $sourceGis) {
            return redirect()->route('corporate.formation')
                ->withErrors(['gis_required' => 'Please complete and accept a GIS record first before creating SEC-COI.']);
        }

        $draftPath = null;
        $notaryPath = null;

        if ($request->hasFile('draft_file_upload')) {
            $draftPath = $this->storeSecCoiFile($request->file('draft_file_upload'), 'draft');
        }

        if ($request->hasFile('notary_file_upload')) {
            $notaryPath = $this->storeSecCoiFile($request->file('notary_file_upload'), 'notary');
        }

        $isApprover = $this->canApproveCorporate();

        $payload = [
            'corporate_name'    => $sourceGis->corporation_name ?: $request->corporate_name,
            'company_reg_no'    => $sourceGis->company_reg_no ?: $request->company_reg_no,
            'issued_by'         => $this->employeeName(),
            'issued_on'         => $request->issued_on,
            'date_upload'       => $request->date_upload,
            'file_path'         => $draftPath,
            'notary_file_path'  => $notaryPath,
            'approval_status'   => $isApprover ? 'Approved' : 'Pending',
            'workflow_status'   => $isApprover ? 'Accepted' : 'Uploaded',
            'submitted_by'      => Auth::id(),
            'approved_by'       => $isApprover ? Auth::id() : null,
            'approved_at'       => $isApprover ? now() : null,
        ];

        if (Schema::hasColumn('sec_coi', 'company_id')) {
            $payload['company_id'] = null;
        }

        SecCoi::create($payload);

        return redirect()->route('corporate.formation')
            ->with('success', $isApprover ? 'SEC-COI saved successfully.' : 'SEC-COI saved as uploaded record.');
    }

    public function show($id)
    {
        $record = $this->internalSecCoiQuery()->findOrFail($id);

        if (!$this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.sec-coi-preview', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = $this->internalSecCoiQuery()->findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $request->validate([
            'corporate_name' => 'required|string|max:255',
            'company_reg_no' => 'required|string|max:255',
            'issued_on'      => 'required|date',
            'date_upload'    => 'required|date',
        ]);

        $record->update([
            'corporate_name' => $request->corporate_name,
            'company_reg_no' => $request->company_reg_no,
            'issued_by'      => $record->issued_by ?: $this->employeeName(),
            'issued_on'      => $request->issued_on,
            'date_upload'    => $request->date_upload,
        ]);

        return back()->with('success', 'SEC-COI details updated successfully.');
    }

    public function uploadDraftFile(Request $request, $id)
    {
        $request->validate([
            'draft_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = $this->internalSecCoiQuery()->findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $filePath = $this->storeSecCoiFile($request->file('draft_file'), 'draft');

        $record->update([
            'file_path' => $filePath,
        ]);

        return back()->with('success', 'Draft file attached successfully.');
    }

    public function uploadNotaryFile(Request $request, $id)
    {
        $request->validate([
            'notary_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = $this->internalSecCoiQuery()->findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $filePath = $this->storeSecCoiFile($request->file('notary_file'), 'notary');

        $record->update([
            'notary_file_path' => $filePath,
        ]);

        return back()->with('success', 'Notary file attached successfully.');
    }

    public function submit($id)
    {
        $record = $this->internalSecCoiQuery()->findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record cannot be submitted.');
        }

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        return back()->with('success', 'SEC-COI submitted for approval.');
    }
}