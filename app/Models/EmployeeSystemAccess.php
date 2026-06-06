<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSystemAccess extends Model
{
    protected $fillable = [
        'employee_id',
        'system_platform_name',
        'account_type',
        'username_email',
        'role_access_level',
        'access_status',
        'date_access_created',
        'date_access_removed',
        'assigned_by',
        'approved_by',
        'approval_status',
        'approved_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_access_created' => 'date',
        'date_access_removed' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
