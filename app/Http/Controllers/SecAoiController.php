<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\SecAoi;
use App\Models\GisRecord;

class SecAoiController extends Controller
{

    private function latestAcceptedGis(): ?GisRecord
    {
        return GisRecord::query()
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

    public function index()
    {
        if ($this->canApproveCorporate()) {
            $records = SecAoi::latest()->get();
        } else {
            $records = SecAoi::where('submitted_by', Auth::id())->latest()->get();
        }

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
                ->withErrors(['gis_required' => 'Please complete and accept a GIS record first before creating SEC-AOI.']);
        }

        $draftPath = null;
        $notaryPath = null;

        if ($request->hasFile('draft_file_upload')) {
            $file = $request->file('draft_file_upload');
            $fileName = time() . '_draft_' . $file->getClientOriginalName();
            $file->storeAs('sec_aoi', $fileName, 'public');
            $draftPath = 'sec_aoi/' . $fileName;
        }

        if ($request->hasFile('notary_file_upload')) {
            $file = $request->file('notary_file_upload');
            $fileName = time() . '_notary_' . $file->getClientOriginalName();
            $file->storeAs('sec_aoi', $fileName, 'public');
            $notaryPath = 'sec_aoi/' . $fileName;
        }

        $isApprover = $this->canApproveCorporate();

        SecAoi::create([
            'company_id'               => $sourceGis->company_id,
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
        ]);

        return redirect()->route('corporate.sec_aoi')
            ->with('success', $isApprover ? 'SEC-AOI saved successfully.' : 'SEC-AOI saved as uploaded record.');
    }

    public function show($id)
    {
        $record = SecAoi::findOrFail($id);

        if (!$this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.sec-aoi-preview', compact('record'));
    }

    public function uploadDraftFile(Request $request, $id)
    {
        $request->validate([
            'draft_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = SecAoi::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('draft_file');
        $fileName = time() . '_draft_' . $file->getClientOriginalName();
        $file->storeAs('sec_aoi', $fileName, 'public');
        $filePath = 'sec_aoi/' . $fileName;

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

        $record = SecAoi::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('notary_file');
        $fileName = time() . '_notary_' . $file->getClientOriginalName();
        $file->storeAs('sec_aoi', $fileName, 'public');
        $filePath = 'sec_aoi/' . $fileName;

        $record->update([
            'notary_file_path' => $filePath,
        ]);

        return back()->with('success', 'Notary file attached successfully.');
    }

    public function submit($id)
    {
        $record = SecAoi::findOrFail($id);

        if (!$this->canEditRecord($record)) {
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