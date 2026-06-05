<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\EmployeeVerificationLog;
use App\Models\Office;
use App\Models\Unit;
use App\Support\HumanCapitalLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use RequestsHumanCapitalApproval;
    private array $employmentStatuses = [
        'Active', 'Probationary', 'Regular', 'Project-Based', 'Fixed-Term', 'Part-Time',
        'Casual / Temporary', 'Consultant / Independent Contractor', 'Resigned',
        'Terminated', 'End of Contract', 'Retired', 'Deceased', 'Inactive', 'Others',
    ];

    public function index()
    {
        $employees = Employee::with(['office', 'branch', 'department', 'division', 'unit', 'user'])
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'employee_code' => $item->employee_code,
                    'first_name' => $item->first_name,
                    'middle_name' => $item->middle_name,
                    'last_name' => $item->last_name,
                    'suffix' => $item->suffix,
                    'nickname' => $item->nickname,
                    'gender' => $item->gender,
                    'civil_status' => $item->civil_status,
                    'nationality' => $item->nationality,
                    'religion' => $item->religion,
                    'date_of_birth' => optional($item->date_of_birth)->format('Y-m-d'),
                    'full_name' => $item->full_name,
                    'age' => $item->date_of_birth ? $item->date_of_birth->age : $item->age,
                    'place_of_birth' => $item->place_of_birth,
                    'blood_type' => $item->blood_type,
                    'height' => $item->height,
                    'weight' => $item->weight,
                    'is_pwd' => $item->is_pwd,
                    'is_solo_parent' => $item->is_solo_parent,
                    'is_senior_citizen' => $item->is_senior_citizen,
                    'address' => $item->address,
                    'current_address' => $item->current_address ?: $item->address,
                    'permanent_address' => $item->permanent_address,
                    'phone_number' => $item->phone_number,
                    'alternate_phone_number' => $item->alternate_phone_number,
                    'email' => $item->email,
                    'personal_email' => $item->personal_email,
                    'work_email' => $item->work_email,
                    'company_email' => $item->company_email ?: $item->work_email,
                    'emergency_contact_name' => $item->emergency_contact_name,
                    'emergency_contact_relationship' => $item->emergency_contact_relationship,
                    'emergency_contact_number' => $item->emergency_contact_number,
                    'emergency_contact_address' => $item->emergency_contact_address,
                    'login_email' => $item->work_email ?: $item->email,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name,
                    'user_email' => $item->user?->email,
                    'user_role' => $item->user?->role,
                    'has_user_account' => (bool) $item->user_id,
                    'profile_photo' => $item->profile_photo,
                    'profile_photo_url' => $item->profile_photo ? Storage::url($item->profile_photo) : null,
                    'photo_metadata' => $item->photo_metadata ?? [],

                    'office_id' => $item->office_id,
                    'branch_id' => $item->branch_id,
                    'department_id' => $item->department_id,
                    'division_id' => $item->division_id,
                    'unit_id' => $item->unit_id,

                    'office_name' => $item->office?->office_name,
                    'branch_name' => $item->branch?->branch_name,
                    'department_name' => $item->department?->department_name,
                    'division_name' => $item->division?->division_name,
                    'unit_name' => $item->unit?->unit_name,

                    'applicant_id' => $item->applicant_id,
                    'job_id' => $item->job_id,
                    'mrf_reference' => $item->mrf_reference,
                    'jpf_reference' => $item->jpf_reference,
                    'position' => $item->position,
                    'job_level_rank' => $item->job_level_rank,
                    'employment_type' => $item->employment_type,
                    'employment_status' => $item->employment_status ?: 'Active',
                    'work_classification' => $item->work_classification,
                    'work_arrangement' => $item->work_arrangement,
                    'date_hired' => optional($item->date_hired)->format('Y-m-d'),
                    'start_date' => optional($item->start_date)->format('Y-m-d'),
                    'probationary_end_date' => optional($item->probationary_end_date)->format('Y-m-d'),
                    'regularization_date' => optional($item->regularization_date)->format('Y-m-d'),
                    'contract_duration' => $item->contract_duration,
                    'immediate_supervisor' => $item->immediate_supervisor,
                    'reporting_to' => $item->reporting_to,
                    'payroll_group' => $item->payroll_group,
                    'work_location' => $item->work_location,
                    'company_assigned_to' => $item->company_assigned_to,
                    'recruitment_status' => $item->recruitment_status,
                    'onboarding_status' => $item->onboarding_status,
                    'payroll_type' => $item->payroll_type,
                    'payroll_frequency' => $item->payroll_frequency,
                    'salary_grade' => $item->salary_grade,
                    'basic_salary' => $item->basic_salary,
                    'hourly_rate' => $item->hourly_rate,
                    'allowances' => $item->allowances ?? [],
                    'incentives' => $item->incentives ?? [],
                    'bonus_eligibility' => $item->bonus_eligibility,
                    'overtime_eligibility' => $item->overtime_eligibility,
                    'night_differential_eligibility' => $item->night_differential_eligibility,
                    'holiday_pay_eligibility' => $item->holiday_pay_eligibility,
                    'benefits_checklist' => $item->benefits_checklist ?? [],
                    'tin_number' => $item->tin_number,
                    'sss_number' => $item->sss_number,
                    'philhealth_number' => $item->philhealth_number,
                    'pagibig_number' => $item->pagibig_number,
                    'passport_number' => $item->passport_number,
                    'passport_expiry_date' => optional($item->passport_expiry_date)->format('Y-m-d'),
                    'drivers_license_number' => $item->drivers_license_number,
                    'drivers_license_expiry_date' => optional($item->drivers_license_expiry_date)->format('Y-m-d'),
                    'prc_license_number' => $item->prc_license_number,
                    'prc_license_expiry_date' => optional($item->prc_license_expiry_date)->format('Y-m-d'),
                    'other_government_information' => $item->other_government_information ?? [],
                    'educational_background' => $item->educational_background ?? [],
                    'employment_history' => $item->employment_history ?? [],
                    'certifications_trainings' => $item->certifications_trainings ?? [],
                    'skills_competencies' => $item->skills_competencies ?? [],
                    'employee_attachments' => $this->attachmentUrls($item->employee_attachments ?? []),
                    'system_access' => $item->system_access ?? [],
                    'compliance_consents' => $item->compliance_consents ?? [],
                    'activity_audit' => $item->activity_audit ?? [],
                    'salary_employment_history' => $item->salary_employment_history ?? [],
                    'status_effective_date' => optional($item->status_effective_date)->format('Y-m-d'),
                    'status_reason' => $item->status_reason,
                    'status_remarks' => $item->status_remarks,
                    'status_approved_by' => $item->status_approved_by,
                    'digital_id_token' => $item->digital_id_token,
                    'digital_id_issued_at' => optional($item->digital_id_issued_at)->format('Y-m-d'),
                    'digital_id_valid_until' => optional($item->digital_id_valid_until)->format('Y-m-d'),
                    'verification_enabled' => $item->verification_enabled,
                    'verification_url' => route('employee.verify.form', ['employee' => $item->employee_code]),
                    'schedule_start_time' => $item->schedule_start_time ? substr($item->schedule_start_time, 0, 5) : '',
                    'schedule_end_time' => $item->schedule_end_time ? substr($item->schedule_end_time, 0, 5) : '',
                ];
            })
            ->values();

        if (request()->wantsJson()) {
            return $employees;
        }

        return view('human-capital.employee-profile', [
            'employees' => $employees,

            'officeOptions' => Office::orderBy('office_name')
                ->get(['id', 'office_name', 'branch_id'])
                ->values(),

            'branchOptions' => Branch::orderBy('branch_name')
                ->get(['id', 'branch_name'])
                ->values(),

            'departmentOptions' => Department::orderBy('department_name')
                ->get(['id', 'office_id', 'department_name'])
                ->values(),

            'divisionOptions' => Division::orderBy('division_name')
                ->get(['id', 'department_id', 'division_name'])
                ->values(),

            'unitOptions' => Unit::orderBy('unit_name')
                ->get(['id', 'division_id', 'unit_name'])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('human-capital.employee-profile')
            ->withErrors([
                'employee_profile' => 'Direct employee profile creation is disabled. Please create employee profiles through Human Capital → On Boarding → Employee Registration after completed onboarding.',
            ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'captured_photo' => ['nullable', 'string'],

            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'civil_status' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', 'string', 'max:20'],
            'height' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'string', 'max:50'],
            'is_pwd' => ['nullable'],
            'is_solo_parent' => ['nullable'],
            'is_senior_citizen' => ['nullable'],
            'address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'alternate_phone_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:employees,email,' . $employee->id],
            'personal_email' => ['nullable', 'email', 'max:255', 'unique:employees,personal_email,' . $employee->id],
            'work_email' => ['nullable', 'email', 'max:255', 'unique:employees,work_email,' . $employee->id],
            'company_email' => ['nullable', 'email', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:255'],
            'emergency_contact_address' => ['nullable', 'string'],

            'office_id' => ['nullable', Rule::exists('offices', 'id')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'division_id' => ['nullable', Rule::exists('divisions', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],

            'position' => ['nullable', 'string', 'max:255'],
            'applicant_id' => ['nullable', 'string', 'max:255'],
            'job_id' => ['nullable', 'string', 'max:255'],
            'mrf_reference' => ['nullable', 'string', 'max:255'],
            'jpf_reference' => ['nullable', 'string', 'max:255'],
            'job_level_rank' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'employment_status_other' => ['nullable', 'string', 'max:255'],
            'work_classification' => ['nullable', 'string', 'max:255'],
            'work_arrangement' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'probationary_end_date' => ['nullable', 'date'],
            'regularization_date' => ['nullable', 'date'],
            'contract_duration' => ['nullable', 'string', 'max:255'],
            'immediate_supervisor' => ['nullable', 'string', 'max:255'],
            'reporting_to' => ['nullable', 'string', 'max:255'],
            'payroll_group' => ['nullable', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'company_assigned_to' => ['nullable', 'string', 'max:255'],
            'recruitment_status' => ['nullable', 'string', 'max:255'],
            'onboarding_status' => ['nullable', 'string', 'max:255'],
            'payroll_type' => ['required', Rule::in(['Monthly Paid', 'Daily Paid'])],
            'payroll_frequency' => ['nullable', 'string', 'max:255'],
            'salary_grade' => ['nullable', 'string', 'max:255'],
            'salary_grade_other' => ['nullable', 'string', 'max:255'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'string'],
            'incentives' => ['nullable', 'string'],
            'benefits_checklist' => ['nullable', 'array'],
            'benefits_checklist.*' => ['nullable', 'string'],
            'benefits_other' => ['nullable', 'string', 'max:255'],
            'bonus_eligibility' => ['nullable'],
            'overtime_eligibility' => ['nullable'],
            'night_differential_eligibility' => ['nullable'],
            'holiday_pay_eligibility' => ['nullable'],
            'tin_number' => ['nullable', 'string', 'max:255'],
            'sss_number' => ['nullable', 'string', 'max:255'],
            'philhealth_number' => ['nullable', 'string', 'max:255'],
            'pagibig_number' => ['nullable', 'string', 'max:255'],
            'passport_number' => ['nullable', 'string', 'max:255'],
            'passport_expiry_date' => ['nullable', 'date'],
            'drivers_license_number' => ['nullable', 'string', 'max:255'],
            'drivers_license_expiry_date' => ['nullable', 'date'],
            'prc_license_number' => ['nullable', 'string', 'max:255'],
            'prc_license_expiry_date' => ['nullable', 'date'],
            'educational_background' => ['nullable', 'string'],
            'employment_history' => ['nullable', 'string'],
            'certifications_trainings' => ['nullable', 'string'],
            'skills_competencies' => ['nullable', 'string'],
            'system_access' => ['nullable', 'string'],
            'compliance_consents' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,webp', 'max:10240'],
            'attachment_category' => ['nullable', 'string', 'max:255'],
            'attachment_title' => ['nullable', 'string', 'max:255'],
            'attachment_remarks' => ['nullable', 'string', 'max:1000'],
            'other_government_information' => ['nullable', 'string'],
            'status_effective_date' => ['nullable', 'date'],
            'status_reason' => ['nullable', 'string', 'max:255'],
            'status_remarks' => ['nullable', 'string'],
            'status_approved_by' => ['nullable', 'string', 'max:255'],
            'status_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'schedule_start_time' => ['nullable', 'date_format:H:i'],
            'schedule_end_time' => ['nullable', 'date_format:H:i'],
        ]);

        if (($validated['employment_status'] ?? null) === 'Others') {
            $validated['employment_status'] = $validated['employment_status_other'] ?? 'Others';
        }

        if (($validated['salary_grade'] ?? null) === 'Others') {
            $validated['salary_grade'] = $validated['salary_grade_other'] ?? 'Others';
        }

        if (in_array($validated['employment_status'] ?? '', ['Resigned', 'Terminated', 'End of Contract', 'Retired', 'Deceased', 'Inactive'], true)) {
            $request->validate([
                'status_effective_date' => ['required', 'date'],
                'status_reason' => ['required', 'string', 'max:255'],
                'status_approved_by' => ['required', 'string', 'max:255'],
                'status_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            ]);
        }

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }

            $validated['profile_photo'] = $request->file('profile_photo')->store('employee-photos', 'public');
            $validated['photo_metadata'] = $this->photoMetadata($request, 'Uploaded');
        } elseif ($request->filled('captured_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }

            $validated['profile_photo'] = $this->storeCapturedPhoto($request->captured_photo);
            $validated['photo_metadata'] = $this->photoMetadata($request, 'Camera Capture');
        }

        if ($request->hasFile('status_attachment')) {
            if ($employee->status_attachment_path) {
                Storage::disk('public')->delete($employee->status_attachment_path);
            }

            $validated['status_attachment_path'] = $request->file('status_attachment')->store('employee-status', 'public');
        }

        $validated['is_pwd'] = $request->boolean('is_pwd');
        $validated['is_solo_parent'] = $request->boolean('is_solo_parent');
        $validated['is_senior_citizen'] = $request->boolean('is_senior_citizen');
        $validated['bonus_eligibility'] = $request->boolean('bonus_eligibility');
        $validated['overtime_eligibility'] = $request->boolean('overtime_eligibility');
        $validated['night_differential_eligibility'] = $request->boolean('night_differential_eligibility');
        $validated['holiday_pay_eligibility'] = $request->boolean('holiday_pay_eligibility');
        $validated['verification_enabled'] = true;

        $validated['allowances'] = $this->linesToArray($request->allowances);
        $validated['incentives'] = $this->linesToArray($request->incentives);
        $validated['benefits_checklist'] = $this->selectionWithOther($request->input('benefits_checklist', []), $request->benefits_other);
        $validated['educational_background'] = $this->jsonField($request->educational_background);
        $validated['employment_history'] = $this->jsonField($request->employment_history);
        $validated['certifications_trainings'] = $this->jsonField($request->certifications_trainings);
        $validated['skills_competencies'] = $this->skillsField($request->skills_competencies);
        $validated['other_government_information'] = $this->jsonField($request->other_government_information);
        $validated['system_access'] = $this->jsonField($request->system_access);
        $validated['compliance_consents'] = $this->consentPayload($request);
        $validated['employee_attachments'] = $this->storeEmployeeAttachments($request, $employee);

        $validated['hourly_rate'] = $this->computeHourlyRate(
            $validated['basic_salary'],
            $validated['payroll_type']
        );

        if (!empty($validated['office_id'])) {
            $office = Office::find($validated['office_id']);
            $validated['branch_id'] = $office?->branch_id;
        }

        $validated['address'] = $validated['current_address'] ?? $validated['address'] ?? null;
        $validated['company_email'] = $validated['company_email'] ?: ($validated['work_email'] ?? null);
        $validated['email'] = $validated['work_email'] ?: ($validated['email'] ?? $employee->email);
        $validated['age'] = !empty($validated['date_of_birth'])
            ? \Carbon\Carbon::parse($validated['date_of_birth'])->age
            : ($validated['age'] ?? null);
        $validated['activity_audit'] = $this->auditTrail($employee, $validated, $request);
        $validated['salary_employment_history'] = $this->salaryEmploymentHistory($employee, $validated, $request);

        unset($validated['captured_photo'], $validated['employment_status_other'], $validated['salary_grade_other'], $validated['benefits_other'], $validated['attachments'], $validated['attachment_category'], $validated['attachment_title'], $validated['attachment_remarks'], $validated['status_attachment']);

        $this->requestHumanCapitalChange(
            $request,
            'Employee Profile',
            'update',
            $employee,
            $validated,
            trim($employee->full_name ?: $employee->employee_code)
        );

        return redirect()->back()->with('success', 'Employee update submitted for admin approval.');
    }

    public function verificationForm(?string $employee = null)
    {
        return view('employee-verification', ['employeeCode' => $employee]);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => ['required', 'regex:/^\d{5}$/'],
            'employee_name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
            'requestor_name' => ['required', 'string', 'max:255'],
            'requestor_company' => ['nullable', 'string', 'max:255'],
            'requestor_email' => ['required', 'email', 'max:255'],
            'requestor_contact' => ['required', 'string', 'max:255'],
            'requestor_position' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::where('employee_code', $validated['employee_code'])
            ->where('verification_enabled', true)
            ->get()
            ->first(fn (Employee $item) => strtolower($item->full_name) === strtolower(trim($validated['employee_name'])));

        $reference = 'VER-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        EmployeeVerificationLog::create([
            'verification_reference' => $reference,
            'employee_id' => $employee?->id,
            'employee_code' => $validated['employee_code'],
            'employee_name' => $validated['employee_name'],
            'purpose' => $validated['purpose'],
            'requestor_name' => $validated['requestor_name'],
            'requestor_company' => $validated['requestor_company'] ?? null,
            'requestor_email' => $validated['requestor_email'],
            'requestor_contact' => $validated['requestor_contact'],
            'requestor_position' => $validated['requestor_position'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'result_status' => $employee ? 'Matched' : 'No Match',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['submitted_at' => now()->toDateTimeString()],
        ]);

        return view('employee-verification', [
            'employeeCode' => $validated['employee_code'],
            'result' => $employee ? $this->publicEmployeeResult($employee, $reference) : null,
            'noMatch' => !$employee,
        ]);
    }

    private function computeHourlyRate($salary, $type)
    {
        if ($type === 'Monthly Paid') {
            return round($salary / 22 / 8, 2);
        }

        return round($salary / 8, 2);
    }

    private function attachmentUrls(array $attachments): array
    {
        return collect($attachments)->map(function ($item) {
            if (!empty($item['path'])) {
                $item['url'] = Storage::url($item['path']);
            }
            return $item;
        })->values()->all();
    }

    private function storeCapturedPhoto(string $dataUrl): ?string
    {
        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $dataUrl, $matches)) {
            return null;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $data = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));
        $path = 'employee-photos/capture-' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    private function photoMetadata(Request $request, string $source): array
    {
        return [
            'source' => $source,
            'uploaded_at' => now()->toDateTimeString(),
            'captured_by' => optional($request->user())->name,
            'user_id' => optional($request->user())->id,
            'ip_address' => $request->ip(),
            'device' => $request->userAgent(),
        ];
    }

    private function linesToArray(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', (string) $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function jsonField(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $this->linesToArray($value);
    }

    private function skillsField(?string $value): array
    {
        return collect($this->linesToArray($value))
            ->map(fn ($item) => str_starts_with($item, '•') ? $item : '• ' . $item)
            ->values()
            ->all();
    }

    private function selectionWithOther(array|string|null $values, ?string $other): array
    {
        $items = is_array($values) ? $values : $this->linesToArray($values);
        $items = collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->reject(fn ($item) => $item === 'Others')
            ->values();

        if (in_array('Others', (array) $values, true) && filled($other)) {
            $items->push('Others: ' . trim($other));
        }

        return $items->all();
    }

    private function consentPayload(Request $request): array
    {
        $selected = $request->input('compliance_consents', []);

        return collect((array) $selected)->map(fn ($label) => [
            'label' => $label,
            'accepted_at' => now()->toDateTimeString(),
            'accepted_by' => optional($request->user())->name,
            'ip_address' => $request->ip(),
            'device' => $request->userAgent(),
        ])->values()->all();
    }

    private function storeEmployeeAttachments(Request $request, Employee $employee): array
    {
        $attachments = $employee->employee_attachments ?? [];

        foreach ($request->file('attachments', []) as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('employee-attachments/' . $employee->id, 'public');
            $attachments[] = [
                'category' => $request->attachment_category ?: 'Other Attachments',
                'title' => $request->attachment_title ?: $this->defaultAttachmentTitle($request->attachment_category, $file->getClientOriginalName()),
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'path' => $path,
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_by' => optional($request->user())->name,
                'version' => count($attachments) + 1,
                'verification_status' => 'For Review',
                'remarks' => $request->attachment_remarks,
            ];
        }

        return $attachments;
    }

    private function defaultAttachmentTitle(?string $category, string $fileName): string
    {
        if (filled($category) && $category !== 'Other Attachments') {
            return $category;
        }

        return pathinfo($fileName, PATHINFO_FILENAME);
    }

    private function auditTrail(Employee $employee, array $validated, Request $request): array
    {
        $audit = $employee->activity_audit ?? [];
        foreach (['position', 'department_id', 'employment_status', 'work_arrangement', 'basic_salary', 'payroll_type', 'schedule_start_time', 'schedule_end_time'] as $field) {
            if (array_key_exists($field, $validated) && (string) $employee->{$field} !== (string) $validated[$field]) {
                $audit[] = [
                    'field' => $field,
                    'previous_value' => $employee->{$field},
                    'new_value' => $validated[$field],
                    'updated_by' => optional($request->user())->name,
                    'timestamp' => now()->toDateTimeString(),
                    'ip_address' => $request->ip(),
                    'device' => $request->userAgent(),
                ];
            }
        }

        return $audit;
    }

    private function salaryEmploymentHistory(Employee $employee, array $validated, Request $request): array
    {
        $history = $employee->salary_employment_history ?? [];
        foreach (['basic_salary', 'position', 'department_id', 'employment_status', 'work_arrangement', 'payroll_type'] as $field) {
            if (array_key_exists($field, $validated) && (string) $employee->{$field} !== (string) $validated[$field]) {
                $history[] = [
                    'field' => $field,
                    'previous_value' => $employee->{$field},
                    'new_value' => $validated[$field],
                    'effective_date' => $validated['status_effective_date'] ?? now()->toDateString(),
                    'reason' => $validated['status_reason'] ?? null,
                    'remarks' => $validated['status_remarks'] ?? null,
                    'approved_by' => $validated['status_approved_by'] ?? null,
                    'updated_by' => optional($request->user())->name,
                    'timestamp' => now()->toDateTimeString(),
                ];
            }
        }

        return $history;
    }

    private function employeeLogSnapshot(Employee $employee): array
    {
        return $employee->only([
            'employee_code',
            'first_name',
            'middle_name',
            'last_name',
            'position',
            'department_id',
            'division_id',
            'unit_id',
            'employment_status',
            'employment_type',
            'work_arrangement',
            'basic_salary',
            'payroll_type',
            'payroll_frequency',
            'salary_grade',
            'schedule_start_time',
            'schedule_end_time',
            'work_email',
            'company_email',
            'phone_number',
            'tin_number',
            'sss_number',
            'philhealth_number',
            'pagibig_number',
            'benefits_checklist',
            'employee_attachments',
            'other_government_information',
            'profile_photo',
        ]);
    }

    private function logEmployeeProfileUpdate(Request $request, Employee $employee, array $oldValues): void
    {
        $newValues = $this->employeeLogSnapshot($employee);
        $changedOld = [];
        $changedNew = [];

        foreach ($newValues as $field => $value) {
            if (json_encode($oldValues[$field] ?? null) !== json_encode($value)) {
                $changedOld[$field] = $oldValues[$field] ?? null;
                $changedNew[$field] = $value;
            }
        }

        if (empty($changedNew)) {
            return;
        }

        HumanCapitalLogger::log($request, [
            'module' => 'Employee Profile',
            'action' => 'updated',
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'subject_name' => trim($employee->full_name ?: $employee->employee_code),
            'description' => 'Employee profile updated.',
            'old_values' => $changedOld,
            'new_values' => $changedNew,
        ]);
    }

    private function publicEmployeeResult(Employee $employee, string $reference): array
    {
        return [
            'reference' => $reference,
            'full_name' => $employee->full_name,
            'employee_code' => $employee->employee_code,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'employment_status' => $employee->employment_status ?: 'Active',
            'employment_type' => $employee->employment_type,
            'date_hired' => optional($employee->date_hired ?: $employee->start_date)->format('F d, Y'),
            'company' => 'John Kelly & Company / JK&C Inc.',
            'verification_date' => now()->format('F d, Y'),
        ];
    }
}
