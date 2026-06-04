<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'nickname',
        'gender',
        'civil_status',
        'nationality',
        'religion',
        'date_of_birth',
        'age',
        'place_of_birth',
        'blood_type',
        'height',
        'weight',
        'is_pwd',
        'is_solo_parent',
        'is_senior_citizen',
        'address',
        'current_address',
        'permanent_address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
        'emergency_contact_address',
        'phone_number',
        'alternate_phone_number',
        'email',
        'personal_email',
        'work_email',
        'company_email',
        'profile_photo',
        'digital_signature_path',
        'photo_metadata',
        'office_id',
        'branch_id',
        'department_id',
        'division_id',
        'unit_id',
        'applicant_id',
        'job_id',
        'mrf_reference',
        'jpf_reference',
        'position',
        'job_level_rank',
        'employment_type',
        'employment_status',
        'work_classification',
        'work_arrangement',
        'date_hired',
        'start_date',
        'probationary_end_date',
        'regularization_date',
        'contract_duration',
        'immediate_supervisor',
        'reporting_to',
        'payroll_group',
        'work_location',
        'company_assigned_to',
        'recruitment_status',
        'onboarding_status',
        'payroll_type',
        'payroll_frequency',
        'salary_grade',
        'basic_salary',
        'hourly_rate',
        'allowances',
        'incentives',
        'bonus_eligibility',
        'overtime_eligibility',
        'night_differential_eligibility',
        'holiday_pay_eligibility',
        'benefits_checklist',
        'tin_number',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'passport_number',
        'passport_expiry_date',
        'drivers_license_number',
        'drivers_license_expiry_date',
        'prc_license_number',
        'prc_license_expiry_date',
        'other_government_information',
        'educational_background',
        'employment_history',
        'certifications_trainings',
        'skills_competencies',
        'employee_attachments',
        'system_access',
        'compliance_consents',
        'activity_audit',
        'salary_employment_history',
        'status_effective_date',
        'status_reason',
        'status_remarks',
        'status_approved_by',
        'status_attachment_path',
        'digital_id_token',
        'verification_reference',
        'digital_id_issued_at',
        'digital_id_valid_until',
        'verification_enabled',
        'schedule_start_time',
        'schedule_end_time',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'date_of_birth' => 'date',
        'date_hired' => 'date',
        'start_date' => 'date',
        'probationary_end_date' => 'date',
        'regularization_date' => 'date',
        'status_effective_date' => 'date',
        'passport_expiry_date' => 'date',
        'drivers_license_expiry_date' => 'date',
        'prc_license_expiry_date' => 'date',
        'digital_id_issued_at' => 'date',
        'digital_id_valid_until' => 'date',
        'is_pwd' => 'boolean',
        'is_solo_parent' => 'boolean',
        'is_senior_citizen' => 'boolean',
        'bonus_eligibility' => 'boolean',
        'overtime_eligibility' => 'boolean',
        'night_differential_eligibility' => 'boolean',
        'holiday_pay_eligibility' => 'boolean',
        'verification_enabled' => 'boolean',
        'allowances' => 'array',
        'incentives' => 'array',
        'benefits_checklist' => 'array',
        'educational_background' => 'array',
        'employment_history' => 'array',
        'certifications_trainings' => 'array',
        'skills_competencies' => 'array',
        'employee_attachments' => 'array',
        'other_government_information' => 'array',
        'system_access' => 'array',
        'compliance_consents' => 'array',
        'photo_metadata' => 'array',
        'activity_audit' => 'array',
        'salary_employment_history' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (!$employee->employee_code) {
                do {
                    $code = static::generateCompliantEmployeeCode();
                } while (static::where('employee_code', $code)->exists());

                $employee->employee_code = $code;
            }

            if (!$employee->digital_id_token) {
                $employee->digital_id_token = (string) \Illuminate\Support\Str::uuid();
            }

            if (!$employee->verification_reference) {
                $employee->verification_reference = 'EV-' . now()->format('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            }

            if (!$employee->employment_status) {
                $employee->employment_status = 'Active';
            }

            if (!$employee->digital_id_issued_at) {
                $employee->digital_id_issued_at = now()->toDateString();
            }

            if (blank($employee->payroll_type)) {
                $employee->payroll_type = 'Monthly Paid';
            }
        });
    }

    private static function generateCompliantEmployeeCode(): string
    {
        $firstDigits = ['1', '2', '3', '5', '7', '8', '9'];
        $digits = ['0', '1', '2', '3', '5', '7', '8', '9'];

        do {
            $code = $firstDigits[array_rand($firstDigits)];

            while (strlen($code) < 5) {
                $code .= $digits[array_rand($digits)];
            }
        } while (str_contains($code, '13') || str_contains($code, '31'));

        return $code;
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getLoginEmailAttribute()
    {
        return $this->work_email ?: $this->email;
    }

    public function payrollProfile()
    {
        return $this->hasOne(\App\Models\EmployeePayrollProfile::class);
    }

    public function payrollSummaries()
    {
        return $this->hasMany(\App\Models\PayrollSummary::class);
    }
}
