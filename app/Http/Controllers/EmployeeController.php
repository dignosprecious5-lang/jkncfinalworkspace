<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['office', 'branch', 'department', 'division', 'unit', 'user'])
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
                    'personal_email' => $item->personal_email,
                    'work_email' => $item->work_email,
                    'login_email' => $item->work_email ?: $item->email,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name,
                    'user_email' => $item->user?->email,
                    'user_role' => $item->user?->role,
                    'has_user_account' => (bool) $item->user_id,
                    'profile_photo' => $item->profile_photo,
                    'profile_photo_url' => $item->profile_photo ? Storage::url($item->profile_photo) : null,

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
                    'schedule_start_time' => $item->schedule_start_time ? substr($item->schedule_start_time, 0, 5) : '',
                    'schedule_end_time' => $item->schedule_end_time ? substr($item->schedule_end_time, 0, 5) : '',
                ];
            })
            ->values();

        if (request()->wantsJson()) {
            return $employees;
        }

        return view('human-capital.employee-profile', [
            'employees' => $employees,

            'officeOptions' => Office::orderBy('office_name')
                ->get(['id', 'office_name', 'branch_id'])
                ->values(),

            'branchOptions' => Branch::orderBy('branch_name')
                ->get(['id', 'branch_name'])
                ->values(),

            'departmentOptions' => Department::orderBy('department_name')
                ->get(['id', 'office_id', 'department_name'])
                ->values(),

            'divisionOptions' => Division::orderBy('division_name')
                ->get(['id', 'department_id', 'division_name'])
                ->values(),

            'unitOptions' => Unit::orderBy('unit_name')
                ->get(['id', 'division_id', 'unit_name'])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('human-capital.employee-profile')
            ->withErrors([
                'employee_profile' => 'Direct employee profile creation is disabled. Please create employee profiles through Human Capital → On Boarding → Employee Registration after completed onboarding.',
            ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:employees,email,' . $employee->id],
            'personal_email' => ['nullable', 'email', 'max:255', 'unique:employees,personal_email,' . $employee->id],
            'work_email' => ['nullable', 'email', 'max:255', 'unique:employees,work_email,' . $employee->id],

            'office_id' => ['nullable', Rule::exists('offices', 'id')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'division_id' => ['nullable', Rule::exists('divisions', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],

            'position' => ['nullable', 'string', 'max:255'],
            'payroll_type' => ['required', Rule::in(['Monthly Paid', 'Daily Paid'])],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'schedule_start_time' => ['nullable', 'date_format:H:i'],
            'schedule_end_time' => ['nullable', 'date_format:H:i'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }

            $validated['profile_photo'] = $request->file('profile_photo')->store('employee-photos', 'public');
        }

        $validated['hourly_rate'] = $this->computeHourlyRate(
            $validated['basic_salary'],
            $validated['payroll_type']
        );

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
