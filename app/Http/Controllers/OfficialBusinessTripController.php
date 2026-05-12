<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OfficialBusinessTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfficialBusinessTripController extends Controller
{
    public function index()
    {
        $employees = Employee::with('department')
            ->orderBy('last_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'phone_number' => $employee->phone_number,
                    'position' => $employee->position,
                    'department' => $employee->department?->department_name,
                ];
            })
            ->values();

        $trips = OfficialBusinessTrip::latest()
            ->get()
            ->map(fn ($trip) => $this->formatTrip($trip))
            ->values();

        return view('human-capital.obf', [
            'employees' => $employees,
            'trips' => $trips,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateObf($request);

        $employee = Employee::with('department')->findOrFail($validated['employee_id']);

        OfficialBusinessTrip::create([
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

        return redirect()->route('human-capital.obf')->with('success', 'OBF request submitted successfully.');
    }

    public function update(Request $request, OfficialBusinessTrip $officialBusinessTrip)
    {
        $validated = $this->validateObf($request, true);

        $existingAttachments = $officialBusinessTrip->attachment_paths ?? [];
        $newAttachments = $this->storeAttachments($request);

        $officialBusinessTrip->update([
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
        ]);

        return redirect()->route('human-capital.obf')->with('success', 'OBF request updated successfully.');
    }

    public function approve(OfficialBusinessTrip $officialBusinessTrip)
    {
        $officialBusinessTrip->update(['status' => 'Approved']);

        return redirect()->route('human-capital.obf')->with('success', 'OBF request approved.');
    }

    public function reject(Request $request, OfficialBusinessTrip $officialBusinessTrip)
    {
        $request->validate(['remarks' => ['nullable', 'string']]);

        $officialBusinessTrip->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks ?: $officialBusinessTrip->remarks,
        ]);

        return redirect()->route('human-capital.obf')->with('success', 'OBF request rejected.');
    }

    public function destroy(OfficialBusinessTrip $officialBusinessTrip)
    {
        foreach (($officialBusinessTrip->attachment_paths ?? []) as $attachment) {
            if (!empty($attachment['path'])) {
                Storage::disk('public')->delete($attachment['path']);
            }
        }

        $officialBusinessTrip->delete();

        return redirect()->route('human-capital.obf')->with('success', 'OBF request deleted successfully.');
    }

    private function validateObf(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'employee_id' => [$isUpdate ? 'nullable' : 'required', 'exists:employees,id'],

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
}
