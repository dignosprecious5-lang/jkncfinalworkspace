<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateInterview extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'position',
        'type',
        'round',
        'interviewer',
        'interview_date',
        'duration',
        'meeting_link',
        'status',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
    ];
}
