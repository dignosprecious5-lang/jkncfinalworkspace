<?php

namespace App\Http\Controllers;

use App\Models\OnboardingChecklist;
use App\Models\OnboardingEmployeeRegistration;
use App\Models\OnboardingTraining;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingRecordController extends Controller
{
    public function records(): JsonResponse
    {
        return response()->json([
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

    public function storeChecklist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employeeName' => ['required', 'string', 'max:255'],
            'checked' => ['nullable', 'array'],
            'checked.*' => ['string', 'max:255'],
            'totalDocs' => ['required', 'integer', 'min:0'],
        ]);

        $checked = $validated['checked'] ?? [];

        $checklist = OnboardingChecklist::create([
            'employee_name' => $validated['employeeName'],
            'checked_documents' => $checked,
            'docs_submitted' => count($checked),
            'total_docs' => $validated['totalDocs'],
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Checklist saved successfully.',
            'record' => $this->formatChecklist($checklist),
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
            'trainingId' => ['required', 'integer', 'exists:trainings,id'],
            'startDate' => ['nullable', 'date'],
            'dueDate' => ['nullable', 'date'],
            'trainer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $training = OnboardingTraining::create([
            'employee_name' => $validated['employeeName'],
            'training_id' => $validated['trainingId'],
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

    private function formatChecklist(OnboardingChecklist $item): array
    {
        return [
            'id' => $item->id,
            'employeeName' => $item->employee_name,
            'docsSubmitted' => $item->docs_submitted,
            'totalDocs' => $item->total_docs,
            'checked' => $item->checked_documents ?? [],
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
            'training_id' => $item->training_id,
            'program' => $item->program,
            'trainer' => $item->trainer,
            'startDate' => optional($item->start_date)->format('Y-m-d'),
            'dueDate' => optional($item->due_date)->format('Y-m-d'),
            'description' => $item->description,
            'status' => $item->status,
        ];
    }
}