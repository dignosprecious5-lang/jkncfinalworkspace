<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'applicant_id',
        'name',
        'position',
        'email',
        'phone',
        'photo_path',
        'cv_path',
        'cover_letter_path',
        'cover_letter',
        'application_data',
        'attachment_paths',
        'consent_accepted_at',
        'consent_version',
        'submission_metadata',
        'applicant_type',
        'internal_remarks',
        'status',
        'applied_date',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'application_data' => 'array',
        'attachment_paths' => 'array',
        'submission_metadata' => 'array',
        'consent_accepted_at' => 'datetime',
    ];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    public function getIsInternalAttribute(): bool
    {
        return str_contains(strtolower((string) $this->applicant_type), 'existing')
            || str_contains(strtolower((string) $this->applicant_type), 'internal');
    }
}
