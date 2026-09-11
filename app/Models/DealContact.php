<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealContact extends Model
{
    use HasFactory;

    protected $table = 'deal_contacts';

    protected $fillable = [
        'deal_id',
        'contact_type',
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'email',
        'mobile_number',
        'address',
        'position',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}