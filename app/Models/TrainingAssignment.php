<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'training_id',
        'assignment_type',
        'start_date',
        'due_date',
        'completed_at',
        'trainer',
        'description',
        'status',
        'remarks',
        'assigned_by',
        'certificate_issued',
        'certificate_issued_at',
        'certificate_code',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'Completed' || $this->completed_at !== null;
    }

    public function hasCertificate(): bool
    {
        return $this->certificate_issued === true;
    }
    
}
