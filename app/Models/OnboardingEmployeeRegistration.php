<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingEmployeeRegistration extends Model
{
    protected $fillable = [
        'onboarding_checklist_id',
        'employee_profile_id',
        'full_name',
        'employee_id',
        'department',
        'start_date',
        'work_email',
        'manager',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function checklist()
    {
        return $this->belongsTo(OnboardingChecklist::class, 'onboarding_checklist_id');
    }

    public function employeeProfile()
    {
        return $this->belongsTo(Employee::class, 'employee_profile_id');
    }
}
