<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegularProject extends Model
{
    protected $table = 'regular_projects';

    protected $fillable = [
        'ref',
        'title',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
