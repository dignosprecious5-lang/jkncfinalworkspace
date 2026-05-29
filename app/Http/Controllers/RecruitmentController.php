<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManpowerRequest;
use App\Models\JobPosting;
use App\Models\CandidateApplication;
use App\Models\CandidateAssessment;
use App\Models\CandidateInterview;
use App\Models\JobOffer;
use App\Models\OrganizationalAddress;
use App\Models\Branch;
use App\Models\Office;
use App\Models\Department;
use App\Models\Division;
use App\Models\Unit;
use App\Models\Position;
use App\Models\SalaryGrade;
use App\Models\PayrollLevel;
use App\Models\OnboardingChecklist;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\AssessmentProceedingMail;
use App\Mail\AssessmentTestMail;
use App\Mail\InterviewScheduleMail;
use App\Mail\JobOfferMail;
use App\Mail\PdsInvitationMail;
use App\Mail\ChecklistSubmissionMail;
use App\Models\Training;
use App\Models\User;
use App\Models\AssessmentType;
use App\Models\AssessmentQuestion;

class RecruitmentController extends Controller
{
    public function index()
    {
        $mrfData = ManpowerRequest::latest()->get();
        $jpfData = JobPosting::latest()->get();
        $cafData = CandidateApplication::latest()->get();
        $assessmentData = CandidateAssessment::latest()->get();
        $interviewData = CandidateInterview::latest()->get();
        $jobOfferData = JobOffer::latest()->get();

        $organizationalAddresses = OrganizationalAddress::orderBy('full_address')
            ->get()
            ->map(function ($address) {
                return [
                    'id' => $address->id,
                    'full_address' => $address->full_address,
                    'country' => $address->country,
                    'region_name' => $address->region_name,
                    'province_name' => $address->province_name,
                    'city_name' => $address->city_name,
                    'barangay_name' => $address->barangay_name,
                ];
            })
            ->values();

        $branches = Branch::with('address')
            ->orderBy('branch_name')
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'branch_name' => $branch->branch_name,
                    'branch_head' => $branch->branch_head,
                    'address_id' => $branch->address_id,
                    'address' => optional($branch->address)->full_address,
                ];
            })
            ->values();

        $offices = Office::with(['branch', 'address'])
            ->orderBy('office_name')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'office_name' => $office->office_name,
                    'office_head' => $office->office_head,
                    'branch_id' => $office->branch_id,
                    'branch_name' => optional($office->branch)->branch_name,
                    'address_id' => $office->address_id,
                    'address' => optional($office->address)->full_address,
                ];
            })
            ->values();

        $departments = Department::with(['office', 'address'])
            ->orderBy('department_name')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'department_name' => $department->department_name,
                    'department_head' => $department->department_head,
                    'office_id' => $department->office_id,
                    'office_name' => optional($department->office)->office_name,
                    'address_id' => $department->address_id,
                    'address' => optional($department->address)->full_address,
                ];
            })
            ->values();

        $divisions = Division::with(['department', 'address'])
            ->orderBy('division_name')
            ->get()
            ->map(function ($division) {
                return [
                    'id' => $division->id,
                    'division_name' => $division->division_name,
                    'division_head' => $division->division_head,
                    'department_id' => $division->department_id,
                    'department_name' => optional($division->department)->department_name,
                    'address_id' => $division->address_id,
                    'address' => optional($division->address)->full_address,
                ];
            })
            ->values();

        $units = Unit::with(['division', 'address'])
            ->orderBy('unit_name')
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'unit_name' => $unit->unit_name,
                    'unit_head' => $unit->unit_head,
                    'division_id' => $unit->division_id,
                    'division_name' => optional($unit->division)->division_name,
                    'address_id' => $unit->address_id,
                    'address' => optional($unit->address)->full_address,
                ];
            })
            ->values();

        $positions = Position::with(['unit', 'address'])
            ->orderBy('position_name')
            ->get()
            ->map(function ($position) {
                return [
                    'id' => $position->id,
                    'position_name' => $position->position_name,
                    'unit_id' => $position->unit_id,
                    'unit_name' => optional($position->unit)->unit_name,
                    'address_id' => $position->address_id,
                    'address' => optional($position->address)->full_address,
                ];
            })
            ->values();

        $salaryGrades = SalaryGrade::orderBy('code')
            ->get()
            ->map(function ($grade) {
                return [
                    'id' => $grade->id,
                    'code' => $grade->code,
                    'name' => $grade->name,
                    'payment_type' => $grade->payment_type,
                    'monthly_basic_pay' => $grade->monthly_basic_pay,
                    'applicable_daily_rate' => $grade->applicable_daily_rate,
                    'hourly_rate' => $grade->hourly_rate,
                    'minute_rate' => $grade->minute_rate,
                    'yearly_rate' => $grade->yearly_rate,
                ];
            })
            ->values();

        $payrollLevels = PayrollLevel::with('salaryGrade')
            ->orderBy('level_name')
            ->get()
            ->map(function ($level) {
                return [
                    'id' => $level->id,
                    'salary_grade_id' => $level->salary_grade_id,
                    'salary_grade' => optional($level->salaryGrade)->code,
                    'level_name' => $level->level_name,
                    'computation_type' => $level->computation_type,
                    'work_schedule' => $level->work_schedule,
                    'work_schedule_label' => $level->work_schedule_label ?? null,
                    'hours_per_day' => $level->hours_per_day,
                ];
            })
            ->values();

        $approvalUsers = User::whereIn('role', ['SuperAdmin', 'Admin', 'Employee'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'position' => $user->position,
                    'department' => $user->department,
                ];
            })
            ->values();

        return view('human-capital.recruitment', compact(
            'mrfData',
            'jpfData',
            'cafData',
            'assessmentData',
            'interviewData',
            'jobOfferData',
            'organizationalAddresses',
            'branches',
            'offices',
            'departments',
            'divisions',
            'units',
            'positions',
            'salaryGrades',
            'payrollLevels',
            'approvalUsers'
        ));
    }

    public function showPublicApplicationForm()
    {
        $jobPostings = JobPosting::whereIn('status', ['Posted', 'Screening'])
            ->orderBy('position')
            ->get()
            ->filter(fn($jpf) => $this->jpfApprovalsAllApproved($jpf))
            ->map(fn ($job) => [
                'id' => $job->id,
                'job_id' => $job->job_id,
                'position' => $job->position,
                'department' => $job->department,
                'department_unit' => $job->department_unit,
                'employment_type' => $job->employment_type,
                'work_arrangement' => $job->work_arrangement,
                'work_schedule' => $job->work_schedule,
                'location' => $job->location,
                'office_branch_site' => $job->office_branch_site,
                'applicable_area' => $job->applicable_area,
                'target_hire_date' => optional($job->target_hire_date)->format('Y-m-d'),
                'date_needed' => optional($job->date_needed)->format('Y-m-d'),
            ])
            ->values();

        return view('careers.apply', compact('jobPostings'));
    }

    public function showPublicCareersPage()
    {
        $jobPostings = JobPosting::whereIn('status', ['Posted', 'Screening'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($jpf) => $this->jpfApprovalsAllApproved($jpf))
            ->values();

        return view('human-capital.homepage_public', compact('jobPostings'));
    }

    public function showJobDetail($id)
    {
        $job = JobPosting::findOrFail($id);

        // Verify the job is approved and publicly available
        if (!in_array($job->status, ['Posted', 'Screening'], true) || !$this->jpfApprovalsAllApproved($job)) {
            abort(404, 'Job posting not found or is not currently available.');
        }

        return view('careers.job-detail', compact('job'));
    }

    public function onboarding()
    {
        $pdsData = \App\Models\PersonalDataSheet::latest()->get()->map(function ($pds) {
            $data = is_array($pds->data) ? $pds->data : (json_decode($pds->data, true) ?: []);

            return array_merge($data, [
                'id' => $pds->id,
                'db_id' => $pds->id,
                'fullName' => $pds->full_name ?: ($data['fullName'] ?? ''),
                'position' => $pds->position ?: ($data['position'] ?? ''),
                'email' => $pds->email ?: ($data['email'] ?? ''),
                'phone' => $pds->phone ?: ($data['phone'] ?? ''),
                'status' => $pds->status,
                'submittedDate' => optional($pds->created_at)->format('Y-m-d'),
            ]);
        });

        $trainingPrograms = Training::orderBy('title')->get();

        return view('human-capital.onboarding', compact('pdsData', 'trainingPrograms'));
    }


    public function showPublicPDSForm($token = null)
    {
        $jobOffer = null;

        if ($token) {
            $jobOffer = JobOffer::where('accept_token', $token)->firstOrFail();

            if ($jobOffer->status !== 'Accepted') {
                abort(403, 'This PDS link is only available after accepting the job offer.');
            }
        }

        return view('careers.pds', [
            'jobOffer' => $jobOffer,
            'token' => $token,
        ]);
    }


    public function storePDS(Request $request)
    {
        try {
            $jobOffer = null;

            if ($request->jobOfferToken) {
                $jobOffer = JobOffer::where('accept_token', $request->jobOfferToken)->first();
            }

            $fullName = $request->fullName;

            if (!$fullName) {
                $nameParts = array_filter([
                    $request->surname,
                    $request->firstName,
                    $request->middleName,
                ]);

                $fullName = implode(', ', array_filter([
                    $request->surname,
                    trim(implode(' ', array_filter([$request->firstName, $request->middleName]))),
                ]));
            }

            $position = $request->position ?: optional($jobOffer)->position;
            $email = $request->email ?: optional($jobOffer)->candidate_email;
            $phone = $request->phone ?: $request->mobileNo;

            $payload = $request->all();
            $payload['fullName'] = $fullName;
            $payload['position'] = $position;
            $payload['email'] = $email;
            $payload['phone'] = $phone;

            $pds = \App\Models\PersonalDataSheet::updateOrCreate(
                [
                    'job_offer_id' => optional($jobOffer)->id,
                    'email' => $email,
                ],
                [
                    'full_name' => $fullName,
                    'position' => $position,
                    'phone' => $phone,
                    'data' => $payload,
                    'status' => 'Submitted',
                ]
            );

            $checklist = OnboardingChecklist::firstOrCreate(
                ['personal_data_sheet_id' => $pds->id],
                [
                    'employee_name' => $pds->full_name,
                    'employee_email' => $pds->email,
                    'position' => $pds->position,
                    'checked_documents' => [],
                    'docs_submitted' => 0,
                    'docs_approved' => 0,
                    'total_docs' => 11,
                    'status' => 'Pending Documents',
                    'upload_token' => (string) Str::uuid(),
                    'created_by' => null,
                ]
            );

            if (!$checklist->upload_token) {
                $checklist->update([
                    'upload_token' => (string) Str::uuid(),
                ]);

                $checklist->refresh();
            }

            if ($pds->email) {
                try {
                    Mail::to($pds->email)->send(new ChecklistSubmissionMail(
                        $checklist,
                        route('careers.checklist.show', $checklist->upload_token)
                    ));
                } catch (\Exception $mailError) {
                    Log::error('Failed to send checklist upload email: ' . $mailError->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'PDS Submitted Successfully. Please check your email for the checklist upload link.',
                'data' => $pds,
                'checklist' => $checklist,
            ]);
        } catch (\Exception $e) {
            Log::error('PDS submission failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    private function requestArray(Request $request, string $key): array
    {
        $value = $request->input($key, []);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? array_values(array_filter($decoded)) : array_values(array_filter([$value]));
        }

        return is_array($value) ? array_values(array_filter($value)) : [];
    }

    private function normalizeBulletText(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $items = preg_split('/\r\n|\r|\n|•/', $value);
        $items = array_values(array_filter(array_map(function ($item) {
            return trim(preg_replace('/^[-*]\s*/', '', $item));
        }, $items)));

        return count($items) ? implode("\n", array_map(fn ($item) => '• ' . $item, $items)) : null;
    }

    private function nextMrfReference(): string
    {
        $year = date('Y');
        $last = ManpowerRequest::where('request_id', 'like', "MRF-{$year}-%")
            ->orderByDesc('request_id')
            ->value('request_id');

        $sequence = 1;
        if ($last && preg_match('/MRF-' . $year . '-(\d+)/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return "MRF-{$year}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    private function storeMrfAttachment(Request $request, string $inputName, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile($inputName)) {
            return $existingPath;
        }

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $request->file($inputName)->store('mrf-attachments', 'public');
    }

    public function storeMRF(Request $request)
    {
        if ($request->requestId && ManpowerRequest::where('request_id', $request->requestId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The MRF Reference Number already exists. Please use a unique reference number.',
            ], 422);
        }

        $data = [
            'request_id'          => $request->requestId ?: $this->nextMrfReference(),
            'address_id'          => $request->orgAddressId,
            'branch_id'           => $request->orgBranchId,
            'office_id'           => $request->orgOfficeId,
            'department_id'       => $request->orgDepartmentId,
            'division_id'         => $request->orgDivisionId,
            'unit_id'             => $request->orgUnitId,
            'position_id'         => $request->orgPositionId,

            'department'         => $request->department,
            'date_requested'     => $request->dateRequested,
            'date_required'      => $request->dateRequired,
            'position'           => $request->position,
            'employment_type'    => $request->employmentType,
            'immediate_supervisor' => $request->immediateSupervisor,
            'target_start_date'   => $request->targetStartDate,
            'job_level_rank'      => $request->jobLevelRank,
            'work_classification' => $request->workClassification,
            'work_arrangement'    => $request->workArrangement,
            'work_schedule'       => $request->workSchedule,
            'duties'             => $request->duties,
            'nature_of_request'  => $request->natureOfRequest,
            'age_range'          => $request->ageRange,
            'civil_status'       => $request->civilStatus,
            'gender'             => $request->gender,
            'headcount'          => $request->headcount,
            'education'          => $request->education,
            'qualifications'     => $request->qualifications,
            'required_skills'     => $this->normalizeBulletText($request->requiredSkills),
            'benefits_checklist'  => $this->requestArray($request, 'benefitsChecklist'),
            'required_licenses'   => $this->normalizeBulletText($request->requiredLicenses),
            'required_documents'  => $this->requestArray($request, 'requiredDocuments'),
            'salary_min'          => $request->salaryMin,
            'salary_max'          => $request->salaryMax,
            'contract_duration'   => $request->contractDuration,
            'urgency_level'       => $request->urgencyLevel,
            'candidate_profile_attached' => $request->boolean('candidateProfileAttached'),
            'job_description_attached'   => $request->boolean('jobDescriptionAttached'),
            'candidate_profile_path'     => $this->storeMrfAttachment($request, 'candidateProfileFile'),
            'job_description_path'       => $this->storeMrfAttachment($request, 'jobDescriptionFile'),
            'endorsements'        => [
                'immediate_supervisor' => $request->immediateSupervisorEndorsement,
                'department_head' => $request->departmentHeadEndorsement,
                'hc_head' => $request->hcHeadValidation,
                'finance_head' => $request->financeHeadClearance,
            ],
            'requested_by'       => $request->requestedBy,
            'approved_by'        => $request->approvedBy,
            'remarks'            => $request->remarks,
            'request_status'     => $request->requestStatus ?: 'Pending',
            'charged_to'         => $request->chargedTo,
            'breakdown_details'  => $request->breakdownDetails,
            'hired_personnel'    => $request->hiredPersonnel,
            'date_hired'         => $request->dateHired,
            'processed_by'       => $request->processedBy,
            'checked_by'         => $request->checkedBy,
        ];

        $mrf = ManpowerRequest::create($data);
        return response()->json(['success' => true, 'data' => $mrf]);
    }

    public function updateMRF(Request $request, $id)
    {
        $mrf = ManpowerRequest::findOrFail($id);

        if ($request->requestId && ManpowerRequest::where('request_id', $request->requestId)->whereKeyNot($mrf->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The MRF Reference Number already exists. Please use a unique reference number.',
            ], 422);
        }

        $data = [
            'request_id'          => $request->requestId ?: $mrf->request_id,
            'address_id'          => $request->orgAddressId,
            'branch_id'           => $request->orgBranchId,
            'office_id'           => $request->orgOfficeId,
            'department_id'       => $request->orgDepartmentId,
            'division_id'         => $request->orgDivisionId,
            'unit_id'             => $request->orgUnitId,
            'position_id'         => $request->orgPositionId,

            'department'         => $request->department,
            'date_requested'     => $request->dateRequested,
            'date_required'      => $request->dateRequired,
            'position'           => $request->position,
            'employment_type'    => $request->employmentType,
            'immediate_supervisor' => $request->immediateSupervisor,
            'target_start_date'   => $request->targetStartDate,
            'job_level_rank'      => $request->jobLevelRank,
            'work_classification' => $request->workClassification,
            'work_arrangement'    => $request->workArrangement,
            'work_schedule'       => $request->workSchedule,
            'duties'             => $request->duties,
            'nature_of_request'  => $request->natureOfRequest,
            'age_range'          => $request->ageRange,
            'civil_status'       => $request->civilStatus,
            'gender'             => $request->gender,
            'headcount'          => $request->headcount,
            'education'          => $request->education,
            'qualifications'     => $request->qualifications,
            'required_skills'     => $this->normalizeBulletText($request->requiredSkills),
            'benefits_checklist'  => $this->requestArray($request, 'benefitsChecklist'),
            'required_licenses'   => $this->normalizeBulletText($request->requiredLicenses),
            'required_documents'  => $this->requestArray($request, 'requiredDocuments'),
            'salary_min'          => $request->salaryMin,
            'salary_max'          => $request->salaryMax,
            'contract_duration'   => $request->contractDuration,
            'urgency_level'       => $request->urgencyLevel,
            'candidate_profile_attached' => $request->boolean('candidateProfileAttached'),
            'job_description_attached'   => $request->boolean('jobDescriptionAttached'),
            'candidate_profile_path'     => $this->storeMrfAttachment($request, 'candidateProfileFile', $mrf->candidate_profile_path),
            'job_description_path'       => $this->storeMrfAttachment($request, 'jobDescriptionFile', $mrf->job_description_path),
            'endorsements'        => [
                'immediate_supervisor' => $request->immediateSupervisorEndorsement,
                'department_head' => $request->departmentHeadEndorsement,
                'hc_head' => $request->hcHeadValidation,
                'finance_head' => $request->financeHeadClearance,
            ],
            'requested_by'       => $request->requestedBy,
            'approved_by'        => $request->approvedBy,
            'remarks'            => $request->remarks,
            'request_status'     => $request->requestStatus ?: $mrf->request_status,
            'charged_to'         => $request->chargedTo,
            'breakdown_details'  => $request->breakdownDetails,
            'hired_personnel'    => $request->hiredPersonnel,
            'date_hired'         => $request->dateHired,
            'processed_by'       => $request->processedBy,
            'checked_by'         => $request->checkedBy,
        ];

        $mrf->update($data);
        return response()->json(['success' => true, 'data' => $mrf]);
    }

    public function approveMRF($id)
    {
        $mrf = ManpowerRequest::findOrFail($id);
        $mrf->update(['request_status' => 'Approved']);
        return response()->json(['success' => true, 'data' => $mrf]);
    }

    public function cancelMRF($id)
    {
        $mrf = ManpowerRequest::findOrFail($id);
        $mrf->update(['request_status' => 'Cancelled']);
        return response()->json(['success' => true, 'data' => $mrf]);
    }


    private function resolveJpfMrf(Request $request, ?JobPosting $jpf = null): ?ManpowerRequest
    {
        $mrfId = $request->input('mrfId')
            ?: $request->input('mrf_id')
            ?: optional($jpf)->mrf_id;

        if ($mrfId) {
            $mrf = ManpowerRequest::find($mrfId);

            if ($mrf) {
                return $mrf;
            }
        }

        $relatedMrfNo = $request->input('relatedMrfNo')
            ?: $request->input('related_mrf_no')
            ?: optional($jpf)->related_mrf_no;

        if ($relatedMrfNo) {
            return ManpowerRequest::where('request_id', $relatedMrfNo)->first();
        }

        return null;
    }

    private function requestValue(Request $request, string $camelKey, string $snakeKey, $fallback = null)
    {
        if ($request->has($camelKey)) {
            return $request->input($camelKey);
        }

        if ($request->has($snakeKey)) {
            return $request->input($snakeKey);
        }

        return $fallback;
    }

    private function approvedMrfIsValid(?ManpowerRequest $mrf): bool
    {
        return $mrf && strtolower((string) $mrf->request_status) === 'approved';
    }


    private function buildJpfApprovalPayload($payload, string $label, ?array $existing = null): array
    {
        $payload = is_array($payload) ? $payload : [];
        $existing = is_array($existing) ? $existing : [];

        $approverId = $payload['approver_id'] ?? null;
        $approverId = $approverId !== '' ? $approverId : null;

        $approver = $approverId ? User::find($approverId) : null;
        $sameApprover = $approver && (int) ($existing['approver_id'] ?? 0) === (int) $approver->id;

        return [
            'label' => $label,
            'approver_id' => $approver ? $approver->id : null,
            'name' => $approver ? $approver->name : ($payload['name'] ?? ($existing['name'] ?? '')),
            'email' => $approver ? $approver->email : ($payload['email'] ?? ($existing['email'] ?? '')),
            'role' => $approver ? $approver->role : ($payload['role'] ?? ($existing['role'] ?? '')),
            'position' => $approver ? $approver->position : ($payload['position'] ?? ($existing['position'] ?? '')),
            'department' => $approver ? $approver->department : ($payload['department'] ?? ($existing['department'] ?? '')),
            'status' => $sameApprover ? ($existing['status'] ?? 'Pending') : 'Pending',
            'date' => $sameApprover ? ($existing['date'] ?? null) : null,
            'approved_at' => $sameApprover ? ($existing['approved_at'] ?? null) : null,
            'decided_by' => $sameApprover ? ($existing['decided_by'] ?? null) : null,
            'decided_by_name' => $sameApprover ? ($existing['decided_by_name'] ?? null) : null,
        ];
    }



    private function jpfApprovalColumns(): array
    {
        return [
            'human_capital_approval',
            'hiring_manager_approval',
            'finance_approval',
            'president_approval',
        ];
    }

    private function jpfApprovalIsApproved($approval): bool
    {
        return is_array($approval) && strtolower((string) ($approval['status'] ?? '')) === 'approved';
    }

    private function jpfApprovalsAllApprovedFromArray(array $approvals): bool
    {
        foreach ($this->jpfApprovalColumns() as $column) {
            if (!$this->jpfApprovalIsApproved($approvals[$column] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function jpfApprovalsAllApproved(JobPosting $jpf): bool
    {
        return $this->jpfApprovalsAllApprovedFromArray([
            'human_capital_approval' => $jpf->human_capital_approval,
            'hiring_manager_approval' => $jpf->hiring_manager_approval,
            'finance_approval' => $jpf->finance_approval,
            'president_approval' => $jpf->president_approval,
        ]);
    }

    private function normalizeJpfStatus(?string $requestedStatus, array $approvals, ?JobPosting $existing = null): string
    {
        $requestedStatus = trim((string) ($requestedStatus ?: 'Draft'));
        $allApproved = $this->jpfApprovalsAllApprovedFromArray($approvals);

        if ($requestedStatus === 'Cancelled') {
            return 'Cancelled';
        }

        if ($requestedStatus === 'Draft') {
            return 'Draft';
        }

        if (!$allApproved) {
            return 'For Approval';
        }

        $allowedAfterApproval = ['Approved', 'Posted', 'Screening', 'Interviewing', 'Offer Stage', 'Filled', 'Closed'];

        if (in_array($requestedStatus, $allowedAfterApproval, true)) {
            return $requestedStatus;
        }

        return 'Approved';
    }

    private function postedJpfCanReceiveApplicants(JobPosting $jpf): bool
    {
        return in_array($jpf->status, ['Posted', 'Screening'], true) && $this->jpfApprovalsAllApproved($jpf);
    }

    private function jpfCanProceedToJobOffer(JobPosting $jpf): bool
    {
        return in_array($jpf->status, ['Posted', 'Screening', 'Interviewing', 'Offer Stage'], true) && $this->jpfApprovalsAllApproved($jpf);
    }

    public function actOnJPFApproval(Request $request, $id, $level)
    {
        $columns = [
            'human-capital' => 'human_capital_approval',
            'hiring-manager' => 'hiring_manager_approval',
            'finance' => 'finance_approval',
            'president' => 'president_approval',
        ];

        if (!array_key_exists($level, $columns)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid approval level.'
            ], 422);
        }

        $request->validate([
            'status' => ['required', 'in:Approved,Hold,Cancelled'],
        ]);

        $jpf = JobPosting::findOrFail($id);

        if (!in_array($jpf->status, ['For Approval'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This JPF is not currently submitted for approval. Draft records must be submitted as For Approval first, and approved records no longer need approval actions.'
            ], 422);
        }

        $column = $columns[$level];
        $approval = is_array($jpf->{$column}) ? $jpf->{$column} : [];
        $user = $request->user();

        $isSelectedApprover = isset($approval['approver_id']) && (int) $approval['approver_id'] === (int) $user->id;

        if (!$isSelectedApprover) {
            return response()->json([
                'success' => false,
                'message' => 'Only the selected approver for this approval level can approve, hold, or cancel this JPF. Other approvers may approve their own assigned levels separately.'
            ], 403);
        }

        $currentApprovalStatus = strtolower((string) ($approval['status'] ?? 'Pending'));
        if (in_array($currentApprovalStatus, ['approved', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This approval level is already finalized.'
            ], 422);
        }

        // Parallel approval: every selected approver may act on their own assigned level
        // while the JPF is For Approval. The system only changes the JPF to Approved
        // when all approval levels are already Approved.

        $approval['status'] = $request->status;
        $approval['date'] = now()->format('Y-m-d');
        $approval['approved_at'] = now()->toDateTimeString();
        $approval['decided_by'] = $user->id;
        $approval['decided_by_name'] = $user->name;

        $approvalSnapshot = [
            'human_capital_approval' => $jpf->human_capital_approval,
            'hiring_manager_approval' => $jpf->hiring_manager_approval,
            'finance_approval' => $jpf->finance_approval,
            'president_approval' => $jpf->president_approval,
        ];
        $approvalSnapshot[$column] = $approval;

        $statusUpdate = $jpf->status;

        if ($request->status === 'Cancelled') {
            $statusUpdate = 'Cancelled';
        } elseif ($this->jpfApprovalsAllApprovedFromArray($approvalSnapshot)) {
            $statusUpdate = in_array($jpf->status, ['Posted', 'Screening', 'Interviewing', 'Offer Stage', 'Filled', 'Closed'], true)
                ? $jpf->status
                : 'Approved';
        } elseif (!in_array($jpf->status, ['Draft', 'Cancelled', 'Closed'], true)) {
            $statusUpdate = 'For Approval';
        }

        $jpf->update([
            $column => $approval,
            'status' => $statusUpdate,
        ]);

        $jpf->refresh();

        return response()->json([
            'success' => true,
            'message' => 'JPF approval updated successfully.',
            'data' => $jpf,
        ]);
    }

    public function storeJPF(Request $request)
    {
        $mrf = $this->resolveJpfMrf($request);

        if (!$mrf) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an approved MRF before creating a JPF.'
            ], 422);
        }

        if (!$this->approvedMrfIsValid($mrf)) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved MRF records can be used to create a JPF.'
            ], 422);
        }

        $approvalData = [
            'human_capital_approval' => $this->buildJpfApprovalPayload($request->humanCapitalApproval, 'Human Capital'),
            'hiring_manager_approval' => $this->buildJpfApprovalPayload($request->hiringManagerApproval, 'Hiring Manager'),
            'finance_approval'       => $this->buildJpfApprovalPayload($request->financeApproval, 'Finance'),
            'president_approval'     => $this->buildJpfApprovalPayload($request->presidentApproval, 'President / Final'),
        ];

        $data = [
            'mrf_id'                 => $mrf->id,
            'address_id'             => $request->orgAddressId,
            'branch_id'              => $request->orgBranchId,
            'office_id'              => $request->orgOfficeId,
            'department_id'          => $request->orgDepartmentId,
            'division_id'            => $request->orgDivisionId,
            'unit_id'                => $request->orgUnitId,
            'position_id'            => $request->orgPositionId,
            'salary_grade_id'        => $request->salaryGradeId,

            'position'               => $request->position,
            'employment_type'        => $request->employmentType,
            'location'               => $request->workLocation,
            'salary_range'           => $request->minSalary . ' - ' . $request->maxSalary,
            'job_description'        => $request->duties,
            'requirements'           => $request->education,
            'posted_date'            => $request->postingStartDate ?: date('Y-m-d'),
            'status'                 => $this->normalizeJpfStatus($request->status, $approvalData),

            'related_mrf_no'         => $mrf->request_id,
            'date_opened'            => $request->dateOpened,
            'hiring_status'          => $request->hiringStatus,
            'company_name'           => $request->companyName,
            'office_branch_site'     => $request->officeBranchSite,
            'department_unit'        => $request->departmentUnit,
            'hiring_manager'         => $request->hiringManager,
            'department_superior'    => $request->departmentSuperior,
            'no_of_vacancies'        => $request->noOfVacancies,
            'position_level'         => $request->positionLevel,
            'reports_to'             => $request->reportsTo,
            'min_salary_offer'       => $request->minSalary,
            'max_salary_offer'       => $request->maxSalary,
            'salary_grade'           => $request->salaryGrade,
            'applicable_region'      => $request->applicableRegion ?: 'Central Visayas',
            'applicable_area'        => $request->applicableArea,
            'current_daily_min_wage' => $request->dailyMinWage,
            'monthly_equivalent'     => $request->monthlyEquivalent,
            'wage_compliance'        => $request->wageCompliance,
            'benefits_package'       => $request->benefits,
            'work_schedule'          => $request->workSchedule,
            'rest_days'              => $request->restDays,
            'education_req'          => $request->education,
            'experience_req'         => $request->experience,
            'skills_req'             => $request->skills,
            'licenses_req'           => $request->licenses,
            'preferred_qualifications' => $request->preferredQualifications,
            'duties_responsibilities' => $request->duties,
            'recruitment_channels'   => $request->channels,
            'screening_flow'         => $request->screeningFlow,
            'date_needed'            => $request->dateNeeded,
            'posting_start_date'     => $request->postingStartDate,
            'target_hire_date'       => $request->targetHireDate,
            'human_capital_approval' => $approvalData['human_capital_approval'],
            'hiring_manager_approval' => $approvalData['hiring_manager_approval'],
            'finance_approval'       => $approvalData['finance_approval'],
            'president_approval'     => $approvalData['president_approval'],
        ];

        if (!isset($data['job_id'])) {
            $year = date('Y');
            $count = JobPosting::whereYear('created_at', $year)->count() + 1;
            $data['job_id'] = "JPF-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $jpf = JobPosting::create($data);
        return response()->json(['success' => true, 'data' => $jpf]);
    }

    public function updateJPF(Request $request, $id)
    {
        $jpf = JobPosting::findOrFail($id);
        $mrf = $this->resolveJpfMrf($request, $jpf);

        if (!$mrf) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an approved MRF before updating this JPF.'
            ], 422);
        }

        if (!$this->approvedMrfIsValid($mrf)) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved MRF records can be linked to a JPF.'
            ], 422);
        }

        $approvalData = [
            'human_capital_approval' => $this->buildJpfApprovalPayload(
                $request->input('humanCapitalApproval', $request->input('human_capital_approval', $jpf->human_capital_approval)),
                'Human Capital',
                $jpf->human_capital_approval
            ),
            'hiring_manager_approval' => $this->buildJpfApprovalPayload(
                $request->input('hiringManagerApproval', $request->input('hiring_manager_approval', $jpf->hiring_manager_approval)),
                'Hiring Manager',
                $jpf->hiring_manager_approval
            ),
            'finance_approval'       => $this->buildJpfApprovalPayload(
                $request->input('financeApproval', $request->input('finance_approval', $jpf->finance_approval)),
                'Finance',
                $jpf->finance_approval
            ),
            'president_approval'     => $this->buildJpfApprovalPayload(
                $request->input('presidentApproval', $request->input('president_approval', $jpf->president_approval)),
                'President / Final',
                $jpf->president_approval
            ),
        ];

        $data = [
            'mrf_id'                 => $mrf->id,
            'address_id'             => $this->requestValue($request, 'orgAddressId', 'address_id', $jpf->address_id),
            'branch_id'              => $this->requestValue($request, 'orgBranchId', 'branch_id', $jpf->branch_id),
            'office_id'              => $this->requestValue($request, 'orgOfficeId', 'office_id', $jpf->office_id),
            'department_id'          => $this->requestValue($request, 'orgDepartmentId', 'department_id', $jpf->department_id),
            'division_id'            => $this->requestValue($request, 'orgDivisionId', 'division_id', $jpf->division_id),
            'unit_id'                => $this->requestValue($request, 'orgUnitId', 'unit_id', $jpf->unit_id),
            'position_id'            => $this->requestValue($request, 'orgPositionId', 'position_id', $jpf->position_id),
            'salary_grade_id'        => $this->requestValue($request, 'salaryGradeId', 'salary_grade_id', $jpf->salary_grade_id),

            'position'               => $this->requestValue($request, 'position', 'position', $jpf->position),
            'employment_type'        => $this->requestValue($request, 'employmentType', 'employment_type', $jpf->employment_type),
            'location'               => $this->requestValue($request, 'workLocation', 'location', $jpf->location),
            'salary_range'           => trim((string) $this->requestValue($request, 'minSalary', 'min_salary_offer', $jpf->min_salary_offer)) . ' - ' . trim((string) $this->requestValue($request, 'maxSalary', 'max_salary_offer', $jpf->max_salary_offer)),
            'job_description'        => $this->requestValue($request, 'duties', 'job_description', $jpf->job_description),
            'requirements'           => $this->requestValue($request, 'education', 'requirements', $jpf->requirements),
            'status'                 => $this->normalizeJpfStatus($this->requestValue($request, 'status', 'status', $jpf->status), $approvalData, $jpf),

            'related_mrf_no'         => $mrf->request_id,
            'date_opened'            => $this->requestValue($request, 'dateOpened', 'date_opened', $jpf->date_opened),
            'hiring_status'          => $this->requestValue($request, 'hiringStatus', 'hiring_status', $jpf->hiring_status),
            'company_name'           => $this->requestValue($request, 'companyName', 'company_name', $jpf->company_name),
            'office_branch_site'     => $this->requestValue($request, 'officeBranchSite', 'office_branch_site', $jpf->office_branch_site),
            'department_unit'        => $this->requestValue($request, 'departmentUnit', 'department_unit', $jpf->department_unit),
            'hiring_manager'         => $this->requestValue($request, 'hiringManager', 'hiring_manager', $jpf->hiring_manager),
            'department_superior'    => $this->requestValue($request, 'departmentSuperior', 'department_superior', $jpf->department_superior),
            'no_of_vacancies'        => $this->requestValue($request, 'noOfVacancies', 'no_of_vacancies', $jpf->no_of_vacancies),
            'position_level'         => $this->requestValue($request, 'positionLevel', 'position_level', $jpf->position_level),
            'reports_to'             => $this->requestValue($request, 'reportsTo', 'reports_to', $jpf->reports_to),
            'min_salary_offer'       => $this->requestValue($request, 'minSalary', 'min_salary_offer', $jpf->min_salary_offer),
            'max_salary_offer'       => $this->requestValue($request, 'maxSalary', 'max_salary_offer', $jpf->max_salary_offer),
            'salary_grade'           => $this->requestValue($request, 'salaryGrade', 'salary_grade', $jpf->salary_grade),
            'applicable_region'      => $this->requestValue($request, 'applicableRegion', 'applicable_region', $jpf->applicable_region),
            'applicable_area'        => $this->requestValue($request, 'applicableArea', 'applicable_area', $jpf->applicable_area),
            'current_daily_min_wage' => $this->requestValue($request, 'dailyMinWage', 'current_daily_min_wage', $jpf->current_daily_min_wage),
            'monthly_equivalent'     => $this->requestValue($request, 'monthlyEquivalent', 'monthly_equivalent', $jpf->monthly_equivalent),
            'wage_compliance'        => $this->requestValue($request, 'wageCompliance', 'wage_compliance', $jpf->wage_compliance),
            'benefits_package'       => $this->requestValue($request, 'benefits', 'benefits_package', $jpf->benefits_package),
            'work_schedule'          => $this->requestValue($request, 'workSchedule', 'work_schedule', $jpf->work_schedule),
            'rest_days'              => $this->requestValue($request, 'restDays', 'rest_days', $jpf->rest_days),
            'education_req'          => $this->requestValue($request, 'education', 'education_req', $jpf->education_req),
            'experience_req'         => $this->requestValue($request, 'experience', 'experience_req', $jpf->experience_req),
            'skills_req'             => $this->requestValue($request, 'skills', 'skills_req', $jpf->skills_req),
            'licenses_req'           => $this->requestValue($request, 'licenses', 'licenses_req', $jpf->licenses_req),
            'preferred_qualifications' => $this->requestValue($request, 'preferredQualifications', 'preferred_qualifications', $jpf->preferred_qualifications),
            'duties_responsibilities' => $this->requestValue($request, 'duties', 'duties_responsibilities', $jpf->duties_responsibilities),
            'recruitment_channels'   => $this->requestValue($request, 'channels', 'recruitment_channels', $jpf->recruitment_channels),
            'screening_flow'         => $this->requestValue($request, 'screeningFlow', 'screening_flow', $jpf->screening_flow),
            'date_needed'            => $this->requestValue($request, 'dateNeeded', 'date_needed', $jpf->date_needed),
            'posting_start_date'     => $this->requestValue($request, 'postingStartDate', 'posting_start_date', $jpf->posting_start_date),
            'target_hire_date'       => $this->requestValue($request, 'targetHireDate', 'target_hire_date', $jpf->target_hire_date),
            'posted_date'            => $this->requestValue($request, 'posted_date', 'posted_date', $jpf->posted_date),
            'human_capital_approval' => $approvalData['human_capital_approval'],
            'hiring_manager_approval' => $approvalData['hiring_manager_approval'],
            'finance_approval'       => $approvalData['finance_approval'],
            'president_approval'     => $approvalData['president_approval'],
        ];

        $jpf->update($data);
        $jpf->refresh();

        return response()->json(['success' => true, 'data' => $jpf]);
    }

    public function storeCAF(Request $request)
    {
        $isPublicSubmission = $request->routeIs('careers.apply.submit');

        $request->validate([
            'jobPostingId' => ['required', 'exists:job_postings,id'],
            'firstName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'fullName' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:100'],
            'cv' => [$isPublicSubmission ? 'required' : 'nullable', 'file', 'max:4096'],
            'consentAccepted' => [$isPublicSubmission ? 'accepted' : 'nullable'],
        ]);

        $cvPath = null;
        if ($request->hasFile('cv')) {
            $cvPath = $request->file('cv')->store('resumes', 'public');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        $coverLetterPath = null;
        if ($request->hasFile('cover_letter_file')) {
            $coverLetterPath = $request->file('cover_letter_file')->store('cover_letters', 'public');
        }

        $portfolioPath = null;
        if ($request->hasFile('portfolio_file')) {
            $portfolioPath = $request->file('portfolio_file')->store('portfolios', 'public');
        }

        $governmentIdPath = null;
        if ($request->hasFile('government_id')) {
            $governmentIdPath = $request->file('government_id')->store('candidate_ids', 'public');
        }

        $jobPosting = JobPosting::find($request->jobPostingId);

        if (!$jobPosting) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid JPF before creating a candidate record.'
            ], 422);
        }

        if (!$this->postedJpfCanReceiveApplicants($jobPosting)) {
            return response()->json([
                'success' => false,
                'message' => 'Only fully approved Posted/Screening JPF records can be used for candidate records.'
            ], 422);
        }

        $applicantType = $request->applicantType ?: 'New Applicant';
        $fullName = $request->fullName ?: trim(implode(' ', array_filter([
            $request->firstName,
            $request->middleName,
            $request->lastName,
        ])));

        $applicationData = $request->except([
            '_token',
            'photo',
            'cv',
            'cover_letter_file',
            'portfolio_file',
            'government_id',
        ]);
        $applicationData['job'] = [
            'id' => $jobPosting->id,
            'job_id' => $jobPosting->job_id,
            'position' => $jobPosting->position,
            'department_unit' => $jobPosting->department_unit,
            'employment_type' => $jobPosting->employment_type,
            'work_arrangement' => $jobPosting->work_arrangement ?? null,
        ];

        $attachmentPaths = [
            'photo' => $photoPath,
            'resume_cv' => $cvPath,
            'cover_letter' => $coverLetterPath,
            'portfolio' => $portfolioPath,
            'government_id' => $governmentIdPath,
        ];

        $caf = CandidateApplication::create([
            'applicant_id' => $this->generateApplicantId(),
            'job_posting_id' => $jobPosting->id,
            'name' => $fullName,
            'position' => $request->positionApplied ?: $jobPosting->position,
            'email' => $request->email,
            'phone' => $request->phone,
            'photo_path' => $photoPath,
            'cv_path' => $cvPath,
            'cover_letter_path' => $coverLetterPath,
            'cover_letter' => $request->coverLetter,
            'application_data' => $applicationData,
            'attachment_paths' => $attachmentPaths,
            'consent_accepted_at' => $request->boolean('consentAccepted') ? now() : null,
            'consent_version' => $request->boolean('consentAccepted') ? '2026-05-28-v1' : null,
            'submission_metadata' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'submitted_at' => now()->toDateTimeString(),
            ],
            'applicant_type' => $applicantType,
            'internal_remarks' => $request->internalRemarks,
            'status' => 'Pending',
            'applied_date' => date('Y-m-d')
        ]);

        if ($jobPosting->status === 'Posted') {
            $jobPosting->update(['status' => 'Screening']);
        }

        return response()->json(['success' => true, 'data' => $caf]);
    }

    private function generateApplicantId(): string
    {
        $year = date('Y');
        $count = CandidateApplication::whereYear('created_at', $year)->count() + 1;

        do {
            $id = 'CAF-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (CandidateApplication::where('applicant_id', $id)->exists());

        return $id;
    }

    public function updateCAF(Request $request, $id)
    {
        $caf = CandidateApplication::findOrFail($id);
        $jobPosting = JobPosting::find($request->jobPostingId);

        if (!$jobPosting) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid JPF before updating this candidate record.'
            ], 422);
        }

        if (!$this->postedJpfCanReceiveApplicants($jobPosting)) {
            return response()->json([
                'success' => false,
                'message' => 'Only fully approved Posted/Screening JPF records can be used for candidate records.'
            ], 422);
        }

        $data = [
            'job_posting_id' => $jobPosting->id,
            'name' => $request->fullName,
            'position' => $request->positionApplied ?: $jobPosting->position,
            'email' => $request->email,
            'phone' => $request->phone,
            'cover_letter' => $request->coverLetter,
            'applicant_type' => $request->applicantType ?: ($caf->applicant_type ?: 'New Applicant'),
            'internal_remarks' => $request->internalRemarks,
            'status' => $request->status ?: $caf->status,
        ];

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('resumes', 'public');
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('photos', 'public');
        }

        if ($request->hasFile('cover_letter_file')) {
            $data['cover_letter_path'] = $request->file('cover_letter_file')->store('cover_letters', 'public');
        }

        $caf->update($data);
        return response()->json(['success' => true, 'data' => $caf]);
    }

    public function proceedToAssessment($id)
    {
        $caf = CandidateApplication::findOrFail($id);
        $caf->update(['status' => 'Assessment']);

        // Check if assessment already exists to avoid duplicates
        $assessment = CandidateAssessment::where('email', $caf->email)
            ->where('position', $caf->position)
            ->whereIn('status', ['Pending Assessment', 'In Progress', 'Passed'])
            ->latest()
            ->first();

        if (!$assessment) {
            $assessment = CandidateAssessment::create([
                'name' => $caf->name,
                'email' => $caf->email,
                'position' => $caf->position,
                'photo_path' => $caf->photo_path,
                'cv_path' => $caf->cv_path,
                'cover_letter_path' => $caf->cover_letter_path,
                'test_type' => 'Technical Test', // Default
                'assessment_date' => date('Y-m-d'),
                'notes' => trim('Automatically created from CAF. Applicant Type: ' . ($caf->applicant_type ?: 'New Applicant') . '. ' . ($caf->internal_remarks ?: '')),
                'status' => 'Pending Assessment'
            ]);
        }

        if ($caf->email) {
            try {
                Mail::to($caf->email)->send(new AssessmentProceedingMail($caf->name, $caf->position));
            } catch (\Exception $e) {
                \Log::error("Failed to send assessment mail to {$caf->email}: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'assessment' => $assessment]);
    }

    public function storeAssessment(Request $request)
    {
        $assessment = CandidateAssessment::create([
            'name' => $request->name,
            'email' => $request->email,
            'position' => $request->position,
            'test_type' => $request->test,
            'assessment_date' => $request->date,
            'notes' => $request->notes,
            'status' => 'Pending Assessment'
        ]);

        // Update Candidate Application status if ID is provided
        if ($request->caf_id) {
            $caf = CandidateApplication::find($request->caf_id);
            if ($caf) {
                $caf->update(['status' => 'Assessment']);

                // Send email to the applicant
                if ($caf->email) {
                    try {
                        Mail::to($caf->email)->send(new AssessmentProceedingMail($caf->name, $caf->position));
                    } catch (\Exception $e) {
                        // Log error but don't fail the request
                        \Log::error("Failed to send assessment mail to {$caf->email}: " . $e->getMessage());
                    }
                }
            }
        }

        return response()->json(['success' => true, 'data' => $assessment]);
    }

    public function updateAssessmentStatus(Request $request, $id)
    {
        $assessment = CandidateAssessment::findOrFail($id);
        $assessment->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }

    public function latestAssessments()
    {
        $assessments = CandidateAssessment::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }

    public function storeInterview(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'position' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'interviewer' => 'required|string|max:255',
            'interview_date' => 'required',
            'duration' => 'nullable|integer|min:1',
            'meeting_link' => 'nullable|string|max:1000',
        ]);

        try {
            $interview = CandidateInterview::create([
                'name' => $request->name,
                'email' => $request->email,
                'position' => $request->position,
                'type' => $request->type,
                'round' => $request->type, // Backward compatibility with old column
                'interviewer' => $request->interviewer,
                'interview_date' => $request->interview_date,
                'duration' => $request->duration ?: 60,
                'meeting_link' => $request->meeting_link,
                'status' => 'Scheduled',
            ]);

            $matchedJpf = JobPosting::where('position', $interview->position)
                ->whereIn('status', ['Posted', 'Screening'])
                ->latest()
                ->first();

            if ($matchedJpf && $this->jpfApprovalsAllApproved($matchedJpf)) {
                $matchedJpf->update(['status' => 'Interviewing']);
            }

            try {
                Mail::to($interview->email)->send(new InterviewScheduleMail($interview));
            } catch (\Exception $mailException) {
                Log::error('Failed to send interview schedule email to ' . $interview->email . ': ' . $mailException->getMessage());

                return response()->json([
                    'success' => true,
                    'data' => $interview,
                    'warning' => 'Interview was scheduled, but the email could not be sent. Check mail settings/logs.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $interview,
                'message' => 'Interview scheduled and email sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateInterviewStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Scheduled,Completed,Passed,Failed,Cancelled'
        ]);

        $interview = CandidateInterview::findOrFail($id);
        $interview->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'data' => $interview
        ]);
    }

    public function deleteInterview($id)
    {
        CandidateInterview::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function deleteCAF($id)
    {
        CandidateApplication::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function deleteAssessment($id)
    {
        CandidateAssessment::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function deleteMRF($id)
    {
        ManpowerRequest::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function deleteJPF($id)
    {
        JobPosting::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function storeJobOffer(Request $request)
    {
        try {
            $interview = CandidateInterview::find($request->interviewId);

            if (!$interview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a Completed/Passed Interview before creating a Job Offer.'
                ], 422);
            }

            if (!in_array(strtolower((string) $interview->status), ['completed', 'passed'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interview must be Completed or Passed before creating a Job Offer.'
                ], 422);
            }

            $jpf = JobPosting::find($request->jobPostingId);

            if (!$jpf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a fully approved active JPF before creating a Job Offer.'
                ], 422);
            }

            $status = strtolower((string) $jpf->status);

            if (!$this->jpfCanProceedToJobOffer($jpf)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only fully approved active JPF records can be used for Job Offer.'
                ], 422);
            }

            $candidateEmail = $interview->email ?? $request->candidateEmail ?? null;

            $jobOffer = JobOffer::create([
                'interview_id'     => $interview->id,
                'job_posting_id'   => $jpf->id,

                'address_id'       => $request->orgAddressId ?: $jpf->address_id,
                'branch_id'        => $request->orgBranchId ?: $jpf->branch_id,
                'office_id'        => $request->orgOfficeId ?: $jpf->office_id,
                'department_id'    => $request->orgDepartmentId ?: $jpf->department_id,
                'division_id'      => $request->orgDivisionId ?: $jpf->division_id,
                'unit_id'          => $request->orgUnitId ?: $jpf->unit_id,
                'position_id'      => $request->orgPositionId ?: $jpf->position_id,
                'salary_grade_id'  => $request->salaryGradeId ?: $jpf->salary_grade_id,

                'candidate_email'  => $candidateEmail,
                'name'             => $request->name ?: $interview->name,
                'position'         => $request->position ?: $interview->position,
                'salary'           => $request->salary,
                'start_date'       => $request->startDate,
                'employment_type'  => $request->employmentType ?: $jpf->employment_type,
                'department'       => $request->department ?: ($jpf->department_unit ?? $jpf->department ?? null),
                'company_address'  => $request->companyAddress ?: $jpf->location,
                'benefits'         => $request->benefits,
                'accept_token'     => $this->generateJobOfferToken(),
                'status'           => $request->status ?: 'Draft',
            ]);

            if (!in_array($jpf->status, ['Filled', 'Closed', 'Cancelled'], true)) {
                $jpf->update(['status' => 'Offer Stage']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job Offer saved as Draft. Review the details and click Send Job Offer when ready.',
                'data' => $jobOffer
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving Job Offer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function resendJobOfferEmail($id)
    {
        try {
            $jobOffer = JobOffer::findOrFail($id);
            $jobOffer = $this->ensureJobOfferToken($jobOffer);

            if (!$jobOffer->candidate_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Candidate email was not found for this job offer.'
                ], 422);
            }

            $interview = $jobOffer->interview_id
                ? CandidateInterview::find($jobOffer->interview_id)
                : CandidateInterview::where('name', $jobOffer->name)
                ->where('position', $jobOffer->position)
                ->latest()
                ->first();

            $jpf = $jobOffer->job_posting_id
                ? JobPosting::find($jobOffer->job_posting_id)
                : null;

            Mail::to($jobOffer->candidate_email)->send(new JobOfferMail($jobOffer, $interview, $jpf));

            if (!in_array($jobOffer->status, ['Accepted', 'Declined'], true)) {
                $jobOffer->update(['status' => 'Sent']);
                $jobOffer->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Job Offer email resent successfully.',
                'data' => $jobOffer
            ]);
        } catch (\Exception $e) {
            Log::error('Error resending Job Offer email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function latestJobOffers()
    {
        return response()->json([
            'success' => true,
            'data' => JobOffer::latest()->get(),
        ]);
    }

    public function acceptJobOffer($token)
    {
        $jobOffer = JobOffer::where('accept_token', $token)->firstOrFail();

        if ($jobOffer->status === 'Accepted') {
            return view('careers.job-offer-response', [
                'jobOffer' => $jobOffer,
                'decision' => 'accepted',
                'title' => 'Job Offer Already Accepted',
                'message' => 'You have already accepted this job offer. The Human Capital team will continue with your onboarding process.',
            ]);
        }

        if ($jobOffer->status === 'Declined') {
            return view('careers.job-offer-response', [
                'jobOffer' => $jobOffer,
                'decision' => 'declined',
                'title' => 'Job Offer Already Declined',
                'message' => 'This job offer has already been declined. If this was a mistake, please contact the Human Capital team.',
            ]);
        }

        $pdsMessage = 'Please check your email for the PDS form link.';

        $jobOffer->update([
            'status' => 'Accepted',
            'accepted_at' => now(),
            'declined_at' => null,
        ]);

        $jobOffer->refresh();

        if ($jobOffer->job_posting_id) {
            $jpf = JobPosting::find($jobOffer->job_posting_id);
            if ($jpf) {
                $jpf->update(['status' => 'Filled']);
            }
        }

        if (!$jobOffer->pds_sent_at && $jobOffer->candidate_email) {
            try {
                Mail::to($jobOffer->candidate_email)->send(new PdsInvitationMail($jobOffer, route('careers.pds', ['token' => $jobOffer->accept_token])));

                $jobOffer->update([
                    'pds_sent_at' => now(),
                ]);

                $jobOffer->refresh();
            } catch (\Exception $e) {
                Log::error('Failed to send PDS invitation after job offer acceptance: ' . $e->getMessage());
                $pdsMessage = 'Your acceptance was recorded, but the PDS email failed to send. The Human Capital team will resend it.';
            }
        } elseif (!$jobOffer->candidate_email) {
            $pdsMessage = 'Your acceptance was recorded, but no candidate email was found for the PDS link.';
        } elseif ($jobOffer->pds_sent_at) {
            $pdsMessage = 'The PDS form link was already sent to your email.';
        }

        return view('careers.job-offer-response', [
            'jobOffer' => $jobOffer,
            'decision' => 'accepted',
            'title' => 'Job Offer Accepted',
            'message' => 'Thank you for accepting the job offer. ' . $pdsMessage,
        ]);
    }

    public function declineJobOffer($token)
    {
        $jobOffer = JobOffer::where('accept_token', $token)->firstOrFail();

        if ($jobOffer->status === 'Accepted') {
            return view('careers.job-offer-response', [
                'jobOffer' => $jobOffer,
                'decision' => 'accepted',
                'title' => 'Job Offer Already Accepted',
                'message' => 'You have already accepted this job offer, so it can no longer be declined from this link. Please contact the Human Capital team if this was a mistake.',
            ]);
        }

        if ($jobOffer->status === 'Declined') {
            return view('careers.job-offer-response', [
                'jobOffer' => $jobOffer,
                'decision' => 'declined',
                'title' => 'Job Offer Already Declined',
                'message' => 'You have already declined this job offer. If this was a mistake, please contact the Human Capital team.',
            ]);
        }

        $jobOffer->update([
            'status' => 'Declined',
            'declined_at' => now(),
            'accepted_at' => null,
        ]);

        $jobOffer->refresh();

        return view('careers.job-offer-response', [
            'jobOffer' => $jobOffer,
            'decision' => 'declined',
            'title' => 'Job Offer Declined',
            'message' => 'Your response has been recorded. Thank you for informing John Kelly & Company.',
        ]);
    }

    private function ensureJobOfferToken(JobOffer $jobOffer): JobOffer
    {
        if (!$jobOffer->accept_token) {
            $jobOffer->update([
                'accept_token' => $this->generateJobOfferToken(),
            ]);

            $jobOffer->refresh();
        }

        return $jobOffer;
    }

    private function generateJobOfferToken(): string
    {
        do {
            $token = Str::random(64);
        } while (JobOffer::where('accept_token', $token)->exists());

        return $token;
    }

    public function deleteJobOffer($id)
    {
        JobOffer::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function sendAssessmentTest(Request $request, $id)
    {
        $assessment = CandidateAssessment::findOrFail($id);

        // Update the test type if provided
        if ($request->has('test_type')) {
            $assessment->update(['test_type' => $request->test_type]);
        }

        // Update the email if provided (in case it was missing or corrected in the UI)
        if ($request->has('email')) {
            $assessment->update(['email' => $request->email]);
        }

        // Generate internal tracking URL
        $testUrl = route('recruitment.assessment.start', ['uuid' => $assessment->uuid]);

        if ($assessment->email) {
            try {
                Mail::to($assessment->email)->send(new AssessmentTestMail($assessment->name, $assessment->test_type, $testUrl));

                // Status remains "Pending Assessment" until they click the link

                return response()->json(['success' => true, 'message' => 'Test invitation sent', 'assessment' => $assessment]);
            } catch (\Exception $e) {
                \Log::error("Failed to send assessment test to {$assessment->email}: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Failed to send email'], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'Candidate email not found'], 400);
    }

    public function startAssessment($uuid)
    {
        $assessment = CandidateAssessment::where('uuid', $uuid)->firstOrFail();

        if (in_array($assessment->status, ['Passed', 'Failed'], true)) {
            return view('careers.assessment-test', [
                'assessment' => $assessment,
                'questions' => [],
                'submitted' => true,
                'score' => $assessment->score,
                'status' => $assessment->status,
                'message' => 'You have already submitted this assessment.'
            ]);
        }

        if ($assessment->status === 'Pending Assessment') {
            $assessment->update(['status' => 'In Progress']);
        }

        $questions = $this->getAssessmentQuestions($assessment->test_type);

        return view('careers.assessment-test', [
            'assessment' => $assessment,
            'questions' => $questions,
            'submitted' => false,
            'score' => null,
            'status' => null,
            'message' => null,
        ]);
    }

    public function submitAssessmentTest(Request $request, $uuid)
    {
        $assessment = CandidateAssessment::where('uuid', $uuid)->firstOrFail();

        if (in_array($assessment->status, ['Passed', 'Failed'], true)) {
            return view('careers.assessment-test', [
                'assessment' => $assessment,
                'questions' => [],
                'submitted' => true,
                'score' => $assessment->score,
                'status' => $assessment->status,
                'message' => 'You have already submitted this assessment.'
            ]);
        }

        $questions = $this->getAssessmentQuestions($assessment->test_type);

        $request->validate([
            'answers' => 'required|array',
        ]);

        $answers = $request->input('answers', []);
        $correct = 0;

        foreach ($questions as $index => $question) {
            $givenAnswer = $answers[$index] ?? null;

            if ($givenAnswer !== null && (string) $givenAnswer === (string) $question['answer']) {
                $correct++;
            }
        }

        $total = count($questions);
        $score = $total > 0 ? round(($correct / $total) * 100) : 0;
        $status = $score >= 75 ? 'Passed' : 'Failed';

        $assessment->update([
            'score' => $score . '%',
            'status' => $status,
            'assessment_date' => now()->toDateString(),
        ]);

        return view('careers.assessment-test', [
            'assessment' => $assessment,
            'questions' => [],
            'submitted' => true,
            'score' => $score . '%',
            'status' => $status,
            'message' => 'Assessment submitted successfully.',
        ]);
    }

    private function getAssessmentQuestions($testType): array
    {
        $typeName = trim((string) $testType);
        $typeLower = strtolower($typeName);

        $assessmentType = null;

        if ($typeName !== '') {
            $assessmentType = AssessmentType::where('name', $typeName)
                ->orWhere('slug', Str::slug($typeName))
                ->first();
        }

        if (!$assessmentType) {
            if (str_contains($typeLower, 'personality')) {
                $assessmentType = AssessmentType::where('name', 'Personality Test')
                    ->orWhere('slug', 'personality-test')
                    ->first();
            } elseif (str_contains($typeLower, 'aptitude') || str_contains($typeLower, 'amplitude')) {
                $assessmentType = AssessmentType::where('name', 'Aptitude Test')
                    ->orWhere('slug', 'aptitude-test')
                    ->first();
            } else {
                $assessmentType = AssessmentType::where('name', 'Technical Test')
                    ->orWhere('slug', 'technical-test')
                    ->first();
            }
        }

        if (!$assessmentType || !$assessmentType->is_active) {
            return [];
        }

        return AssessmentQuestion::where('assessment_type_id', $assessmentType->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($question) {
                return [
                    'question' => $question->question,
                    'choices' => [
                        $question->choice_a,
                        $question->choice_b,
                        $question->choice_c,
                        $question->choice_d,
                    ],
                    'answer' => (int) $question->correct_answer,
                ];
            })
            ->toArray();
    }

    public function updateAssessmentResult(Request $request, $id)
    {
        $assessment = CandidateAssessment::findOrFail($id);
        $request->validate([
            'score' => 'required|numeric|min:0|max:100'
        ]);

        $score = $request->score;
        $status = ($score >= 75) ? 'Passed' : 'Failed';

        $assessment->update([
            'score' => $score . '%',
            'status' => $status,
            'assessment_date' => date('Y-m-d') // Record the date of completion
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assessment result recorded.',
            'assessment' => $assessment
        ]);
    }
}
