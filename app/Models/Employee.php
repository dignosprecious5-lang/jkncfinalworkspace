<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'age',
        'address',
        'phone_number',
        'email',
        'office_id',
        'branch_id',
        'department_id',
        'division_id',
        'unit_id',
        'position',
        'payroll_type',
        'basic_salary',
        'hourly_rate',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($employee) {
            $nextId = static::max('id') + 1;
            $employee->employee_code = 'EMP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        });
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

    public function payrollProfile()
    {
        return $this->hasOne(\App\Models\EmployeePayrollProfile::class);
    }

    public function payrollSummaries()
    {
        return $this->hasMany(\App\Models\PayrollSummary::class);
    }


}