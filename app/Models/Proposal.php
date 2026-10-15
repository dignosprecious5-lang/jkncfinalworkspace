<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $fillable = [
        'proposal_code',
        'client_name',
        'client_email',
        'service_id',
        'proposed_price',
        'status',
        'valid_until',
        'notes',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}