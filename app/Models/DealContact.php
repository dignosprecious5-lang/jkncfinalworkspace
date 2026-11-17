<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealContact extends Model
{
    protected $fillable = [
        'deal_id', 'contact_type', 'salutation', 'first_name', 'middle_name',
        'last_name', 'name_extension', 'email', 'mobile_number', 'address',
        'position', 'is_primary',
    ];

    protected $casts = ['is_primary' => 'boolean'];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
