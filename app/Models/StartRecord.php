<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StartRecord extends Model
{
    protected $fillable = [
        'deal_id',
        'casa_id',
        'proposal_id',
        'proposal_version_id',
        'status',
        'memo_status',
        'activation_summary',
        'reviewed_by',
        'reviewed_at',
        'issued_by',
        'issued_at',
        'return_reason',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'activation_summary' => 'array',
        'reviewed_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function engagementGroups(): HasMany
    {
        return $this->hasMany(StartEngagementGroup::class);
    }
}