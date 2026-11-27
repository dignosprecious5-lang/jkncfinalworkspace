<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealPaymentAllocation;
use App\Services\DealFinanceDocuments;
use App\Services\DealPaymentEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealPaymentAllocationController extends Controller
{
    public function index(Request $request, Deal $deal, DealPaymentEligibility $eligibility)
    {
        abort_unless(app(DealFinanceDocuments::class)->canView($request->user()), 403);

        return response()->json($eligibility->state($deal));
    }

    public function store(Request $request, Deal $deal, DealPaymentEligibility $eligibility)
    {
        abort_unless(app(DealFinanceDocuments::class)->canUpload($request->user()), 403);
        $data = $request->validate([
            'request_id' => ['required', 'uuid'],
            'scope' => ['required', 'in:item,entire,downpayment,retainer,milestone'],
            'itemId' => ['nullable', 'string', 'max:255', 'required_if:scope,item'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'method' => ['required', 'string', 'max:255'],
            'ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'fileName' => ['nullable', 'string', 'max:255'],
            'fileSize' => ['nullable', 'integer', 'min:0', 'max:10485760'],
            'fileDataUrl' => ['nullable', 'string', 'max:14000000'],
            'fileType' => ['nullable', 'string', 'max:255'],
            'isImage' => ['nullable', 'boolean'],
        ]);
        DB::transaction(function () use ($deal, $data, $request, $eligibility) {
            $locked = Deal::whereKey($deal->id)->lockForUpdate()->firstOrFail();
            $proposal = $locked->proposal()->lockForUpdate()->first();
            // Serialize with client decision updates, which also lock the proposal row.
            $existing = DealPaymentAllocation::where('deal_id', $locked->id)->where('request_id', $data['request_id'])->first();
            if ($existing) {
                return;
            }
            $allocations = $eligibility->allocations($locked, $data['scope'], $data['itemId'] ?? null, round((float) $data['amount'], 2));
            $payload = array_merge(collect($data)->except('request_id')->all(), [
                'amount' => round((float) $data['amount'], 2),
                'itemId' => $data['scope'] === 'item' ? $data['itemId'] : null,
                'date' => now()->format('M d, Y'), 'time' => now()->format('h:i A'),
                'recordedBy' => $request->user()->name, 'docType' => 'Official Receipt',
                'fileSize' => isset($data['fileSize']) ? round($data['fileSize'] / 1024, 1).' KB' : null,
            ]);
            DealPaymentAllocation::create([
                'deal_id' => $locked->id, 'deal_proposal_id' => $proposal->id,
                'recorded_by_id' => $request->user()->id, 'request_id' => $data['request_id'],
                'payload' => $payload, 'allocations' => $allocations,
            ]);
        });

        return response()->json($eligibility->state($deal), 201);
    }
}
