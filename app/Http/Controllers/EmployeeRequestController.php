<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\User;
use App\Notifications\SystemRealtimeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;

class EmployeeRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $canManageEmployeeRequests = $this->canManageRequests();

        $employeeRequests = $canManageEmployeeRequests
            ? EmployeeRequest::latest()->get()
            : collect();

        $myEmployeeRequests = EmployeeRequest::where('user_id', auth()->id())
            ->latest()
            ->get();

        $employees = $canManageEmployeeRequests
            ? Employee::with('department')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (Employee $employee) => $this->formatEmployee($employee))
                ->values()
            : collect();

        $currentEmployee = $this->currentEmployee();
        $currentEmployeeProfile = $currentEmployee
            ? $this->formatEmployee($currentEmployee)
            : null;

        return view('human-capital.employee-requests', compact(
            'employeeRequests',
            'myEmployeeRequests',
            'canManageEmployeeRequests',
            'employees',
            'currentEmployeeProfile'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_type' => 'required|string|max:255',
            'employee_id' => ($this->canManageRequests() ? 'required' : 'nullable').'|nullable|exists:employees,id',
        ]);

        $employee = $this->resolveEmployee($request->integer('employee_id') ?: null);
        $requestUser = $this->userForEmployee($employee);

        $employeeRequest = EmployeeRequest::create([
            'user_id' => $requestUser->id,
            'employee_name' => $employee->full_name,

            // Main request type
            'request_type' => $request->request_type,

            // Common fields
            'department' => $employee->department?->department_name,
            'request_date' => $request->request_date,

            // Overtime Request fields
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $this->calculateOvertimeEndTime($request),
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

        $this->notifyAdmins(
            title: 'New employee request submitted',
            message: $employeeRequest->employee_name . ' submitted a ' . $employeeRequest->request_type . ' request.',
            url: route('admin.human-capital.dashboard')
        );

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

        $employee = $this->currentEmployee();

        $employeeRequest->update([
            'department' => $employee?->department?->department_name,
            'request_date' => $request->request_date,

            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $this->calculateOvertimeEndTime($request),
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

        $this->notifyAdmins(
            title: 'Employee request revision submitted',
            message: $employeeRequest->employee_name . ' resubmitted a revised ' . $employeeRequest->request_type . ' request.',
            url: route('admin.human-capital.dashboard')
        );

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request revision submitted successfully.');
    }

    public function approve(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $employeeRequest->update([
            'status' => 'Approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        $this->notifyEmployee(
            $employeeRequest,
            title: 'Employee request approved',
            message: 'Your ' . $employeeRequest->request_type . ' request has been approved.',
            url: route('human-capital.employee-requests.index')
        );

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

        $this->notifyEmployee(
            $employeeRequest,
            title: 'Employee request rejected',
            message: 'Your ' . $employeeRequest->request_type . ' request has been rejected.',
            url: route('human-capital.employee-requests.index')
        );

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

        $this->notifyEmployee(
            $employeeRequest,
            title: 'Employee request sent back for revision',
            message: 'Your ' . $employeeRequest->request_type . ' request needs revision.',
            url: route('human-capital.employee-requests.index')
        );

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request sent back for revision.');
    }

    private function notifyAdmins(string $title, string $message, ?string $url = null): void
    {
        $admins = User::query()
            ->get()
            ->filter(fn (User $user) => $user->isAdmin() || $user->isSuperAdmin())
            ->values();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new SystemRealtimeNotification(
            title: $title,
            message: $message,
            url: $url ?: route('admin.human-capital.dashboard'),
            module: 'Employee Requests',
            icon: 'fa-file-signature'
        ));
    }

    private function notifyEmployee(EmployeeRequest $employeeRequest, string $title, string $message, ?string $url = null): void
    {
        $user = User::find($employeeRequest->user_id);

        if (! $user) {
            return;
        }

        $user->notify(new SystemRealtimeNotification(
            title: $title,
            message: $message,
            url: $url ?: route('human-capital.employee-requests.index'),
            module: 'Employee Requests',
            icon: 'fa-file-signature'
        ));
    }

    private function authorizeAdminAccess(): void
    {
        $user = auth()->user();

        abort_unless($user && ($user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('access_hc_employee_requests')), 403);
    }

    private function canManageRequests(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->hasPermission('access_hc_employee_requests'));
    }

    private function currentEmployee(): ?Employee
    {
        $user = auth()->user();

        return Employee::with('department')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user?->id ?: 0)
                    ->orWhere('email', $user?->email)
                    ->orWhere('work_email', $user?->email)
                    ->orWhere('company_email', $user?->email);
            })
            ->first();
    }

    private function resolveEmployee(?int $employeeId): Employee
    {
        if ($this->canManageRequests() && $employeeId) {
            return Employee::with('department')->findOrFail($employeeId);
        }

        $employee = $this->currentEmployee();

        if (! $employee) {
            abort(403, 'No employee profile is linked to your account email.');
        }

        return $employee;
    }

    private function userForEmployee(Employee $employee): User
    {
        $user = $employee->user_id
            ? User::find($employee->user_id)
            : User::whereIn('email', array_filter([$employee->email, $employee->work_email, $employee->company_email]))->first();

        if (! $user) {
            abort(422, 'The selected employee does not have a linked user account email.');
        }

        return $user;
    }

    private function formatEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
        ];
    }

    private function calculateOvertimeEndTime(Request $request): ?string
    {
        if ($request->request_type !== 'Overtime Request' || ! $request->start_time || ! is_numeric($request->total_hours)) {
            return $request->end_time;
        }

        return Carbon::createFromFormat('H:i', substr($request->start_time, 0, 5))
            ->addMinutes((int) round(((float) $request->total_hours) * 60))
            ->format('H:i');
    }
}
