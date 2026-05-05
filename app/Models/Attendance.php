<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'date',
        'employee_name',
        'time_in',
        'time_out',
        'break_in',
        'break_out',
        'lunch_in',
        'lunch_out',
        'late',
        'total_working_hours',
        'total_lunch',
        'total_break',
        'status'
    ];
}