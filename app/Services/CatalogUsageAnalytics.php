<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Reads current, catalog-linked activity for one service or product.
 * No result is cached: revisiting a workspace reflects saved source records.
 */
class CatalogUsageAnalytics
{
    public function forCatalogItem(string $type, int $catalogId, ?string $from = null, ?string $to = null): array
    {
        $type = $type === 'product' ? 'product' : 'service';
        $idColumn = $type.'_id';
        $metrics = [
            'approvedProposalsCount' => 0,
            'activeEngagementsCount' => null,
            'completedEngagementsCount' => null,
            'dealsCount' => 0,
            'proposalsCount' => 0,
            'acceptedCount' => 0,
            'uniqueClientsCount' => 0,
            'engagementsCreatedCount' => null,
            'proposedValue' => 0.0,
            'acceptedContractedValue' => 0.0,
            'discountValue' => 0.0,
            'billedRevenue' => null,
            'collectedRevenue' => null,
        ];
        $sources = [];
        $dealRows = collect();
        $matchedDeals = [];
        $linkedLineIds = [];
        $sourceColumns = array_values(array_filter([
            'id', 'account_id', 'company_id', 'contact_id', 'services_products', 'created_at',
        ], fn (string $column): bool => Schema::hasColumn('deals', $column)));

        if (Schema::hasTable('deals') && in_array('services_products', $sourceColumns, true)) {
            try {
                $dealRows = $this->period(DB::table('deals')->select($sourceColumns)->whereNotNull('services_products'), 'deals', $from, $to)->get();
                foreach ($dealRows as $deal) {
                    foreach ($this->decodeItems($deal->services_products ?? null) as $item) {
                        if (! $this->isLinkedTo($item, $type, $catalogId)) {
                            continue;
                        }
                        $matchedDeals[(int) $deal->id] = $deal;
                        if (filled($item['id'] ?? null)) {
                            $linkedLineIds[$deal->id.'|'.(string) $item['id']] = true;
                        }
                    }
                }
                $sources['deals'] = $this->connected(count($matchedDeals), 'Deals and Deal Line Items');
                $metrics['dealsCount'] = count($matchedDeals);
                $clientKeys = [];
                foreach ($matchedDeals as $deal) {
                    $clientId = $deal->account_id ?? null;
                    $clientType = 'account';
                    if (! $clientId) {
                        $clientId = $deal->company_id ?? null;
                        $clientType = 'company';
                    }
                    if (! $clientId) {
                        $clientId = $deal->contact_id ?? null;
                        $clientType = 'contact';
                    }
                    if ($clientId) {
                        $clientKeys[$clientType.':'.$clientId] = true;
                    }
                }
                $metrics['uniqueClientsCount'] = count($clientKeys);
                $sources['clients'] = $this->connected(count($clientKeys), 'Unique Clients');
            } catch (Throwable $exception) {
                $sources['deals'] = $this->failed('Deals and Deal Line Items', $exception);
                $sources['clients'] = $this->failed('Unique Clients', $exception);
                $metrics['dealsCount'] = null;
                $metrics['uniqueClientsCount'] = null;
            }
        } else {
            $sources['deals'] = $this->unavailable('Deals and Deal Line Items', 'Deal line items have no readable catalog reference.');
            $sources['clients'] = $this->unavailable('Unique Clients', 'Client linkage is unavailable without connected Deals.');
            $metrics['dealsCount'] = null;
            $metrics['uniqueClientsCount'] = null;
        }

        $currentProposals = collect();
        if (Schema::hasTable('deal_proposals') && Schema::hasColumn('deal_proposals', 'deal_id') && Schema::hasColumn('deal_proposals', 'line_items_snapshot')) {
            try {
                $proposalRows = $this->period(DB::table('deal_proposals'), 'deal_proposals', $from, $to)->get();
                $currentProposals = $this->currentProposalRows($proposalRows);
                $proposalItems = [];
                foreach ($currentProposals as $proposal) {
                    $snapshotItems = $proposal->line_items_snapshot ?? null;
                    if ($snapshotItems === null && ! filled($proposal->client_form_sent_at ?? null)
                        && ! filled($proposal->client_approved_at ?? null)
                        && in_array(strtolower((string) ($proposal->status ?? 'draft')), ['', 'draft', 'saved'], true)) {
                        $snapshotItems = Schema::hasColumn('deals', 'services_products')
                            ? DB::table('deals')->where('id', $proposal->deal_id)->value('services_products') : null;
                    }
                    foreach ($this->decodeItems($snapshotItems) as $index => $item) {
                        if (! $this->isLinkedTo($item, $type, $catalogId)
                            && ! (filled($item['id'] ?? null) && empty($item[$idColumn]) && strtoupper((string) ($item['type'] ?? 'SERVICE')) === strtoupper($type) && isset($linkedLineIds[$proposal->deal_id.'|'.(string) $item['id']]))
                        ) {
                            continue;
                        }
                        $itemId = (string) ($item['id'] ?? ('legacy-'.($index + 1)));
                        $proposalItems[] = ['proposal' => $proposal, 'item' => $item, 'item_id' => $itemId];
                    }
                }
                $currentProposals = collect($proposalItems);
                $metrics['proposalsCount'] = $currentProposals->pluck('proposal.id')->unique()->count();
                $metrics['approvedProposalsCount'] = $currentProposals->filter(fn ($row) => filled($row['proposal']->client_approved_at ?? null) || in_array(strtolower((string) ($row['proposal']->status ?? '')), ['approved', 'accepted', 'contracted'], true))->pluck('proposal.id')->unique()->count();
                $metrics['proposedValue'] = $currentProposals->sum(fn (array $row): float => $this->lineTotal($row['item']));
                $metrics['discountValue'] = $currentProposals->sum(fn (array $row): float => $this->numeric($row['item']['discount'] ?? 0));
                $sources['proposals'] = $this->connected($metrics['proposalsCount'], 'Current Proposal versions and Proposal Items');
                $sources['discount'] = $this->connected($currentProposals->sum(fn (array $row): float => $this->numeric($row['item']['discount'] ?? 0)), 'Proposal item discounts');
            } catch (Throwable $exception) {
                $sources['proposals'] = $this->failed('Proposals and Proposal Items', $exception);
                $sources['discount'] = $this->failed('Proposal item discounts', $exception);
                $metrics['approvedProposalsCount'] = null;
            $metrics['proposalsCount'] = null;
                $metrics['proposedValue'] = null;
                $metrics['discountValue'] = null;
            }
        } else {
            $sources['proposals'] = $this->unavailable('Proposals and Proposal Items', 'Current proposal snapshots are unavailable.');
            $sources['discount'] = $this->unavailable('Proposal item discounts', 'Current proposal item discounts are unavailable.');
            $metrics['approvedProposalsCount'] = null;
            $metrics['proposalsCount'] = null;
            $metrics['proposedValue'] = null;
            $metrics['discountValue'] = null;
        }

        if (Schema::hasTable('deal_proposal_item_responses') && $currentProposals->isNotEmpty()) {
            try {
                $proposalIds = $currentProposals->pluck('proposal.id')->unique()->values();
                $responses = DB::table('deal_proposal_item_responses')
                    ->whereIn('deal_proposal_id', $proposalIds)
                    ->orderByDesc('responded_at')
                    ->orderByDesc('id')
                    ->get()
                    ->unique(fn ($response) => $response->deal_proposal_id.'|'.$response->line_item_id)
                    ->keyBy(fn ($response) => $response->deal_proposal_id.'|'.$response->line_item_id);
                $acceptedRows = $currentProposals->filter(function (array $row) use ($responses): bool {
                    $response = $responses->get($row['proposal']->id.'|'.$row['item_id']);
                    if ($response) {
                        return strtolower((string) $response->decision) === 'accepted';
                    }

                    return filled($row['proposal']->client_approved_at ?? null)
                        || in_array(strtolower((string) ($row['proposal']->status ?? '')), ['approved', 'accepted', 'contracted'], true);
                });
                $metrics['acceptedCount'] = $acceptedRows->count();
                $metrics['acceptedContractedValue'] = $acceptedRows->sum(fn (array $row): float => $this->lineTotal($row['item']));
                $sources['client_decisions'] = $this->connected($responses->count(), 'Client Decisions');
            } catch (Throwable $exception) {
                $sources['client_decisions'] = $this->failed('Client Decisions', $exception);
                $metrics['acceptedCount'] = null;
                $metrics['acceptedContractedValue'] = null;
            }
        } elseif (Schema::hasTable('deal_proposal_item_responses')) {
            $sources['client_decisions'] = $this->connected(0, 'Client Decisions');
        } else {
            $sources['client_decisions'] = $this->unavailable('Client Decisions', 'The per-item decision store is unavailable.');
            $metrics['acceptedCount'] = null;
            $metrics['acceptedContractedValue'] = null;
        }

        $metrics['engagementsCreatedCount'] = $this->aggregateDirectSource(
            'engagements', $idColumn, ['id'], $catalogId, $sources, 'engagements', 'Operational Engagements', false, $from, $to
        );
        $metrics['billedRevenue'] = $this->aggregateDirectSource(
            'billings', $idColumn, ['total_amount', 'amount', 'billed_amount'], $catalogId, $sources, 'billing', 'Billing Records', true, $from, $to
        );
        $metrics['collectedRevenue'] = $this->aggregateDirectSource(
            'collections', $idColumn, ['paid_amount', 'amount', 'collected_amount'], $catalogId, $sources, 'collections', 'Collections / Payments', true, $from, $to
        );

        if (Schema::hasColumn('engagements', $idColumn) && Schema::hasColumn('engagements', 'status') && (! ($from || $to) || Schema::hasColumn('engagements', 'created_at'))) {
            $base = $this->period(DB::table('engagements')->where($idColumn, $catalogId), 'engagements', $from, $to);
            $metrics['activeEngagementsCount'] = (clone $base)->whereIn(DB::raw('LOWER(status)'), ['active', 'in_progress', 'ongoing'])->distinct()->count('id');
            $metrics['completedEngagementsCount'] = (clone $base)->whereIn(DB::raw('LOWER(status)'), ['completed', 'closed'])->distinct()->count('id');
        }
        $usageOverTime = [];
        foreach ($matchedDeals as $deal) {
            if (filled($deal->created_at ?? null)) {
                $month = substr($deal->created_at, 0, 7);
                $usageOverTime[$month] = ($usageOverTime[$month] ?? 0) + 1;
            }
        }
        ksort($usageOverTime);
        return [
            'usageOverTime' => $usageOverTime,
            'metrics' => $metrics,
            'sources' => $sources,
            'live' => in_array($sources['deals']['state'] ?? '', ['connected'], true)
                && in_array($sources['proposals']['state'] ?? '', ['connected'], true),
        ];
    }

