<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stockholder;
use App\Models\AuthorizedCapitalStock;

class StockholderController extends Controller
{
    private function authorizedParValue(int $gisId, string $shareType): float
    {
        $authorized = AuthorizedCapitalStock::where('gis_id', $gisId)
            ->where('share_type', $shareType)
            ->first();

        if (! $authorized) {
            abort(422, 'Please add this Type of Shares in Authorized Capital Stock first.');
        }

        return (float) $authorized->par_value;
    }

    private function computedAmount(int $gisId, string $shareType, int|float $shares): float
    {
        return round(((float) $shares) * $this->authorizedParValue($gisId, $shareType), 2);
    }

    public function recalculateFromAuthorizedCapital(int $gisId): void
    {
        $this->recalculateStockholderOwnership($gisId);
    }

    private function recalculateStockholderOwnership(int $gisId): void
    {
        $rows = Stockholder::where('gis_id', $gisId)->get();
        $totalShares = (float) $rows->sum('shares');

        foreach ($rows as $row) {
            $computedAmount = $this->computedAmount(
                (int) $row->gis_id,
                (string) $row->share_type,
                (float) $row->shares
            );

            $row->update([
                'amount' => $computedAmount,
                'ownership_percentage' => $totalShares > 0
                    ? round(((float) $row->shares / $totalShares) * 100, 2)
                    : 0,
                'amount_paid' => min((float) $row->amount_paid, $computedAmount),
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'gis_id'           => 'required|exists:gis_records,id',
            'stockholder_name' => 'required|string|max:255',
            'address'          => 'required|string|max:255',
            'gender'           => 'required|in:M,F',
            'nationality'      => 'required|string|max:255',
            'incr'             => 'required|in:Y,N,0,1',
            'share_type'       => 'required|string|max:255',
            'shares'           => 'required|integer|min:0',
            'amount_paid'      => 'nullable|numeric|min:0',
            'tin'              => 'nullable|string|max:255',
        ]);

        $amount = $this->computedAmount(
            (int) $request->gis_id,
            (string) $request->share_type,
            (float) $request->shares
        );

        Stockholder::create([
            'gis_id'               => $request->gis_id,
            'stockholder_name'     => $request->stockholder_name,
            'address'              => $request->address,
            'gender'               => $request->gender,
            'nationality'          => $request->nationality,
            'incr'                 => in_array((string) $request->incr, ['Y', '1'], true) ? 1 : 0,
            'share_type'           => $request->share_type,
            'shares'               => $request->shares,
            'amount'               => $amount,
            'ownership_percentage' => 0,
            'amount_paid'          => min((float) $request->input('amount_paid', 0), $amount),
            'tin'                  => $request->tin,
        ]);

        $this->recalculateStockholderOwnership((int) $request->gis_id);

        return back()->with('success', 'Stockholder added successfully.');
    }

    public function update(Request $request, Stockholder $record)
    {
        $request->validate([
            'stockholder_name' => 'required|string|max:255',
            'address'          => 'required|string|max:255',
            'gender'           => 'required|in:M,F',
            'nationality'      => 'required|string|max:255',
            'incr'             => 'required|in:Y,N,0,1',
            'share_type'       => 'required|string|max:255',
            'shares'           => 'required|integer|min:0',
            'amount_paid'      => 'nullable|numeric|min:0',
            'tin'              => 'nullable|string|max:255',
        ]);

        $amount = $this->computedAmount(
            (int) $record->gis_id,
            (string) $request->share_type,
            (float) $request->shares
        );

        $record->update([
            'stockholder_name' => $request->stockholder_name,
            'address'          => $request->address,
            'gender'           => $request->gender,
            'nationality'      => $request->nationality,
            'incr'             => in_array((string) $request->incr, ['Y', '1'], true) ? 1 : 0,
            'share_type'       => $request->share_type,
            'shares'           => $request->shares,
            'amount'           => $amount,
            'amount_paid'      => min((float) $request->input('amount_paid', 0), $amount),
            'tin'              => $request->tin,
        ]);

        $this->recalculateStockholderOwnership((int) $record->gis_id);

        return back()->with('success', 'Stockholder updated successfully.');
    }

    public function destroy(Stockholder $record)
    {
        $gisId = (int) $record->gis_id;
        $record->delete();

        $this->recalculateStockholderOwnership($gisId);

        return back()->with('success', 'Stockholder deleted successfully.');
    }
}
