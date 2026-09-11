<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StartEngagementGroup extends Model
{
    protected $fillable = [
        'start_record_id',

        // Engagement Group
        'engagement_type',
        'group_code',
        'temporary_group_key',
        'group_name',
        'title',

        // Deal Items / Scope
        'deal_item_id',
        'deal_item_ids',
        'scope_deliverables',

        // Timeline
        'start_date',
        'target_end_date',
        'duration_days',

        // Frequencies
        'service_frequency',
        'billing_frequency',
        'reporting_frequency',

        // Status / Assignment
        'status',
        'project_manager',

        // Notes
        'notes',
    ];

    protected $casts = [
        'deal_item_ids' => 'array',
        'start_date' => 'date:Y-m-d',
        'target_end_date' => 'date:Y-m-d',
        'duration_days' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | START RECORD
    |--------------------------------------------------------------------------
    */

    public function startRecord(): BelongsTo
    {
        return $this->belongsTo(StartRecord::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ENGAGEMENT ASSIGNMENTS
    |--------------------------------------------------------------------------
    */

    public function assignments(): HasMany
    {
        return $this->hasMany(EngagementAssignment::class);
    }
}