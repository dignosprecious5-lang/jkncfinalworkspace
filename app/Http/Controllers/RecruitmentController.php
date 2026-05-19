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
use Illuminate\Support\Str;
use App\Mail\AssessmentProceedingMail;
use App\Mail\AssessmentTestMail;
use App\Mail\InterviewScheduleMail;
use App\Mail\JobOfferMail;
use App\Mail\PdsInvitationMail;
use App\Mail\ChecklistSubmissionMail;
use App\Models\Training;

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
            'payrollLevels'
        ));
    }

    public function showPublicApplicationForm()
{
    $jobPostings = JobPosting::whereIn('status', ['Posted', 'Open'])
        ->orderBy('position')
        ->get();

    if ($jobPostings->isEmpty()) {
        $jobPostings = JobPosting::orderBy('position')->get();
    }

    return view('careers.apply', compact('jobPostings'));
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


    public function storeMRF(Request $request)
    {
        $data = [
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
            'duties'             => $request->duties,
            'nature_of_request'  => $request->natureOfRequest,
            'age_range'          => $request->ageRange,
            'civil_status'       => $request->civilStatus,
            'gender'             => $request->gender,
            'headcount'          => $request->headcount,
            'education'          => $request->education,
            'qualifications'     => $request->qualifications,
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
        
        if (!isset($data['request_id'])) {
            $year = date('Y');
            $count = ManpowerRequest::whereYear('created_at', $year)->count() + 1;
            $data['request_id'] = "MRF-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $mrf = ManpowerRequest::create($data);
        return response()->json(['success' => true, 'data' => $mrf]);
    }

    public function updateMRF(Request $request, $id)
    {
        $mrf = ManpowerRequest::findOrFail($id);
        $data = [
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
            'duties'             => $request->duties,
            'nature_of_request'  => $request->natureOfRequest,
            'age_range'          => $request->ageRange,
            'civil_status'       => $request->civilStatus,
            'gender'             => $request->gender,
            'headcount'          => $request->headcount,
            'education'          => $request->education,
            'qualifications'     => $request->qualifications,
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

    public function storeJPF(Request $request)
    {
        $mrf = ManpowerRequest::find($request->mrfId);

        if (!$mrf) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an approved MRF before creating a JPF.'
            ], 422);
        }

        if (strtolower((string) $mrf->request_status) !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved MRF records can be used to create a JPF.'
            ], 422);
        }

        $data = [
            'mrf_id'                 => $request->mrfId,
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
            'status'                 => $request->status ?: 'Draft',

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
            'preferred_qualifications'=> $request->preferredQualifications,
            'duties_responsibilities'=> $request->duties,
            'recruitment_channels'   => $request->channels,
            'screening_flow'         => $request->screeningFlow,
            'date_needed'            => $request->dateNeeded,
            'posting_start_date'     => $request->postingStartDate,
            'target_hire_date'       => $request->targetHireDate,
            'human_capital_approval' => $request->humanCapitalApproval,
            'hiring_manager_approval'=> $request->hiringManagerApproval,
            'finance_approval'       => $request->financeApproval,
            'president_approval'     => $request->presidentApproval,
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
        $mrf = ManpowerRequest::find($request->mrfId);

        if (!$mrf) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an approved MRF before updating this JPF.'
            ], 422);
        }

        if (strtolower((string) $mrf->request_status) !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved MRF records can be linked to a JPF.'
            ], 422);
        }

        $data = [
            'mrf_id'                 => $request->mrfId,
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
            'status'                 => $request->status ?: $jpf->status,

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
            'applicable_region'      => $request->applicableRegion,
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
            'preferred_qualifications'=> $request->preferredQualifications,
            'duties_responsibilities'=> $request->duties,
            'recruitment_channels'   => $request->channels,
            'screening_flow'         => $request->screeningFlow,
            'date_needed'            => $request->dateNeeded,
            'posting_start_date'     => $request->postingStartDate,
            'target_hire_date'       => $request->targetHireDate,
            'human_capital_approval' => $request->humanCapitalApproval,
            'hiring_manager_approval'=> $request->hiringManagerApproval,
            'finance_approval'       => $request->financeApproval,
            'president_approval'     => $request->presidentApproval,
        ];
        $jpf->update($data);
        return response()->json(['success' => true, 'data' => $jpf]);
    }

    public function storeCAF(Request $request)
    {
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

        $caf = CandidateApplication::create([
            'name' => $request->fullName,
            'position' => $request->positionApplied,
            'email' => $request->email,
            'phone' => $request->phone,
            'photo_path' => $photoPath,
            'cv_path' => $cvPath,
            'cover_letter_path' => $coverLetterPath,
            'cover_letter' => $request->coverLetter,
            'status' => 'Pending',
            'applied_date' => date('Y-m-d')
        ]);

        return response()->json(['success' => true, 'data' => $caf]);
    }

    public function updateCAF(Request $request, $id)
    {
        $caf = CandidateApplication::findOrFail($id);
        $data = [
            'name' => $request->fullName,
            'position' => $request->positionApplied,
            'email' => $request->email,
            'phone' => $request->phone,
            'cover_letter' => $request->coverLetter,
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
        $assessment = CandidateAssessment::where('name', $caf->name)
            ->where('position', $caf->position)
            ->where('status', 'Pending Assessment')
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
                'notes' => 'Automatically created from CAF',
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
                'message' => 'Please select a JPF with status Posted/Open before creating a Job Offer.'
            ], 422);
        }

        $status = strtolower((string) $jpf->status);

        if (!in_array($status, ['posted', 'open'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only JPF records with status Posted/Open can be used for Job Offer.'
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
            'status'           => 'Pending',
        ]);

        if ($candidateEmail) {
            try {
                Mail::to($candidateEmail)->send(new JobOfferMail($jobOffer, $interview, $jpf));

                $jobOffer->update([
                    'status' => 'Sent',
                ]);

                $jobOffer->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'Job Offer created and emailed successfully.',
                    'data' => $jobOffer
                ]);
            } catch (\Exception $mailError) {
                Log::error("Failed to send job offer email to {$candidateEmail}: " . $mailError->getMessage());

                return response()->json([
                    'success' => true,
                    'warning' => 'Job Offer was saved, but the email failed to send: ' . $mailError->getMessage(),
                    'data' => $jobOffer
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'warning' => 'Job Offer was saved, but no candidate email was found.',
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
        $type = strtolower((string) $testType);

        if (str_contains($type, 'personality')) {
            return [
                [
                    'question' => 'How do you usually handle urgent tasks?',
                    'choices' => [
                        'I ignore them until later.',
                        'I prioritize them and communicate with the team.',
                        'I wait for others to decide.',
                        'I stop all other work permanently.'
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'Which behavior best shows professionalism?',
                    'choices' => [
                        'Arriving late without notice.',
                        'Keeping commitments and communicating clearly.',
                        'Avoiding feedback.',
                        'Not following instructions.'
                    ],
                    'answer' => 1,
                ],
                [
                    'question' => 'When receiving feedback, what is the best response?',
                    'choices' => [
                        'Listen, clarify, and improve.',
                        'Ignore the feedback.',
                        'Argue immediately.',
                        'Blame someone else.'
                    ],
                    'answer' => 0,
                ],
                [
                    'question' => 'Which trait is important in a workplace?',
                    'choices' => [
                        'Accountability',
                        'Dishonesty',
                        'Carelessness',
                        'Avoiding teamwork'
                    ],
                    'answer' => 0,
                ],
                [
                    'question' => 'What should you do if you do not understand a task?',
                    'choices' => [
                        'Pretend you understand.',
                        'Ask for clarification.',
                        'Submit random work.',
                        'Delay without informing anyone.'
                    ],
                    'answer' => 1,
                ],
            ];
        }

        if (str_contains($type, 'aptitude') || str_contains($type, 'amplitude')) {
            return [
                [
                    'question' => 'What is 15% of 200?',
                    'choices' => ['15', '20', '30', '45'],
                    'answer' => 2,
                ],
                [
                    'question' => 'If A is greater than B, and B is greater than C, which is true?',
                    'choices' => ['C is greatest', 'A is greatest', 'B is greatest', 'All are equal'],
                    'answer' => 1,
                ],
                [
                    'question' => 'Complete the pattern: 2, 4, 8, 16, ___',
                    'choices' => ['18', '24', '30', '32'],
                    'answer' => 3,
                ],
                [
                    'question' => 'A task starts at 9:15 AM and ends at 10:45 AM. How long did it take?',
                    'choices' => ['1 hour', '1 hour 15 minutes', '1 hour 30 minutes', '2 hours'],
                    'answer' => 2,
                ],
                [
                    'question' => 'Which word is closest in meaning to "reliable"?',
                    'choices' => ['Dependable', 'Careless', 'Late', 'Weak'],
                    'answer' => 0,
                ],
            ];
        }

        return [
            [
                'question' => 'What does HTML stand for?',
                'choices' => [
                    'HyperText Markup Language',
                    'HighText Machine Language',
                    'HyperTool Multi Language',
                    'Home Tool Markup Language'
                ],
                'answer' => 0,
            ],
            [
                'question' => 'Which SQL command is used to retrieve data?',
                'choices' => ['INSERT', 'SELECT', 'UPDATE', 'DELETE'],
                'answer' => 1,
            ],
            [
                'question' => 'In Laravel, which folder usually contains controllers?',
                'choices' => [
                    'resources/views',
                    'database/migrations',
                    'app/Http/Controllers',
                    'public/assets'
                ],
                'answer' => 2,
            ],
            [
                'question' => 'Which HTTP method is commonly used to submit a form that creates a new record?',
                'choices' => ['GET', 'POST', 'PUT', 'DELETE'],
                'answer' => 1,
            ],
            [
                'question' => 'What is the purpose of validation in a system?',
                'choices' => [
                    'To check and control user input',
                    'To delete all records',
                    'To slow down the system',
                    'To remove authentication'
                ],
                'answer' => 0,
            ],
        ];
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
