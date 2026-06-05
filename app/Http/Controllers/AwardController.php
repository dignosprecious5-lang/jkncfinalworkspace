<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class AwardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Award::with([
            'employee',
            'training',
            'assignment',
        ])->latest();

        $isAdmin = $user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('access_hc_awards');
        $employees = [];
        $selectedEmployeeId = null;

        /*
         * Admin / Super Admin:
         * - Can see all awards.
         * - Can filter by employee_id query parameter.
         *
         * Normal user:
         * - Can only see awards connected to their own Employee Profile.
         *
         * Important:
         * This uses users.email = employees.email.
         */
        if (! $isAdmin) {
            $query->whereHas('employee', function ($employeeQuery) use ($user) {
                $employeeQuery->where('user_id', $user?->id ?: 0)
                    ->orWhere('email', $user?->email)
                    ->orWhere('work_email', $user?->email)
                    ->orWhere('company_email', $user?->email);
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
