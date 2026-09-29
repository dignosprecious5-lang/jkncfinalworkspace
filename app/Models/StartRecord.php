<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StartRecord extends Model
{
    protected $fillable = [
        'deal_id',
        'start_code',
        'batch_name',
        'casa_id',
        'proposal_id',
        'proposal_version_id',
        'status',
        'memo_status',
        'service_memo_ref',
        'service_memo_data',
        'service_memo_revisions',
        'activation_summary',
        'activated_items',
        'history_logs',
        'reviewed_by',
        'reviewed_at',
        'authorized_by',
        'created_by',
        'issued_by',
        'issued_at',
        'return_reason',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'activation_summary' => 'array',
        'activated_items' => 'array',
        'service_memo_data' => 'array',
        'service_memo_revisions' => 'array',
        'history_logs' => 'array',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(EngagementAssignment::class);
    }
}