    private function currentProposalRows($rows)
    {
        if ($rows->isEmpty()) {
            return collect();
        }
        if (Schema::hasColumn('deal_proposals', 'is_current')) {
            return $rows->filter(fn ($row) => (bool) ($row->is_current ?? false))->values();
        }

        return $rows->sortByDesc(fn ($row) => [$row->proposal_version ?? 0, $row->id])
            ->unique('deal_id')
            ->values();
    }

    private function decodeItems(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values($value) : [];
    }

    private function isLinkedTo(array $item, string $type, int $catalogId): bool
    {
        if (strtoupper((string) ($item['type'] ?? 'SERVICE')) !== strtoupper($type)) {
            return false;
        }

        return (string) ($item[$type.'_id'] ?? '') === (string) $catalogId;
    }

    private function lineTotal(array $item): float
    {
        if (is_numeric($item['line_total'] ?? null)) {
            return (float) $item['line_total'];
        }
        $price = $this->numeric($item['unitPrice'] ?? $item['unit_price'] ?? $item['price'] ?? 0);
        $quantity = $this->numeric($item['qty'] ?? 1);

        return max(0, $price * $quantity - $this->numeric($item['discount'] ?? 0) + $this->numeric($item['tax'] ?? 0));
    }

    private function numeric(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function aggregateDirectSource(
        string $table,
        string $catalogColumn,
        array $candidateColumns,
        int $catalogId,
        array &$sources,
        string $sourceKey,
        string $label,
        bool $sum = false,
        ?string $from = null,
        ?string $to = null,
    ): ?float {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $catalogColumn)) {
            $sources[$sourceKey] = $this->unavailable($label, 'No direct catalog-item relationship is available.');
            return null;
        }
        $valueColumn = $sum
            ? collect($candidateColumns)->first(fn (string $column): bool => Schema::hasColumn($table, $column))
            : 'id';
        if (! $valueColumn || ! Schema::hasColumn($table, $valueColumn)) {
            $sources[$sourceKey] = $this->unavailable($label, 'The source has no supported amount field.');
            return null;
        }

