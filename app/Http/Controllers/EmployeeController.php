<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['office', 'branch', 'department', 'division', 'unit'])
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'employee_code' => $item->employee_code,
                    'first_name' => $item->first_name,
                    'last_name' => $item->last_name,
                    'full_name' => $item->full_name,
                    'age' => $item->age,
                    'address' => $item->address,
                    'phone_number' => $item->phone_number,
                    'email' => $item->email,
                    'office_id' => $item->office_id,
                    'branch_id' => $item->branch_id,
                    'department_id' => $item->department_id,
                    'division_id' => $item->division_id,
                    'unit_id' => $item->unit_id,
                    'office_name' => $item->office?->office_name,
                    'branch_name' => $item->branch?->branch_name,
                    'department_name' => $item->department?->department_name,
                    'division_name' => $item->division?->division_name,
                    'unit_name' => $item->unit?->unit_name,
                    'position' => $item->position,
                    'payroll_type' => $item->payroll_type,
                    'basic_salary' => $item->basic_salary,
                    'hourly_rate' => $item->hourly_rate,
                ];
            })
            ->values();

        return view('human-capital.employee-profile', [
            'employees' => $employees,

            // offices table has: id, office_name, branch_id
            'officeOptions' => Office::orderBy('office_name')
                ->get(['id', 'office_name', 'branch_id'])
                ->values(),

            // branches table has: id, branch_name, address_id
            // It does NOT have office_id.
            'branchOptions' => Branch::orderBy('branch_name')
                ->get(['id', 'branch_name'])
                ->values(),

            // departments table has: id, department_name, office_id
            // It does NOT have branch_id.
            'departmentOptions' => Department::orderBy('department_name')
                ->get(['id', 'office_id', 'department_name'])
                ->values(),

            // divisions table has: id, division_name, department_id
            // It does NOT have office_id or branch_id.
            'divisionOptions' => Division::orderBy('division_name')
                ->get(['id', 'department_id', 'division_name'])
                ->values(),

            // units table has: id, unit_name, division_id
            // It does NOT have office_id, branch_id, or department_id.
            'unitOptions' => Unit::orderBy('unit_name')
                ->get(['id', 'division_id', 'unit_name'])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],

            'office_id' => ['nullable', Rule::exists('offices', 'id')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'division_id' => ['nullable', Rule::exists('divisions', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],

            'position' => ['nullable', 'string', 'max:255'],
            'payroll_type' => ['required', Rule::in(['Monthly Paid', 'Daily Paid'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['hourly_rate'] = $this->computeHourlyRate(
            $validated['basic_salary'],
            $validated['payroll_type']
        );

        // Make branch match the selected office when office is provided.
        if (!empty($validated['office_id'])) {
            $office = Office::find($validated['office_id']);
            $validated['branch_id'] = $office?->branch_id;
        }

        Employee::create($validated);

        return redirect()->back()->with('success', 'Employee added successfully.');
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email,' . $employee->id],

            'office_id' => ['nullable', Rule::exists('offices', 'id')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'division_id' => ['nullable', Rule::exists('divisions', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],

            'position' => ['nullable', 'string', 'max:255'],
            'payroll_type' => ['required', Rule::in(['Monthly Paid', 'Daily Paid'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['hourly_rate'] = $this->computeHourlyRate(
            $validated['basic_salary'],
            $validated['payroll_type']
        );

        // Make branch match the selected office when office is provided.
        if (!empty($validated['office_id'])) {
            $office = Office::find($validated['office_id']);
            $validated['branch_id'] = $office?->branch_id;
        }

        $employee->update($validated);

        return redirect()->back()->with('success', 'Employee updated successfully.');
    }

    private function computeHourlyRate($salary, $type)
    {
        if ($type === 'Monthly Paid') {
            return round($salary / 22 / 8, 2);
        }

        return round($salary / 8, 2);
    }
}
