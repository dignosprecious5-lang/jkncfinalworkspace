<?php

namespace App\Http\Controllers;

use App\Models\TransmittalReceipt;
use App\Services\TransmittalWorkflowBoundary;

class TransmittalReceiptController extends Controller
{
    public function show($id)
    {
        $receipt = TransmittalReceipt::with('transmittal')
            ->whereIn('transmittal_id', TransmittalWorkflowBoundary::v1Query()->select('transmittals.id'))
            ->findOrFail($id);

        return view('transmittal.receipt', compact('receipt'));
    }
}