<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeploymentController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['branch', 'office', 'department', 'division', 'unit'])
            ->orderBy('last_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'position' => $employee->position,
                    'branch_id' => $employee->branch_id,
                    'office_id' => $employee->office_id,
                    'department_id' => $employee->department_id,
                    'division_id' => $employee->division_id,
                    'unit_id' => $employee->unit_id,
                    'branch_name' => $employee->branch?->branch_name,
                    'office_name' => $employee->office?->office_name,
                    'department_name' => $employee->department?->department_name,
                    'division_name' => $employee->division?->division_name,
                    'unit_name' => $employee->unit?->unit_name,
                ];
            })
            ->values();

        $deployments = Deployment::with(['employee', 'branch', 'office', 'department', 'division', 'unit'])
            ->latest()
            ->get()
            ->map(fn ($deployment) => $this->formatDeployment($deployment))
            ->values();

        return view('human-capital.deployment', [
            'employees' => $employees,
            'deployments' => $deployments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'deployment_date' => ['nullable', 'date'],
            'reporting_manager' => ['nullable', 'string', 'max:255'],
            'deployment_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $employee = Employee::with(['branch', 'office', 'department', 'division', 'unit'])
            ->findOrFail($validated['employee_id']);

        $deployment = Deployment::create([
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,

            'branch_id' => $employee->branch_id,
            'office_id' => $employee->office_id,
            'department_id' => $employee->department_id,
            'division_id' => $employee->division_id,
            'unit_id' => $employee->unit_id,

            'deployment_date' => $validated['deployment_date'] ?? null,
            'reporting_manager' => $validated['reporting_manager'] ?? null,
            'deployment_type' => $validated['deployment_type'] ?? 'Initial Deployment',
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('deployment')
            ->with('success', 'Deployment record saved successfully.');
    }

    public function update(Request $request, Deployment $deployment)
    {
        $validated = $request->validate([
            'deployment_date' => ['nullable', 'date'],
            'reporting_manager' => ['nullable', 'string', 'max:255'],
            'deployment_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $deployment->update($validated);

        return redirect()
            ->route('deployment')
            ->with('success', 'Deployment record updated successfully.');
    }

    public function destroy(Deployment $deployment)
    {
        $deployment->delete();

        return redirect()
            ->route('deployment')
            ->with('success', 'Deployment record deleted successfully.');
    }

    private function formatDeployment(Deployment $deployment): array
    {
        return [
            'id' => $deployment->id,
            'employee_id' => $deployment->employee_id,
            'employee_code' => $deployment->employee_code,
            'employee_name' => $deployment->employee_name,
            'position' => $deployment->position,

            'branch_name' => $deployment->branch?->branch_name,
            'office_name' => $deployment->office?->office_name,
            'department_name' => $deployment->department?->department_name,
            'division_name' => $deployment->division?->division_name,
            'unit_name' => $deployment->unit?->unit_name,

            'deployment_date' => optional($deployment->deployment_date)->format('Y-m-d'),
            'reporting_manager' => $deployment->reporting_manager,
            'deployment_type' => $deployment->deployment_type,
            'status' => $deployment->status,
            'remarks' => $deployment->remarks,
        ];
    }
}