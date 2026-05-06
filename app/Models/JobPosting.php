<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'wage_compliance' => 'array',
        'benefits_package' => 'array',
        'work_schedule' => 'array',
        'recruitment_channels' => 'array',
        'screening_flow' => 'array',
        'human_capital_approval' => 'array',
        'hiring_manager_approval' => 'array',
        'finance_approval' => 'array',
        'president_approval' => 'array',
        'date_opened' => 'date',
        'date_needed' => 'date',
        'posting_start_date' => 'date',
        'target_hire_date' => 'date',
        'posted_date' => 'date',
    ];

    public function mrf()
    {
        return $this->belongsTo(ManpowerRequest::class, 'mrf_id');
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

    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class, 'job_posting_id');
    }
}