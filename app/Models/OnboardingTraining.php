<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingTraining extends Model
{
    protected $fillable = [
        'employee_name',
        'training_id',
        'program',
        'start_date',
        'due_date',
        'trainer',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
    ];
}