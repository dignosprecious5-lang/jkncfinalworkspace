<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngagementAssignment extends Model
{
    protected $fillable = [
        'start_engagement_group_id',
        'role',
        'assigned_to',
        'status',
        'notes',
    ];

    public function engagementGroup(): BelongsTo
    {
        return $this->belongsTo(StartEngagementGroup::class);
    }
}