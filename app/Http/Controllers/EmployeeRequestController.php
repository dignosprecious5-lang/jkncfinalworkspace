<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeRequest;
use App\Models\User;
use App\Http\Controllers\Concerns\ScopesHumanCapitalRecords;
use App\Http\Controllers\Concerns\UsesLatestGisCompanyHeader;
use App\Notifications\HumanCapitalWorkflowNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;

class EmployeeRequestController extends Controller
{
    use ScopesHumanCapitalRecords;
    use UsesLatestGisCompanyHeader;

    public function index()
    {
        $user = auth()->user();

        $canManageEmployeeRequests = $this->canManageRequests();
        $currentEmployee = $this->currentEmployee();

        $employeeRequests = $canManageEmployeeRequests
            ? EmployeeRequest::latest()->get()->map(fn (EmployeeRequest $item) => $this->formatEmployeeRequest($item))
            : collect();

        $identity = $this->humanCapitalEmployeeIdentity($currentEmployee, $user);

        $myEmployeeRequests = $this->applyEmployeeIdentityScope(
                EmployeeRequest::query(),
                $identity,
                [],
                ['user_id'],
                ['employee_name']
            )
            ->latest()
            ->get()
            ->map(fn (EmployeeRequest $item) => $this->formatEmployeeRequest($item));

        $employees = $canManageEmployeeRequests
            ? Employee::with('department')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (Employee $employee) => $this->formatEmployee($employee))
                ->values()
            : collect();

        $currentEmployeeProfile = $currentEmployee
            ? $this->formatEmployee($currentEmployee)
            : null;

