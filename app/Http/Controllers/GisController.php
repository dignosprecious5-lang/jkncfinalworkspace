<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\GisRecord;

class GisController extends Controller
{
    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(GisRecord $gis): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $gis->submitted_by === (int) Auth::id()
            && in_array($gis->workflow_status, ['Uploaded', 'Reverted']);
    }

    private function canAccessCompanyInfo(GisRecord $gis): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $gis->submitted_by === (int) Auth::id();
    }


    private function internalGisQuery()
    {
        $query = GisRecord::query();

        // Internal Corporate module records must be company_id NULL or 0 only.
        // Company-specific Corporate Formation records have company_id = company id
        // and must never appear here.
        if (Schema::hasColumn('gis_records', 'company_id')) {
            $query->where(function ($q) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', 0);
            });
        }

        // IMPORTANT:
        // Do not hide internal Corporate GIS records by comparing corporation_name
        // or company_reg_no against Company/BIF records. Some valid Corporate GIS
        // records use JK&C INC. or BIF-like values, and the old filter made saved
        // records disappear from the Uploaded/Accepted tabs.
        return $query;
    }

    private function companyModuleIdentifiers(): array
    {
        $names = [];
        $regNos = [];

        if (Schema::hasTable('companies')) {
            DB::table('companies')->orderBy('id')->chunk(200, function ($companies) use (&$names, &$regNos) {
                foreach ($companies as $company) {
                    foreach ((array) $company as $key => $value) {
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

        if (Schema::hasTable('company_bifs')) {
            DB::table('company_bifs')->orderBy('id')->chunk(200, function ($bifs) use (&$names, &$regNos) {
                foreach ($bifs as $bif) {
                    foreach ((array) $bif as $key => $value) {
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

    private function isCompanyModuleGis(GisRecord $gis): bool
    {
        return Schema::hasColumn('gis_records', 'company_id') && ! empty($gis->company_id);
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

    public function index()
    {
        $query = $this->internalGisQuery();

        if (! $this->canApproveCorporate()) {
            $query->where('submitted_by', Auth::id());
        }

        $gis = $query->latest()->get();

        return view('corporate.gis', compact('gis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'submission_status'   => 'nullable|string|max:255',
            'receive_on'          => 'nullable|date',
            'period_date'         => 'nullable|string|max:255',
            'company_reg_no'      => 'nullable|string|max:255',
            'corporation_name'    => 'nullable|string|max:255',
            'annual_meeting'      => 'nullable|date',
            'meeting_type'        => 'nullable|string|max:255',
            'draft_file_upload'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'notary_file_upload'  => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'logo_upload'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $draftPath = null;
        $notaryPath = null;
        $logoPath = null;

        if ($request->hasFile('draft_file_upload')) {
            $file = $request->file('draft_file_upload');
            $fileName = time() . '_draft_' . $file->getClientOriginalName();
            $file->storeAs('gis_files', $fileName, 'public');
            $draftPath = 'gis_files/' . $fileName;
        }

        if ($request->hasFile('notary_file_upload')) {
            $file = $request->file('notary_file_upload');
            $fileName = time() . '_notary_' . $file->getClientOriginalName();
            $file->storeAs('gis_files', $fileName, 'public');
            $notaryPath = 'gis_files/' . $fileName;
        }

        if ($request->hasFile('logo_upload')) {
            $file = $request->file('logo_upload');
            $fileName = time() . '_logo_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->storeAs('gis_logos', $fileName, 'public');
            $logoPath = 'gis_logos/' . $fileName;
        }

        $payload = [
            'uploaded_by'       => $this->employeeName(),

            // New GIS records must always start as Uploaded/Pending, even if
            // the logged-in user is an admin or approver. The record may still
            // be incomplete at this stage because files/details can be added
            // later before submission.
            'submission_status' => 'Uploaded',
            'receive_on'        => $request->receive_on,
            'period_date'       => $request->period_date,
            'company_reg_no'    => $request->company_reg_no,
            'corporation_name'  => $request->corporation_name,
            'annual_meeting'    => $request->annual_meeting,
            'meeting_type'      => $request->meeting_type,
            'file'              => $draftPath,
            'notary_file_path'  => $notaryPath,
            'logo_path'         => $logoPath,
            'approval_status'   => 'Pending',
            'workflow_status'   => 'Uploaded',
            'submitted_by'      => Auth::id(),
            'approved_by'       => null,
            'approved_at'       => null,
            'review_note'       => null,
        ];

        if (Schema::hasColumn('gis_records', 'company_id')) {
            $payload['company_id'] = null;
        }

        GisRecord::create($payload);

        return redirect()->route('corporate.gis')
            ->with('success', 'GIS saved as uploaded record. Complete the files/details first, then submit it for approval.');
    }

    public function companyInfo()
    {
        // Internal Corporate General Information must only use internal Corporate GIS.
        // Company-module GIS records have company_id and must not appear here.
        $gis = $this->internalGisQuery()
            ->where(function ($query) {
                $query->where('approval_status', 'Approved')
                    ->orWhere('workflow_status', 'Accepted');
            })
            ->latest()
            ->first();

        if (! $gis) {
            $gis = new GisRecord();
        }

        return view('corporate.company-general-information', compact('gis'));
    }

    public function companyInfoById($id)
    {
        $gis = GisRecord::findOrFail($id);

        if ($this->isCompanyModuleGis($gis)) {
            abort(404);
        }

        if (!$this->canAccessCompanyInfo($gis)) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.company-general-information', compact('gis'));
    }

    public function updateCompanyInfo(Request $request, $id)
    {
        $request->validate([
            'date_registered'         => 'nullable|date',
            'trade_name'              => 'nullable|string|max:255',
            'fiscal_year_end'         => 'nullable|string|max:255',
            'tin'                     => 'nullable|string|max:255',
            'website'                 => 'nullable|string|max:255',
            'email'                   => 'nullable|email|max:255',
            'principal_address'       => 'nullable|string',
            'business_address'        => 'nullable|string',
            'official_mobile'         => 'nullable|string|max:255',
            'alternate_mobile'        => 'nullable|string|max:255',
            'auditor'                 => 'nullable|string|max:255',
            'industry'                => 'nullable|string|max:255',
            'geo_code'                => 'nullable|string|max:255',
            'parent_company_name'     => 'nullable|string|max:255',
            'parent_company_sec_no'   => 'nullable|string|max:255',
            'parent_company_address'  => 'nullable|string|max:255',
            'subsidiary_name'         => 'nullable|string|max:255',
            'subsidiary_sec_no'       => 'nullable|string|max:255',
            'subsidiary_address'      => 'nullable|string|max:255',
            'logo_upload'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $gis = GisRecord::findOrFail($id);

        if (!$this->canEditRecord($gis) && !$this->canApproveCorporate()) {
            abort(403, 'This record can no longer be edited.');
        }

        $payload = [
            'date_registered'         => $request->date_registered,
            'trade_name'              => $request->trade_name,
            'fiscal_year_end'         => $request->fiscal_year_end,
            'tin'                     => $request->tin,
            'website'                 => $request->website,
            'email'                   => $request->email,
            'principal_address'       => $request->principal_address,
            'business_address'        => $request->business_address,
            'official_mobile'         => $request->official_mobile,
            'alternate_mobile'        => $request->alternate_mobile,
            'auditor'                 => $request->auditor,
            'industry'                => $request->industry,
            'geo_code'                => $request->geo_code,
            'parent_company_name'     => $request->parent_company_name,
            'parent_company_sec_no'   => $request->parent_company_sec_no,
            'parent_company_address'  => $request->parent_company_address,
            'subsidiary_name'         => $request->subsidiary_name,
            'subsidiary_sec_no'       => $request->subsidiary_sec_no,
            'subsidiary_address'      => $request->subsidiary_address,
        ];

        if ($request->hasFile('logo_upload')) {
            if ($gis->logo_path) {
                Storage::disk('public')->delete($gis->logo_path);
            }

            $file = $request->file('logo_upload');
            $fileName = time() . '_logo_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
            $file->storeAs('gis_logos', $fileName, 'public');
            $payload['logo_path'] = 'gis_logos/' . $fileName;
        }

        $gis->update($payload);

        return redirect()->route('gis.show', $gis->id)
            ->with('success', 'GIS Company Information completed successfully.');
    }

    public function capitalStructure()
    {
        return view('corporate.gis-capital-structure');
    }

    public function directorsOfficers()
    {
        return view('corporate.gis-directors-officers');
    }

    public function stockholders()
    {
        return view('corporate.gis-stockholders');
    }

    public function show($id)
    {
        $gis = GisRecord::with([
            'authorizedCapital',
            'subscribedCapital',
            'paidUpCapital',
            'directors',
            'stockholders',
            'ubos'
        ])->findOrFail($id);

        if ($this->isCompanyModuleGis($gis)) {
            abort(404);
        }

        if (!$this->canApproveCorporate() && (int) $gis->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.gis-show', compact('gis'));
    }

    public function uploadDraftFile(Request $request, $id)
    {
        $request->validate([
            'draft_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $gis = GisRecord::findOrFail($id);

        if ($this->isCompanyModuleGis($gis)) {
            abort(404);
        }

        if (!$this->canEditRecord($gis)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('draft_file');
        $fileName = time() . '_draft_' . $file->getClientOriginalName();
        $file->storeAs('gis_files', $fileName, 'public');
        $filePath = 'gis_files/' . $fileName;

        $gis->update([
            'file' => $filePath,
        ]);

        return back()->with('success', 'Draft file attached successfully.');
    }

    public function uploadNotaryFile(Request $request, $id)
    {
        $request->validate([
            'notary_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $gis = GisRecord::findOrFail($id);

        if ($this->isCompanyModuleGis($gis)) {
            abort(404);
        }

        if (!$this->canEditRecord($gis)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('notary_file');
        $fileName = time() . '_notary_' . $file->getClientOriginalName();
        $file->storeAs('gis_files', $fileName, 'public');
        $filePath = 'gis_files/' . $fileName;

        $gis->update([
            'notary_file_path' => $filePath,
        ]);

        return back()->with('success', 'Notary file attached successfully.');
    }

    public function submit($id)
    {
        $gis = GisRecord::findOrFail($id);

        if ($this->isCompanyModuleGis($gis)) {
            abort(404);
        }

        if (!$this->canEditRecord($gis)) {
            abort(403, 'This record cannot be submitted.');
        }

        if (empty($gis->file) || empty($gis->notary_file_path)) {
            return back()->with('error', 'You must upload both Draft and Notary files before submitting.');
        }

        $gis->update([
            'submission_status' => 'Submitted',
            'workflow_status'   => 'Submitted',
            'approval_status'   => 'Pending',
            'review_note'       => null,
        ]);

        return back()->with('success', 'GIS submitted for approval.');
    }
}