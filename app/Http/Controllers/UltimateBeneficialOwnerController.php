<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\GisRecord;
use App\Models\UltimateBeneficialOwner;

class UltimateBeneficialOwnerController extends Controller
{
    private function canApproveCorporate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->hasPermission('approve_corporate');
    }

    private function canEditRecord(GisRecord $gis): bool
    {
        if ($this->canApproveCorporate()) {
            return true;
        }

        return (int) $gis->submitted_by === (int) Auth::id()
            && in_array($gis->workflow_status, ['Uploaded', 'Reverted']);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $gis = GisRecord::findOrFail($data['gis_id']);

        if (!$this->canEditRecord($gis)) {
            abort(403, 'This record can no longer be edited.');
        }

        UltimateBeneficialOwner::create($data);

        return back()->with('success', 'UBO added successfully.');
    }

    public function update(Request $request, UltimateBeneficialOwner $record)
    {
        $data = $this->validatedData($request, false);

        if (!$this->canEditRecord($record->gis)) {
            abort(403, 'This record can no longer be edited.');
        }

        $record->update($data);

        return back()->with('success', 'UBO updated successfully.');
    }

    public function destroy(UltimateBeneficialOwner $record)
    {
        if (!$this->canEditRecord($record->gis)) {
            abort(403, 'This record can no longer be edited.');
        }

        $record->delete();

        return back()->with('success', 'UBO deleted successfully.');
    }

    private function validatedData(Request $request, bool $requireGis = true): array
    {
        $rules = [
            'complete_name' => 'required|string|max:255',
            'specific_residential_address' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'tax_identification_no' => 'nullable|string|max:255',
            'ownership_voting_rights' => 'nullable|numeric|min:0|max:100',
            'beneficial_owner_type' => 'nullable|in:D,I',
            'beneficial_ownership_category' => 'nullable|in:A,B,C,D,E,F,G,H,I',
        ];

        if ($requireGis) {
            $rules['gis_id'] = 'required|exists:gis_records,id';
        }

        return $request->validate($rules);
    }
}
