<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalTask extends Model
{
    use HasFactory;

    protected $table = 'operational_tasks';

    protected $fillable = [
        'engagement_id',
        'engagement_period_id',
        'service_version_id',
        'service_activity_id',
        'parent_task_id',
        'title',
        'description',
        'expected_days',
        'expected_working_hours',
        'is_mandatory',
        'is_billable',
        'status',
        'due_date',
        'assigned_to',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_mandatory' => 'boolean',
        'is_billable' => 'boolean',
    ];

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(Engagement::class);
    }

    public function engagementPeriod(): BelongsTo
    {
        return $this->belongsTo(
            EngagementPeriod::class,
            'engagement_period_id'
        );
    }

    public function serviceVersion(): BelongsTo
    {
        return $this->belongsTo(
            ServiceVersion::class,
            'service_version_id'
        );
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }
}
