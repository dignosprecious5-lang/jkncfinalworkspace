<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Bylaw;

class BylawController extends Controller
{
    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(Bylaw $record): bool
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

        if (! $user) {
            return 'Unknown Employee';
        }

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
            $records = Bylaw::latest()->get();
        } else {
            $records = Bylaw::where('submitted_by', Auth::id())->latest()->get();
        }

        return view('corporate.bylaws', compact('records'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'corporation_name'    => 'required',
            'company_reg_no'      => 'required',
            'type_of_formation'   => 'nullable|string',
            'aoi_version'         => 'nullable|string',
            'aoi_type'            => 'nullable|string',
            'aoi_date'            => 'nullable|date',
            'regular_asm'         => 'nullable|string',
            'asm_notice'          => 'nullable|string',
            'regular_bodm'        => 'nullable|string',
            'bodm_notice'         => 'nullable|string',
            'date_upload'         => 'nullable|date',
            'draft_file_upload'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'notary_file_upload'  => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $draftPath = null;
        $notaryPath = null;

        if ($request->hasFile('draft_file_upload')) {
            $file = $request->file('draft_file_upload');
            $fileName = time() . '_draft_' . $file->getClientOriginalName();
            $file->storeAs('bylaws', $fileName, 'public');
            $draftPath = 'bylaws/' . $fileName;
        }

        if ($request->hasFile('notary_file_upload')) {
            $file = $request->file('notary_file_upload');
            $fileName = time() . '_notary_' . $file->getClientOriginalName();
            $file->storeAs('bylaws', $fileName, 'public');
            $notaryPath = 'bylaws/' . $fileName;
        }

        $isApprover = $this->canApproveCorporate();

        Bylaw::create([
            'corporation_name'   => $request->corporation_name,
            'company_reg_no'     => $request->company_reg_no,
            'type_of_formation'  => $request->type_of_formation,
            'aoi_version'        => $request->aoi_version,
            'aoi_type'           => $request->aoi_type,
            'aoi_date'           => $request->aoi_date,
            'regular_asm'        => $request->regular_asm,
            'asm_notice'         => $request->asm_notice,
            'regular_bodm'       => $request->regular_bodm,
            'bodm_notice'        => $request->bodm_notice,
            'uploaded_by'        => $this->employeeName(),
            'date_upload'        => $request->date_upload ?: now()->toDateString(),
            'file_path'          => $draftPath,
            'notary_file_path'   => $notaryPath,
            'approval_status'    => $isApprover ? 'Approved' : 'Pending',
            'workflow_status'    => $isApprover ? 'Accepted' : 'Uploaded',
            'submitted_by'       => Auth::id(),
            'approved_by'        => $isApprover ? Auth::id() : null,
            'approved_at'        => $isApprover ? now() : null,
        ]);

        return redirect()->route('corporate.bylaws')
            ->with('success', $isApprover ? 'Bylaws saved successfully.' : 'Bylaws saved as uploaded record.');
    }

    public function show($id)
    {
        $record = Bylaw::findOrFail($id);

        if (!$this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.bylaws-preview', compact('record'));
    }

    public function uploadDraftFile(Request $request, $id)
    {
        $request->validate([
            'draft_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = Bylaw::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('draft_file');
        $fileName = time() . '_draft_' . $file->getClientOriginalName();
        $file->storeAs('bylaws', $fileName, 'public');
        $filePath = 'bylaws/' . $fileName;

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

        $record = Bylaw::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $file = $request->file('notary_file');
        $fileName = time() . '_notary_' . $file->getClientOriginalName();
        $file->storeAs('bylaws', $fileName, 'public');
        $filePath = 'bylaws/' . $fileName;

        $record->update([
            'notary_file_path' => $filePath,
        ]);

        return back()->with('success', 'Notary file attached successfully.');
    }

    public function submit($id)
    {
        $record = Bylaw::findOrFail($id);

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

        return back()->with('success', 'Bylaws submitted for approval.');
    }
}