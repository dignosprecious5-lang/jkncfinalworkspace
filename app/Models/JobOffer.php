<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_id',
        'job_posting_id',
        'address_id',
        'branch_id',
        'office_id',
        'department_id',
        'division_id',
        'unit_id',
        'position_id',
        'salary_grade_id',

        'candidate_email',
        'name',
        'position',
        'salary',
        'start_date',
        'employment_type',
        'department',
        'company_address',
        'benefits',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function interview()
    {
        return $this->belongsTo(CandidateInterview::class, 'interview_id');
    }

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

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

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_id');
    }
}
