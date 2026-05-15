<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'age',
        'address',
        'phone_number',
        'email',
        'personal_email',
        'work_email',
        'profile_photo',
        'office_id',
        'branch_id',
        'department_id',
        'division_id',
        'unit_id',
        'position',
        'payroll_type',
        'basic_salary',
        'hourly_rate',
        'schedule_start_time',
        'schedule_end_time',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (!$employee->employee_code) {
                $nextId = (static::max('id') ?? 0) + 1;
                $employee->employee_code = 'EMP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getLoginEmailAttribute()
    {
        return $this->work_email ?: $this->email;
    }

    public function payrollProfile()
    {
        return $this->hasOne(\App\Models\EmployeePayrollProfile::class);
    }

    public function payrollSummaries()
    {
        return $this->hasMany(\App\Models\PayrollSummary::class);
    }
}
