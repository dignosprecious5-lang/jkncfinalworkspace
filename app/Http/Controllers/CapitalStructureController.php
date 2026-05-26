<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuthorizedCapitalStock;
use App\Models\SubscribedCapital;
use App\Models\PaidUpCapital;
use App\Models\Stockholder;

class CapitalStructureController extends Controller
{
    private function money(float|int|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    private function calculateAmount($shares, $parValue): float
    {
        return $this->money(((float) $shares) * ((float) $parValue));
    }

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

    private function recalculateSubscribedOwnership(int $gisId): void
    {
        $rows = SubscribedCapital::where('gis_id', $gisId)->get();
        $totalShares = (float) $rows->sum('number_of_shares');

        foreach ($rows as $row) {
            $parValue = $this->authorizedParValue((int) $row->gis_id, (string) $row->share_type);

            $row->update([
                'par_value' => $parValue,
                'amount' => $this->calculateAmount($row->number_of_shares, $parValue),
                'ownership_percentage' => $totalShares > 0
                    ? round(((float) $row->number_of_shares / $totalShares) * 100, 2)
                    : 0,
            ]);
        }
    }

    private function recalculatePaidupOwnership(int $gisId): void
    {
        $rows = PaidUpCapital::where('gis_id', $gisId)->get();
        $totalShares = (float) $rows->sum('number_of_shares');

        foreach ($rows as $row) {
            $parValue = $this->authorizedParValue((int) $row->gis_id, (string) $row->share_type);

            $row->update([
                'par_value' => $parValue,
                'amount' => $this->calculateAmount($row->number_of_shares, $parValue),
                'ownership_percentage' => $totalShares > 0
                    ? round(((float) $row->number_of_shares / $totalShares) * 100, 2)
                    : 0,
            ]);
        }
    }

    public function storeAuthorized(Request $request)
    {
        $request->validate([
            'gis_id'            => 'required|exists:gis_records,id',
            'share_type'        => 'required|string|max:255',
            'number_of_shares'  => 'required|integer|min:1',
            'par_value'         => 'required|numeric|min:0',
        ]);

        AuthorizedCapitalStock::create([
            'gis_id'           => $request->gis_id,
            'share_type'       => $request->share_type,
            'number_of_shares' => $request->number_of_shares,
            'par_value'        => $request->par_value,
            'amount'           => $this->calculateAmount($request->number_of_shares, $request->par_value),
        ]);

        return back()->with('success', 'Authorized Capital added successfully.');
    }

    public function updateAuthorized(Request $request, AuthorizedCapitalStock $record)
    {
        $request->validate([
            'share_type'        => 'required|string|max:255',
            'number_of_shares'  => 'required|integer|min:1',
            'par_value'         => 'required|numeric|min:0',
        ]);

        $oldGisId = (int) $record->gis_id;

        if ((string) $record->share_type !== (string) $request->share_type) {
            $shareTypeIsUsed = SubscribedCapital::where('gis_id', $record->gis_id)
                    ->where('share_type', $record->share_type)
                    ->exists()
                || PaidUpCapital::where('gis_id', $record->gis_id)
                    ->where('share_type', $record->share_type)
                    ->exists()
                || Stockholder::where('gis_id', $record->gis_id)
                    ->where('share_type', $record->share_type)
                    ->exists();

            if ($shareTypeIsUsed) {
                return back()->withErrors([
                    'authorized_capital' => 'This share type is already used in subscribed capital, paid-up capital, or stockholders. Keep the same share type or edit/delete the related records first.',
                ]);
            }
        }

        $record->update([
            'share_type'       => $request->share_type,
            'number_of_shares' => $request->number_of_shares,
            'par_value'        => $request->par_value,
            'amount'           => $this->calculateAmount($request->number_of_shares, $request->par_value),
        ]);

        $this->recalculateSubscribedOwnership($oldGisId);
        $this->recalculatePaidupOwnership($oldGisId);

        app(StockholderController::class)->recalculateFromAuthorizedCapital($oldGisId);

        return back()->with('success', 'Authorized Capital updated successfully. Related subscribed, paid-up, and stockholder records were recalculated.');
    }

    public function destroyAuthorized(AuthorizedCapitalStock $record)
    {
        $hasSubscribed = SubscribedCapital::where('gis_id', $record->gis_id)
            ->where('share_type', $record->share_type)
            ->exists();

        $hasPaidup = PaidUpCapital::where('gis_id', $record->gis_id)
            ->where('share_type', $record->share_type)
            ->exists();

        $hasStockholders = Stockholder::where('gis_id', $record->gis_id)
            ->where('share_type', $record->share_type)
            ->exists();

        if ($hasSubscribed || $hasPaidup || $hasStockholders) {
            return back()->withErrors([
                'authorized_capital' => 'This share type is already used in subscribed capital, paid-up capital, or stockholders. Delete or edit those records first.',
            ]);
        }

        $record->delete();

        return back()->with('success', 'Authorized Capital deleted successfully.');
    }

    public function storeSubscribed(Request $request)
    {
        $request->validate([
            'gis_id'        => 'required|exists:gis_records,id',
            'nationality'   => 'required|string|max:255',
            'stockholders'  => 'required|integer|min:0',
            'share_type'    => 'required|string|max:255',
            'shares'        => 'required|integer|min:0',
        ]);

        $parValue = $this->authorizedParValue((int) $request->gis_id, (string) $request->share_type);

        SubscribedCapital::create([
            'gis_id'               => $request->gis_id,
            'nationality'          => $request->nationality,
            'no_of_stockholders'   => $request->stockholders,
            'share_type'           => $request->share_type,
            'number_of_shares'     => $request->shares,
            'par_value'            => $parValue,
            'amount'               => $this->calculateAmount($request->shares, $parValue),
            'ownership_percentage' => 0,
        ]);

        $this->recalculateSubscribedOwnership((int) $request->gis_id);

        return back()->with('success', 'Subscribed Capital added successfully.');
    }

    public function updateSubscribed(Request $request, SubscribedCapital $record)
    {
        $request->validate([
            'nationality'   => 'required|string|max:255',
            'stockholders'  => 'required|integer|min:0',
            'share_type'    => 'required|string|max:255',
            'shares'        => 'required|integer|min:0',
        ]);

        $parValue = $this->authorizedParValue((int) $record->gis_id, (string) $request->share_type);

        $record->update([
            'nationality'        => $request->nationality,
            'no_of_stockholders' => $request->stockholders,
            'share_type'         => $request->share_type,
            'number_of_shares'   => $request->shares,
            'par_value'          => $parValue,
            'amount'             => $this->calculateAmount($request->shares, $parValue),
        ]);

        $this->recalculateSubscribedOwnership((int) $record->gis_id);

        return back()->with('success', 'Subscribed Capital updated successfully.');
    }

    public function destroySubscribed(SubscribedCapital $record)
    {
        $gisId = (int) $record->gis_id;
        $record->delete();

        $this->recalculateSubscribedOwnership($gisId);

        return back()->with('success', 'Subscribed Capital deleted successfully.');
    }

    public function storePaidup(Request $request)
    {
        $request->validate([
            'gis_id'        => 'required|exists:gis_records,id',
            'nationality'   => 'required|string|max:255',
            'stockholders'  => 'required|integer|min:0',
            'share_type'    => 'required|string|max:255',
            'shares'        => 'required|integer|min:0',
        ]);

        $parValue = $this->authorizedParValue((int) $request->gis_id, (string) $request->share_type);

        PaidUpCapital::create([
            'gis_id'               => $request->gis_id,
            'nationality'          => $request->nationality,
            'no_of_stockholders'   => $request->stockholders,
            'share_type'           => $request->share_type,
            'number_of_shares'     => $request->shares,
            'par_value'            => $parValue,
            'amount'               => $this->calculateAmount($request->shares, $parValue),
            'ownership_percentage' => 0,
        ]);

        $this->recalculatePaidupOwnership((int) $request->gis_id);

        return back()->with('success', 'Paid-Up Capital added successfully.');
    }

    public function updatePaidup(Request $request, PaidUpCapital $record)
    {
        $request->validate([
            'nationality'   => 'required|string|max:255',
            'stockholders'  => 'required|integer|min:0',
            'share_type'    => 'required|string|max:255',
            'shares'        => 'required|integer|min:0',
        ]);

        $parValue = $this->authorizedParValue((int) $record->gis_id, (string) $request->share_type);

        $record->update([
            'nationality'        => $request->nationality,
            'no_of_stockholders' => $request->stockholders,
            'share_type'         => $request->share_type,
            'number_of_shares'   => $request->shares,
            'par_value'          => $parValue,
            'amount'             => $this->calculateAmount($request->shares, $parValue),
        ]);

        $this->recalculatePaidupOwnership((int) $record->gis_id);

        return back()->with('success', 'Paid-Up Capital updated successfully.');
    }

    public function destroyPaidup(PaidUpCapital $record)
    {
        $gisId = (int) $record->gis_id;
        $record->delete();

        $this->recalculatePaidupOwnership($gisId);

        return back()->with('success', 'Paid-Up Capital deleted successfully.');
    }
}