        return view('human-capital.employee-requests', compact(
            'employeeRequests',
            'myEmployeeRequests',
            'canManageEmployeeRequests',
            'employees',
            'currentEmployeeProfile'
        ) + [
            'companyHeader' => $this->latestGisCompanyHeader(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_type' => 'required|string|max:255',
            'request_type_other' => 'required_if:request_type,Other|nullable|string|max:255',
            'employee_id' => ($this->canManageRequests() ? 'required' : 'nullable').'|nullable|exists:employees,id',
            'purpose' => 'required_if:request_type,COE Request Form|nullable|string|max:255',
            'coe_type' => 'required_if:request_type,COE Request Form|nullable|string|in:Employment Only,With Compensation',
            'coe_purpose_other' => 'required_if:purpose,Others|nullable|string|max:255',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $employee = $this->resolveEmployee($request->integer('employee_id') ?: null);
        $requestUser = $this->userForEmployee($employee);

        $attachmentPayload = $this->attachmentPayload($request);
        $coePayload = $this->coeColumnPayload($request);
        $otherRequestPayload = $this->otherRequestTypePayload($request);

        try {
            $employeeRequest = EmployeeRequest::create([
                'user_id' => $requestUser->id,
                'employee_name' => $employee->full_name,

                // Main request type
                'request_type' => $request->request_type,

                // Common fields
                'department' => $employee->department?->department_name,
                'request_date' => $request->request_date,

                // Overtime Request fields
                'overtime_date' => $request->overtime_date,
                'start_time' => $request->start_time,
                'end_time' => $this->calculateOvertimeEndTime($request),
                'total_hours' => $request->total_hours,

                // Leave Application fields
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'number_of_days' => $request->number_of_days,
                'with_pay' => $request->with_pay,

                // Attendance Correction fields
                'attendance_date' => $request->attendance_date,
                'correction_type' => $request->correction_type,
                'correct_time' => $request->correct_time,

                // Undertime / Absence fields
                'absence_type' => $request->absence_type,
                'time_affected' => $request->time_affected,

                // COE Request fields
                'purpose' => $request->purpose,
                'date_needed' => $request->date_needed,
                'number_of_copies' => $request->number_of_copies,

                // Shared text fields
                'reason' => $request->reason,
                'remarks' => $request->remarks,

                // Default status
                'status' => 'Pending',
            ] + $otherRequestPayload + $coePayload + $attachmentPayload);
        } catch (\Throwable $exception) {
            $this->deleteStoredAttachment($attachmentPayload);

            throw $exception;
        }

        $this->notifyAdmins(
            title: 'New employee request submitted',
            message: $employeeRequest->employee_name . ' submitted a ' . $this->displayRequestType($employeeRequest) . ' request.',
            url: route('admin.human-capital.dashboard')
        );

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request submitted successfully.');
    }

    public function updateRevision(Request $request, EmployeeRequest $employeeRequest)
    {
        abort_unless($employeeRequest->user_id === auth()->id(), 403);

        abort_unless($employeeRequest->status === 'For Revision', 403);

        $request->validate([
            'department' => 'nullable|string|max:255',
            'request_type' => 'nullable|string|max:255',
            'request_type_other' => 'required_if:request_type,Other|nullable|string|max:255',
            'request_date' => 'nullable|date',
            'overtime_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'total_hours' => 'nullable|numeric',
            'leave_type' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'number_of_days' => 'nullable|numeric',
            'with_pay' => 'nullable|string|max:255',
            'attendance_date' => 'nullable|date',
            'correction_type' => 'nullable|string|max:255',
            'correct_time' => 'nullable',
            'absence_type' => 'nullable|string|max:255',
            'time_affected' => 'nullable',
            'purpose' => 'required_if:request_type,COE Request Form|nullable|string|max:255',
            'coe_type' => 'required_if:request_type,COE Request Form|nullable|string|in:Employment Only,With Compensation',
            'coe_purpose_other' => 'required_if:purpose,Others|nullable|string|max:255',
            'date_needed' => 'nullable|date',
            'number_of_copies' => 'nullable|integer',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $employee = $this->currentEmployee();

        $oldAttachmentPath = $employeeRequest->attachment_path;
        $attachmentPayload = $this->attachmentPayload($request, $employeeRequest);
        $coePayload = $this->coeColumnPayload($request);
        $otherRequestPayload = $this->otherRequestTypePayload($request);

        try {
            $employeeRequest->update([
                'department' => $employee?->department?->department_name,
                'request_type' => $request->request_type ?: $employeeRequest->request_type,
                'request_date' => $request->request_date,

                'overtime_date' => $request->overtime_date,
                'start_time' => $request->start_time,
                'end_time' => $this->calculateOvertimeEndTime($request),
                'total_hours' => $request->total_hours,

                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'number_of_days' => $request->number_of_days,
                'with_pay' => $request->with_pay,

                'attendance_date' => $request->attendance_date,
                'correction_type' => $request->correction_type,
                'correct_time' => $request->correct_time,

                'absence_type' => $request->absence_type,
                'time_affected' => $request->time_affected,

                'purpose' => $request->purpose,
                'date_needed' => $request->date_needed,
                'number_of_copies' => $request->number_of_copies,

                'reason' => $request->reason,
                'remarks' => $request->remarks,

                'status' => 'Pending',
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ] + $otherRequestPayload + $coePayload + $attachmentPayload);
        } catch (\Throwable $exception) {
            $this->deleteStoredAttachment($attachmentPayload);

            throw $exception;
        }

        $this->deleteReplacedAttachment($oldAttachmentPath, $attachmentPayload);

        $this->notifyAdmins(
            title: 'Employee request revision submitted',
            message: $employeeRequest->employee_name . ' resubmitted a revised ' . $this->displayRequestType($employeeRequest) . ' request.',
            url: route('admin.human-capital.dashboard')
        );

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request revision submitted successfully.');
    }

    public function approve(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $employeeRequest->update([
            'status' => 'Approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        $this->notifyEmployee(
            $employeeRequest,
            title: 'Employee request approved',
            message: 'Your ' . $this->displayRequestType($employeeRequest) . ' request has been approved.',
            url: route('human-capital.employee-requests.index')
        );

        if ($this->isCoeRequest($employeeRequest)) {
            $this->sendApprovedCoeEmail($employeeRequest);
        }

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request approved successfully.');
    }

    public function downloadCoe(EmployeeRequest $employeeRequest)
    {
        abort_unless($this->canViewRequest($employeeRequest), 403);
        abort_unless($this->isCoeRequest($employeeRequest), 404);
        abort_unless($employeeRequest->status === 'Approved' || $this->canManageRequests(), 403);

        $coeData = $this->coeData($employeeRequest);
        $pdf = $this->coePdf($employeeRequest, $coeData);

        return $pdf->download(($coeData['coe_number'] ?: 'COE-' . $employeeRequest->id) . '.pdf');
    }

    public function update(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'request_type' => 'required|string|max:255',
            'request_type_other' => 'required_if:request_type,Other|nullable|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'department' => 'nullable|string|max:255',
            'request_date' => 'nullable|date',
            'overtime_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'total_hours' => 'nullable|numeric',
            'leave_type' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'number_of_days' => 'nullable|numeric',
            'with_pay' => 'nullable|string|max:255',
            'attendance_date' => 'nullable|date',
            'correction_type' => 'nullable|string|max:255',
            'correct_time' => 'nullable',
            'absence_type' => 'nullable|string|max:255',
            'time_affected' => 'nullable',
            'purpose' => 'required_if:request_type,COE Request Form|nullable|string|max:255',
            'coe_type' => 'required_if:request_type,COE Request Form|nullable|string|in:Employment Only,With Compensation',
            'coe_purpose_other' => 'required_if:purpose,Others|nullable|string|max:255',
            'date_needed' => 'nullable|date',
            'number_of_copies' => 'nullable|integer',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'admin_note' => 'nullable|string|max:1000',
            'status' => 'required|string|in:Pending,Approved,For Revision,Declined',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        $employee = Employee::with('department')->findOrFail($request->integer('employee_id'));
        $requestUser = $this->userForEmployee($employee);

        $oldAttachmentPath = $employeeRequest->attachment_path;
        $attachmentPayload = $this->attachmentPayload($request, $employeeRequest);
        $coePayload = $this->coeColumnPayload($request);
        $otherRequestPayload = $this->otherRequestTypePayload($request);

        try {
            $employeeRequest->update([
                'user_id' => $requestUser->id,
                'employee_name' => $employee->full_name,
                'request_type' => $request->request_type,
                'department' => $employee->department?->department_name,
                'request_date' => $request->request_date,
                'overtime_date' => $request->overtime_date,
                'start_time' => $request->start_time,
                'end_time' => $this->calculateOvertimeEndTime($request),
                'total_hours' => $request->total_hours,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'number_of_days' => $request->number_of_days,
                'with_pay' => $request->with_pay,
                'attendance_date' => $request->attendance_date,
                'correction_type' => $request->correction_type,
                'correct_time' => $request->correct_time,
                'absence_type' => $request->absence_type,
                'time_affected' => $request->time_affected,
                'purpose' => $request->purpose,
                'date_needed' => $request->date_needed,
                'number_of_copies' => $request->number_of_copies,
                'reason' => $request->reason,
                'remarks' => $request->remarks,
                'status' => $request->status,
                'admin_note' => $request->admin_note,
            ] + $otherRequestPayload + $coePayload + $attachmentPayload);
        } catch (\Throwable $exception) {
            $this->deleteStoredAttachment($attachmentPayload);

            throw $exception;
        }

        $this->deleteReplacedAttachment($oldAttachmentPath, $attachmentPayload);

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request updated successfully.');
    }

    public function reject(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $employeeRequest->update([
            'status' => 'Declined',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        $this->notifyEmployee(
            $employeeRequest,
            title: 'Employee request rejected',
            message: 'Your ' . $this->displayRequestType($employeeRequest) . ' request has been rejected.',
            url: route('human-capital.employee-requests.index')
        );

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request rejected successfully.');
    }

    public function revise(Request $request, EmployeeRequest $employeeRequest)
    {
        $this->authorizeAdminAccess();

        $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        $employeeRequest->update([
            'status' => 'For Revision',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->admin_note,
        ]);

        $this->notifyEmployee(
            $employeeRequest,
            title: 'Employee request sent back for revision',
            message: 'Your ' . $this->displayRequestType($employeeRequest) . ' request needs revision.',
            url: route('human-capital.employee-requests.index')
        );

        return redirect()
            ->route('human-capital.employee-requests.index')
            ->with('success', 'Employee request sent back for revision.');
    }

    private function notifyAdmins(string $title, string $message, ?string $url = null): void
    {
        $admins = User::query()
            ->get()
            ->filter(fn (User $user) => $user->isAdmin() || $user->isSuperAdmin())
            ->values();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new HumanCapitalWorkflowNotification(
            title: $title,
            message: $message,
            url: $url ?: route('admin.human-capital.dashboard'),
            humanCapitalModule: 'Employee Requests',
            recordTitle: '',
            actorName: auth()->user()?->name ?? auth()->user()?->email ?? ''
        ));
    }

    private function attachmentPayload(Request $request, ?EmployeeRequest $employeeRequest = null): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        if (! $this->employeeRequestAttachmentColumnsExist()) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('employee-request-attachments', 'public'),
            'attachment_original_name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteStoredAttachment(array $attachmentPayload): void
    {
        if (! empty($attachmentPayload['attachment_path'])) {
            Storage::disk('public')->delete($attachmentPayload['attachment_path']);
        }
    }

    private function deleteReplacedAttachment(?string $oldAttachmentPath, array $attachmentPayload): void
    {
        if ($oldAttachmentPath && ! empty($attachmentPayload['attachment_path']) && $oldAttachmentPath !== $attachmentPayload['attachment_path']) {
            Storage::disk('public')->delete($oldAttachmentPath);
        }
    }

    private function employeeRequestAttachmentColumnsExist(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        return $exists = Schema::hasColumns('employee_requests', [
            'attachment_path',
            'attachment_original_name',
        ]);
    }

    private function coeColumnPayload(Request $request): array
    {
        if (! $this->employeeRequestCoeColumnsExist()) {
            return [];
        }

        $purpose = (string) $request->input('purpose', '');
        $purposeOther = $purpose === 'Others'
            ? trim((string) $request->input('coe_purpose_other', ''))
            : null;

        return [
            'coe_type' => $request->input('coe_type') ?: 'Employment Only',
            'coe_purpose_other' => $purposeOther,
        ];
    }

    private function otherRequestTypePayload(Request $request): array
    {
        if (! $this->employeeRequestOtherTypeColumnExists()) {
            return [];
        }

        return [
            'request_type_other' => $request->input('request_type') === 'Other'
                ? trim((string) $request->input('request_type_other', ''))
                : null,
        ];
    }

    private function employeeRequestOtherTypeColumnExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        return $exists = Schema::hasColumn('employee_requests', 'request_type_other');
    }

    private function employeeRequestCoeColumnsExist(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        return $exists = Schema::hasColumns('employee_requests', [
            'coe_type',
            'coe_purpose_other',
        ]);
    }

    private function notifyEmployee(EmployeeRequest $employeeRequest, string $title, string $message, ?string $url = null): void
    {
        $user = User::find($employeeRequest->user_id);

        if (! $user) {
            return;
        }

        $user->notify(new HumanCapitalWorkflowNotification(
            title: $title,
            message: $message,
            url: $url ?: route('human-capital.employee-requests.index'),
            humanCapitalModule: 'Employee Requests',
            recordTitle: $this->displayRequestType($employeeRequest),
            actorName: ''
        ));
    }

    private function formatEmployeeRequest(EmployeeRequest $employeeRequest): array
    {
        $data = $employeeRequest->toArray();
        $data['request_type_other'] = $data['request_type_other'] ?? '';
        $data['display_request_type'] = $this->displayRequestType($employeeRequest);
        $data['coe_type'] = $data['coe_type'] ?? 'Employment Only';
        $data['coe_purpose_other'] = $data['coe_purpose_other'] ?? '';
        $data['created_at'] = optional($employeeRequest->created_at)->format('Y-m-d H:i:s');
        $data['updated_at'] = optional($employeeRequest->updated_at)->format('Y-m-d H:i:s');
        $data['coe_preview'] = $this->isCoeRequest($employeeRequest) ? $this->coeData($employeeRequest) : null;
        $data['coe_download_url'] = $this->isCoeRequest($employeeRequest)
            ? route('human-capital.employee-requests.coe.download', $employeeRequest)
            : null;

        return $data;
    }

    private function isCoeRequest(EmployeeRequest $employeeRequest): bool
    {
        return $employeeRequest->request_type === 'COE Request Form';
    }

    private function displayRequestType(EmployeeRequest $employeeRequest): string
    {
        if ($employeeRequest->request_type === 'Other' && $employeeRequest->request_type_other) {
            return $employeeRequest->request_type_other;
        }

        return $employeeRequest->request_type ?: 'Employee Request';
    }

    private function canViewRequest(EmployeeRequest $employeeRequest): bool
    {
        if ($this->canManageRequests()) {
            return true;
        }

        if ($employeeRequest->user_id === auth()->id()) {
            return true;
        }

        $employee = $this->currentEmployee();

        return $employee
            && $employeeRequest->employee_name
            && strcasecmp($employeeRequest->employee_name, $employee->full_name) === 0;
    }

    private function coeData(EmployeeRequest $employeeRequest): array
    {
        $employee = $this->employeeForRequest($employeeRequest);
        $companyHeader = $this->latestGisCompanyHeader();
        $approvedAt = $employeeRequest->reviewed_at
            ? Carbon::parse($employeeRequest->reviewed_at)
            : ($employeeRequest->updated_at ?: now());
        $dateFrom = $employee?->date_hired ?: $employee?->start_date;
        $isSeparated = $employee && ! in_array(strtolower((string) $employee->employment_status), ['', 'active', 'regular', 'probationary'], true);
        $dateTo = $isSeparated ? ($employee->status_effective_date ?: now()) : null;

        return [
            'logo_url' => $companyHeader['logo_url'],
            'company_name' => $companyHeader['company_name'],
            'company_address' => $companyHeader['company_address'],
            'employee_name' => $employee?->full_name ?: $employeeRequest->employee_name,
            'position' => $employee?->position ?: '-',
            'department' => $employee?->department?->department_name ?: $employeeRequest->department ?: '-',
            'start_date' => $dateFrom ? $dateFrom->format('F d, Y') : '-',
            'end_date' => $dateTo ? $dateTo->format('F d, Y') : 'Present',
            'coe_type' => $employeeRequest->coe_type ?: 'Employment Only',
            'show_salary' => ($employeeRequest->coe_type ?: 'Employment Only') === 'With Compensation',
            'monthly_basic_salary' => $employee ? 'PHP ' . number_format((float) $employee->basic_salary, 2) : '-',
            'purpose' => $this->coePurposeText($employeeRequest),
            'date_issued' => $approvedAt->format('F d, Y'),
            'date_approved' => $approvedAt->format('F d, Y h:i A'),
            'coe_number' => 'COE-' . now()->format('Y') . '-' . str_pad((string) $employeeRequest->id, 5, '0', STR_PAD_LEFT),
            'approver_name' => User::find($employeeRequest->reviewed_by)?->name ?: 'Human Capital',
            'download_url' => route('human-capital.employee-requests.coe.download', $employeeRequest),
        ];
    }

    private function coePurposeText(EmployeeRequest $employeeRequest): string
    {
        if ($employeeRequest->purpose === 'Others') {
            return $employeeRequest->coe_purpose_other ?: $employeeRequest->remarks ?: 'employment verification';
        }

        return $employeeRequest->purpose ?: $employeeRequest->remarks ?: 'employment verification';
    }

    private function employeeForRequest(EmployeeRequest $employeeRequest): ?Employee
    {
        $user = User::find($employeeRequest->user_id);

        return Employee::with('department')
            ->where('user_id', $employeeRequest->user_id)
            ->when($user?->email, function ($query, string $email) {
                $query->orWhere('email', $email)
                    ->orWhere('work_email', $email)
                    ->orWhere('company_email', $email);
            })
            ->first();
    }

    private function coePdf(EmployeeRequest $employeeRequest, array $coeData)
    {
        return Pdf::loadView('human-capital.pdf.coe', [
            'employeeRequest' => $employeeRequest,
            'coe' => $coeData,
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Georgia',
            ]);
    }

    private function sendApprovedCoeEmail(EmployeeRequest $employeeRequest): void
    {
        $user = User::find($employeeRequest->user_id);

        if (! $user || ! $user->email) {
            return;
        }

        $coeData = $this->coeData($employeeRequest);
        $pdf = $this->coePdf($employeeRequest, $coeData);

        Mail::send('emails.branded-workflow-notification', [
            'logoUrl' => rtrim((string) config('app.url'), '/') . '/images/imaglogo.png',
            'notifiableName' => $user->name ?? 'there',
            'title' => 'Certificate of Employment approved',
            'body' => 'Your Certificate of Employment has been approved. The PDF copy is attached to this email and is also available in your Employee Requests tab.',
            'moduleName' => 'Employee Requests',
            'recordTitle' => $coeData['coe_number'],
            'actorName' => $coeData['approver_name'],
            'reviewNote' => null,
            'url' => $coeData['download_url'],
            'buttonLabel' => 'Download COE',
        ], function ($mail) use ($user, $coeData, $pdf) {
            $mail->to($user->email)
                ->subject('Certificate of Employment Approved - ' . $coeData['coe_number'])
                ->attachData($pdf->output(), $coeData['coe_number'] . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });
    }

    private function authorizeAdminAccess(): void
    {
        $user = auth()->user();

        abort_unless($user && $this->canManageRequests(), 403);
    }

    private function canManageRequests(): bool
    {
        $user = auth()->user();

        return $user && $this->canManageHumanCapitalModule('access_hc_employee_requests', true);
    }

    private function currentEmployee(): ?Employee
    {
        return $this->currentHumanCapitalEmployee(auth()->user());
    }

    private function resolveEmployee(?int $employeeId): Employee
    {
        if ($this->canManageRequests() && $employeeId) {
            return Employee::with('department')->findOrFail($employeeId);
        }

        $employee = $this->currentEmployee();

        if (! $employee) {
            abort(403, 'No employee profile is linked to your account email.');
        }

        return $employee;
    }

    private function userForEmployee(Employee $employee): User
    {
        $user = $employee->user_id
            ? User::find($employee->user_id)
            : User::whereIn('email', array_filter([$employee->email, $employee->work_email, $employee->company_email]))->first();

        if (! $user) {
            abort(422, 'The selected employee does not have a linked user account email.');
        }

        return $user;
    }

    private function formatEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
        ];
    }

    private function calculateOvertimeEndTime(Request $request): ?string
    {
        if ($request->request_type !== 'Overtime Request' || ! $request->start_time || ! is_numeric($request->total_hours)) {
            return $request->end_time;
        }

        return Carbon::createFromFormat('H:i', substr($request->start_time, 0, 5))
            ->addMinutes((int) round(((float) $request->total_hours) * 60))
            ->format('H:i');
    }
}
