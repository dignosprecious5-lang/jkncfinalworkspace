<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\SalesMarketingEarner;
use App\Models\SalesMarketingIda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesMarketingIdaController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermission('access_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        $idas = SalesMarketingIda::with(['deal', 'allocations.earner'])
            ->latest()
            ->get();

        $idasForEdit = $idas->map(function ($ida) {
            return [
                'id' => $ida->id,
                'deal_id' => $ida->deal_id,
                'condeal_ref_no' => $ida->condeal_ref_no,
                'client_name' => $ida->client_name,
                'business_name' => $ida->business_name,
                'service_area' => $ida->service_area,
                'product_engagement_structure' => $ida->product_engagement_structure,
                'deal_value' => (float) $ida->deal_value,
                'workflow_status' => $ida->workflow_status,
                'update_url' => route('sales-marketing.ida.update', $ida),
                'delete_url' => route('sales-marketing.ida.destroy', $ida),
                'allocations' => $ida->allocations->map(function ($allocation) {
                    return [
                        'id' => $allocation->id,
                        'earner_id' => $allocation->earner_id,
                        'role' => $allocation->role,
                        'commission_category' => $allocation->commission_category,
                        'commission_type' => $allocation->commission_type ?: 'Percentage',
                        'commission_rate' => (float) $allocation->commission_rate,
                        'commission_amount' => (float) $allocation->commission_amount,
                        'status' => $allocation->status ?: 'Pending',
                    ];
                })->values(),
            ];
        })->values();

        $deals = Deal::orderBy('deal_code')->get()->map(function ($deal) {
            $clientName = trim(implode(' ', array_filter([
                $deal->first_name ?? null,
                $deal->middle_name ?? null,
                $deal->last_name ?? null,
            ])));

            return [
                'id' => $deal->id,
                'deal_code' => $deal->deal_code,
                'client_name' => $clientName ?: ($deal->deal_name ?? ''),
                'business_name' => $deal->company_name ?? '',
                'service_area' => $deal->service_area ?? '',
                'product_engagement_structure' => $deal->engagement_type ?? '',
                'deal_value' => $deal->total_estimated_engagement_value ?? 0,
            ];
        })->values();

        $earners = SalesMarketingEarner::orderBy('full_name')->get();

        return view('sales-marketing.ida.index', compact(
            'idas',
            'idasForEdit',
            'deals',
            'earners'
        ));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('create_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        $validated = $this->validateIda($request);

        DB::transaction(function () use ($validated) {
            $dealValue = (float) ($validated['deal_value'] ?? 0);

            $ida = SalesMarketingIda::create([
                'deal_id' => $validated['deal_id'] ?? null,
                'condeal_ref_no' => $validated['condeal_ref_no'] ?? null,
                'client_name' => $validated['client_name'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'service_area' => $validated['service_area'] ?? null,
                'product_engagement_structure' => $validated['product_engagement_structure'] ?? null,
                'deal_value' => $dealValue,
                'workflow_status' => 'Uploaded',
                'created_by' => auth()->id(),
            ]);

            $this->saveAllocations($ida, $validated['allocations'] ?? [], $dealValue);
        });

        return redirect()
            ->route('sales-marketing.ida.index')
            ->with('success', 'IDA record created successfully.');
    }

    public function show(SalesMarketingIda $ida)
    {
        if (!auth()->user()->hasPermission('access_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        $ida->load(['deal', 'allocations.earner']);

        return view('sales-marketing.ida.show', compact('ida'));
    }

    public function update(Request $request, SalesMarketingIda $ida)
    {
        if (!auth()->user()->hasPermission('create_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        if (!$this->canModify($ida)) {
            return back()->withErrors([
                'ida' => 'Only Uploaded or Reverted IDA records can be edited.',
            ]);
        }

        $validated = $this->validateIda($request);

        DB::transaction(function () use ($validated, $ida) {
            $dealValue = (float) ($validated['deal_value'] ?? 0);

            $ida->update([
                'deal_id' => $validated['deal_id'] ?? null,
                'condeal_ref_no' => $validated['condeal_ref_no'] ?? null,
                'client_name' => $validated['client_name'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'service_area' => $validated['service_area'] ?? null,
                'product_engagement_structure' => $validated['product_engagement_structure'] ?? null,
                'deal_value' => $dealValue,
                'workflow_status' => 'Uploaded',
            ]);

            $ida->allocations()->delete();

            $this->saveAllocations($ida, $validated['allocations'] ?? [], $dealValue);
        });

        return redirect()
            ->route('sales-marketing.ida.index')
            ->with('success', 'IDA record updated successfully.');
    }

    public function destroy(SalesMarketingIda $ida)
    {
        if (!auth()->user()->hasPermission('create_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        if (!$this->canModify($ida)) {
            return back()->withErrors([
                'ida' => 'Only Uploaded or Reverted IDA records can be deleted.',
            ]);
        }

        DB::transaction(function () use ($ida) {
            $ida->allocations()->delete();
            $ida->delete();
        });

        return redirect()
            ->route('sales-marketing.ida.index')
            ->with('success', 'IDA record deleted successfully.');
    }

    public function submit(SalesMarketingIda $ida)
    {
        if (!auth()->user()->hasPermission('create_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        if (!in_array($ida->workflow_status, ['Uploaded', 'Reverted', null], true)) {
            return back()->withErrors([
                'ida' => 'Only Uploaded or Reverted IDA records can be submitted.',
            ]);
        }

        if (!$ida->allocations()->exists()) {
            return back()->withErrors([
                'ida' => 'You cannot submit an IDA record without allocation rows.',
            ]);
        }

        $ida->update([
            'workflow_status' => 'Submitted',
        ]);

        return redirect()
            ->route('sales-marketing.ida.show', $ida)
            ->with('success', 'IDA record submitted for approval.');
    }

    public function accept(SalesMarketingIda $ida)
    {
        if (!auth()->user()->hasPermission('approve_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        if ($ida->workflow_status !== 'Submitted') {
            return back()->withErrors([
                'ida' => 'Only Submitted IDA records can be accepted.',
            ]);
        }

        $ida->update([
            'workflow_status' => 'Accepted',
        ]);

        return redirect()
            ->route('sales-marketing.ida.show', $ida)
            ->with('success', 'IDA record accepted successfully.');
    }

    public function revert(SalesMarketingIda $ida)
    {
        if (!auth()->user()->hasPermission('approve_sales_marketing')) {
            abort(403, 'Unauthorized');
        }

        if ($ida->workflow_status !== 'Submitted') {
            return back()->withErrors([
                'ida' => 'Only Submitted IDA records can be reverted.',
            ]);
        }

        $ida->update([
            'workflow_status' => 'Reverted',
        ]);

        return redirect()
            ->route('sales-marketing.ida.show', $ida)
            ->with('success', 'IDA record reverted successfully.');
    }

    private function validateIda(Request $request): array
    {
        return $request->validate([
            'deal_id' => ['nullable', 'exists:deals,id'],
            'condeal_ref_no' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'product_engagement_structure' => ['nullable', 'string', 'max:255'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.earner_id' => ['nullable', 'exists:sales_marketing_earners,id'],
            'allocations.*.role' => ['nullable', 'string', 'max:255'],
            'allocations.*.commission_category' => ['nullable', 'string', 'max:255'],
            'allocations.*.commission_type' => ['nullable', 'string', 'max:255'],
            'allocations.*.commission_rate' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.commission_amount' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.status' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function saveAllocations(SalesMarketingIda $ida, array $allocations, float $dealValue): void
    {
        foreach ($allocations as $allocation) {
            $earnerId = $allocation['earner_id'] ?? null;
            $role = $allocation['role'] ?? null;
            $category = $allocation['commission_category'] ?? null;
            $type = $allocation['commission_type'] ?? 'Percentage';
            $rate = (float) ($allocation['commission_rate'] ?? 0);
            $manualAmount = (float) ($allocation['commission_amount'] ?? 0);

            if (
                empty($earnerId) &&
                empty($role) &&
                empty($category) &&
                empty($type) &&
                $rate <= 0 &&
                $manualAmount <= 0
            ) {
                continue;
            }

            if ($type === 'Percentage') {
                $computedAmount = $dealValue * ($rate / 100);
            } else {
                $computedAmount = $manualAmount;
                $rate = 0;
            }

            $ida->allocations()->create([
                'earner_id' => $earnerId,
                'role' => $role,
                'commission_category' => $category,
                'commission_type' => $type,
                'commission_rate' => $rate,
                'commission_amount' => $computedAmount,
                'status' => $allocation['status'] ?? 'Pending',
            ]);
        }
    }

    private function canModify(SalesMarketingIda $ida): bool
    {
        return in_array($ida->workflow_status, ['Uploaded', 'Reverted', null], true);
    }
}