<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealProposalItemResponse extends Model
{
    protected $fillable = [
        'deal_proposal_id',
        'line_item_id',
        'line_item_name',
        'decision',
        'comment',
        'client_name',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(DealProposal::class, 'deal_proposal_id');
    }
}
