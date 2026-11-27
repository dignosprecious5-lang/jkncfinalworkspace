<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealPaymentAllocation;
use App\Models\DealProposal;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Current client decisions are authoritative; historical allocations are immutable. */
class DealPaymentEligibility
{
    public function currentProposal(Deal $deal): ?DealProposal
    {
        return Schema::hasTable('deal_proposals') ? $deal->proposal()->first() : null;
    }

    public function items(Deal $deal): array
    {
        $proposal = $this->currentProposal($deal);
        if (! $proposal || ! is_array($proposal->line_items_snapshot)
            || in_array(strtolower((string) $proposal->status), ['rejected', 'declined', 'cancelled', 'superseded'], true)) {
            return [];
        }
        $latest = $proposal->itemResponses()->orderByDesc('responded_at')->orderByDesc('id')
            ->get()->unique('line_item_id')->keyBy('line_item_id');
        $items = [];
        foreach (array_values($proposal->line_items_snapshot) as $index => $raw) {
            $raw = is_array($raw) ? $raw : ['name' => (string) $raw];
            $id = (string) ($raw['id'] ?? ('legacy-'.($index + 1)));
            // Overall/internal proposal approval never approves an undecided item.
            if ($latest->get($id)?->decision !== 'accepted') {
                continue;
            }
            $unitPrice = $raw['unit_price'] ?? $raw['unitPrice'] ?? $raw['price'] ?? $raw['amount'] ?? 0;
            $price = round(max(0, (float) ($raw['line_total'] ?? (
                (float) ($raw['qty'] ?? 1) * (float) $unitPrice
                - (float) ($raw['discount'] ?? 0) + (float) ($raw['tax'] ?? 0)
            ))), 2);
            $items[] = [
                'id' => $id, 'proposal_id' => $proposal->id,
                'name' => (string) ($raw['name'] ?? 'Commercial Scope Item'),
                'code' => (string) ($raw['item_code'] ?? (($deal->deal_code ?: 'ITEM').'-'.($index + 1))),
                'price' => $price, 'decision' => 'accepted', 'route' => $raw['route'] ?? 'Regular', 'type' => strtoupper($raw['type'] ?? 'SERVICE'),
            ];
        }

        return $items;
    }

    public function state(Deal $deal): array
    {
        $items = $this->items($deal);
        $proposalId = $this->currentProposal($deal)?->id;
        $approved = collect($items)->keyBy('id');
        $allocated = array_fill_keys(array_column($items, 'id'), 0);
        $records = [];
        $payments = Schema::hasTable('deal_payment_allocations')
            ? DealPaymentAllocation::where('deal_id', $deal->id)->latest('id')->get() : collect();
        foreach ($payments as $payment) {
            $review = [];
            foreach ($payment->allocations as $allocation) {
                $id = (string) $allocation['item_id'];
                if ($payment->deal_proposal_id != $proposalId || ! $approved->has($id)) {
                    $review[] = $id;

                    continue;
                }
                $allocated[$id] += (float) $allocation['amount'];
            }
            $records[] = array_merge($payment->payload, [
                'id' => $payment->id, 'serverRecorded' => true,
                'allocations' => $payment->allocations,
                'requiresReview' => $review !== [], 'reviewItemIds' => $review,
                'reviewReason' => $review ? 'Payment includes an item no longer approved in the current proposal. Review or adjustment required.' : null,
            ]);
        }
        foreach ($items as &$item) {
            $item['allocated'] = round($allocated[$item['id']] ?? 0, 2);
            $item['balance'] = round(max(0, $item['price'] - $item['allocated']), 2);
            // Preserve the existing progressive readiness rule: a positive allocation permits START.
            $item['ready'] = $item['allocated'] > 0;
        }
        unset($item);

        return [
            'items' => $items, 'records' => $records,
            'approved_total' => round(array_sum(array_column($items, 'price')), 2),
            'allocated_total' => round(array_sum(array_column($items, 'allocated')), 2),
            'required_payment' => round(array_sum(array_column($items, 'balance')), 2),
            'review_required' => collect($records)->contains('requiresReview', true),
        ];
    }

    public function allocations(Deal $deal, string $scope, ?string $itemId, float $amount): array
    {
        $items = $this->items($deal);
        if ($scope === 'item') {
            $items = array_values(array_filter($items, fn ($item) => $item['id'] === $itemId));
        }
        if ($items === []) {
            throw ValidationException::withMessages(['itemId' => 'Only client-approved items in the current proposal can receive payments.']);
        }
        $total = array_sum(array_column($items, 'price'));
        $remaining = (int) round($amount * 100);
        $original = $remaining;
        $allocations = [];
        foreach ($items as $index => $item) {
            $cents = $index === count($items) - 1 ? $remaining
                : min($remaining, (int) round($original * ($total > 0 ? $item['price'] / $total : 1 / count($items))));
            $remaining -= $cents;
            $allocations[] = ['item_id' => $item['id'], 'item_name' => $item['name'], 'amount' => $cents / 100];
        }

        return $allocations;
    }

    public function batchEligible(Deal $deal, array $selection, bool $requirePayment = true): bool
    {
        if ($selection === []) {
            return false;
        }
        $items = $this->state($deal)['items'];
        foreach ($selection as $selected) {
            $selected = is_array($selected) ? $selected : ['name' => (string) $selected];
            $id = $selected['id'] ?? $selected['item_id'] ?? null;
            $matches = array_values(array_filter($items, fn ($item) => $id !== null
                ? $item['id'] === (string) $id
                : $item['name'] === ($selected['name'] ?? $selected['title'] ?? '')));
            // Name-only legacy batches must resolve unambiguously.
            if (count($matches) !== 1 || (isset($selected['name']) && $selected['name'] !== $matches[0]['name']) || ($requirePayment && ! $matches[0]['ready'])) {
                return false;
            }
        }

        return true;
    }

    public function assertActivationAllowed(Deal $deal): void
    {
        $state = $this->state($deal);
        $ready = array_values(array_filter($state['items'], fn ($item) => $item['ready']));
        $this->assertBatchEligible($deal, $ready);

    }

    public function assertBatchEligible(Deal $deal, array $selection, bool $requirePayment = true): void
    {
        if (! $this->batchEligible($deal, $selection, $requirePayment)) {
            throw ValidationException::withMessages(['activated_items' => 'Every selected item must be client-approved in the current proposal and have a recorded payment allocation before activation.']);
        }
    }
}
