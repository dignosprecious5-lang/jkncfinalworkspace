<?php

namespace App\Http\Controllers;

use App\Models\Permit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PermitController extends Controller
{
    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(Permit $record): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $record->submitted_by === (int) Auth::id()
            && in_array($record->workflow_status, ['Uploaded', 'Reverted']);
    }

    public function page()
    {
        if ($this->canApproveCorporate()) {
            $records = Permit::latest()->get();
        } else {
            $records = Permit::where('submitted_by', Auth::id())->latest()->get();
        }

        return view('corporate.lgu', compact('records'));
    }

    public function index()
    {
        if ($this->canApproveCorporate()) {
            $permits = Permit::latest()->get();
        } else {
            $permits = Permit::where('submitted_by', Auth::id())->latest()->get();
        }

        return response()->json(
            $permits->map(function ($permit) {
                return [
                    'id' => $permit->id,
                    'permit_type' => $permit->permit_type,
                    'document_type' => $permit->document_type,
                    'permit_number' => $permit->permit_number,
                    'date_of_registration' => $permit->date_of_registration?->format('Y-m-d'),
                    'approved_date_of_registration' => $permit->approved_date_of_registration?->format('Y-m-d'),
                    'expiration_date_of_registration' => $permit->expiration_date_of_registration?->format('Y-m-d'),
                    'user' => $permit->user,
                    'tin' => $permit->tin,
                    'document_name' => $permit->document_name,
                    'document_path' => $permit->document_path,
                    'approval_status' => $permit->approval_status ?? 'Pending',
                    'workflow_status' => $permit->workflow_status ?? 'Uploaded',
                    'review_note' => $permit->review_note,
                    'status' => $permit->status,
                    'created_at' => $permit->created_at?->format('M d, Y'),
                ];
            })
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'permit_type' => 'required|string',
            'document_type' => 'required|string',
            'tin' => 'nullable|string',
            'date_of_registration' => 'nullable|date',
            'expiration_date_of_registration' => 'nullable|date',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $documentPath = null;
        $documentName = null;

        if ($request->hasFile('document')) {
            $file = $request->file('document');

            $originalName = $file->getClientOriginalName();
            $filename = time() . '_' . $this->sanitizeFileName($originalName);

            $documentName = $originalName;
            $documentPath = $file->storeAs('documents/permits', $filename, 'public');
        }

        do {
            $permitNumber = 'PMT-' . now()->format('Y') . '-' . random_int(100000, 999999);
        } while (Permit::where('permit_number', $permitNumber)->exists());

        $isApprover = $this->canApproveCorporate();

        $permit = Permit::create([
            'permit_type' => $request->permit_type,
            'document_type' => $request->document_type,
            'permit_number' => $permitNumber,
            'date_of_registration' => $request->date_of_registration ?: null,
            'approved_date_of_registration' => $isApprover ? ($request->date_of_registration ?: null) : null,
            'expiration_date_of_registration' => $request->expiration_date_of_registration ?: null,
            'user' => Auth::check() ? Auth::user()->name : 'System',
            'tin' => $request->tin,
            'document_name' => $documentName,
            'document_path' => $documentPath,
            'approval_status' => $isApprover ? 'Approved' : 'Pending',
            'workflow_status' => $isApprover ? 'Accepted' : 'Uploaded',
            'submitted_by' => Auth::id(),
            'approved_by' => $isApprover ? Auth::id() : null,
            'approved_at' => $isApprover ? now() : null,
            'review_note' => null,
        ]);

        return response()->json([
            'message' => $isApprover ? 'LGU saved successfully.' : 'LGU saved as uploaded record.',
            'permit' => [
                'id' => $permit->id,
                'permit_type' => $permit->permit_type,
                'document_type' => $permit->document_type,
                'permit_number' => $permit->permit_number,
                'date_of_registration' => $permit->date_of_registration?->format('Y-m-d'),
                'approved_date_of_registration' => $permit->approved_date_of_registration?->format('Y-m-d'),
                'expiration_date_of_registration' => $permit->expiration_date_of_registration?->format('Y-m-d'),
                'user' => $permit->user,
                'tin' => $permit->tin,
                'document_name' => $permit->document_name,
                'document_path' => $permit->document_path,
                'approval_status' => $permit->approval_status,
                'workflow_status' => $permit->workflow_status,
                'review_note' => $permit->review_note,
                'status' => $permit->status,
            ]
        ], 201);
    }

    public function show($id)
    {
        $record = Permit::findOrFail($id);

        if (!$this->canApproveCorporate() && (int) $record->submitted_by !== (int) Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'id' => $record->id,
            'permit_type' => $record->permit_type,
            'document_type' => $record->document_type,
            'permit_number' => $record->permit_number,
            'date_of_registration' => $record->date_of_registration?->format('Y-m-d'),
            'approved_date_of_registration' => $record->approved_date_of_registration?->format('Y-m-d'),
            'expiration_date_of_registration' => $record->expiration_date_of_registration?->format('Y-m-d'),
            'user' => $record->user,
            'tin' => $record->tin,
            'document_name' => $record->document_name,
            'document_path' => $record->document_path,
            'approval_status' => $record->approval_status,
            'workflow_status' => $record->workflow_status,
            'review_note' => $record->review_note,
            'status' => $record->status,
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = Permit::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        $request->validate([
            'permit_type' => 'required|string|max:255',
            'document_type' => 'required|string|max:255',
            'tin' => 'nullable|string|max:255',
            'date_of_registration' => 'nullable|date',
            'expiration_date_of_registration' => 'nullable|date',
        ]);

        $record->update([
            'permit_type' => $request->permit_type,
            'document_type' => $request->document_type,
            'tin' => $request->tin,
            'date_of_registration' => $request->date_of_registration,
            'expiration_date_of_registration' => $request->expiration_date_of_registration,
        ]);

        return response()->json([
            'message' => 'LGU details updated successfully.'
        ]);
    }

    public function uploadDocument(Request $request, $id)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $record = Permit::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record can no longer be edited.');
        }

        if ($record->document_path && Storage::disk('public')->exists($this->normalizePublicPath($record->document_path))) {
            Storage::disk('public')->delete($this->normalizePublicPath($record->document_path));
        }

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . $this->sanitizeFileName($originalName);
        $filePath = $file->storeAs('documents/permits', $fileName, 'public');

        $record->update([
            'document_name' => $originalName,
            'document_path' => $filePath,
        ]);

        return response()->json([
            'message' => 'Document attached successfully.'
        ]);
    }

    public function submit($id)
    {
        $record = Permit::findOrFail($id);

        if (!$this->canEditRecord($record)) {
            abort(403, 'This record cannot be submitted.');
        }

        $record->update([
            'workflow_status' => 'Submitted',
            'approval_status' => 'Pending',
            'review_note' => null,
        ]);

        return response()->json([
            'message' => 'LGU submitted for approval.'
        ]);
    }

    private function sanitizeFileName(string $fileName): string
    {
        return preg_replace('/[^A-Za-z0-9.\-_]/', '_', $fileName) ?: 'uploaded_file';
    }

    private function normalizePublicPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $path = ltrim($path, '/');
        $path = preg_replace('#^public/#', '', $path);
        $path = preg_replace('#^storage/#', '', $path);

        return $path;
    }

    public function showMayorPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.mayors-permit', compact('permit'));
    }

    public function showBarangayBusinessPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.barangay-business-permit', compact('permit'));
    }

    public function showFirePermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.fire-permit', compact('permit'));
    }

    public function showSanitaryPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.sanitary-permit', compact('permit'));
    }

    public function showOboPermitTemplate($id)
    {
        $permit = Permit::findOrFail($id);
        return view('corporate.permits.templates.obo-permit', compact('permit'));
    }
}