<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\SecCoi;

class CorporateFormationController extends Controller
{
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
        if ($this->canApproveCorporate()) {
            $records = SecCoi::latest()->get();
        } else {
            $records = SecCoi::where('submitted_by', Auth::id())->latest()->get();
        }

        return view('corporate.corporate-formation', compact('records'));
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

        $draftPath = null;
        $notaryPath = null;

        if ($request->hasFile('draft_file_upload')) {
            $draftPath = $this->storeSecCoiFile($request->file('draft_file_upload'), 'draft');
        }

        if ($request->hasFile('notary_file_upload')) {
            $notaryPath = $this->storeSecCoiFile($request->file('notary_file_upload'), 'notary');
        }

        $isApprover = $this->canApproveCorporate();

        SecCoi::create([
            'corporate_name'    => $request->corporate_name,
            'company_reg_no'    => $request->company_reg_no,
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
        ]);

        return redirect()->route('corporate.formation')
            ->with('success', $isApprover ? 'SEC-COI saved successfully.' : 'SEC-COI saved as uploaded record.');
    }

    public function show($id)
    {
        $record = SecCoi::findOrFail($id);

        if (!$this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('corporate.sec-coi-preview', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = SecCoi::findOrFail($id);

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

        $record = SecCoi::findOrFail($id);

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

        $record = SecCoi::findOrFail($id);

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
        $record = SecCoi::findOrFail($id);

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