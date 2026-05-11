<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRelation extends Model
{
    public const INCIDENT = 'incident_report';
    public const GRIEVANCE = 'employee_grievance';
    public const MEDIATION = 'mediation_resolution';

    protected $fillable = [
        'reference_no',
        'form_type',
        'employee_id',
        'employee_code',
        'employee_name',
        'position',
        'department',
        'filed_at',
        'subject',
        'details',
        'attachment_paths',
        'hr_remarks',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'filed_at' => 'date',
        'details' => 'array',
        'attachment_paths' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFormTypeLabelAttribute(): string
    {
        return match ($this->form_type) {
            self::INCIDENT => 'Incident Report Form',
            self::GRIEVANCE => 'Employee Grievance Form',
            self::MEDIATION => 'Mediation / Resolution Form',
            default => 'Employee Relations Form',
        };
    }
}
