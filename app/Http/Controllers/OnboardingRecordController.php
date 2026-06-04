<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\CandidateApplication;
use App\Models\Department;
use App\Models\Division;
use App\Models\Office;
use App\Models\OnboardingChecklist;
use App\Models\OnboardingEmployeeRegistration;
use App\Models\PersonalDataSheet;
use App\Models\TrainingAssignment;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OnboardingRecordController extends Controller
{
    private array $requiredDocuments = [
        'valid_id' => 'Valid ID (Government-issued)',
        'birth_certificate' => 'Birth Certificate',
        'sss' => 'SSS Number / E-1 / Static Info',
        'philhealth' => 'PhilHealth MDR',
        'pagibig' => 'Pag-IBIG MDF / MID',
        'tin_bir' => 'TIN / BIR Form',
        'nbi_clearance' => 'NBI Clearance',
        'medical_certificate' => 'Medical Certificate',
        'tor_diploma' => 'Transcript of Records / Diploma',
        'signed_job_offer' => 'Signed Job Offer',
        'two_by_two_picture' => '2x2 Picture',
    ];

    public function records(): JsonResponse
    {
        return response()->json([
            'pdsApplicants' => PersonalDataSheet::with('jobOffer')
                ->latest()
                ->get()
                ->map(fn ($item) => $this->formatPdsApplicant($item)),

            'checklists' => OnboardingChecklist::latest()
                ->get()
                ->map(fn ($item) => $this->formatChecklist($item)),

            'employees' => OnboardingEmployeeRegistration::latest()
                ->get()
                ->map(fn ($item) => $this->formatEmployee($item)),

            'trainings' => TrainingAssignment::with(['employee', 'training'])
                ->where('assignment_type', 'onboarding')
                ->latest()
                ->get()
                ->map(fn ($item) => $this->formatTraining($item)),
        ]);
    }

    public function showPublicChecklistUpload($token)
    {
        $checklist = OnboardingChecklist::where('upload_token', $token)->firstOrFail();

        return view('careers.checklist-upload', [
            'checklist' => $checklist,
            'documents' => $this->requiredDocuments,
        ]);
    }

    public function submitPublicChecklistUpload(Request $request, $token)
    {
        $checklist = OnboardingChecklist::where('upload_token', $token)->firstOrFail();

        $request->validate([
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $documents = [];
        $existing = collect($checklist->checked_documents ?? [])->keyBy('key');

        foreach ($this->requiredDocuments as $key => $label) {
            $old = $existing->get($key, []);
            $file = $request->file("documents.$key");

            if ($file) {
                if (!empty($old['file_path'])) {
                    Storage::disk('public')->delete($old['file_path']);
                }

                $filePath = $file->store('onboarding/checklists/'.$checklist->id, 'public');

                $documents[] = [
                    'key' => $key,
                    'label' => $label,
                    'status' => 'Submitted',
                    'remarks' => null,
                    'file_path' => $filePath,
                    'file_url' => Storage::url($filePath),
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toDateTimeString(),
                    'reviewed_at' => null,
                ];
            } else {
                $documents[] = [
                    'key' => $key,
                    'label' => $label,
                    'status' => $old['status'] ?? 'Missing',
                    'remarks' => $old['remarks'] ?? null,
                    'file_path' => $old['file_path'] ?? null,
                    'file_url' => $old['file_url'] ?? null,
                    'original_name' => $old['original_name'] ?? null,
                    'uploaded_at' => $old['uploaded_at'] ?? null,
                    'reviewed_at' => $old['reviewed_at'] ?? null,
                ];
            }
        }

        $docsSubmitted = collect($documents)->whereNotNull('file_path')->count();
        $docsApproved = collect($documents)->where('status', 'Approved')->count();

        $checklist->update([
            'checked_documents' => $documents,
            'docs_submitted' => $docsSubmitted,
            'docs_approved' => $docsApproved,
            'total_docs' => count($this->requiredDocuments),
            'status' => $docsSubmitted > 0 ? 'For Review' : 'Pending Documents',
            'submitted_at' => now(),
        ]);

        if ($checklist->personalDataSheet) {
            $checklist->personalDataSheet->update(['status' => 'Checklist Submitted']);
        }

        return redirect()
            ->route('careers.checklist.show', $checklist->upload_token)
            ->with('success', 'Your documents were submitted successfully. Human Capital will review them.');
    }

    public function storeChecklist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pdsId' => ['required', 'exists:personal_data_sheets,id'],
            'employeeName' => ['required', 'string', 'max:255'],
            'employeeEmail' => ['nullable', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $pds = PersonalDataSheet::findOrFail($validated['pdsId']);

        $checklist = OnboardingChecklist::firstOrNew([
            'personal_data_sheet_id' => $pds->id,
        ]);

        $documents = [];

        foreach ($this->requiredDocuments as $key => $label) {
            $file = $request->file("documents.$key");
            $filePath = null;
            $originalName = null;

            if ($file) {
                $filePath = $file->store('onboarding/checklists/'.$pds->id, 'public');
                $originalName = $file->getClientOriginalName();
            }

            $documents[] = [
                'key' => $key,
                'label' => $label,
                'status' => $filePath ? 'Submitted' : 'Missing',
                'remarks' => null,
                'file_path' => $filePath,
                'file_url' => $filePath ? Storage::url($filePath) : null,
                'original_name' => $originalName,
                'uploaded_at' => $filePath ? now()->toDateTimeString() : null,
                'reviewed_at' => null,
            ];
        }

        $docsSubmitted = collect($documents)->whereNotNull('file_path')->count();

        $checklist->fill([
            'employee_name' => $validated['employeeName'],
            'employee_email' => $validated['employeeEmail'] ?? $pds->email,
            'position' => $validated['position'] ?? $pds->position,
            'checked_documents' => $documents,
            'docs_submitted' => $docsSubmitted,
            'docs_approved' => 0,
            'total_docs' => count($this->requiredDocuments),
            'status' => $docsSubmitted > 0 ? 'For Review' : 'Pending Documents',
            'upload_token' => $checklist->upload_token ?: (string) Str::uuid(),
            'created_by' => Auth::id(),
        ]);

        $checklist->save();

        $pds->update(['status' => 'Checklist Submitted']);

        return response()->json([
            'message' => 'Checklist saved successfully.',
            'record' => $this->formatChecklist($checklist->fresh()),
        ]);
    }

    public function reviewChecklistDocument(Request $request, OnboardingChecklist $checklist): JsonResponse
    {
        $validated = $request->validate([
            'documentKey' => ['required', 'string', Rule::in(array_keys($this->requiredDocuments))],
            'status' => ['required', Rule::in(['Approved', 'Needs Re-upload'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $documents = collect($checklist->checked_documents ?? [])->map(function ($document) use ($validated) {
            if (($document['key'] ?? null) === $validated['documentKey']) {
                $document['status'] = $validated['status'];
                $document['remarks'] = $validated['remarks'] ?? null;
                $document['reviewed_at'] = now()->toDateTimeString();
            }

            return $document;
        })->values()->all();

        $docsSubmitted = collect($documents)->whereNotNull('file_path')->count();
        $docsApproved = collect($documents)->where('status', 'Approved')->count();
        $hasReupload = collect($documents)->contains(fn ($document) => ($document['status'] ?? null) === 'Needs Re-upload');

        $status = 'For Review';

        if ($docsApproved === count($this->requiredDocuments)) {
            $status = 'Completed';
        } elseif ($hasReupload) {
            $status = 'Needs Re-upload';
        } elseif ($docsSubmitted === 0) {
            $status = 'Pending Documents';
        }

        $checklist->update([
            'checked_documents' => $documents,
            'docs_submitted' => $docsSubmitted,
            'docs_approved' => $docsApproved,
            'status' => $status,
        ]);

        if ($status === 'Completed' && $checklist->personalDataSheet) {
            $checklist->personalDataSheet->update(['status' => 'Checklist Completed']);
        }

        return response()->json([
            'message' => 'Document review updated successfully.',
            'record' => $this->formatChecklist($checklist->fresh()),
        ]);
    }

    public function destroyChecklist(OnboardingChecklist $checklist): JsonResponse
    {
        $checklist->delete();

        return response()->json([
            'message' => 'Checklist deleted successfully.',
        ]);
    }

    public function storeEmployee(Request $request): JsonResponse
{
    $validated = $request->validate([
        'checklistId' => ['required', 'exists:onboarding_checklists,id'],
        'fullName' => ['required', 'string', 'max:255'],
        'employeeId' => [
            'required',
            'regex:/^\d{5}$/',
            'not_regex:/[46]/',
            'not_regex:/(13|31)/',
            'unique:onboarding_employee_registrations,employee_id',
            'unique:employees,employee_code',
        ],
        'department' => ['nullable', 'string', 'max:255'],
        'position' => ['required', 'string', 'max:255'],
        'payrollType' => ['required', Rule::in(['Monthly Paid', 'Daily Paid'])],
        'basicSalary' => ['required', 'numeric', 'min:0'],
        'startDate' => ['nullable', 'date'],
        'workEmail' => ['required', 'email', 'max:255'],
        'manager' => ['nullable', 'string', 'max:255'],
    ]);

    $checklist = OnboardingChecklist::with('personalDataSheet.jobOffer')->findOrFail($validated['checklistId']);

    if ($checklist->status !== 'Completed') {
        return response()->json([
            'message' => 'Employee Registration is only allowed when the checklist status is Completed.',
        ], 422);
    }

    $existingRegistration = OnboardingEmployeeRegistration::where('onboarding_checklist_id', $checklist->id)->first();

    if ($existingRegistration) {
        return response()->json([
            'message' => 'This applicant is already registered as an employee.',
        ], 422);
    }

    $pds = $checklist->personalDataSheet;
    $jobOffer = $pds?->jobOffer;
    $pdsData = $pds && is_array($pds->data) ? $pds->data : [];
    $offerDetails = is_array($jobOffer?->offer_details) ? $jobOffer->offer_details : [];
    $candidateApplication = $this->findCandidateApplication($jobOffer, $pds);
    $applicationData = is_array($candidateApplication?->application_data) ? $candidateApplication->application_data : [];

    $firstName = trim($pdsData['firstName'] ?? '');
    $lastName = trim($pdsData['surname'] ?? '');
    $middleName = trim($pdsData['middleName'] ?? '');

    if (!$firstName || !$lastName) {
        [$firstName, $lastName] = $this->splitEmployeeName($validated['fullName']);
    }

    $address = $this->buildPdsAddress($pdsData);
    $permanentAddress = $this->buildPdsAddress($pdsData, 'perm');

    // CAF/PDS email remains the applicant personal/contact email.
    $personalEmail = $checklist->employee_email
        ?: ($pds->email ?? ($jobOffer?->candidate_email ?? null));

    // Work email is the official company email and will be used for login account creation.
    $workEmail = $validated['workEmail'];

    if (Employee::where('email', $workEmail)->orWhere('work_email', $workEmail)->exists()) {
        return response()->json([
            'message' => 'This work email is already used in Employee Profile.',
        ], 422);
    }

    // New applicants normally have Job Offer data.
    // Existing employees/personnel do not, so use HR/Admin manual input from Employee Registration.
    $basicSalary = $jobOffer
        ? $this->parseMoneyAmount($jobOffer?->salary)
        : (float) $validated['basicSalary'];

    $payrollType = $jobOffer
        ? $this->inferPayrollType($jobOffer?->employment_type)
        : $validated['payrollType'];

    $position = $jobOffer?->position ?: $validated['position'];

    $hourlyRate = $this->computeEmployeeHourlyRate($basicSalary, $payrollType);
    $profilePhoto = $this->resolveEmployeeProfilePhoto($candidateApplication, $checklist);
    $attachments = $this->buildEmployeeAttachments($checklist, $candidateApplication, $jobOffer);
    $organization = $this->completeOrgAssignment([
        'branch_id' => $this->resolveOrgId($jobOffer?->branch_id, $offerDetails, $applicationData, ['branch_id', 'branchId', 'orgBranchId']),
        'office_id' => $this->resolveOrgId($jobOffer?->office_id, $offerDetails, $applicationData, ['office_id', 'officeId', 'orgOfficeId']),
        'department_id' => $this->resolveOrgId($jobOffer?->department_id, $offerDetails, $applicationData, ['department_id', 'departmentId', 'orgDepartmentId']),
        'division_id' => $this->resolveOrgId($jobOffer?->division_id, $offerDetails, $applicationData, ['division_id', 'divisionId', 'orgDivisionId']),
        'unit_id' => $this->resolveOrgId($jobOffer?->unit_id, $offerDetails, $applicationData, ['unit_id', 'unitId', 'orgUnitId']),
    ]);

    $employeeProfile = Employee::create([
        'employee_code' => $validated['employeeId'],
        'first_name' => $firstName,
        'middle_name' => $middleName,
        'last_name' => $lastName,
        'suffix' => $pdsData['nameExt'] ?? null,
        'gender' => $pdsData['sex'] ?? null,
        'civil_status' => $pdsData['civilStatus'] ?? null,
        'nationality' => $pdsData['citizenship'] ?? ($applicationData['nationality'] ?? null),
        'religion' => $applicationData['religion'] ?? null,
        'date_of_birth' => $pdsData['dob'] ?? ($applicationData['dateOfBirth'] ?? null),
        'place_of_birth' => $pdsData['pob'] ?? null,
        'blood_type' => $pdsData['bloodType'] ?? null,
        'height' => $pdsData['height'] ?? null,
        'weight' => $pdsData['weight'] ?? null,
        'is_pwd' => $this->yesNoBoolean($applicationData['pwd'] ?? null),
        'is_solo_parent' => $this->yesNoBoolean($applicationData['soloParent'] ?? null),
        'is_senior_citizen' => $this->yesNoBoolean($applicationData['seniorCitizen'] ?? null),
        'address' => $address ?: $jobOffer?->company_address,
        'current_address' => $address ?: ($applicationData['currentAddress'] ?? null),
        'permanent_address' => $permanentAddress ?: ($applicationData['permanentAddress'] ?? null),
        'phone_number' => $pds->phone ?? ($pdsData['mobileNo'] ?? $pdsData['phone'] ?? null),
        'alternate_phone_number' => $pdsData['telNo'] ?? null,
        'emergency_contact_name' => $applicationData['emergencyName'] ?? null,
        'emergency_contact_relationship' => $applicationData['emergencyRelationship'] ?? null,
        'emergency_contact_number' => $applicationData['emergencyNumber'] ?? null,
        'emergency_contact_address' => $applicationData['emergencyAddress'] ?? null,

        // Keep legacy email as work email so old modules continue working.
        'email' => $workEmail,
        'personal_email' => $personalEmail,
        'work_email' => $workEmail,
        'company_email' => $workEmail,
        'profile_photo' => $profilePhoto,
        'photo_metadata' => $profilePhoto ? [
            'source' => 'Recruitment / Pre-employment',
            'transferred_at' => now()->toDateTimeString(),
            'candidate_application_id' => $candidateApplication?->id,
        ] : [],

        // Organizational assignment from Job Offer
        'office_id' => $organization['office_id'],
        'branch_id' => $organization['branch_id'],
        'department_id' => $organization['department_id'],
        'division_id' => $organization['division_id'],
        'unit_id' => $organization['unit_id'],

        // Position and payroll from Job Offer for new applicants, or HR manual input for existing employees.
        'applicant_id' => $candidateApplication?->applicant_id ?: ($offerDetails['applicantId'] ?? null),
        'job_id' => $offerDetails['jobId'] ?? null,
        'position' => $position,
        'job_level_rank' => $offerDetails['jobLevelRank'] ?? null,
        'employment_type' => $jobOffer?->employment_type ?: ($offerDetails['employmentType'] ?? null),
        'employment_status' => 'Active',
        'work_arrangement' => $offerDetails['workArrangement'] ?? ($applicationData['preferredWorkArrangement'] ?? null),
        'work_location' => $jobOffer?->company_address ?: ($offerDetails['companyAddress'] ?? null),
        'date_hired' => $validated['startDate'] ?? $jobOffer?->start_date,
        'start_date' => $validated['startDate'] ?? $jobOffer?->start_date,
        'immediate_supervisor' => $offerDetails['immediateSupervisor'] ?? null,
        'reporting_to' => $validated['manager'] ?? ($offerDetails['reportingTo'] ?? null),
        'recruitment_status' => 'Job Offer Accepted',
        'onboarding_status' => 'Employee Registered',
        'payroll_type' => $payrollType,
        'salary_grade' => $offerDetails['salaryGrade'] ?? null,
        'basic_salary' => $basicSalary,
        'hourly_rate' => $hourlyRate,
        'benefits_checklist' => $this->benefitsFromValue($jobOffer?->benefits ?: ($offerDetails['benefits'] ?? null)),
        'tin_number' => $pdsData['tin'] ?? null,
        'sss_number' => $pdsData['sss'] ?? null,
        'philhealth_number' => $pdsData['philhealth'] ?? null,
        'pagibig_number' => $pdsData['pagibig'] ?? null,
        'educational_background' => $this->buildEmployeeEducation($pdsData, $applicationData),
        'employment_history' => $this->decodeArray($applicationData['employmentHistory'] ?? []),
        'certifications_trainings' => $this->buildEmployeeCertifications($pdsData, $applicationData),
        'skills_competencies' => $this->buildEmployeeSkills($applicationData),
        'employee_attachments' => $attachments,
        'activity_audit' => [[
            'timestamp' => now()->toDateTimeString(),
            'field' => 'Employee Profile Created',
            'previous_value' => null,
            'new_value' => 'Created from recruitment and onboarding records',
            'updated_by' => optional(Auth::user())->name,
        ]],
        'salary_employment_history' => [[
            'timestamp' => now()->toDateTimeString(),
            'field' => 'Initial Salary',
            'previous_value' => null,
            'new_value' => $basicSalary,
            'updated_by' => optional(Auth::user())->name,
        ]],
    ]);

    $employee = OnboardingEmployeeRegistration::create([
        'onboarding_checklist_id' => $checklist->id,
        'employee_profile_id' => $employeeProfile->id,
        'full_name' => $validated['fullName'],
        'employee_id' => $validated['employeeId'],
        'department' => $jobOffer?->department ?: ($validated['department'] ?? null),
        'start_date' => $validated['startDate'] ?? $jobOffer?->start_date,
        'work_email' => $workEmail,
        'manager' => $validated['manager'] ?? null,
        'created_by' => Auth::id(),
    ]);

    if ($pds) {
        $pds->update(['status' => 'Employee Registered']);
    }

    return response()->json([
        'message' => 'Employee registration saved successfully and reflected in Employee Profile. Create the login account from Admin Panel → Users using this work email.',
        'record' => $this->formatEmployee($employee->fresh()),
    ]);
}

    public function destroyEmployee(OnboardingEmployeeRegistration $employee): JsonResponse
    {
        $employee->delete();

        return response()->json([
            'message' => 'Employee registration deleted successfully.',
        ]);
    }

    public function storeTraining(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employeeId' => ['required', 'integer', 'exists:employees,id'],
            'trainingId' => ['required', 'integer', 'exists:trainings,id'],
            'startDate' => ['nullable', 'date'],
            'dueDate' => ['nullable', 'date'],
            'trainer' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['Pending', 'Scheduled', 'In Progress', 'Completed', 'Failed', 'Cancelled'])],
        ]);

        $training = TrainingAssignment::updateOrCreate(
            [
                'employee_id' => $validated['employeeId'],
                'training_id' => $validated['trainingId'],
                'assignment_type' => 'onboarding',
            ],
            [
                'start_date' => $validated['startDate'] ?? null,
                'due_date' => $validated['dueDate'] ?? null,
                'trainer' => $validated['trainer'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'Pending',
                'assigned_by' => Auth::id(),
            ]
        );

        return response()->json([
            'message' => 'Training assignment saved successfully.',
            'record' => $this->formatTraining($training->fresh(['employee', 'training'])),
        ]);
    }

    public function updateTrainingStatus(Request $request, TrainingAssignment $training): JsonResponse
    {
        if ($training->assignment_type !== 'onboarding') {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Pending', 'Scheduled', 'In Progress', 'Completed', 'Failed', 'Cancelled'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $training->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? $training->remarks,
            'completed_at' => $validated['status'] === 'Completed' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Training assignment status updated.',
            'record' => $this->formatTraining($training->fresh(['employee', 'training'])),
        ]);
    }

    public function destroyTraining(TrainingAssignment $training): JsonResponse
    {
        if ($training->assignment_type !== 'onboarding') {
            abort(404);
        }

        $training->delete();

        return response()->json([
            'message' => 'Training assignment deleted successfully.',
        ]);
    }

    private function formatPdsApplicant(PersonalDataSheet $item): array
    {
        $data = is_array($item->data) ? $item->data : (json_decode($item->data, true) ?: []);

        $jobOffer = $item->jobOffer;
        $applicantType = $data['applicant_type']
            ?? $data['applicantType']
            ?? $data['type']
            ?? null;

        if (!$applicantType && $jobOffer) {
            $applicantType = 'New Applicant / Recruitment';
        }

        return [
            'id' => $item->id,
            'db_id' => $item->id,
            'jobOfferId' => $item->job_offer_id,
            'fullName' => $item->full_name ?: ($data['fullName'] ?? ''),
            'position' => $item->position ?: ($data['position'] ?? ($jobOffer?->position ?? '')),
            'email' => $item->email ?: ($data['email'] ?? ($jobOffer?->candidate_email ?? '')),
            'phone' => $item->phone ?: ($data['phone'] ?? ($data['mobileNo'] ?? '')),
            'status' => $item->status,
            'applicantType' => $applicantType ?: 'Not specified',
            'submittedDate' => optional($item->created_at)->format('Y-m-d'),
            'createdAt' => optional($item->created_at)->toDateTimeString(),
            'updatedAt' => optional($item->updated_at)->toDateTimeString(),
            'data' => $data,
            'jobOffer' => $jobOffer ? [
                'id' => $jobOffer->id,
                'name' => $jobOffer->name,
                'position' => $jobOffer->position,
                'salary' => $jobOffer->salary,
                'start_date' => optional($jobOffer->start_date)->format('Y-m-d'),
                'employment_type' => $jobOffer->employment_type,
                'department' => $jobOffer->department,
                'status' => $jobOffer->status,
            ] : null,
        ];
    }

    private function formatChecklist(OnboardingChecklist $item): array
{
    $pds = $item->personalDataSheet;
    $jobOffer = $pds?->jobOffer;
    $offerDetails = is_array($jobOffer?->offer_details) ? $jobOffer->offer_details : [];
    $reportingManager = $offerDetails['reportingTo']
        ?? $offerDetails['immediateSupervisor']
        ?? $jobOffer?->reporting_to
        ?? $jobOffer?->immediate_supervisor
        ?? null;

    return [
        'id' => $item->id,
        'pdsId' => $item->personal_data_sheet_id,
        'employeeName' => $item->employee_name,
        'employeeEmail' => $item->employee_email,
        'position' => $jobOffer?->position ?: $item->position,
        'startDate' => optional($jobOffer?->start_date)->format('Y-m-d'),

        // Job Offer data for Employee Registration preview/autofill
        'jobOfferDepartment' => $jobOffer?->department,
        'jobOfferSalary' => $jobOffer?->salary,
        'jobOfferEmploymentType' => $jobOffer?->employment_type,
        'jobOfferReportingManager' => $reportingManager,
        'jobOfferBranchId' => $jobOffer?->branch_id,
        'jobOfferOfficeId' => $jobOffer?->office_id,
        'jobOfferDepartmentId' => $jobOffer?->department_id,
        'jobOfferDivisionId' => $jobOffer?->division_id,
        'jobOfferUnitId' => $jobOffer?->unit_id,

        'docsSubmitted' => $item->docs_submitted,
        'docsApproved' => $item->docs_approved ?? 0,
        'totalDocs' => $item->total_docs,
        'checked' => $item->checked_documents ?? [],
        'documents' => $item->checked_documents ?? [],
        'status' => $item->status ?? 'For Review',
        'uploadUrl' => $item->upload_token ? route('careers.checklist.show', $item->upload_token) : null,
        'submittedDate' => optional($item->created_at)->format('m/d/Y'),
    ];
}

    private function formatEmployee(OnboardingEmployeeRegistration $item): array
{
    return [
        'id' => $item->id,
        'checklistId' => $item->onboarding_checklist_id,
        'employeeProfileId' => $item->employee_profile_id,
        'fullName' => $item->full_name,
        'employeeId' => $item->employee_id,
        'department' => $item->department,
        'startDate' => optional($item->start_date)->format('Y-m-d'),
        'workEmail' => $item->work_email,
        'manager' => $item->manager,
    ];
}



private function splitEmployeeName(string $fullName): array
{
    $fullName = trim($fullName);

    if (str_contains($fullName, ',')) {
        [$lastName, $firstName] = array_map('trim', explode(',', $fullName, 2));
        return [$firstName ?: $fullName, $lastName ?: ''];
    }

    $parts = preg_split('/\s+/', $fullName);

    if (count($parts) <= 1) {
        return [$fullName, ''];
    }

    $lastName = array_pop($parts);
    $firstName = implode(' ', $parts);

    return [$firstName, $lastName];
}

private function buildPdsAddress(array $data, string $prefix = 'res'): ?string
{
    $map = $prefix === 'perm'
        ? ['permHouse', 'permStreet', 'permSubdiv', 'permBrgy', 'permCity', 'permProv', 'permZip']
        : ['resHouse', 'resStreet', 'resSubdiv', 'resBrgy', 'resCity', 'resProv', 'resZip'];

    $parts = array_filter([
        $data[$map[0]] ?? null,
        $data[$map[1]] ?? null,
        $data[$map[2]] ?? null,
        $data[$map[3]] ?? null,
        $data[$map[4]] ?? null,
        $data[$map[5]] ?? null,
        $data[$map[6]] ?? null,
    ]);

    return $parts ? implode(', ', $parts) : null;
}

private function findCandidateApplication($jobOffer, ?PersonalDataSheet $pds): ?CandidateApplication
{
    $email = strtolower((string) ($jobOffer?->candidate_email ?: $pds?->email));
    $name = strtolower((string) ($jobOffer?->name ?: $pds?->full_name));

    if ($email === '' && $name === '') {
        return null;
    }

    return CandidateApplication::query()
        ->when($jobOffer?->job_posting_id, fn ($query) => $query->where('job_posting_id', $jobOffer->job_posting_id))
        ->where(function ($query) use ($email, $name) {
            if ($email !== '') {
                $query->orWhereRaw('LOWER(email) = ?', [$email]);
            }

            if ($name !== '') {
                $query->orWhereRaw('LOWER(name) = ?', [$name]);
            }
        })
        ->latest()
        ->first()
        ?: CandidateApplication::query()
            ->where(function ($query) use ($email, $name) {
                if ($email !== '') {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($name !== '') {
                    $query->orWhereRaw('LOWER(name) = ?', [$name]);
                }
            })
            ->latest()
            ->first();
}

private function resolveEmployeeProfilePhoto(?CandidateApplication $candidateApplication, OnboardingChecklist $checklist): ?string
{
    if ($candidateApplication?->photo_path && Storage::disk('public')->exists($candidateApplication->photo_path)) {
        return $candidateApplication->photo_path;
    }

    $twoByTwo = collect($checklist->checked_documents ?? [])->firstWhere('key', 'two_by_two_picture');
    $path = $twoByTwo['file_path'] ?? null;

    return $path && Storage::disk('public')->exists($path) ? $path : null;
}

private function buildEmployeeAttachments(OnboardingChecklist $checklist, ?CandidateApplication $candidateApplication, $jobOffer): array
{
    $attachments = [];

    foreach (($checklist->checked_documents ?? []) as $document) {
        if (empty($document['file_path'])) {
            continue;
        }

        $attachments[] = $this->employeeAttachment(
            $document['file_path'],
            $document['original_name'] ?? $document['label'] ?? 'Pre-employment Document',
            $document['label'] ?? 'Pre-employment Document',
            'Pre-employment Checklist',
            $document['uploaded_at'] ?? null
        );
    }

    foreach (($candidateApplication?->attachment_paths ?? []) as $key => $path) {
        if (!$path) {
            continue;
        }

        $attachments[] = $this->employeeAttachment(
            $path,
            $this->attachmentLabel($key),
            'Recruitment Attachment',
            'Candidate Application',
            optional($candidateApplication?->created_at)->toDateTimeString()
        );
    }

    if ($jobOffer?->signed_offer_path) {
        $attachments[] = $this->employeeAttachment(
            $jobOffer->signed_offer_path,
            'Signed Job Offer',
            'Signed Job Offer',
            'Job Offer Acceptance',
            optional($jobOffer->signed_offer_uploaded_at)->toDateTimeString()
        );
    }

    return collect($attachments)
        ->filter(fn ($item) => !empty($item['path']))
        ->unique('path')
        ->values()
        ->all();
}

private function employeeAttachment(?string $path, string $fileName, string $category, string $source, ?string $uploadedAt = null): array
{
    $exists = $path && Storage::disk('public')->exists($path);

    return [
        'path' => $path,
        'file_name' => $fileName,
        'category' => $category,
        'source' => $source,
        'file_type' => $path ? strtoupper(pathinfo($path, PATHINFO_EXTENSION)) : null,
        'file_size' => $exists ? Storage::disk('public')->size($path) : null,
        'uploaded_at' => $uploadedAt,
    ];
}

private function attachmentLabel(string $key): string
{
    return match ($key) {
        'photo' => 'Applicant Photo',
        'resume_cv' => 'Resume / CV',
        'cover_letter' => 'Cover Letter',
        'portfolio' => 'Portfolio',
        'government_id' => 'Valid Government ID',
        default => Str::headline(str_replace('_', ' ', $key)),
    };
}

private function resolveOrgId($directValue, array $offerDetails, array $applicationData, array $keys): ?int
{
    foreach ([$directValue] as $value) {
        if ($this->validOrgId($value)) {
            return (int) $value;
        }
    }

    foreach ($keys as $key) {
        foreach ([$offerDetails[$key] ?? null, $applicationData[$key] ?? null] as $value) {
            if ($this->validOrgId($value)) {
                return (int) $value;
            }
        }
    }

    return null;
}

private function completeOrgAssignment(array $organization): array
{
    if (!empty($organization['unit_id'])) {
        $unit = Unit::find($organization['unit_id']);
        $organization['division_id'] = $organization['division_id'] ?: $unit?->division_id;
    }

    if (!empty($organization['division_id'])) {
        $division = Division::find($organization['division_id']);
        $organization['department_id'] = $organization['department_id'] ?: $division?->department_id;
    }

    if (!empty($organization['department_id'])) {
        $department = Department::find($organization['department_id']);
        $organization['office_id'] = $organization['office_id'] ?: $department?->office_id;
    }

    if (!empty($organization['office_id'])) {
        $office = Office::find($organization['office_id']);
        $organization['branch_id'] = $organization['branch_id'] ?: $office?->branch_id;
    }

    return [
        'branch_id' => $organization['branch_id'] ?: null,
        'office_id' => $organization['office_id'] ?: null,
        'department_id' => $organization['department_id'] ?: null,
        'division_id' => $organization['division_id'] ?: null,
        'unit_id' => $organization['unit_id'] ?: null,
    ];
}

private function validOrgId($value): bool
{
    return $value !== null && $value !== '' && is_numeric($value) && (int) $value > 0;
}

private function decodeArray($value): array
{
    if (is_array($value)) {
        return $value;
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    return [];
}

private function buildEmployeeEducation(array $pdsData, array $applicationData): array
{
    $rows = [];

    foreach ([
        'Elem' => 'Elementary',
        'Sec' => 'Secondary',
        'Coll' => 'College',
        'Mast' => 'Masters',
        'Doct' => 'Doctorate',
    ] as $key => $label) {
        $school = $pdsData["educ{$key}School"] ?? null;
        $degree = $pdsData["educ{$key}Degree"] ?? null;
        $from = $pdsData["educ{$key}From"] ?? null;
        $to = $pdsData["educ{$key}To"] ?? null;

        if ($school || $degree || $from || $to) {
            $rows[] = trim($label . ': ' . implode(' | ', array_filter([$school, $degree, trim(($from ?: '') . ' - ' . ($to ?: ''), ' -')])));
        }
    }

    foreach ($this->decodeArray($applicationData['education'] ?? []) as $education) {
        if (!is_array($education)) {
            continue;
        }

        $rows[] = implode(' | ', array_filter([
            $education['level'] ?? null,
            $education['school'] ?? null,
            $education['degree'] ?? null,
            $education['course'] ?? null,
            $education['year'] ?? null,
            $education['honors'] ?? null,
        ]));
    }

    return array_values(array_unique(array_filter($rows)));
}

private function buildEmployeeCertifications(array $pdsData, array $applicationData): array
{
    $rows = [];

    foreach (($pdsData['lnd'] ?? []) as $training) {
        if (!is_array($training) || empty($training['title'])) {
            continue;
        }

        $rows[] = implode(' | ', array_filter([
            $training['title'] ?? null,
            $training['conductedBy'] ?? null,
            $training['date'] ?? null,
            isset($training['cert']) ? 'Certificate: ' . $training['cert'] : null,
        ]));
    }

    foreach ($this->decodeArray($applicationData['certifications'] ?? []) as $certification) {
        if (!is_array($certification) || empty($certification['name'])) {
            continue;
        }

        $rows[] = implode(' | ', array_filter([
            $certification['name'] ?? null,
            $certification['provider'] ?? null,
            $certification['status'] ?? null,
            $certification['dateTaken'] ?? ($certification['datePlanned'] ?? null),
            $certification['code'] ?? null,
        ]));
    }

    return array_values(array_unique(array_filter($rows)));
}

private function buildEmployeeSkills(array $applicationData): array
{
    return array_values(array_filter([
        $applicationData['technicalSkills'] ?? null,
        $applicationData['softwareTools'] ?? null,
        $applicationData['certificationsLicenses'] ?? null,
        $applicationData['languages'] ?? null,
    ]));
}

private function linesFromText($value): array
{
    if (is_array($value)) {
        return array_values(array_filter($value));
    }

    return array_values(array_filter(preg_split('/\r?\n|,/', (string) $value)));
}

private function benefitsFromValue($value): array
{
    if (is_array($value)) {
        return collect($value)->map(fn ($item) => trim((string) $item))->filter()->values()->all();
    }

    $text = trim((string) $value);
    if ($text === '') {
        return [];
    }

    $knownBenefits = [
        'Social Security System (SSS)',
        'PhilHealth',
        'Pag-IBIG Fund (HDMF)',
        '13th Month Pay',
        'Overtime Pay',
        'Night Differential Pay, if applicable',
        'Rest Day / Special Holiday Premium Pay, if applicable',
        'Maternity Benefits, per law',
        'Paternity Benefits, per law',
        'Solo Parent and other statutory leave benefits, if applicable',
        'Retirement Benefits as required by law or policy, if applicable',
        'Other benefits mandated under Philippine labor laws',
        'Bonus, Performance Incentive Schemes and Merit-Based Rewards',
        'Healthcare, Insurance, and Investment Benefit Plan after 6 months of employment, subject to company policy and eligibility',
        'Service Incentive Leave',
        'Incentives / Commission',
        'Holiday Pay',
        'HMO',
        'Day Shift + Weekends Off',
        'No Work on Philippine Holidays, subject to operations',
        'Structured and Professional Work Environment',
        'Exposure to Corporate Advisory and Governance Practice',
        'Opportunity for Long-Term Growth Based on Performance',
    ];

    $items = [];
    $remaining = $text;

    foreach ($knownBenefits as $benefit) {
        if (stripos($text, $benefit) !== false) {
            $items[] = $benefit;
            $remaining = str_ireplace($benefit, '', $remaining);
        }
    }

    $fallback = preg_split('/\r?\n|,/', $remaining);
    foreach ($fallback as $item) {
        $clean = trim($item, " \t\n\r\0\x0B,");
        if ($clean !== '' && !in_array(strtolower($clean), ['if applicable', 'per law', 'subject to operations'], true)) {
            $items[] = $clean;
        }
    }

    return array_values(array_unique(array_filter($items)));
}

private function yesNoBoolean($value): ?bool
{
    if ($value === null || $value === '') {
        return null;
    }

    return in_array(strtolower((string) $value), ['yes', '1', 'true', 'on'], true);
}


private function parseMoneyAmount($value): float
{
    if ($value === null || $value === '') {
        return 0;
    }

    if (is_numeric($value)) {
        return (float) $value;
    }

    $clean = str_replace(',', '', (string) $value);

    preg_match_all('/\d+(?:\.\d+)?/', $clean, $matches);

    if (empty($matches[0])) {
        return 0;
    }

    // If salary is stored as a range like "15000.00 - 15000.00",
    // use the first amount as the employee basic salary.
    return (float) $matches[0][0];
}

private function inferPayrollType(?string $employmentType): string
{
    $type = strtolower((string) $employmentType);

    if (str_contains($type, 'daily')) {
        return 'Daily Paid';
    }

    return 'Monthly Paid';
}

private function computeEmployeeHourlyRate(float $salary, string $payrollType): float
{
    if ($salary <= 0) {
        return 0;
    }

    if ($payrollType === 'Monthly Paid') {
        return round($salary / 22 / 8, 2);
    }

    return round($salary / 8, 2);
}

    private function formatTraining(TrainingAssignment $item): array
    {
        $employeeName = '';
        if ($item->employee) {
            $employeeName = ($item->employee->first_name ?? '') . ' ' . ($item->employee->last_name ?? '');
        }

        return [
            'id' => $item->id,
            'employeeId' => $item->employee_id,
            'employeeName' => trim($employeeName),
            'trainingId' => $item->training_id,
            'program' => $item->training?->title,
            'trainer' => $item->trainer,
            'startDate' => optional($item->start_date)->format('Y-m-d'),
            'dueDate' => optional($item->due_date)->format('Y-m-d'),
            'completedAt' => optional($item->completed_at)->format('Y-m-d H:i'),
            'description' => $item->description,
            'status' => $item->status,
            'remarks' => $item->remarks,
            'assignmentType' => $item->assignment_type,
        ];
    }
}
