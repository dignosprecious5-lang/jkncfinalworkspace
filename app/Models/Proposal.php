<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $fillable = [
        'deal_id',
        'recipient_email',
        'subject',
        'introduction',
        'scope',
        'terms',
        'discount',
        'tax',
    ];

    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
