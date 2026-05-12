<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $canManageAttendance = $this->canManageAttendance($user);
        $period = $request->input('period', 'daily');
        $selectedDate = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        [$startDate, $endDate] = $this->dateRangeForPeriod($period, $selectedDate);

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

        $todayAttendance = Attendance::firstOrNew([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

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

    public function clock(Request $request)
    {
        $request->validate([
            'action' => ['nullable', 'in:clock_in,start_break_1,end_break_1,start_lunch,end_lunch,start_break_2,end_break_2,clock_out'],
        ]);

        $user = Auth::user();
        $today = now()->toDateString();
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ],
            [
                'employee_name' => $user->name,
                'status' => 'pending',
            ]
        );

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

        foreach ($validated as $key => $value) {
            $attendance->{$key} = $value ?: null;
        }

        $attendance->recalculateTotals();
        $attendance->save();

        return back()->with('success', 'Attendance record updated.');
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
            || $user->isAdmin();
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
}
