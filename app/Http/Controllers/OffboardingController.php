<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
use App\Http\Controllers\Concerns\UsesLatestGisCompanyHeader;
use App\Models\Employee;
use App\Models\OffboardingRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OffboardingController extends Controller
{
    use RequestsHumanCapitalApproval;
    use UsesLatestGisCompanyHeader;

    public function index()
    {
        $employees = Employee::with('department')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone_number' => $employee->phone_number,
                'position' => $employee->position,
                'department' => $employee->department?->department_name,
            ])
            ->values();

        $records = OffboardingRecord::latest()
            ->get()
            ->map(fn (OffboardingRecord $record) => $this->formatRecord($record))
            ->values();

        return view('human-capital.offboarding', [
            'employees' => $employees,
            'records' => $records,
            'companyHeader' => $this->latestGisCompanyHeader(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRecord($request);
        $employee = Employee::with('department')->findOrFail($validated['employee_id']);

        $attachmentPayload = $this->attachmentPayload($request);

        try {
            OffboardingRecord::create([
            'reference_no' => $this->generateReferenceNo($validated['form_type']),
            'form_type' => $validated['form_type'],
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'details' => $this->detailsFromValidated($validated),
            'status' => $validated['status'] ?? 'Draft',
            'created_by' => Auth::id(),
            ] + $attachmentPayload);
        } catch (\Throwable $exception) {
            $this->deleteStoredAttachment($attachmentPayload);

            throw $exception;
        }

        return redirect()
            ->route('human-capital.offboarding')
            ->with('success', 'Offboarding record created successfully.');
    }

    public function update(Request $request, OffboardingRecord $offboardingRecord)
    {
        $validated = $this->validateRecord($request);
        $employee = Employee::with('department')->findOrFail($validated['employee_id']);

        $attachmentPayload = $this->attachmentPayload($request);

        $payload = [
            'form_type' => $validated['form_type'],
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'details' => $this->detailsFromValidated($validated),
            'status' => $validated['status'] ?? $offboardingRecord->status,
            'updated_by' => Auth::id(),
        ] + $attachmentPayload;

        $this->requestHumanCapitalChange($request, 'Offboarding', 'update', $offboardingRecord, $payload, $offboardingRecord->reference_no ?: $offboardingRecord->employee_name);

        return redirect()
            ->route('human-capital.offboarding')
            ->with('success', 'Offboarding update submitted for admin approval.');
    }

    public function destroy(Request $request, OffboardingRecord $offboardingRecord)
    {
        $this->requestHumanCapitalChange($request, 'Offboarding', 'delete', $offboardingRecord, null, $offboardingRecord->reference_no ?: $offboardingRecord->employee_name);

        return back()->with('success', 'Offboarding deletion submitted for admin approval.');
    }

    private function validateRecord(Request $request): array
    {
        $numericFields = [
            'basic_pay',
            'unused_leave_pay',
            'thirteenth_month',
            'other_earnings',
            'deductions',
            'net_final_pay',
            'settlement_amount',
        ];

        $dateFields = [
            'notice_date',
            'effective_date',
            'final_working_day',
            'submission_date',
            'acceptance_date',
            'interview_date',
            'clearance_due_date',
            'turnover_due_date',
            'release_date',
            'payment_date',
            'signed_date',
        ];

        $rules = [
            'form_type' => ['required', Rule::in(OffboardingRecord::TYPES)],
            'employee_id' => ['required', 'exists:employees,id'],
            'status' => ['required', Rule::in(['Draft', 'Pending', 'Processing', 'Completed', 'Cancelled'])],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ];

        foreach ($this->detailFields() as $field) {
            $rules[$field] = ['nullable', 'string'];
        }

        foreach ($dateFields as $field) {
            $rules[$field] = ['nullable', 'date'];
        }

        foreach ($numericFields as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0'];
        }

        return $request->validate($rules);
    }

    private function detailFields(): array
    {
        return [
            'termination_reason',
            'facts_and_basis',
            'company_property',
            'hr_reviewer',
            'legal_review',
            'remarks',
            'resignation_type',
            'notice_period',
            'reason_for_leaving',
            'transition_notes',
            'accepted_by',
            'interviewer',
            'employment_experience',
            'rehire_eligible',
            'primary_reason',
            'work_environment_feedback',
            'management_feedback',
            'recommendations',
            'it_clearance',
            'finance_clearance',
            'admin_clearance',
            'hc_clearance',
            'accountabilities',
            'assigned_to',
            'documents_status',
            'client_accounts_status',
            'system_access_status',
            'pending_tasks_status',
            'turnover_notes',
            'payment_method',
            'computation_notes',
            'witness_name',
            'release_clause',
            'notary_details',
        ];
    }

    private function detailsFromValidated(array $validated): array
    {
        return collect($validated)
            ->except(['form_type', 'employee_id', 'status', 'attachment'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function attachmentPayload(Request $request): array
    {
        if (! $request->hasFile('attachment') || ! $this->offboardingAttachmentColumnsExist()) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('offboarding/attachments', 'public'),
            'attachment_original_name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteStoredAttachment(array $attachmentPayload): void
    {
        if (! empty($attachmentPayload['attachment_path'])) {
            Storage::disk('public')->delete($attachmentPayload['attachment_path']);
        }
    }

    private function offboardingAttachmentColumnsExist(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        return $exists = Schema::hasColumns('offboarding_records', [
            'attachment_path',
            'attachment_original_name',
        ]);
    }

    private function generateReferenceNo(string $formType): string
    {
        $prefix = match ($formType) {
            OffboardingRecord::TERMINATION => 'TER',
            OffboardingRecord::RESIGNATION => 'RES',
            OffboardingRecord::EXIT_INTERVIEW => 'EXI',
            OffboardingRecord::CLEARANCE => 'CLR',
            OffboardingRecord::TURNOVER => 'TUR',
            OffboardingRecord::FINAL_PAY => 'FPC',
            OffboardingRecord::QUITCLAIM => 'QTC',
            default => 'OFF',
        };

        return $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    private function formatRecord(OffboardingRecord $record): array
    {
        return array_merge($record->details ?? [], [
            'id' => $record->id,
            'reference_no' => $record->reference_no,
            'form_type' => $record->form_type,
            'type' => $record->form_type,
            'form_type_label' => $record->form_type_label,
            'employee_id' => $record->employee_id,
            'employee_code' => $record->employee_code,
            'employee_name' => $record->employee_name,
            'position' => $record->position,
            'department' => $record->department,
            'attachment_url' => $record->attachment_url,
            'attachment_original_name' => $record->attachment_original_name,
            'status' => $record->status,
        ]);
    }
}
