<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'name',
    ];

    public function userPositions(): HasMany
    {
        return $this->hasMany(UserPosition::class);
    }
}