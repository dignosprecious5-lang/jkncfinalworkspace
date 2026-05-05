<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'salary',
        'start_date',
        'employment_type',
        'department',
        'benefits',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];
}