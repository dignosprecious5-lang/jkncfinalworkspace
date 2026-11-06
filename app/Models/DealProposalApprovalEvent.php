<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Approval history is append-only through this model.
 * Privileged database access can still alter or remove these rows.
 */
class DealProposalApprovalEvent extends Model
{
    protected $fillable = [
        'deal_proposal_version_id',
        'event_type',
        'occurred_at',
        'actor_type',
        'actor_name',
        'note',
        'event_metadata',
        'idempotency_key',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'event_metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Deal proposal approval events are append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Deal proposal approval events cannot be deleted through the application.');
        });
    }

    public function proposalVersion(): BelongsTo
    {
        return $this->belongsTo(DealProposalVersion::class, 'deal_proposal_version_id');
    }
}
