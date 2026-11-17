<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealHistory extends Model
{
    public const TYPE_DEAL_CREATED = 'deal_created';
    public const TYPE_STAGE_CHANGED = 'stage_changed';

    protected $fillable = [
        'deal_id', 'user_id', 'user_name', 'activity_type', 'title',
        'description', 'old_stage', 'new_stage', 'old_value', 'new_value', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
