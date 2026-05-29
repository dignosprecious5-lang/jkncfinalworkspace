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
        'target_start_date' => 'date',
        'date_hired' => 'date',
        'benefits_checklist' => 'array',
        'required_documents' => 'array',
        'endorsements' => 'array',
        'candidate_profile_attached' => 'boolean',
        'job_description_attached' => 'boolean',
    ];

    public function address()
    {
        return $this->belongsTo(OrganizationalAddress::class, 'address_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function departmentRecord()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function positionRecord()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class, 'mrf_id');
    }
}
