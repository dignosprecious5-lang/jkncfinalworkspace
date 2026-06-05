<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Employee;
use App\Http\Controllers\Concerns\ScopesHumanCapitalRecords;
use Illuminate\Support\Facades\Auth;

class AwardController extends Controller
{
    use ScopesHumanCapitalRecords;

    public function index()
    {
        $user = Auth::user();

        $query = Award::with([
            'employee',
            'training',
            'assignment',
        ])->latest();

        $isAdmin = $this->canManageHumanCapitalModule('access_hc_awards', true);
        $employees = [];
        $selectedEmployeeId = null;

        if (! $isAdmin) {
            $employee = $this->currentHumanCapitalEmployee($user);
            $query->where(function ($awardQuery) use ($user, $employee) {
                $awardQuery->whereHas('employee', function ($employeeQuery) use ($user) {
                    $employeeQuery->where('user_id', $user?->id ?: 0)
                        ->orWhere('email', $user?->email)
                        ->orWhere('work_email', $user?->email)
                        ->orWhere('company_email', $user?->email);
                })
                    ->orWhereHas('assignment', fn ($assignmentQuery) => $employee ? $assignmentQuery->where('employee_id', $employee->id) : $assignmentQuery->whereRaw('1 = 0'));
            });
        } else {
            // For admin/superadmin, get all employees for the dropdown
            $employees = Employee::orderBy('first_name')->get();

            // Check if there's a filter by employee_id
            $selectedEmployeeId = request()->query('employee_id');
            if ($selectedEmployeeId) {
                $query->where('employee_id', $selectedEmployeeId);
            }
        }

        $awards = $query->get();

        return view('human-capital.awards', compact('awards', 'isAdmin', 'employees', 'selectedEmployeeId'));
    }
}
