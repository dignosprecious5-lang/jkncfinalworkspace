<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DirectorOfficer;

class DirectorOfficerController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        DirectorOfficer::create($data);

        return back()->with('success', 'Director / Officer added successfully.');
    }

    public function update(Request $request, DirectorOfficer $record)
    {
        $data = $this->validatedData($request, false);

        $record->update($data);

        return back()->with('success', 'Director / Officer updated successfully.');
    }

    public function destroy(DirectorOfficer $record)
    {
        $record->delete();

        return back()->with('success', 'Director / Officer deleted successfully.');
    }

    private function validatedData(Request $request, bool $requireGis = true): array
    {
        $rules = [
            'officer_name'  => 'required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'address'       => 'required|string|max:255',
            'gender'        => 'required|in:M,F',
            'nationality'   => 'required|string|max:255',
            'incr'          => 'required|in:Y,N,0,1',
            'stockholder'   => 'required|in:Y,N,0,1',
            'board'         => 'required|in:C,M',
            'officer_type'  => 'required|string|max:255',
            'committee'     => 'nullable|string|max:255',
            'tin'           => 'nullable|string|max:255',
        ];

        if ($requireGis) {
            $rules['gis_id'] = 'required|exists:gis_records,id';
        }

        $validated = $request->validate($rules);

        $validated['incr'] = in_array((string) $request->incr, ['Y', '1'], true) ? 1 : 0;
        $validated['stockholder'] = in_array((string) $request->stockholder, ['Y', '1'], true) ? 1 : 0;

        return $validated;
    }
}
