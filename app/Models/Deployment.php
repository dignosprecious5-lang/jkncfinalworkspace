<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_code',
        'employee_name',
        'position',
        'branch_id',
        'office_id',
        'department_id',
        'division_id',
        'unit_id',
        'deployment_date',
        'reporting_manager',
        'deployment_type',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'deployment_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}