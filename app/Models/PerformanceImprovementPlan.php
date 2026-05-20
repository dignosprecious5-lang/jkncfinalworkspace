<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceImprovementPlan extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_name',
        'position',
        'department',
        'supervisor_name',
        'start_date',
        'target_completion_date',
        'improvement_areas',
        'status',
        'notes',
        'employee_comments',
        'initiated_by_name',
        'initiated_by_position',
        'initiated_by_date',
        'reviewed_by_name',
        'reviewed_by_position',
        'reviewed_by_date',
        'employee_acknowledgment_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_completion_date' => 'date',
        'initiated_by_date' => 'date',
        'reviewed_by_date' => 'date',
        'employee_acknowledgment_date' => 'date',
        'improvement_areas' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
