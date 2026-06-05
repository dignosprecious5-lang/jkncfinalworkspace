<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
use App\Models\Employee;
use App\Models\EmployeeRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeRelationController extends Controller
{
    use RequestsHumanCapitalApproval;
    public function index()
    {
        $user = Auth::user();
        $canManageRelations = $this->canManageRelations();
        $currentEmployee = $this->currentEmployee();

        $employees = Employee::with('department')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone_number' => $employee->phone_number,
                'position' => $employee->position,
                'department' => $employee->department?->department_name,
            ])
            ->values();

        $query = EmployeeRelation::latest();

        if (! $canManageRelations) {
            if (! $currentEmployee) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('employee_id', $currentEmployee->id);
            }
        }

        $relations = $query->get()
            ->map(fn ($relation) => $this->formatRelation($relation))
            ->values();

        return view('human-capital.employee-relations', [
            'employees' => $employees,
            'relations' => $relations,
            'canManageRelations' => $canManageRelations,
            'currentEmployee' => $currentEmployee ? [
                'id' => $currentEmployee->id,
                'employee_code' => $currentEmployee->employee_code,
                'full_name' => $currentEmployee->full_name,
                'email' => $currentEmployee->email,
                'phone_number' => $currentEmployee->phone_number,
                'position' => $currentEmployee->position,
                'department' => $currentEmployee->department?->department_name,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRelation($request);
        $employee = $this->resolveEmployee($validated['employee_id'] ?? null);

        EmployeeRelation::create([
            'reference_no' => $this->generateReferenceNo($validated['form_type']),
            'form_type' => $validated['form_type'],
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'filed_at' => $validated['filed_at'] ?? now()->toDateString(),
            'subject' => $validated['subject'],
            'details' => $this->detailsFromRequest($validated),
            'attachment_paths' => $this->storeAttachments($request),
            'status' => 'Pending',
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('human-capital.employee-relations')
            ->with('success', 'Employee relations form submitted successfully.');
    }

    public function update(Request $request, EmployeeRelation $employeeRelation)
    {
        $this->authorizeRelationAccess($employeeRelation, true);

        $validated = $this->validateRelation($request, true);
        $employee = $this->resolveEmployee($validated['employee_id'] ?? $employeeRelation->employee_id);

        $payload = [
            'form_type' => $validated['form_type'],
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'filed_at' => $validated['filed_at'] ?? $employeeRelation->filed_at,
            'subject' => $validated['subject'],
            'details' => $this->detailsFromRequest($validated),
            'attachment_paths' => array_values(array_merge($employeeRelation->attachment_paths ?? [], $this->storeAttachments($request))),
            'hr_remarks' => $validated['hr_remarks'] ?? $employeeRelation->hr_remarks,
            'status' => $this->canManageRelations() ? ($validated['status'] ?? $employeeRelation->status) : $employeeRelation->status,
        ];

        $this->requestHumanCapitalChange($request, 'Employee Relations', 'update', $employeeRelation, $payload, $employeeRelation->reference_no ?: $employeeRelation->subject);

        return redirect()
            ->route('human-capital.employee-relations')
            ->with('success', 'Employee relations update submitted for admin approval.');
    }

    public function approve(EmployeeRelation $employeeRelation)
    {
        $this->authorizeManagement();

        $employeeRelation->update([
            'status' => 'Resolved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Employee relations form marked as resolved.');
    }

    public function reject(Request $request, EmployeeRelation $employeeRelation)
    {
        $this->authorizeManagement();

        $request->validate(['hr_remarks' => ['nullable', 'string']]);

        $employeeRelation->update([
            'status' => 'Closed',
            'hr_remarks' => $request->hr_remarks ?: $employeeRelation->hr_remarks,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Employee relations form closed.');
    }

    public function destroy(Request $request, EmployeeRelation $employeeRelation)
    {
        $this->authorizeManagement();

        $this->requestHumanCapitalChange($request, 'Employee Relations', 'delete', $employeeRelation, null, $employeeRelation->reference_no ?: $employeeRelation->subject);

        return back()->with('success', 'Employee relations deletion submitted for admin approval.');
    }

    private function validateRelation(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'form_type' => ['required', Rule::in([
                EmployeeRelation::INCIDENT,
                EmployeeRelation::GRIEVANCE,
                EmployeeRelation::MEDIATION,
            ])],
            'employee_id' => [$this->canManageRelations() ? 'required' : 'nullable', 'nullable', 'exists:employees,id'],
            'filed_at' => ['nullable', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['Pending', 'Under Review', 'For Mediation', 'Resolved', 'Closed'])],
            'hr_remarks' => ['nullable', 'string'],

            'incident_date' => ['required_if:form_type,'.EmployeeRelation::INCIDENT, 'nullable', 'date'],
            'incident_time' => ['nullable'],
            'incident_location' => ['required_if:form_type,'.EmployeeRelation::INCIDENT, 'nullable', 'string', 'max:255'],
            'incident_type' => ['nullable', 'string', 'max:255'],
            'persons_involved' => ['nullable', 'string'],
            'witnesses' => ['nullable', 'string'],
            'incident_description' => ['required_if:form_type,'.EmployeeRelation::INCIDENT, 'nullable', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'injury_or_damage' => ['nullable', 'string'],

            'grievance_date' => ['required_if:form_type,'.EmployeeRelation::GRIEVANCE, 'nullable', 'date'],
            'grievance_category' => ['required_if:form_type,'.EmployeeRelation::GRIEVANCE, 'nullable', 'string', 'max:255'],
            'grievance_against' => ['nullable', 'string', 'max:255'],
            'grievance_summary' => ['required_if:form_type,'.EmployeeRelation::GRIEVANCE, 'nullable', 'string'],
            'prior_steps_taken' => ['nullable', 'string'],
            'desired_resolution' => ['required_if:form_type,'.EmployeeRelation::GRIEVANCE, 'nullable', 'string'],
            'is_confidential' => ['nullable'],

            'mediation_date' => ['required_if:form_type,'.EmployeeRelation::MEDIATION, 'nullable', 'date'],
            'mediator' => ['required_if:form_type,'.EmployeeRelation::MEDIATION, 'nullable', 'string', 'max:255'],
            'parties_involved' => ['nullable', 'string'],
            'issue_summary' => ['required_if:form_type,'.EmployeeRelation::MEDIATION, 'nullable', 'string'],
            'employee_position_statement' => ['nullable', 'string'],
            'management_position_statement' => ['nullable', 'string'],
            'resolution_agreement' => ['required_if:form_type,'.EmployeeRelation::MEDIATION, 'nullable', 'string'],
            'action_items' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);
    }

    private function detailsFromRequest(array $validated): array
    {
        return collect($validated)
            ->except(['form_type', 'employee_id', 'filed_at', 'subject', 'status', 'hr_remarks', 'attachments'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function resolveEmployee(?int $employeeId): Employee
    {
        if ($this->canManageRelations() && $employeeId) {
            return Employee::with('department')->findOrFail($employeeId);
        }

        $employee = $this->currentEmployee();

        if (! $employee) {
            abort(403, 'No employee profile is linked to your user email.');
        }

        return $employee;
    }

    private function currentEmployee(): ?Employee
    {
        $user = Auth::user();

        return Employee::with('department')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user?->id ?: 0)
                    ->orWhere('email', $user?->email)
                    ->orWhere('work_email', $user?->email)
                    ->orWhere('company_email', $user?->email);
            })
            ->first();
    }

    private function canManageRelations(): bool
    {
        $user = Auth::user();

        return $user->isSuperAdmin() || $user->isAdmin() || $user->hasPermission('access_hc_employee_relations');
    }

    private function authorizeManagement(): void
    {
        if (! $this->canManageRelations()) {
            abort(403, 'Only admins can manage employee relations records.');
        }
    }

    private function authorizeRelationAccess(EmployeeRelation $relation, bool $editing = false): void
    {
        if ($this->canManageRelations()) {
            return;
        }

        $employee = $this->currentEmployee();

        if (! $employee || $relation->employee_id !== $employee->id || ($editing && $relation->status !== 'Pending')) {
            abort(403);
        }
    }

    private function storeAttachments(Request $request): array
    {
        $attachments = [];

        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('employee-relations/attachments', 'public');

            $attachments[] = [
                'path' => $path,
                'url' => Storage::url($path),
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        return $attachments;
    }

    private function generateReferenceNo(string $formType): string
    {
        $prefix = match ($formType) {
            EmployeeRelation::INCIDENT => 'IRF',
            EmployeeRelation::GRIEVANCE => 'EGF',
            EmployeeRelation::MEDIATION => 'MRF',
            default => 'ERF',
        };

        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }

    private function formatRelation(EmployeeRelation $relation): array
    {
        return [
            'id' => $relation->id,
            'reference_no' => $relation->reference_no,
            'form_type' => $relation->form_type,
            'form_type_label' => $relation->form_type_label,
            'employee_id' => $relation->employee_id,
            'employee_code' => $relation->employee_code,
            'employee_name' => $relation->employee_name,
            'position' => $relation->position,
            'department' => $relation->department,
            'filed_at' => optional($relation->filed_at)->format('Y-m-d'),
            'subject' => $relation->subject,
            'details' => $relation->details ?? [],
            'attachment_paths' => $relation->attachment_paths ?? [],
            'hr_remarks' => $relation->hr_remarks,
            'status' => $relation->status,
        ];
    }
}