        try {
            $query = $this->period(DB::table($table)->where($catalogColumn, $catalogId), $table, $from, $to);
            if (Schema::hasColumn($table, 'status')) {
                $query->whereNotIn(DB::raw('LOWER(status)'), ['void', 'voided', 'cancelled', 'canceled', 'reversed', 'deleted']);
            }
            $value = $sum ? (float) $query->sum($valueColumn) : (float) $query->count();
            $sources[$sourceKey] = $this->connected($value, $label);
            return $value;
        } catch (Throwable $exception) {
            $sources[$sourceKey] = $this->failed($label, $exception);
            return null;
        }
    }

    private function period($query, string $table, ?string $from, ?string $to)
    {
        if (($from || $to) && ! Schema::hasColumn($table, 'created_at')) {
            throw new \RuntimeException('Date filtering unavailable: '.$table.' has no created_at column.');
        }
        if ($from) { $query->whereDate('created_at', '>=', $from); }
        if ($to) { $query->whereDate('created_at', '<=', $to); }
        return $query;
    }

    private function connected(int|float $count, string $label): array
    {
        return ['label' => $label, 'state' => 'connected', 'detail' => $count > 0 ? 'Connected' : 'Connected · no matching records'];
    }

    private function unavailable(string $label, string $detail): array
    {
        return ['label' => $label, 'state' => 'unavailable', 'detail' => $detail];
    }

    private function failed(string $label, Throwable $exception): array
    {
        report($exception);
        return ['label' => $label, 'state' => 'error', 'detail' => 'Query failed; metric is unavailable'];
    }
}

