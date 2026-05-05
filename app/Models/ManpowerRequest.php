<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManpowerRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date_requested' => 'date',
        'date_required' => 'date',
        'date_hired' => 'date',
    ];
}