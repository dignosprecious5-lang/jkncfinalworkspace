<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactHistoryEntry extends Model
{
    protected $fillable = [
        'contact_id',
        'type',
        'title',
        'description',
        'extra_label',
        'extra_value',
        'user_name',
        'user_initials',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
