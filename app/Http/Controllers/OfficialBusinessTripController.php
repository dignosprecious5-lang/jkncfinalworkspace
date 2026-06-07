<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
use App\Http\Controllers\Concerns\ScopesHumanCapitalRecords;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\OfficialBusinessTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfficialBusinessTripController extends Controller
{
    use RequestsHumanCapitalApproval;
    use ScopesHumanCapitalRecords;

    public function index()
    {
        $canManageObf = $this->canManageObf();
        $currentEmployee = $this->currentEmployee();

        $employeeQuery = $canManageObf
            ? Employee::with('department')->orderBy('last_name')
            : Employee::with('department')->where('id', $currentEmployee?->id ?: 0);

        $employees = $employeeQuery->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'email' => $employee->work_email ?: $employee->email,
                    'personal_email' => $employee->personal_email ?? $employee->email,
                    'work_email' => $employee->work_email ?? null,
                    'phone_number' => $employee->phone_number,
                    'position' => $employee->position,
                    'department' => $employee->department?->department_name,
                ];
            })
            ->values();

        $contacts = Contact::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($contact) {
                $fullName = trim(implode(' ', array_filter([
                    $contact->first_name,
                    $contact->middle_initial,
                    $contact->last_name,
                    $contact->name_extension,
                ])));

                $clientType = 'Local';
                $internationalHints = strtolower(implode(' ', array_filter([
                    $contact->ownership_flag,
                    $contact->foreign_business_nature,
                    $contact->organization_type,
                ])));

                if (str_contains($internationalHints, 'foreign') || str_contains($internationalHints, 'international')) {
                    $clientType = 'International';
                }

                return [
                    'id' => $contact->id,
                    'cif_no' => $contact->cif_no,
                    'full_name' => $fullName !== '' ? $fullName : 'Contact #'.$contact->id,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'company_name' => $contact->company_name,
                    'customer_type' => $contact->customer_type,
                    'client_status' => $contact->client_status,
                    'client_type' => $clientType,
                    'tin' => $contact->tin,
                    'contract_type' => $contact->client_status ? ucfirst((string) $contact->client_status) : null,
                    'label' => trim(($fullName !== '' ? $fullName : 'Contact #'.$contact->id).($contact->company_name ? ' - '.$contact->company_name : '').($contact->email ? ' ('.$contact->email.')' : '')),
                ];
            })
            ->values();

        $identity = $this->humanCapitalEmployeeIdentity($currentEmployee, Auth::user());

        $trips = OfficialBusinessTrip::latest()
            ->when(! $canManageObf, function ($query) use ($identity) {
                $this->applyEmployeeIdentityScope(
                    $query,
                    $identity,
                    ['employee_id'],
                    ['created_by'],
                    ['employee_name']
                );

                if (! empty($identity['name'])) {
                    $query->orWhereJsonContains('team_members', $identity['name']);
                }
            })
            ->get()
            ->map(fn ($trip) => $this->formatTrip($trip))
            ->values();

        return view('human-capital.obf', [
            'employees' => $employees,
            'contacts' => $contacts,
            'trips' => $trips,
            'canManageObf' => $canManageObf,
            'currentEmployee' => $currentEmployee,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateObf($request);

        $employee = $this->resolveEmployee($validated['employee_id'] ?? null);

        $trip = OfficialBusinessTrip::create([
            'ob_reference_no' => $this->generateReferenceNo(),

            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'employee_name' => $employee->full_name,
            'position' => $employee->position,
            'department' => $employee->department?->department_name,
            'immediate_superior' => $validated['immediate_superior'] ?? null,
            'superior_email' => $validated['superior_email'] ?? null,

            'destination' => $validated['destination'],
            'additional_stops' => $validated['additional_stops'] ?? null,
            'purpose' => $validated['purpose'],
            'purpose_options' => $validated['purpose_options'] ?? [],
            'purpose_other' => $validated['purpose_other'] ?? null,
            'trip_type' => $validated['trip_type'] ?? null,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'] ?? null,
            'departure_time' => $validated['departure_time'] ?? null,
            'return_time' => $validated['return_time'] ?? null,
            'nature_of_travel' => $validated['nature_of_travel'] ?? null,
            'team_members' => $this->filterArray($validated['team_members'] ?? []),

            'is_client_travel' => (bool) ($validated['is_client_travel'] ?? false),
            'client_type' => $validated['client_type'] ?? null,
            'client_id_no' => $validated['client_id_no'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'client_contract_number' => $validated['client_contract_number'] ?? null,
            'contract_type' => $validated['contract_type'] ?? null,
            'travel_billability' => $validated['travel_billability'] ?? null,

            'client_payment_status' => $validated['client_payment_status'] ?? null,
            'client_payment_items' => $validated['client_payment_items'] ?? [],
            'client_payment_other' => $validated['client_payment_other'] ?? null,
            'amount_client_will_pay' => $validated['amount_client_will_pay'] ?? 0,

            'travel_credit_details' => [
                'monthly_limit' => $validated['travel_credit_monthly_limit'] ?? null,
                'used_this_month' => $validated['travel_credit_used'] ?? null,
                'needed_for_trip' => $validated['travel_credit_needed'] ?? null,
                'remaining_after_trip' => $validated['travel_credit_remaining'] ?? null,
            ],

            'transportation_mode' => $validated['transportation_mode'] ?? null,
            'transportation_details' => [
                'company_vehicle' => $validated['company_vehicle'] ?? null,
                'assigned_driver' => $validated['assigned_driver'] ?? null,
                'vehicle_details' => $validated['vehicle_details'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'odometer' => $validated['odometer'] ?? null,
                'fuel_level' => $validated['fuel_level'] ?? null,
                'private_vehicle_details' => $validated['private_vehicle_details'] ?? null,
                'public_transport_details' => $validated['public_transport_details'] ?? null,
                'air_travel_details' => $validated['air_travel_details'] ?? null,
                'sea_travel_details' => $validated['sea_travel_details'] ?? null,
            ],
            'estimated_expenses' => $validated['estimated_expenses'] ?? 0,

            'attachment_paths' => $this->storeAttachments($request),
            'attachment_types' => $validated['attachment_types'] ?? [],
            'attachment_other' => $validated['attachment_other'] ?? null,

            'remarks' => $validated['remarks'] ?? null,
            'status' => 'Pending',
            'created_by' => Auth::id(),
        ]);

        $this->notifyHumanCapitalAdmins(
            title: 'Official Business Trip Form submitted',
            message: ($trip->employee_name ?: 'An employee') . ' submitted an Official Business Trip Form for approval.',
            module: 'Official Business Trip Form',
            recordTitle: $trip->ob_reference_no ?: ($trip->destination ?: 'Official Business Trip'),
            actorName: Auth::user()?->name ?? Auth::user()?->email ?? 'System User'
        );

        return redirect()->route('human-capital.obf')->with('success', 'OBF request submitted successfully.');
    }

    public function update(Request $request, OfficialBusinessTrip $officialBusinessTrip)
    {
        $this->authorizeTripAccess($officialBusinessTrip, true);

        $validated = $this->validateObf($request, true);

        $existingAttachments = $officialBusinessTrip->attachment_paths ?? [];
        $newAttachments = $this->storeAttachments($request);

        $payload = [
            'immediate_superior' => $validated['immediate_superior'] ?? null,
            'superior_email' => $validated['superior_email'] ?? null,

            'destination' => $validated['destination'],
            'additional_stops' => $validated['additional_stops'] ?? null,
            'purpose' => $validated['purpose'],
            'purpose_options' => $validated['purpose_options'] ?? [],
            'purpose_other' => $validated['purpose_other'] ?? null,
            'trip_type' => $validated['trip_type'] ?? null,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'] ?? null,
            'departure_time' => $validated['departure_time'] ?? null,
            'return_time' => $validated['return_time'] ?? null,
            'nature_of_travel' => $validated['nature_of_travel'] ?? null,
            'team_members' => $this->filterArray($validated['team_members'] ?? []),

            'is_client_travel' => (bool) ($validated['is_client_travel'] ?? false),
            'client_type' => $validated['client_type'] ?? null,
            'client_id_no' => $validated['client_id_no'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'client_contract_number' => $validated['client_contract_number'] ?? null,
            'contract_type' => $validated['contract_type'] ?? null,
            'travel_billability' => $validated['travel_billability'] ?? null,

            'client_payment_status' => $validated['client_payment_status'] ?? null,
            'client_payment_items' => $validated['client_payment_items'] ?? [],
            'client_payment_other' => $validated['client_payment_other'] ?? null,
            'amount_client_will_pay' => $validated['amount_client_will_pay'] ?? 0,

            'travel_credit_details' => [
                'monthly_limit' => $validated['travel_credit_monthly_limit'] ?? null,
                'used_this_month' => $validated['travel_credit_used'] ?? null,
                'needed_for_trip' => $validated['travel_credit_needed'] ?? null,
                'remaining_after_trip' => $validated['travel_credit_remaining'] ?? null,
            ],

            'transportation_mode' => $validated['transportation_mode'] ?? null,
            'transportation_details' => [
                'company_vehicle' => $validated['company_vehicle'] ?? null,
                'assigned_driver' => $validated['assigned_driver'] ?? null,
                'vehicle_details' => $validated['vehicle_details'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'odometer' => $validated['odometer'] ?? null,
                'fuel_level' => $validated['fuel_level'] ?? null,
                'private_vehicle_details' => $validated['private_vehicle_details'] ?? null,
                'public_transport_details' => $validated['public_transport_details'] ?? null,
                'air_travel_details' => $validated['air_travel_details'] ?? null,
                'sea_travel_details' => $validated['sea_travel_details'] ?? null,
            ],
            'estimated_expenses' => $validated['estimated_expenses'] ?? 0,

            'attachment_paths' => array_values(array_merge($existingAttachments, $newAttachments)),
            'attachment_types' => $validated['attachment_types'] ?? [],
            'attachment_other' => $validated['attachment_other'] ?? null,

            'remarks' => $validated['remarks'] ?? null,
        ];

        $this->requestHumanCapitalChange($request, 'Official Business Trip Form', 'update', $officialBusinessTrip, $payload, $officialBusinessTrip->ob_reference_no ?: $officialBusinessTrip->employee_name);

        return redirect()->route('human-capital.obf')->with('success', 'OBF update submitted for admin approval.');
    }

    public function approve(OfficialBusinessTrip $officialBusinessTrip)
    {
        abort_unless($this->canManageObf(), 403);

        $officialBusinessTrip->update(['status' => 'Approved']);

        if ($officialBusinessTrip->created_by) {
            \App\Models\User::find($officialBusinessTrip->created_by)?->notify(new \App\Notifications\HumanCapitalWorkflowNotification(
                'Official Business Trip Form approved',
                'Your Official Business Trip Form has been approved.',
                route('human-capital.obf'),
                'Official Business Trip Form',
                $officialBusinessTrip->ob_reference_no ?: $officialBusinessTrip->destination,
                Auth::user()?->name ?? Auth::user()?->email ?? ''
            ));
        }

        return redirect()->route('human-capital.obf')->with('success', 'OBF request approved.');
    }

    public function reject(Request $request, OfficialBusinessTrip $officialBusinessTrip)
    {
        abort_unless($this->canManageObf(), 403);

        $request->validate(['remarks' => ['nullable', 'string']]);

        $officialBusinessTrip->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks ?: $officialBusinessTrip->remarks,
        ]);

        if ($officialBusinessTrip->created_by) {
            $message = 'Your Official Business Trip Form has been rejected.';
            if ($request->filled('remarks')) {
                $message .= ' Note: ' . $request->remarks;
            }

            \App\Models\User::find($officialBusinessTrip->created_by)?->notify(new \App\Notifications\HumanCapitalWorkflowNotification(
                'Official Business Trip Form rejected',
                $message,
                route('human-capital.obf'),
                'Official Business Trip Form',
                $officialBusinessTrip->ob_reference_no ?: $officialBusinessTrip->destination,
                Auth::user()?->name ?? Auth::user()?->email ?? ''
            ));
        }

        return redirect()->route('human-capital.obf')->with('success', 'OBF request rejected.');
    }

    public function destroy(Request $request, OfficialBusinessTrip $officialBusinessTrip)
    {
        $this->authorizeTripAccess($officialBusinessTrip, true);

        $this->requestHumanCapitalChange($request, 'Official Business Trip Form', 'delete', $officialBusinessTrip, null, $officialBusinessTrip->ob_reference_no ?: $officialBusinessTrip->employee_name);

        return redirect()->route('human-capital.obf')->with('success', 'OBF deletion submitted for admin approval.');
    }

    private function validateObf(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'employee_id' => [$isUpdate || ! $this->canManageObf() ? 'nullable' : 'required', 'nullable', 'exists:employees,id'],

            'immediate_superior' => ['nullable', 'string', 'max:255'],
            'superior_email' => ['nullable', 'email', 'max:255'],

            'destination' => ['required', 'string', 'max:255'],
            'additional_stops' => ['nullable', 'string'],
            'purpose' => ['required', 'string'],
            'purpose_options' => ['nullable', 'array'],
            'purpose_options.*' => ['nullable', 'string', 'max:255'],
            'purpose_other' => ['nullable', 'string', 'max:255'],
            'trip_type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'departure_time' => ['nullable'],
            'return_time' => ['nullable'],
            'nature_of_travel' => ['nullable', 'string', 'max:255'],
            'team_members' => ['nullable', 'array'],
            'team_members.*' => ['nullable', 'string', 'max:255'],

            'is_client_travel' => ['nullable'],
            'client_type' => ['nullable', 'string', 'max:255'],
            'client_id_no' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_contract_number' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['nullable', 'string', 'max:255'],
            'travel_billability' => ['nullable', 'string', 'max:255'],

            'client_payment_status' => ['nullable', 'string', 'max:255'],
            'client_payment_items' => ['nullable', 'array'],
            'client_payment_items.*' => ['nullable', 'string', 'max:255'],
            'client_payment_other' => ['nullable', 'string', 'max:255'],
            'amount_client_will_pay' => ['nullable', 'numeric', 'min:0'],

            'travel_credit_monthly_limit' => ['nullable', 'numeric', 'min:0'],
            'travel_credit_used' => ['nullable', 'numeric', 'min:0'],
            'travel_credit_needed' => ['nullable', 'numeric', 'min:0'],
            'travel_credit_remaining' => ['nullable', 'numeric', 'min:0'],

            'transportation_mode' => ['nullable', 'string', 'max:255'],
            'company_vehicle' => ['nullable', 'string', 'max:255'],
            'assigned_driver' => ['nullable', 'string', 'max:255'],
            'vehicle_details' => ['nullable', 'string', 'max:255'],
            'plate_number' => ['nullable', 'string', 'max:255'],
            'odometer' => ['nullable', 'string', 'max:255'],
            'fuel_level' => ['nullable', 'string', 'max:255'],
            'private_vehicle_details' => ['nullable', 'string', 'max:255'],
            'public_transport_details' => ['nullable', 'string', 'max:255'],
            'air_travel_details' => ['nullable', 'string', 'max:255'],
            'sea_travel_details' => ['nullable', 'string', 'max:255'],
            'estimated_expenses' => ['nullable', 'numeric', 'min:0'],

            'attachment_types' => ['nullable', 'array'],
            'attachment_types.*' => ['nullable', 'string', 'max:255'],
            'attachment_other' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],

            'remarks' => ['nullable', 'string'],
        ]);
    }

    private function storeAttachments(Request $request): array
    {
        $attachments = [];

        foreach ($request->file('attachments', []) as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('obf/attachments', 'public');

            $attachments[] = [
                'path' => $path,
                'url' => Storage::url($path),
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        return $attachments;
    }

    private function filterArray(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => filled($item))
            ->values()
            ->all();
    }

    private function generateReferenceNo(): string
    {
        return 'OBF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    private function formatTrip(OfficialBusinessTrip $trip): array
    {
        return [
            'id' => $trip->id,
            'ob_reference_no' => $trip->ob_reference_no,
            'employee_id' => $trip->employee_id,
            'employee_code' => $trip->employee_code,
            'employee_name' => $trip->employee_name,
            'position' => $trip->position,
            'department' => $trip->department,
            'immediate_superior' => $trip->immediate_superior,
            'superior_email' => $trip->superior_email,

            'destination' => $trip->destination,
            'additional_stops' => $trip->additional_stops,
            'purpose' => $trip->purpose,
            'purpose_options' => $trip->purpose_options ?? [],
            'purpose_other' => $trip->purpose_other,
            'trip_type' => $trip->trip_type,
            'date_from' => optional($trip->date_from)->format('Y-m-d'),
            'date_to' => optional($trip->date_to)->format('Y-m-d'),
            'departure_time' => $trip->departure_time,
            'return_time' => $trip->return_time,
            'nature_of_travel' => $trip->nature_of_travel,
            'team_members' => $trip->team_members ?? [],

            'is_client_travel' => $trip->is_client_travel,
            'client_type' => $trip->client_type,
            'client_id_no' => $trip->client_id_no,
            'client_name' => $trip->client_name,
            'client_email' => $trip->client_email,
            'client_contract_number' => $trip->client_contract_number,
            'contract_type' => $trip->contract_type,
            'travel_billability' => $trip->travel_billability,

            'client_payment_status' => $trip->client_payment_status,
            'client_payment_items' => $trip->client_payment_items ?? [],
            'client_payment_other' => $trip->client_payment_other,
            'amount_client_will_pay' => $trip->amount_client_will_pay,

            'travel_credit_details' => $trip->travel_credit_details ?? [],
            'transportation_mode' => $trip->transportation_mode,
            'transportation_details' => $trip->transportation_details ?? [],
            'estimated_expenses' => $trip->estimated_expenses,

            'attachment_paths' => $trip->attachment_paths ?? [],
            'attachment_types' => $trip->attachment_types ?? [],
            'attachment_other' => $trip->attachment_other,

            'remarks' => $trip->remarks,
            'status' => $trip->status,
        ];
    }

    private function canManageObf(): bool
    {
        $user = Auth::user();

        return $this->canManageHumanCapitalModule('access_hc_obf', true);
    }

    private function currentEmployee(): ?Employee
    {
        return $this->currentHumanCapitalEmployee(Auth::user());
    }

    private function resolveEmployee(?int $employeeId): Employee
    {
        if ($this->canManageObf() && $employeeId) {
            return Employee::with('department')->findOrFail($employeeId);
        }

        $employee = $this->currentEmployee();

        if (! $employee) {
            abort(403, 'No employee profile is linked to your account email.');
        }

        return $employee;
    }

    private function authorizeTripAccess(OfficialBusinessTrip $trip, bool $editing = false): void
    {
        if ($this->canManageObf()) {
            return;
        }

        $employee = $this->currentEmployee();

        $identity = $this->humanCapitalEmployeeIdentity($employee, Auth::user());
        $teamMembers = collect($trip->team_members ?? [])->map(fn ($item) => strtolower(trim((string) $item)));
        $isParticipant = ! empty($identity['name'])
            && $teamMembers->contains(strtolower(trim((string) $identity['name'])));

        if (! $employee || ($trip->employee_id !== $employee->id && $trip->created_by !== Auth::id() && ! $isParticipant) || ($editing && $trip->status !== 'Pending')) {
            abort(403);
        }
    }
}
