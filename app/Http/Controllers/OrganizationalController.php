<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Division;
use App\Models\Office;
use App\Models\OrganizationalAddress;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationalController extends Controller
{
    public function index()
    {
        $addresses = OrganizationalAddress::latest()->get()->map(fn ($item) => $this->transformAddress($item));

        $branches = Branch::with('address')->latest()->get()->map(fn ($item) => $this->transformBranch($item));

        $offices = Office::with(['branch.address', 'address'])->latest()->get()->map(fn ($item) => $this->transformOffice($item));

        $departments = Department::with(['office.branch.address', 'address'])->latest()->get()->map(fn ($item) => $this->transformDepartment($item));

        $divisions = Division::with(['department.office.branch.address', 'address'])->latest()->get()->map(fn ($item) => $this->transformDivision($item));

        $units = Unit::with(['division.department.office.branch.address', 'address'])->latest()->get()->map(fn ($item) => $this->transformUnit($item));

        $positions = Position::with(['unit.division.department.office.branch.address', 'address'])->latest()->get()->map(fn ($item) => $this->transformPosition($item));

        return view('human-capital.organizational', [
            'addresses' => $addresses->values(),
            'branches' => $branches->values(),
            'offices' => $offices->values(),
            'departments' => $departments->values(),
            'divisions' => $divisions->values(),
            'units' => $units->values(),
            'positions' => $positions->values(),

            'addressOptions' => OrganizationalAddress::orderBy('full_address')->get(['id', 'full_address']),
            'branchOptions' => Branch::orderBy('branch_name')->get(['id', 'branch_name', 'address_id']),
            'officeOptions' => Office::orderBy('office_name')->get(['id', 'office_name', 'branch_id', 'address_id']),
            'departmentOptions' => Department::orderBy('department_name')->get(['id', 'department_name', 'office_id', 'address_id']),
            'divisionOptions' => Division::orderBy('division_name')->get(['id', 'division_name', 'department_id', 'address_id']),
            'unitOptions' => Unit::orderBy('unit_name')->get(['id', 'unit_name', 'division_id', 'address_id']),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        if (!in_array($type, ['address', 'branch', 'office', 'department', 'division', 'unit', 'position'])) {
            return response()->json([
                'message' => 'Invalid type.',
            ], 422);
        }

        switch ($type) {
            case 'address':
                $validated = $request->validate([
                    'country' => ['required', 'string', 'max:255'],
                    'region_code' => ['required', 'string', 'max:20'],
                    'region_name' => ['required', 'string', 'max:255'],
                    'province_code' => ['nullable', 'string', 'max:20'],
                    'province_name' => ['nullable', 'string', 'max:255'],
                    'province_type' => ['nullable', 'string', 'max:50'],
                    'city_code' => ['required', 'string', 'max:20'],
                    'city_name' => ['required', 'string', 'max:255'],
                    'barangay_code' => ['required', 'string', 'max:20'],
                    'barangay_name' => ['required', 'string', 'max:255'],
                    'street_address' => ['required', 'string'],
                    'subdivision_building' => ['nullable', 'string', 'max:255'],
                    'unit_no' => ['nullable', 'string', 'max:255'],
                    'postal_code' => ['nullable', 'string', 'max:20'],
                ]);

                $validated['full_address'] = $this->buildFullAddress($validated);

                $record = OrganizationalAddress::create($validated);

                return response()->json([
                    'message' => 'Address saved successfully.',
                    'record' => $this->transformAddress($record),
                ]);

            case 'branch':
                $validated = $request->validate([
                    'branch_name' => ['required', 'string', 'max:255'],
                    'address_id' => ['required', Rule::exists('organizational_addresses', 'id')],
                    'branch_head' => ['required', 'string', 'max:255'],
                ]);

                $record = Branch::create([
                    'branch_name' => $validated['branch_name'],
                    'address_id' => $validated['address_id'],
                    'branch_head' => $validated['branch_head'],
                ])->load('address');

                return response()->json([
                    'message' => 'Branch saved successfully.',
                    'record' => $this->transformBranch($record),
                ]);

            case 'office':
                $validated = $request->validate([
                    'office_name' => ['required', 'string', 'max:255'],
                    'branch_id' => ['required', Rule::exists('branches', 'id')],
                    'office_head' => ['required', 'string', 'max:255'],
                ]);

                $branch = Branch::findOrFail($validated['branch_id']);

                $record = Office::create([
                    'office_name' => $validated['office_name'],
                    'branch_id' => $branch->id,
                    'address_id' => $branch->address_id,
                    'office_head' => $validated['office_head'],
                ])->load(['branch.address', 'address']);

                return response()->json([
                    'message' => 'Office saved successfully.',
                    'record' => $this->transformOffice($record),
                ]);

            case 'department':
                $validated = $request->validate([
                    'department_name' => ['required', 'string', 'max:255'],
                    'office_id' => ['required', Rule::exists('offices', 'id')],
                    'department_head' => ['required', 'string', 'max:255'],
                ]);

                $office = Office::findOrFail($validated['office_id']);

                $record = Department::create([
                    'department_name' => $validated['department_name'],
                    'office_id' => $office->id,
                    'address_id' => $office->address_id,
                    'department_head' => $validated['department_head'],
                ])->load(['office.branch.address', 'address']);

                return response()->json([
                    'message' => 'Department saved successfully.',
                    'record' => $this->transformDepartment($record),
                ]);

            case 'division':
                $validated = $request->validate([
                    'division_name' => ['required', 'string', 'max:255'],
                    'department_id' => ['required', Rule::exists('departments', 'id')],
                    'division_head' => ['required', 'string', 'max:255'],
                ]);

                $department = Department::findOrFail($validated['department_id']);

                $record = Division::create([
                    'division_name' => $validated['division_name'],
                    'department_id' => $department->id,
                    'address_id' => $department->address_id,
                    'division_head' => $validated['division_head'],
                ])->load(['department.office.branch.address', 'address']);

                return response()->json([
                    'message' => 'Division saved successfully.',
                    'record' => $this->transformDivision($record),
                ]);

            case 'unit':
                $validated = $request->validate([
                    'unit_name' => ['required', 'string', 'max:255'],
                    'division_id' => ['required', Rule::exists('divisions', 'id')],
                    'unit_head' => ['required', 'string', 'max:255'],
                ]);

                $division = Division::findOrFail($validated['division_id']);

                $record = Unit::create([
                    'unit_name' => $validated['unit_name'],
                    'division_id' => $division->id,
                    'address_id' => $division->address_id,
                    'unit_head' => $validated['unit_head'],
                ])->load(['division.department.office.branch.address', 'address']);

                return response()->json([
                    'message' => 'Unit saved successfully.',
                    'record' => $this->transformUnit($record),
                ]);

            case 'position':
                $validated = $request->validate([
                    'position_name' => ['required', 'string', 'max:255'],
                    'unit_id' => ['required', Rule::exists('units', 'id')],
                ]);

                $unit = Unit::findOrFail($validated['unit_id']);

                $record = Position::create([
                    'position_name' => $validated['position_name'],
                    'unit_id' => $unit->id,
                    'address_id' => $unit->address_id,
                ])->load(['unit.division.department.office.branch.address', 'address']);

                return response()->json([
                    'message' => 'Position saved successfully.',
                    'record' => $this->transformPosition($record),
                ]);
        }

        return response()->json([
            'message' => 'Unable to save record.',
        ], 422);
    }


    public function update(Request $request, string $type, int $id)
    {
        if (!in_array($type, ['address', 'branch', 'office', 'department', 'division', 'unit', 'position'])) {
            return response()->json([
                'message' => 'Invalid type.',
            ], 422);
        }

        switch ($type) {
            case 'address':
                $record = OrganizationalAddress::findOrFail($id);

                $validated = $request->validate([
                    'country' => ['required', 'string', 'max:255'],
                    'region_code' => ['required', 'string', 'max:20'],
                    'region_name' => ['required', 'string', 'max:255'],
                    'province_code' => ['nullable', 'string', 'max:20'],
                    'province_name' => ['nullable', 'string', 'max:255'],
                    'province_type' => ['nullable', 'string', 'max:50'],
                    'city_code' => ['required', 'string', 'max:20'],
                    'city_name' => ['required', 'string', 'max:255'],
                    'barangay_code' => ['required', 'string', 'max:20'],
                    'barangay_name' => ['required', 'string', 'max:255'],
                    'street_address' => ['required', 'string'],
                    'subdivision_building' => ['nullable', 'string', 'max:255'],
                    'unit_no' => ['nullable', 'string', 'max:255'],
                    'postal_code' => ['nullable', 'string', 'max:20'],
                ]);

                $validated['full_address'] = $this->buildFullAddress($validated);
                $record->update($validated);

                return response()->json([
                    'message' => 'Address updated successfully.',
                    'record' => $this->transformAddress($record->fresh()),
                ]);

            case 'branch':
                $record = Branch::findOrFail($id);

                $validated = $request->validate([
                    'branch_name' => ['required', 'string', 'max:255'],
                    'address_id' => ['required', Rule::exists('organizational_addresses', 'id')],
                    'branch_head' => ['required', 'string', 'max:255'],
                ]);

                $record->update([
                    'branch_name' => $validated['branch_name'],
                    'address_id' => $validated['address_id'],
                    'branch_head' => $validated['branch_head'],
                ]);

                return response()->json([
                    'message' => 'Branch updated successfully.',
                    'record' => $this->transformBranch($record->fresh()->load('address')),
                ]);

            case 'office':
                $record = Office::findOrFail($id);

                $validated = $request->validate([
                    'office_name' => ['required', 'string', 'max:255'],
                    'branch_id' => ['required', Rule::exists('branches', 'id')],
                    'office_head' => ['required', 'string', 'max:255'],
                ]);

                $branch = Branch::findOrFail($validated['branch_id']);

                $record->update([
                    'office_name' => $validated['office_name'],
                    'branch_id' => $branch->id,
                    'address_id' => $branch->address_id,
                    'office_head' => $validated['office_head'],
                ]);

                return response()->json([
                    'message' => 'Office updated successfully.',
                    'record' => $this->transformOffice($record->fresh()->load(['branch.address', 'address'])),
                ]);

            case 'department':
                $record = Department::findOrFail($id);

                $validated = $request->validate([
                    'department_name' => ['required', 'string', 'max:255'],
                    'office_id' => ['required', Rule::exists('offices', 'id')],
                    'department_head' => ['required', 'string', 'max:255'],
                ]);

                $office = Office::findOrFail($validated['office_id']);

                $record->update([
                    'department_name' => $validated['department_name'],
                    'office_id' => $office->id,
                    'address_id' => $office->address_id,
                    'department_head' => $validated['department_head'],
                ]);

                return response()->json([
                    'message' => 'Department updated successfully.',
                    'record' => $this->transformDepartment($record->fresh()->load(['office.branch.address', 'address'])),
                ]);

            case 'division':
                $record = Division::findOrFail($id);

                $validated = $request->validate([
                    'division_name' => ['required', 'string', 'max:255'],
                    'department_id' => ['required', Rule::exists('departments', 'id')],
                    'division_head' => ['required', 'string', 'max:255'],
                ]);

                $department = Department::findOrFail($validated['department_id']);

                $record->update([
                    'division_name' => $validated['division_name'],
                    'department_id' => $department->id,
                    'address_id' => $department->address_id,
                    'division_head' => $validated['division_head'],
                ]);

                return response()->json([
                    'message' => 'Division updated successfully.',
                    'record' => $this->transformDivision($record->fresh()->load(['department.office.branch.address', 'address'])),
                ]);

            case 'unit':
                $record = Unit::findOrFail($id);

                $validated = $request->validate([
                    'unit_name' => ['required', 'string', 'max:255'],
                    'division_id' => ['required', Rule::exists('divisions', 'id')],
                    'unit_head' => ['required', 'string', 'max:255'],
                ]);

                $division = Division::findOrFail($validated['division_id']);

                $record->update([
                    'unit_name' => $validated['unit_name'],
                    'division_id' => $division->id,
                    'address_id' => $division->address_id,
                    'unit_head' => $validated['unit_head'],
                ]);

                return response()->json([
                    'message' => 'Unit updated successfully.',
                    'record' => $this->transformUnit($record->fresh()->load(['division.department.office.branch.address', 'address'])),
                ]);

            case 'position':
                $record = Position::findOrFail($id);

                $validated = $request->validate([
                    'position_name' => ['required', 'string', 'max:255'],
                    'unit_id' => ['required', Rule::exists('units', 'id')],
                ]);

                $unit = Unit::findOrFail($validated['unit_id']);

                $record->update([
                    'position_name' => $validated['position_name'],
                    'unit_id' => $unit->id,
                    'address_id' => $unit->address_id,
                ]);

                return response()->json([
                    'message' => 'Position updated successfully.',
                    'record' => $this->transformPosition($record->fresh()->load(['unit.division.department.office.branch.address', 'address'])),
                ]);
        }

        return response()->json([
            'message' => 'Unable to update record.',
        ], 422);
    }

    public function destroy(string $type, int $id)
    {
        if (!in_array($type, ['address', 'branch', 'office', 'department', 'division', 'unit', 'position'])) {
            return response()->json([
                'message' => 'Invalid type.',
            ], 422);
        }

        $check = $this->deleteBlockMessage($type, $id);
        if ($check !== null) {
            return response()->json([
                'message' => $check,
            ], 422);
        }

        switch ($type) {
            case 'address':
                OrganizationalAddress::findOrFail($id)->delete();
                break;
            case 'branch':
                Branch::findOrFail($id)->delete();
                break;
            case 'office':
                Office::findOrFail($id)->delete();
                break;
            case 'department':
                Department::findOrFail($id)->delete();
                break;
            case 'division':
                Division::findOrFail($id)->delete();
                break;
            case 'unit':
                Unit::findOrFail($id)->delete();
                break;
            case 'position':
                Position::findOrFail($id)->delete();
                break;
        }

        return response()->json([
            'message' => 'Record deleted successfully.',
        ]);
    }

    private function deleteBlockMessage(string $type, int $id): ?string
    {
        return match ($type) {
            'address' => (
                Branch::where('address_id', $id)->exists()
                || Office::where('address_id', $id)->exists()
                || Department::where('address_id', $id)->exists()
                || Division::where('address_id', $id)->exists()
                || Unit::where('address_id', $id)->exists()
                || Position::where('address_id', $id)->exists()
            ) ? 'This address is still used by another organizational record.' : null,

            'branch' => Office::where('branch_id', $id)->exists()
                ? 'This branch is still used by an office.'
                : null,

            'office' => Department::where('office_id', $id)->exists()
                ? 'This office is still used by a department.'
                : null,

            'department' => Division::where('department_id', $id)->exists()
                ? 'This department is still used by a division.'
                : null,

            'division' => Unit::where('division_id', $id)->exists()
                ? 'This division is still used by a unit.'
                : null,

            'unit' => Position::where('unit_id', $id)->exists()
                ? 'This unit is still used by a position.'
                : null,

            default => null,
        };
    }

    private function buildFullAddress(array $data): string
    {
        $parts = array_filter([
            $data['unit_no'] ?? null,
            $data['subdivision_building'] ?? null,
            $data['street_address'] ?? null,
            $data['barangay_name'] ?? null,
            $data['city_name'] ?? null,
            $data['province_name'] ?? null,
            $data['region_name'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
        ]);

        return implode(', ', $parts);
    }

    private function transformAddress(OrganizationalAddress $item): array
    {
        return [
            'id' => $item->id,
            'country' => $item->country,
            'region_code' => $item->region_code,
            'region_name' => $item->region_name,
            'province_code' => $item->province_code,
            'province_name' => $item->province_name,
            'province_type' => $item->province_type,
            'city_code' => $item->city_code,
            'city_name' => $item->city_name,
            'barangay_code' => $item->barangay_code,
            'barangay_name' => $item->barangay_name,
            'street_address' => $item->street_address,
            'subdivision_building' => $item->subdivision_building,
            'unit_no' => $item->unit_no,
            'postal_code' => $item->postal_code,
            'full_address' => $item->full_address,
        ];
    }

    private function transformBranch(Branch $item): array
    {
        return [
            'id' => $item->id,
            'branch_name' => $item->branch_name,
            'address_id' => $item->address_id,
            'address' => $item->address?->full_address,
            'branch_head' => $item->branch_head,
        ];
    }

    private function transformOffice(Office $item): array
    {
        return [
            'id' => $item->id,
            'office_name' => $item->office_name,
            'branch_id' => $item->branch_id,
            'branch_name' => $item->branch?->branch_name,
            'address_id' => $item->address_id,
            'address' => $item->address?->full_address,
            'office_head' => $item->office_head,
        ];
    }

    private function transformDepartment(Department $item): array
    {
        return [
            'id' => $item->id,
            'department_name' => $item->department_name,
            'office_id' => $item->office_id,
            'office_name' => $item->office?->office_name,
            'branch_name' => $item->office?->branch?->branch_name,
            'address_id' => $item->address_id,
            'address' => $item->address?->full_address,
            'department_head' => $item->department_head,
        ];
    }

    private function transformDivision(Division $item): array
    {
        return [
            'id' => $item->id,
            'division_name' => $item->division_name,
            'department_id' => $item->department_id,
            'department_name' => $item->department?->department_name,
            'office_name' => $item->department?->office?->office_name,
            'branch_name' => $item->department?->office?->branch?->branch_name,
            'address_id' => $item->address_id,
            'address' => $item->address?->full_address,
            'division_head' => $item->division_head,
        ];
    }

    private function transformUnit(Unit $item): array
    {
        return [
            'id' => $item->id,
            'unit_name' => $item->unit_name,
            'division_id' => $item->division_id,
            'division_name' => $item->division?->division_name,
            'department_name' => $item->division?->department?->department_name,
            'office_name' => $item->division?->department?->office?->office_name,
            'branch_name' => $item->division?->department?->office?->branch?->branch_name,
            'address_id' => $item->address_id,
            'address' => $item->address?->full_address,
            'unit_head' => $item->unit_head,
        ];
    }

    private function transformPosition(Position $item): array
    {
        return [
            'id' => $item->id,
            'position_name' => $item->position_name,
            'unit_id' => $item->unit_id,
            'unit_name' => $item->unit?->unit_name,
            'division_name' => $item->unit?->division?->division_name,
            'department_name' => $item->unit?->division?->department?->department_name,
            'office_name' => $item->unit?->division?->department?->office?->office_name,
            'branch_name' => $item->unit?->division?->department?->office?->branch?->branch_name,
            'address_id' => $item->address_id,
            'address' => $item->address?->full_address,
        ];
    }
}