<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'name',
        'position',
        'email',
        'phone',
        'photo_path',
        'cv_path',
        'cover_letter_path',
        'cover_letter',
        'status',
        'applied_date',
    ];

    protected $casts = [
        'applied_date' => 'date',
    ];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }
}
