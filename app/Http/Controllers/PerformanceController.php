<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use App\Models\PerformanceImprovementPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerformanceController extends Controller
{
    private array $criteria = [
        'quality_of_work',
        'timeliness_compliance',
        'productivity_output',
        'attendance_punctuality',
        'policy_compliance',
        'task_ownership',
        'communication_coordination',
        'teamwork_conduct',
        'initiative_problem_solving',
        'adaptability_learning',
        'client_support',
        'care_of_resources',
    ];

    public function index(Request $request)
    {
        $canManagePerformance = $this->canManagePerformance();
        $currentEmployee = $this->currentEmployee();
        $selectedEmployeeId = $canManagePerformance ? $request->query('employee_id') : null;

        $employees = Employee::with('department')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => $this->formatEmployee($employee))
            ->values();

        $evaluations = PerformanceEvaluation::with('employee')
            ->when(! $canManagePerformance, fn ($query) => $currentEmployee ? $query->where('employee_id', $currentEmployee->id) : $query->whereRaw('1 = 0'))
            ->when($canManagePerformance && $selectedEmployeeId, fn ($query) => $query->where('employee_id', $selectedEmployeeId))
            ->latest()
            ->get()
            ->map(fn (PerformanceEvaluation $evaluation) => $this->formatEvaluation($evaluation))
            ->values();

        $pips = PerformanceImprovementPlan::with('employee')
            ->when(! $canManagePerformance, fn ($query) => $currentEmployee ? $query->where('employee_id', $currentEmployee->id) : $query->whereRaw('1 = 0'))
            ->when($canManagePerformance && $selectedEmployeeId, fn ($query) => $query->where('employee_id', $selectedEmployeeId))
            ->latest()
            ->get()
            ->map(fn (PerformanceImprovementPlan $pip) => $this->formatPip($pip))
            ->values();

        return view('human-capital.performance', [
            'evaluations' => $evaluations,
            'pips' => $pips,
            'employees' => $employees,
            'canManagePerformance' => $canManagePerformance,
            'currentEmployee' => $currentEmployee ? $this->formatEmployee($currentEmployee) : null,
            'selectedEmployeeId' => $selectedEmployeeId,
        ]);
    }

    public function getEmployee($id)
    {
        return response()->json($this->formatEmployee(Employee::with('department')->findOrFail($id)));
    }

    public function storeEvaluation(Request $request)
    {
        $this->authorizeManagement();

        $validated = $this->validateEvaluation($request);
        $employee = Employee::with('department')->findOrFail($validated['employee_id']);
        $scores = collect($this->criteria)->map(fn ($field) => (int) ($validated[$field] ?? 0));
        $totalScore = $scores->sum();
        $averageRating = round($totalScore / count($this->criteria), 2);

        PerformanceEvaluation::create([
            ...$validated,
            ...$this->employeeSnapshot($employee),
            'created_by' => Auth::id(),
            'total_score' => $totalScore,
            'average_rating' => $averageRating,
            'overall_performance_rating' => $this->overallRating($averageRating),
        ]);

        return redirect()->route('human-capital.performance')->with('success', 'Performance evaluation saved successfully.');
    }

    public function updateEvaluation(Request $request, $id)
    {
        $evaluation = PerformanceEvaluation::findOrFail($id);
        $canManagePerformance = $this->canManagePerformance();

        if (! $canManagePerformance) {
            $this->authorizeEmployeeRecord($evaluation->employee_id);
            $request->validate(['employee_comments' => ['nullable', 'string']]);
            $evaluation->update([
                'employee_comments' => $request->input('employee_comments'),
                'employee_acknowledgment_date' => now()->toDateString(),
            ]);

            return redirect()->route('human-capital.performance')->with('success', 'Employee comments saved.');
        }

        $validated = $this->validateEvaluation($request);
        $employee = Employee::with('department')->findOrFail($validated['employee_id']);
        $scores = collect($this->criteria)->map(fn ($field) => (int) ($validated[$field] ?? 0));
        $totalScore = $scores->sum();
        $averageRating = round($totalScore / count($this->criteria), 2);

        $evaluation->update([
            ...$validated,
            ...$this->employeeSnapshot($employee),
            'total_score' => $totalScore,
            'average_rating' => $averageRating,
            'overall_performance_rating' => $this->overallRating($averageRating),
        ]);

        return redirect()->route('human-capital.performance')->with('success', 'Performance evaluation updated successfully.');
    }

    public function destroyEvaluation($id)
    {
        $this->authorizeManagement();
        PerformanceEvaluation::findOrFail($id)->delete();

        return redirect()->route('human-capital.performance')->with('success', 'Performance evaluation deleted successfully.');
    }

    public function storePIP(Request $request)
    {
        $this->authorizeManagement();

        $validated = $this->validatePip($request);
        $employee = Employee::with('department')->findOrFail($validated['employee_id']);

        PerformanceImprovementPlan::create([
            ...$validated,
            ...$this->employeeSnapshot($employee),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('human-capital.performance')->with('success', 'Performance improvement plan created successfully.');
    }

    public function updatePIP(Request $request, $id)
    {
        $pip = PerformanceImprovementPlan::findOrFail($id);

        if (! $this->canManagePerformance()) {
            $this->authorizeEmployeeRecord($pip->employee_id);
            $request->validate(['employee_comments' => ['nullable', 'string']]);
            $pip->update([
                'employee_comments' => $request->input('employee_comments'),
                'employee_acknowledgment_date' => now()->toDateString(),
            ]);

            return redirect()->route('human-capital.performance')->with('success', 'Employee comments saved.');
        }

        $validated = $this->validatePip($request);
        $employee = Employee::with('department')->findOrFail($validated['employee_id']);

        $pip->update([
            ...$validated,
            ...$this->employeeSnapshot($employee),
        ]);

        return redirect()->route('human-capital.performance')->with('success', 'Performance improvement plan updated successfully.');
    }

    public function destroyPIP($id)
    {
        $this->authorizeManagement();
        PerformanceImprovementPlan::findOrFail($id)->delete();

        return redirect()->route('human-capital.performance')->with('success', 'Performance improvement plan deleted successfully.');
    }

    private function validateEvaluation(Request $request): array
    {
        $rules = [
            'employee_id' => ['required', 'exists:employees,id'],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'evaluation_period_start' => ['nullable', 'date'],
            'evaluation_period_end' => ['nullable', 'date', 'after_or_equal:evaluation_period_start'],
            'evaluation_date' => ['required', 'date'],
            'key_strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'employee_comments' => ['nullable', 'string'],
            'supervisor_recommendations' => ['nullable', 'array'],
            'supervisor_recommendations.*' => ['nullable', 'string', 'max:255'],
            'action_plan' => ['nullable', 'array'],
            'action_plan.*.improvement_area' => ['nullable', 'string', 'max:255'],
            'action_plan.*.required_action' => ['nullable', 'string', 'max:255'],
            'action_plan.*.target_date' => ['nullable', 'date'],
            'action_plan.*.responsible_person' => ['nullable', 'string', 'max:255'],
            'evaluated_by_name' => ['nullable', 'string', 'max:255'],
            'evaluated_by_position' => ['nullable', 'string', 'max:255'],
            'evaluated_by_date' => ['nullable', 'date'],
            'reviewed_by_name' => ['nullable', 'string', 'max:255'],
            'reviewed_by_position' => ['nullable', 'string', 'max:255'],
            'reviewed_by_date' => ['nullable', 'date'],
        ];

        foreach ($this->criteria as $field) {
            $rules[$field] = ['required', 'integer', 'between:1,5'];
            $rules[$field.'_remarks'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);
        $validated['supervisor_recommendations'] = array_values(array_filter($validated['supervisor_recommendations'] ?? []));
        $validated['action_plan'] = array_values(array_filter($validated['action_plan'] ?? [], fn ($row) => collect($row)->filter()->isNotEmpty()));

        return $validated;
    }

    private function validatePip(Request $request): array
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'target_completion_date' => ['required', 'date', 'after_or_equal:start_date'],
            'improvement_areas' => ['required', 'array'],
            'improvement_areas.*.area' => ['nullable', 'string', 'max:255'],
            'improvement_areas.*.required_action' => ['nullable', 'string', 'max:255'],
            'improvement_areas.*.target_date' => ['nullable', 'date'],
            'improvement_areas.*.responsible_person' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Ongoing,Completed,Discontinued,Escalated'],
            'notes' => ['nullable', 'string'],
            'employee_comments' => ['nullable', 'string'],
            'initiated_by_name' => ['nullable', 'string', 'max:255'],
            'initiated_by_position' => ['nullable', 'string', 'max:255'],
            'initiated_by_date' => ['nullable', 'date'],
            'reviewed_by_name' => ['nullable', 'string', 'max:255'],
            'reviewed_by_position' => ['nullable', 'string', 'max:255'],
            'reviewed_by_date' => ['nullable', 'date'],
        ]);

        $validated['improvement_areas'] = array_values(array_filter($validated['improvement_areas'] ?? [], fn ($row) => collect($row)->filter()->isNotEmpty()));

        return $validated;
    }

    private function canManagePerformance(): bool
    {
        $user = Auth::user();

        return $user->isAdmin() || $user->isSuperAdmin();
    }

    private function authorizeManagement(): void
    {
        if (! $this->canManagePerformance()) {
            abort(403, 'Only admins can create or manage performance forms.');
        }
    }

    private function authorizeEmployeeRecord(int $employeeId): void
    {
        $employee = $this->currentEmployee();

        if (! $employee || $employee->id !== $employeeId) {
            abort(403);
        }
    }

    private function currentEmployee(): ?Employee
    {
        return Employee::with('department')
            ->where('email', Auth::user()->email)
            ->first();
    }

    private function employeeSnapshot(Employee $employee): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
        ];
    }

    private function formatEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'email' => $employee->email,
        ];
    }

    private function formatEvaluation(PerformanceEvaluation $evaluation): array
    {
        $data = $evaluation->toArray();

        foreach (['evaluation_date', 'evaluation_period_start', 'evaluation_period_end', 'evaluated_by_date', 'reviewed_by_date', 'employee_acknowledgment_date'] as $dateField) {
            $data[$dateField] = optional($evaluation->{$dateField})->format('Y-m-d');
        }

        return $data;
    }

    private function formatPip(PerformanceImprovementPlan $pip): array
    {
        $data = $pip->toArray();

        foreach (['start_date', 'target_completion_date', 'initiated_by_date', 'reviewed_by_date', 'employee_acknowledgment_date'] as $dateField) {
            $data[$dateField] = optional($pip->{$dateField})->format('Y-m-d');
        }

        return $data;
    }

    private function overallRating(float $averageRating): string
    {
        if ($averageRating >= 4.50) {
            return 'Excellent';
        }

        if ($averageRating >= 3.50) {
            return 'Very Good';
        }

        if ($averageRating >= 2.50) {
            return 'Satisfactory';
        }

        if ($averageRating >= 1.50) {
            return 'Needs Improvement';
        }

        return 'Unsatisfactory';
    }
}
