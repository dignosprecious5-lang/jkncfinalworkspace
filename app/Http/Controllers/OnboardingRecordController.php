<?php

namespace App\Http\Controllers;

use App\Models\OnboardingChecklist;
use App\Models\OnboardingEmployeeRegistration;
use App\Models\OnboardingTraining;
use App\Models\PersonalDataSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OnboardingRecordController extends Controller
{
    private array $requiredDocuments = [
        'valid_id' => 'Valid ID (Government-issued)',
        'birth_certificate' => 'Birth Certificate',
        'sss' => 'SSS Number / E-1 / Static Info',
        'philhealth' => 'PhilHealth MDR',
        'pagibig' => 'Pag-IBIG MDF / MID',
        'tin_bir' => 'TIN / BIR Form',
        'nbi_clearance' => 'NBI Clearance',
        'medical_certificate' => 'Medical Certificate',
        'tor_diploma' => 'Transcript of Records / Diploma',
        'signed_job_offer' => 'Signed Job Offer',
        'two_by_two_picture' => '2x2 Picture',
    ];

    public function records(): JsonResponse
    {
        return response()->json([
            'pdsApplicants' => PersonalDataSheet::latest()
                ->get()
                ->map(fn ($item) => $this->formatPdsApplicant($item)),

            'checklists' => OnboardingChecklist::latest()
                ->get()
                ->map(fn ($item) => $this->formatChecklist($item)),

            'employees' => OnboardingEmployeeRegistration::latest()
                ->get()
                ->map(fn ($item) => $this->formatEmployee($item)),

            'trainings' => OnboardingTraining::latest()
                ->get()
                ->map(fn ($item) => $this->formatTraining($item)),
        ]);
    }

    public function showPublicChecklistUpload($token)
    {
        $checklist = OnboardingChecklist::where('upload_token', $token)->firstOrFail();

        return view('careers.checklist-upload', [
            'checklist' => $checklist,
            'documents' => $this->requiredDocuments,
        ]);
    }

    public function submitPublicChecklistUpload(Request $request, $token)
    {
        $checklist = OnboardingChecklist::where('upload_token', $token)->firstOrFail();

        $request->validate([
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $documents = [];
        $existing = collect($checklist->checked_documents ?? [])->keyBy('key');

        foreach ($this->requiredDocuments as $key => $label) {
            $old = $existing->get($key, []);
            $file = $request->file("documents.$key");

            if ($file) {
                if (!empty($old['file_path'])) {
                    Storage::disk('public')->delete($old['file_path']);
                }

                $filePath = $file->store('onboarding/checklists/'.$checklist->id, 'public');

                $documents[] = [
                    'key' => $key,
                    'label' => $label,
                    'status' => 'Submitted',
                    'remarks' => null,
                    'file_path' => $filePath,
                    'file_url' => Storage::url($filePath),
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toDateTimeString(),
                    'reviewed_at' => null,
                ];
            } else {
                $documents[] = [
                    'key' => $key,
                    'label' => $label,
                    'status' => $old['status'] ?? 'Missing',
                    'remarks' => $old['remarks'] ?? null,
                    'file_path' => $old['file_path'] ?? null,
                    'file_url' => $old['file_url'] ?? null,
                    'original_name' => $old['original_name'] ?? null,
                    'uploaded_at' => $old['uploaded_at'] ?? null,
                    'reviewed_at' => $old['reviewed_at'] ?? null,
                ];
            }
        }

        $docsSubmitted = collect($documents)->whereNotNull('file_path')->count();
        $docsApproved = collect($documents)->where('status', 'Approved')->count();

        $checklist->update([
            'checked_documents' => $documents,
            'docs_submitted' => $docsSubmitted,
            'docs_approved' => $docsApproved,
            'total_docs' => count($this->requiredDocuments),
            'status' => $docsSubmitted > 0 ? 'For Review' : 'Pending Documents',
            'submitted_at' => now(),
        ]);

        if ($checklist->personalDataSheet) {
            $checklist->personalDataSheet->update(['status' => 'Checklist Submitted']);
        }

        return redirect()
            ->route('careers.checklist.show', $checklist->upload_token)
            ->with('success', 'Your documents were submitted successfully. HR will review them.');
    }

    public function storeChecklist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pdsId' => ['required', 'exists:personal_data_sheets,id'],
            'employeeName' => ['required', 'string', 'max:255'],
            'employeeEmail' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $pds = PersonalDataSheet::findOrFail($validated['pdsId']);

        $checklist = OnboardingChecklist::firstOrNew([
            'personal_data_sheet_id' => $pds->id,
        ]);

        $documents = [];

        foreach ($this->requiredDocuments as $key => $label) {
            $file = $request->file("documents.$key");
            $filePath = null;
            $originalName = null;

            if ($file) {
                $filePath = $file->store('onboarding/checklists/'.$pds->id, 'public');
                $originalName = $file->getClientOriginalName();
            }

            $documents[] = [
                'key' => $key,
                'label' => $label,
                'status' => $filePath ? 'Submitted' : 'Missing',
                'remarks' => null,
                'file_path' => $filePath,
                'file_url' => $filePath ? Storage::url($filePath) : null,
                'original_name' => $originalName,
                'uploaded_at' => $filePath ? now()->toDateTimeString() : null,
                'reviewed_at' => null,
            ];
        }

        $docsSubmitted = collect($documents)->whereNotNull('file_path')->count();

        $checklist->fill([
            'employee_name' => $validated['employeeName'],
            'employee_email' => $validated['employeeEmail'] ?? $pds->email,
            'position' => $validated['position'] ?? $pds->position,
            'checked_documents' => $documents,
            'docs_submitted' => $docsSubmitted,
            'docs_approved' => 0,
            'total_docs' => count($this->requiredDocuments),
            'status' => $docsSubmitted > 0 ? 'For Review' : 'Pending Documents',
            'upload_token' => $checklist->upload_token ?: (string) Str::uuid(),
            'created_by' => Auth::id(),
        ]);

        $checklist->save();

        $pds->update(['status' => 'Checklist Submitted']);

        return response()->json([
            'message' => 'Checklist saved successfully.',
            'record' => $this->formatChecklist($checklist->fresh()),
        ]);
    }

    public function reviewChecklistDocument(Request $request, OnboardingChecklist $checklist): JsonResponse
    {
        $validated = $request->validate([
            'documentKey' => ['required', 'string', Rule::in(array_keys($this->requiredDocuments))],
            'status' => ['required', Rule::in(['Approved', 'Needs Re-upload'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $documents = collect($checklist->checked_documents ?? [])->map(function ($document) use ($validated) {
            if (($document['key'] ?? null) === $validated['documentKey']) {
                $document['status'] = $validated['status'];
                $document['remarks'] = $validated['remarks'] ?? null;
                $document['reviewed_at'] = now()->toDateTimeString();
            }

            return $document;
        })->values()->all();

        $docsSubmitted = collect($documents)->whereNotNull('file_path')->count();
        $docsApproved = collect($documents)->where('status', 'Approved')->count();
        $hasReupload = collect($documents)->contains(fn ($document) => ($document['status'] ?? null) === 'Needs Re-upload');

        $status = 'For Review';

        if ($docsApproved === count($this->requiredDocuments)) {
            $status = 'Completed';
        } elseif ($hasReupload) {
            $status = 'Needs Re-upload';
        } elseif ($docsSubmitted === 0) {
            $status = 'Pending Documents';
        }

        $checklist->update([
            'checked_documents' => $documents,
            'docs_submitted' => $docsSubmitted,
            'docs_approved' => $docsApproved,
            'status' => $status,
        ]);

        if ($status === 'Completed' && $checklist->personalDataSheet) {
            $checklist->personalDataSheet->update(['status' => 'Checklist Completed']);
        }

        return response()->json([
            'message' => 'Document review updated successfully.',
            'record' => $this->formatChecklist($checklist->fresh()),
        ]);
    }

    public function destroyChecklist(OnboardingChecklist $checklist): JsonResponse
    {
        $checklist->delete();

        return response()->json([
            'message' => 'Checklist deleted successfully.',
        ]);
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'employeeId' => ['required', 'string', 'max:255', 'unique:onboarding_employee_registrations,employee_id'],
            'department' => ['nullable', 'string', 'max:255'],
            'startDate' => ['nullable', 'date'],
            'workEmail' => ['nullable', 'email', 'max:255'],
            'manager' => ['nullable', 'string', 'max:255'],
        ]);

        $employee = OnboardingEmployeeRegistration::create([
            'full_name' => $validated['fullName'],
            'employee_id' => $validated['employeeId'],
            'department' => $validated['department'] ?? null,
            'start_date' => $validated['startDate'] ?? null,
            'work_email' => $validated['workEmail'] ?? null,
            'manager' => $validated['manager'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Employee registration saved successfully.',
            'record' => $this->formatEmployee($employee),
        ]);
    }

    public function destroyEmployee(OnboardingEmployeeRegistration $employee): JsonResponse
    {
        $employee->delete();

        return response()->json([
            'message' => 'Employee registration deleted successfully.',
        ]);
    }

    public function storeTraining(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employeeName' => ['required', 'string', 'max:255'],
            'program' => ['required', 'string', 'max:255'],
            'startDate' => ['nullable', 'date'],
            'dueDate' => ['nullable', 'date'],
            'trainer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $training = OnboardingTraining::create([
            'employee_name' => $validated['employeeName'],
            'program' => $validated['program'],
            'start_date' => $validated['startDate'] ?? null,
            'due_date' => $validated['dueDate'] ?? null,
            'trainer' => $validated['trainer'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'Scheduled',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Training assignment saved successfully.',
            'record' => $this->formatTraining($training),
        ]);
    }

    public function destroyTraining(OnboardingTraining $training): JsonResponse
    {
        $training->delete();

        return response()->json([
            'message' => 'Training assignment deleted successfully.',
        ]);
    }

    private function formatPdsApplicant(PersonalDataSheet $item): array
    {
        $data = is_array($item->data) ? $item->data : (json_decode($item->data, true) ?: []);

        return [
            'id' => $item->id,
            'db_id' => $item->id,
            'fullName' => $item->full_name ?: ($data['fullName'] ?? ''),
            'position' => $item->position ?: ($data['position'] ?? ''),
            'email' => $item->email ?: ($data['email'] ?? ''),
            'phone' => $item->phone ?: ($data['phone'] ?? ''),
            'status' => $item->status,
            'submittedDate' => optional($item->created_at)->format('Y-m-d'),
        ];
    }

    private function formatChecklist(OnboardingChecklist $item): array
    {
        return [
            'id' => $item->id,
            'pdsId' => $item->personal_data_sheet_id,
            'employeeName' => $item->employee_name,
            'employeeEmail' => $item->employee_email,
            'position' => $item->position,
            'docsSubmitted' => $item->docs_submitted,
            'docsApproved' => $item->docs_approved ?? 0,
            'totalDocs' => $item->total_docs,
            'checked' => $item->checked_documents ?? [],
            'documents' => $item->checked_documents ?? [],
            'status' => $item->status ?? 'For Review',
            'uploadUrl' => $item->upload_token ? route('careers.checklist.show', $item->upload_token) : null,
            'submittedDate' => optional($item->created_at)->format('m/d/Y'),
        ];
    }

    private function formatEmployee(OnboardingEmployeeRegistration $item): array
    {
        return [
            'id' => $item->id,
            'fullName' => $item->full_name,
            'employeeId' => $item->employee_id,
            'department' => $item->department,
            'startDate' => optional($item->start_date)->format('Y-m-d'),
            'workEmail' => $item->work_email,
            'manager' => $item->manager,
        ];
    }

    private function formatTraining(OnboardingTraining $item): array
    {
        return [
            'id' => $item->id,
            'employeeName' => $item->employee_name,
            'program' => $item->program,
            'trainer' => $item->trainer,
            'startDate' => optional($item->start_date)->format('Y-m-d'),
            'dueDate' => optional($item->due_date)->format('Y-m-d'),
            'description' => $item->description,
            'status' => $item->status,
        ];
    }
}
