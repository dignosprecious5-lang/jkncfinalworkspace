<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $stringColumns = [
                'middle_name' => 'first_name',
                'suffix' => 'middle_name',
                'nickname' => 'suffix',
                'gender' => 'nickname',
                'civil_status' => 'gender',
                'nationality' => 'civil_status',
                'religion' => 'nationality',
                'place_of_birth' => 'age',
                'blood_type' => 'place_of_birth',
                'height' => 'blood_type',
                'weight' => 'height',
                'alternate_phone_number' => 'phone_number',
                'company_email' => 'work_email',
                'applicant_id' => 'company_email',
                'job_id' => 'applicant_id',
                'mrf_reference' => 'job_id',
                'jpf_reference' => 'mrf_reference',
                'job_level_rank' => 'position',
                'employment_type' => 'job_level_rank',
                'employment_status' => 'employment_type',
                'work_classification' => 'employment_status',
                'work_arrangement' => 'work_classification',
                'contract_duration' => 'schedule_end_time',
                'immediate_supervisor' => 'contract_duration',
                'reporting_to' => 'immediate_supervisor',
                'payroll_group' => 'reporting_to',
                'work_location' => 'payroll_group',
                'company_assigned_to' => 'work_location',
                'recruitment_status' => 'company_assigned_to',
                'onboarding_status' => 'recruitment_status',
                'salary_grade' => 'onboarding_status',
                'payroll_frequency' => 'payroll_type',
                'status_reason' => 'employment_status',
                'status_remarks' => 'status_reason',
                'status_approved_by' => 'status_remarks',
                'status_attachment_path' => 'status_approved_by',
                'passport_number' => 'status_attachment_path',
                'drivers_license_number' => 'passport_number',
                'prc_license_number' => 'drivers_license_number',
                'digital_id_token' => 'prc_license_number',
                'verification_reference' => 'digital_id_token',
            ];

            foreach ($stringColumns as $column => $after) {
                if (!Schema::hasColumn('employees', $column)) {
                    $table->string($column)->nullable()->after($after);
                }
            }

            foreach ([
                'date_of_birth' => 'religion',
                'date_hired' => 'work_arrangement',
                'start_date' => 'date_hired',
                'probationary_end_date' => 'start_date',
                'regularization_date' => 'probationary_end_date',
                'status_effective_date' => 'payroll_group',
                'passport_expiry_date' => 'passport_number',
                'drivers_license_expiry_date' => 'drivers_license_number',
                'prc_license_expiry_date' => 'prc_license_number',
                'digital_id_issued_at' => 'verification_reference',
                'digital_id_valid_until' => 'digital_id_issued_at',
            ] as $column => $after) {
                if (!Schema::hasColumn('employees', $column)) {
                    $table->date($column)->nullable()->after($after);
                }
            }

            foreach ([
                'is_pwd' => 'weight',
                'is_solo_parent' => 'is_pwd',
                'is_senior_citizen' => 'is_solo_parent',
                'bonus_eligibility' => 'payroll_frequency',
                'overtime_eligibility' => 'bonus_eligibility',
                'night_differential_eligibility' => 'overtime_eligibility',
                'holiday_pay_eligibility' => 'night_differential_eligibility',
                'verification_enabled' => 'digital_id_valid_until',
            ] as $column => $after) {
                if (!Schema::hasColumn('employees', $column)) {
                    $table->boolean($column)->default(false)->after($after);
                }
            }

            foreach ([
                'current_address' => 'address',
                'permanent_address' => 'current_address',
                'emergency_contact_name' => 'permanent_address',
                'emergency_contact_relationship' => 'emergency_contact_name',
                'emergency_contact_number' => 'emergency_contact_relationship',
                'emergency_contact_address' => 'emergency_contact_number',
                'digital_signature_path' => 'profile_photo',
                'tin_number' => 'status_attachment_path',
                'sss_number' => 'tin_number',
                'philhealth_number' => 'sss_number',
                'pagibig_number' => 'philhealth_number',
                'separation_remarks' => 'status_remarks',
            ] as $column => $after) {
                if (!Schema::hasColumn('employees', $column)) {
                    $table->text($column)->nullable()->after($after);
                }
            }

            foreach ([
                'allowances' => 'holiday_pay_eligibility',
                'incentives' => 'allowances',
                'benefits_checklist' => 'incentives',
                'educational_background' => 'pagibig_number',
                'employment_history' => 'educational_background',
                'certifications_trainings' => 'employment_history',
                'skills_competencies' => 'certifications_trainings',
                'employee_attachments' => 'skills_competencies',
                'system_access' => 'employee_attachments',
                'compliance_consents' => 'system_access',
                'photo_metadata' => 'profile_photo',
                'activity_audit' => 'compliance_consents',
                'salary_employment_history' => 'activity_audit',
            ] as $column => $after) {
                if (!Schema::hasColumn('employees', $column)) {
                    $table->json($column)->nullable()->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            foreach ([
                'salary_employment_history', 'activity_audit', 'photo_metadata', 'compliance_consents', 'system_access',
                'employee_attachments', 'skills_competencies', 'certifications_trainings', 'employment_history',
                'educational_background', 'benefits_checklist', 'incentives', 'allowances', 'pagibig_number',
                'philhealth_number', 'sss_number', 'tin_number', 'digital_signature_path', 'emergency_contact_address',
                'emergency_contact_number', 'emergency_contact_relationship', 'emergency_contact_name',
                'permanent_address', 'current_address', 'verification_enabled', 'digital_id_valid_until',
                'digital_id_issued_at', 'verification_reference', 'digital_id_token', 'prc_license_expiry_date',
                'prc_license_number', 'drivers_license_expiry_date', 'drivers_license_number',
                'passport_expiry_date', 'passport_number', 'status_attachment_path', 'status_approved_by',
                'status_remarks', 'status_reason', 'status_effective_date', 'holiday_pay_eligibility',
                'night_differential_eligibility', 'overtime_eligibility', 'bonus_eligibility',
                'payroll_frequency', 'salary_grade', 'onboarding_status', 'recruitment_status',
                'company_assigned_to', 'work_location', 'payroll_group', 'reporting_to',
                'immediate_supervisor', 'contract_duration', 'regularization_date', 'probationary_end_date',
                'start_date', 'date_hired', 'work_arrangement', 'work_classification', 'employment_status',
                'employment_type', 'job_level_rank', 'jpf_reference', 'mrf_reference', 'job_id', 'applicant_id',
                'company_email', 'alternate_phone_number', 'is_senior_citizen', 'is_solo_parent', 'is_pwd',
                'weight', 'height', 'blood_type', 'place_of_birth', 'date_of_birth', 'religion',
                'nationality', 'civil_status', 'gender', 'nickname', 'suffix', 'middle_name',
            ] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
