<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngagementAssignment extends Model
{
    protected $fillable = [
        'start_record_id',
        'start_engagement_group_id',
        'role',
        'assigned_to',
        'status',
        'acknowledged_at',
        'response',
        'decline_reason',
        'is_required',
        'notes',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'is_required' => 'boolean',
    ];

    protected $appends = [
        'assignee',
    ];

    public function getAssigneeAttribute(): ?string
    {
        return $this->attributes['assigned_to'] ?? null;
    }

    public function setAssigneeAttribute($value): void
    {
        $this->attributes['assigned_to'] = $value;
    }

    public function startRecord(): BelongsTo
    {
        return $this->belongsTo(StartRecord::class);
    }

    public function engagementGroup(): BelongsTo
    {
        return $this->belongsTo(StartEngagementGroup::class, 'start_engagement_group_id');
    }
}