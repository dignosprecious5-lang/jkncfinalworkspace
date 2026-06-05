<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    use RequestsHumanCapitalApproval;
    public function index(Request $request)
    {
        $user = Auth::user();
        $canManageAttendance = $this->canManageAttendance($user);
        $period = $request->input('period', 'daily');
        $selectedDate = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        [$startDate, $endDate] = $this->dateRangeForPeriod($period, $selectedDate);
        $todayAttendance = $this->currentClockAttendance($user);

        $query = Attendance::query()
            ->with('user')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->latest('date')
            ->latest('time_in');

        if (! $canManageAttendance) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $query->where('user_id', $request->integer('employee_id'));
        }

        $attendances = $query->paginate(15)->withQueryString();

        $employees = $canManageAttendance
            ? User::orderBy('name')->get(['id', 'name', 'role'])
            : collect();

        $summaryQuery = Attendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        if (! $canManageAttendance) {
            $summaryQuery->where('user_id', $user->id);
        } elseif ($request->filled('employee_id')) {
            $summaryQuery->where('user_id', $request->integer('employee_id'));
        }

        $summaryRecords = $summaryQuery->get();
        $summary = [
            'days' => $summaryRecords->count(),
            'hours' => $summaryRecords->sum('total_working_hours'),
            'pending' => $summaryRecords->where('status', 'pending')->count(),
        ];

        return view('human-capital.attendance', compact(
            'attendances',
            'employees',
            'period',
            'selectedDate',
            'todayAttendance',
            'summary',
            'canManageAttendance'
        ));
    }

    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $canManageAttendance = $this->canManageAttendance($user);
        $period = $request->input('period', 'daily');
        $selectedDate = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        [$startDate, $endDate] = $this->dateRangeForPeriod($period, $selectedDate);

        $query = Attendance::query()
            ->with('user')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->orderBy('time_in');

        $employeeName = null;

        if (! $canManageAttendance) {
            $query->where('user_id', $user->id);
            $employeeName = $user->name;
        } elseif ($request->filled('employee_id')) {
            $selectedEmployee = User::find($request->integer('employee_id'));
            $query->where('user_id', $request->integer('employee_id'));
            $employeeName = $selectedEmployee?->name;
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            return back()->withErrors([
                'attendance' => 'No attendance records found for the selected filter.',
            ]);
        }

        $summary = [
            'days' => $attendances->count(),
            'hours' => $attendances->sum('total_working_hours'),
            'pending' => $attendances->where('status', 'pending')->count(),
            'approved' => $attendances->where('status', 'approved')->count(),
            'rejected' => $attendances->where('status', 'rejected')->count(),
        ];

        $periodLabel = ucfirst($period === 'payroll' ? 'payroll period' : $period);
        $scopeLabel = $employeeName ? $employeeName : 'All employees';
        $fileName = 'attendance-'.str($scopeLabel)->slug().'-'.$startDate->format('Ymd').'-'.$endDate->format('Ymd').'.pdf';

        return Pdf::loadView('human-capital.attendance-pdf', [
            'attendances' => $attendances,
            'summary' => $summary,
            'periodLabel' => $periodLabel,
            'scopeLabel' => $scopeLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
            'generatedBy' => $user->name,
        ])->setPaper('a4', 'landscape')->download($fileName);
    }

    public function clock(Request $request)
    {
        $request->validate([
            'action' => ['nullable', 'in:clock_in,start_break_1,end_break_1,start_lunch,end_lunch,start_break_2,end_break_2,clock_out'],
        ]);

        $user = Auth::user();
        $this->autoClockOutDueAttendances($user);

        $attendance = $this->currentAttendanceForUser($user);

        $attendance->employee_name = $user->name;
        if (Schema::hasColumn('attendances', 'clock_source')) {
            $attendance->clock_source = $request->routeIs('townhall.*') ? 'townhall' : 'human_capital';
        }

        $expectedAction = $attendance->next_punch_action;
        $action = $request->input('action', $expectedAction);

        if ($expectedAction === null) {
            return back()->with('success', 'Your attendance for today is already complete.');
        }

        if ($action !== $expectedAction) {
            return back()->withErrors([
                'attendance' => 'Please use the next attendance punch: '.$attendance->next_punch_label.'.',
            ]);
        }

        if ($action === 'clock_in') {
            $clockInError = $this->clockInRestrictionMessage($user, $attendance);

            if ($clockInError) {
                return back()->withErrors(['attendance' => $clockInError]);
            }
        }

        $message = $this->applyPunch($attendance, $action);
        $attendance->recalculateTotals();
        $attendance->save();

        return back()->with('success', $message);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $this->authorizeAttendanceManagement();

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time_in' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date'],
            'break_1_start' => ['nullable', 'date'],
            'break_1_end' => ['nullable', 'date'],
            'lunch_start' => ['nullable', 'date'],
            'lunch_end' => ['nullable', 'date'],
            'break_2_start' => ['nullable', 'date'],
            'break_2_end' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $preview = $attendance->replicate();
        foreach ($validated as $key => $value) {
            $preview->{$key} = $value ?: null;
        }
        $preview->recalculateTotals();
        $payload = array_merge($validated, [
            'total_break_hours' => $preview->total_break_hours,
            'total_working_hours' => $preview->total_working_hours,
        ]);

        $this->requestHumanCapitalChange($request, 'Attendance', 'update', $attendance, $payload, $attendance->employee_name ?: 'Attendance #'.$attendance->id);

        return back()->with('success', 'Attendance update submitted for admin approval.');
    }

    public function approve(Attendance $attendance)
    {
        $this->authorizeAttendanceManagement();

        $attendance->status = 'approved';
        $attendance->save();

        return back()->with('success', 'Attendance record approved.');
    }

    public function reject(Attendance $attendance)
    {
        $this->authorizeAttendanceManagement();

        $attendance->status = 'rejected';
        $attendance->save();

        return back()->with('success', 'Attendance record rejected.');
    }

    public function currentClockAttendance(User $user): Attendance
    {
        $this->autoClockOutDueAttendances($user);

        return $this->currentAttendanceForUser($user);
    }

    private function dateRangeForPeriod(string $period, Carbon $selectedDate): array
    {
        return match ($period) {
            'weekly' => [$selectedDate->copy()->startOfWeek(), $selectedDate->copy()->endOfWeek()],
            'monthly' => [$selectedDate->copy()->startOfMonth(), $selectedDate->copy()->endOfMonth()],
            'payroll' => $selectedDate->day <= 15
                ? [$selectedDate->copy()->startOfMonth(), $selectedDate->copy()->day(15)->endOfDay()]
                : [$selectedDate->copy()->day(16)->startOfDay(), $selectedDate->copy()->endOfMonth()],
            default => [$selectedDate->copy()->startOfDay(), $selectedDate->copy()->endOfDay()],
        };
    }

    private function canManageAttendance(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || $user->hasPermission('access_hc_attendance');
    }

    private function authorizeAttendanceManagement(): void
    {
        if (! $this->canManageAttendance(Auth::user())) {
            abort(403, 'Only admins can manage attendance records.');
        }
    }

    private function applyPunch(Attendance $attendance, string $action): string
    {
        $now = now();

        return match ($action) {
            'clock_in' => tap('You are clocked in. Have a focused day.', function () use ($attendance, $now) {
                $attendance->time_in = $now;
            }),
            'start_break_1' => tap('First break started.', function () use ($attendance, $now) {
                $attendance->break_1_start = $now;
            }),
            'end_break_1' => tap('First break ended. Welcome back.', function () use ($attendance, $now) {
                $attendance->break_1_end = $now;
            }),
            'start_lunch' => tap('Lunch started.', function () use ($attendance, $now) {
                $attendance->lunch_start = $now;
            }),
            'end_lunch' => tap('Lunch ended. Attendance updated.', function () use ($attendance, $now) {
                $attendance->lunch_end = $now;
            }),
            'start_break_2' => tap('Second break started.', function () use ($attendance, $now) {
                $attendance->break_2_start = $now;
            }),
            'end_break_2' => tap('Second break ended. Final stretch.', function () use ($attendance, $now) {
                $attendance->break_2_end = $now;
            }),
            'clock_out' => tap('You are clocked out. Attendance record saved.', function () use ($attendance, $now) {
                $attendance->time_out = $now;
            }),
            default => 'Attendance updated.',
        };
    }

    private function currentAttendanceForUser(User $user): Attendance
    {
        $today = now()->toDateString();

        $activeAttendance = Attendance::where('user_id', $user->id)
            ->whereNull('time_out')
            ->whereNotNull('time_in')
            ->latest('date')
            ->latest('time_in')
            ->first();

        if ($activeAttendance) {
            return $activeAttendance;
        }

        $regularAttendance = Attendance::firstOrNew(
            [
                'user_id' => $user->id,
                'date' => $today,
                'work_type' => 'regular',
                'employee_request_id' => null,
            ],
            [
                'employee_name' => $user->name,
                'status' => 'pending',
            ]
        );

        if (! $regularAttendance->exists || ! $regularAttendance->time_in) {
            $overtimeRequest = $this->eligibleOvertimeRequest($user, now(), false);

            if ($overtimeRequest) {
                $overtimeAttendance = Attendance::firstOrNew(
                    [
                        'user_id' => $user->id,
                        'employee_request_id' => $overtimeRequest->id,
                    ],
                    [
                        'employee_name' => $user->name,
                        'date' => $overtimeRequest->overtime_date,
                        'work_type' => 'overtime',
                        'status' => 'pending',
                    ]
                );

                $overtimeAttendance->work_type = 'overtime';
                $overtimeAttendance->employee_name = $user->name;

                if (! $overtimeAttendance->time_out) {
                    return $overtimeAttendance;
                }
            }
        }

        if (! $regularAttendance->exists || ! $regularAttendance->time_out) {
            return $regularAttendance;
        }

        $overtimeRequest = $this->eligibleOvertimeRequest($user, now(), false);

        if (! $overtimeRequest) {
            return $regularAttendance;
        }

        $overtimeAttendance = Attendance::firstOrNew(
            [
                'user_id' => $user->id,
                'employee_request_id' => $overtimeRequest->id,
            ],
            [
                'employee_name' => $user->name,
                'date' => $overtimeRequest->overtime_date,
                'work_type' => 'overtime',
                'status' => 'pending',
            ]
        );

        $overtimeAttendance->work_type = 'overtime';
        $overtimeAttendance->employee_name = $user->name;

        return $overtimeAttendance->time_out ? $regularAttendance : $overtimeAttendance;
    }

    private function clockInRestrictionMessage(User $user, Attendance $attendance): ?string
    {
        $employee = $this->employeeProfileFor($user);

        if (! $employee) {
            return 'No employee profile is linked to your account email yet. Please contact Human Capital.';
        }

        if ($attendance->work_type === 'overtime') {
            $overtimeRequest = $attendance->employee_request_id
                ? EmployeeRequest::find($attendance->employee_request_id)
                : $this->eligibleOvertimeRequest($user, now(), false);

            if (! $overtimeRequest) {
                return 'You need an approved overtime request before clocking in for overtime.';
            }

            [$overtimeStart, $overtimeEnd] = $this->overtimeWindow($overtimeRequest);
            $opensAt = $overtimeStart->copy()->subMinutes(10);

            if (now()->lt($opensAt)) {
                return 'Overtime clock-in opens at '.$opensAt->format('h:i A').'.';
            }

            if (now()->gt($overtimeEnd)) {
                return 'This approved overtime window already ended at '.$overtimeEnd->format('h:i A').'.';
            }

            return null;
        }

        if (! $employee->schedule_start_time || ! $employee->schedule_end_time) {
            return 'Your work schedule is not set yet. Please contact an admin or superadmin.';
        }

        [$shiftStart, $shiftEnd] = $this->regularShiftWindow($employee, Carbon::parse($attendance->date ?? now()->toDateString()));
        $opensAt = $shiftStart->copy()->subMinutes(10);
        $autoClosesAt = $shiftEnd->copy()->addHours(4);

        if (now()->lt($opensAt)) {
            return 'Clock-in opens 10 minutes before your shift, at '.$opensAt->format('h:i A').'.';
        }

        if (now()->gte($autoClosesAt)) {
            return 'Your regular shift clock-in window already closed at '.$autoClosesAt->format('h:i A').'.';
        }

        return null;
    }

    private function autoClockOutDueAttendances(User $user): void
    {
        $employee = $this->employeeProfileFor($user);

        if (! $employee) {
            return;
        }

        Attendance::where('user_id', $user->id)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->get()
            ->each(function (Attendance $attendance) use ($employee) {
                $cutoff = $this->autoClockOutAt($attendance, $employee);

                if (! $cutoff || now()->lt($cutoff)) {
                    return;
                }

                $attendance->time_out = $cutoff;
                $attendance->recalculateTotals();
                $attendance->save();
            });
    }

    private function autoClockOutAt(Attendance $attendance, Employee $employee): ?Carbon
    {
        if ($attendance->work_type === 'overtime' && $attendance->employee_request_id) {
            $overtimeRequest = EmployeeRequest::find($attendance->employee_request_id);

            return $overtimeRequest ? $this->overtimeWindow($overtimeRequest)[1] : null;
        }

        if (! $employee->schedule_start_time || ! $employee->schedule_end_time) {
            return null;
        }

        [, $shiftEnd] = $this->regularShiftWindow($employee, Carbon::parse($attendance->date));

        return $shiftEnd->addHours(4);
    }

    private function employeeProfileFor(User $user): ?Employee
    {
        return Employee::where('email', $user->email)->first();
    }

    private function eligibleOvertimeRequest(User $user, Carbon $now, bool $includeUpcoming): ?EmployeeRequest
    {
        return EmployeeRequest::where('user_id', $user->id)
            ->where('request_type', 'Overtime Request')
            ->where('status', 'Approved')
            ->whereDate('overtime_date', $now->toDateString())
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->orderBy('start_time')
            ->get()
            ->first(function (EmployeeRequest $request) use ($now, $includeUpcoming) {
                [$start, $end] = $this->overtimeWindow($request);
                $opensAt = $start->copy()->subMinutes(10);

                if ($includeUpcoming) {
                    return $now->lte($end);
                }

                return $now->betweenIncluded($opensAt, $end);
            });
    }

    private function regularShiftWindow(Employee $employee, Carbon $date): array
    {
        $start = Carbon::parse($date->toDateString().' '.substr($employee->schedule_start_time, 0, 5));
        $end = Carbon::parse($date->toDateString().' '.substr($employee->schedule_end_time, 0, 5));

        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    private function overtimeWindow(EmployeeRequest $request): array
    {
        $start = Carbon::parse($request->overtime_date.' '.substr($request->start_time, 0, 5));
        $end = Carbon::parse($request->overtime_date.' '.substr($request->end_time, 0, 5));

        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }
}
