<?php

namespace App\Http\Controllers;

use App\Models\SalesMarketingEarner;
use App\Models\SalesMarketingIdaAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesMarketingPayoutController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('approve_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        $payouts = SalesMarketingIdaAllocation::with(['earner', 'ida'])
            ->where('status', 'For Payout')
            ->whereHas('ida', function ($query) {
                $query->where('workflow_status', 'Accepted');
            })
            ->latest()
            ->get();

        $paidPayouts = SalesMarketingIdaAllocation::with(['earner', 'ida'])
            ->where('status', 'Paid')
            ->latest()
            ->limit(20)
            ->get();

        $totalForPayout = $payouts->sum('commission_amount');
        $totalPaid = SalesMarketingIdaAllocation::where('status', 'Paid')->sum('commission_amount');

        return view('sales-marketing.payouts.index', compact(
            'payouts',
            'paidPayouts',
            'totalForPayout',
            'totalPaid'
        ));
    }

    public function requestPayout(SalesMarketingEarner $earner)
    {
        if (!auth()->user()->hasPermission('create_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        $eligibleAllocations = $earner->allocations()
            ->where('status', 'Pending')
            ->whereHas('ida', function ($query) {
                $query->where('workflow_status', 'Accepted');
            })
            ->get();

        if ($eligibleAllocations->isEmpty()) {
            return redirect()
                ->route('sales-marketing.earners.show', $earner)
                ->withErrors([
                    'payout' => 'No accepted pending commissions are available for payout request.',
                ]);
        }

        DB::transaction(function () use ($eligibleAllocations) {
            foreach ($eligibleAllocations as $allocation) {
                $allocation->update([
                    'status' => 'For Payout',
                ]);
            }
        });

        return redirect()
            ->route('sales-marketing.earners.show', $earner)
            ->with('success', 'Payout request submitted successfully.');
    }

    public function markPaid(SalesMarketingIdaAllocation $allocation)
    {
        if (!auth()->user()->hasPermission('approve_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        $allocation->load('ida');

        if (!$allocation->ida || $allocation->ida->workflow_status !== 'Accepted') {
            return back()->withErrors([
                'payout' => 'Only accepted IDA allocations can be marked as paid.',
            ]);
        }

        if ($allocation->status !== 'For Payout') {
            return back()->withErrors([
                'payout' => 'Only For Payout records can be marked as paid.',
            ]);
        }

        $allocation->update([
            'status' => 'Paid',
        ]);

        return redirect()
            ->route('sales-marketing.payouts.index')
            ->with('success', 'Payout marked as paid successfully.');
    }
}