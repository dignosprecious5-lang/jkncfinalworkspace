<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'employee_name',
        'date',
        'time_in',
        'time_out',
        'break_1_start',
        'break_1_end',
        'lunch_start',
        'lunch_end',
        'break_2_start',
        'break_2_end',
        'total_break_mins',
        'total_lunch_mins',
        'total_working_hours',
        'status',
        'clock_source',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'break_1_start' => 'datetime',
            'break_1_end' => 'datetime',
            'lunch_start' => 'datetime',
            'lunch_end' => 'datetime',
            'break_2_start' => 'datetime',
            'break_2_end' => 'datetime',
            'total_working_hours' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recalculateTotals(): void
    {
        $breakMinutes = $this->minutesBetween('break_1_start', 'break_1_end')
            + $this->minutesBetween('break_2_start', 'break_2_end');
        $lunchMinutes = $this->minutesBetween('lunch_start', 'lunch_end');
        $grossMinutes = $this->minutesBetween('time_in', 'time_out');

        $this->total_break_mins = $breakMinutes;
        $this->total_lunch_mins = $lunchMinutes;
        $this->total_working_hours = max(0, $grossMinutes - $breakMinutes - $lunchMinutes) / 60;
    }

    private function minutesBetween(string $startField, string $endField): int
    {
        if (! $this->{$startField} || ! $this->{$endField}) {
            return 0;
        }

        return max(0, $this->{$startField}->diffInMinutes($this->{$endField}));
    }

    public function getShiftStateAttribute(): string
    {
        if (! $this->time_in) {
            return 'not_started';
        }

        if (! $this->time_out) {
            return 'clocked_in';
        }

        return 'completed';
    }

    public function getNextPunchActionAttribute(): ?string
    {
        if ($this->time_out) {
            return null;
        }

        if (! $this->time_in) {
            return 'clock_in';
        }

        if (! $this->break_1_start) {
            return 'start_break_1';
        }

        if (! $this->break_1_end) {
            return 'end_break_1';
        }

        if (! $this->lunch_start) {
            return 'start_lunch';
        }

        if (! $this->lunch_end) {
            return 'end_lunch';
        }

        if (! $this->break_2_start) {
            return 'start_break_2';
        }

        if (! $this->break_2_end) {
            return 'end_break_2';
        }

        return 'clock_out';
    }

    public function getNextPunchLabelAttribute(): string
    {
        return match ($this->next_punch_action) {
            'clock_in' => 'Clock In',
            'start_break_1' => 'Start Break',
            'end_break_1' => 'End Break',
            'start_lunch' => 'Start Lunch',
            'end_lunch' => 'End Lunch',
            'start_break_2' => 'Start 2nd Break',
            'end_break_2' => 'End 2nd Break',
            'clock_out' => 'Clock Out',
            default => 'Shift Complete',
        };
    }

    public function getNextPunchIconAttribute(): string
    {
        return match ($this->next_punch_action) {
            'clock_in' => 'fa-fingerprint',
            'start_break_1', 'start_break_2' => 'fa-mug-saucer',
            'end_break_1', 'end_break_2' => 'fa-play',
            'start_lunch' => 'fa-utensils',
            'end_lunch' => 'fa-circle-check',
            'clock_out' => 'fa-stopwatch',
            default => 'fa-check',
        };
    }

    public function getCurrentPunchStatusAttribute(): string
    {
        return match ($this->next_punch_action) {
            'clock_in' => 'Ready to start',
            'start_break_1' => 'Working',
            'end_break_1' => 'On first break',
            'start_lunch' => 'Back from break',
            'end_lunch' => 'At lunch',
            'start_break_2' => 'Back from lunch',
            'end_break_2' => 'On second break',
            'clock_out' => 'Final stretch',
            default => 'Shift complete',
        };
    }

    public function getTotalBreakLabelAttribute(): string
    {
        return $this->formatMinutes((int) $this->total_break_mins);
    }

    public function getTotalLunchLabelAttribute(): string
    {
        return $this->formatMinutes((int) $this->total_lunch_mins);
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0m';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return trim(($hours ? "{$hours}h " : '') . ($remainingMinutes ? "{$remainingMinutes}m" : ''));
    }
}
