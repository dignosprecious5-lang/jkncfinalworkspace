<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_name',
        'request_type',
        'department',
        'request_date',
        'overtime_date',
        'start_time',
        'end_time',
        'total_hours',
        'leave_type',
        'start_date',
        'end_date',
        'number_of_days',
        'with_pay',
        'attendance_date',
        'correction_type',
        'correct_time',
        'time_affected',
        'purpose',
        'date_needed',
        'number_of_copies',
        'reason',
        'remarks',
        'status',
        'absence_type',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];
}
