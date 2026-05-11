<?php

namespace App\Http\Controllers;

use App\Models\EmployeeRequest;
use Illuminate\Http\Request;

class EmployeeRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $canManageEmployeeRequests = $user->isAdmin() || $user->isSuperAdmin();

        $employeeRequests = $canManageEmployeeRequests
            ? EmployeeRequest::latest()->get()
            : collect();

        $myEmployeeRequests = EmployeeRequest::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('human-capital.employee-requests', compact(
            'employeeRequests',
            'myEmployeeRequests',
            'canManageEmployeeRequests'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_type' => 'required|string|max:255',
        ]);

        EmployeeRequest::create([
            'user_id' => auth()->id(),
            'employee_name' => auth()->user()->name ?? 'Unknown Employee',

            // Main request type
            'request_type' => $request->request_type,

            // Common fields
            'department' => $request->department,
            'request_date' => $request->request_date,

            // Overtime Request fields
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_hours' => $request->total_hours,

            // Leave Application fields
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'number_of_days' => $request->number_of_days,
            'with_pay' => $request->with_pay,

            // Attendance Correction fields
            'attendance_date' => $request->attendance_date,
            'correction_type' => $request->correction_type,
            'correct_time' => $request->correct_time,

            // Undertime / Absence fields
            'absence_type' => $request->absence_type,
            'time_affected' => $request->time_affected,

            // COE Request fields
            'purpose' => $request->purpose,
            'date_needed' => $request->date_needed,
            'number_of_copies' => $request->number_of_copies,

            // Shared text fields
            'reason' => $request->reason,
            'remarks' => $request->remarks,

            // Default status
            'status' => 'Pending',
        ]);


        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request submitted successfully.');
    }

    public function updateRevision(Request $request, EmployeeRequest $employeeRequest)
    {
        abort_unless($employeeRequest->user_id === auth()->id(), 403);

        abort_unless($employeeRequest->status === 'For Revision', 403);

        $request->validate([
            'department' => 'nullable|string|max:255',
            'request_date' => 'nullable|date',
            'overtime_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'total_hours' => 'nullable|numeric',
            'leave_type' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'number_of_days' => 'nullable|numeric',
            'with_pay' => 'nullable|string|max:255',
            'attendance_date' => 'nullable|date',
            'correction_type' => 'nullable|string|max:255',
            'correct_time' => 'nullable',
            'absence_type' => 'nullable|string|max:255',
            'time_affected' => 'nullable',
            'purpose' => 'nullable|string|max:255',
            'date_needed' => 'nullable|date',
            'number_of_copies' => 'nullable|integer',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $employeeRequest->update([
            'department' => $request->department,
            'request_date' => $request->request_date,

            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_hours' => $request->total_hours,

            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'number_of_days' => $request->number_of_days,
            'with_pay' => $request->with_pay,

            'attendance_date' => $request->attendance_date,
            'correction_type' => $request->correction_type,
            'correct_time' => $request->correct_time,

            'absence_type' => $request->absence_type,
            'time_affected' => $request->time_affected,

            'purpose' => $request->purpose,
            'date_needed' => $request->date_needed,
            'number_of_copies' => $request->number_of_copies,

            'reason' => $request->reason,
            'remarks' => $request->remarks,

            'status' => 'Pending',
            'admin_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approve(EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $employeeRequest->update([
            'status' => 'Approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => null,
        ]);

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request approved successfully.');
    }

    public function reject(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $employeeRequest->update([
            'status' => 'Declined',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request rejected successfully.');
    }

    public function revise(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        $employeeRequest->update([
            'status' => 'For Revision',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request sent back for revision.');
    }

    private function authorizeAdminAccess(): void
    {
        $user = auth()->user();

        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin()), 403);
    }
}
