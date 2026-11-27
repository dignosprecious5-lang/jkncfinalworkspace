<?php

namespace App\Services;

use App\Models\DealProposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DealProposalDecisions
{
    public const LABELS = ['pending_client_review' => 'Pending Client Review', 'partially_approved' => 'Partially Approved', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function summary(DealProposal $proposal): array
    {
        $responses = $proposal->itemResponses()->orderByDesc('responded_at')->orderByDesc('id')->get()->unique('line_item_id')->keyBy('line_item_id');
        $groups = ['approved' => [], 'rejected' => [], 'pending' => []];
        foreach (array_values($proposal->line_items_snapshot ?? []) as $index => $raw) {
            $raw = is_array($raw) ? $raw : ['name' => (string) $raw];
            $id = (string) ($raw['id'] ?? ('legacy-'.($index + 1)));
            $response = $responses->get($id);
            $key = match ($response?->decision) {
                'accepted' => 'approved','declined' => 'rejected', default => 'pending'
            };
            $price = round(max(0, (float) ($raw['line_total'] ?? ((float) ($raw['qty'] ?? 1) * (float) ($raw['unit_price'] ?? $raw['unitPrice'] ?? $raw['price'] ?? $raw['amount'] ?? 0) - (float) ($raw['discount'] ?? 0) + (float) ($raw['tax'] ?? 0)))), 2);
            $groups[$key][] = ['id' => $id, 'name' => (string) ($raw['name'] ?? 'Proposal item'), 'amount' => $price, 'status' => ucfirst($key), 'comment' => $response?->comment, 'responded_at' => $response?->responded_at?->toIso8601String()];
        }
        $items = array_merge(...array_values($groups));
        $count = count($items);
        $status = $responses->isEmpty() ? 'pending_client_review' : (count($groups['approved']) === $count ? 'approved' : (count($groups['rejected']) === $count ? 'rejected' : 'partially_approved'));
        if ($count === 0 && in_array($proposal->status, ['approved', 'rejected'], true)) {
            $status = $proposal->status;
        }
        $deal = $proposal->deal;

        return ['status' => $status, 'status_label' => self::LABELS[$status], 'groups' => $groups, 'items' => $items,
            'proposed_total' => is_numeric($proposal->price_total) ? (float) $proposal->price_total : round(array_sum(array_column($items, 'amount')), 2),
            'approved_total' => round(array_sum(array_column($groups['approved'], 'amount')), 2),
            'reference' => $proposal->reference_id ?: ($deal?->deal_code ?: 'PROPOSAL-'.$proposal->id),
            'title' => $proposal->subject ?: ($deal?->deal_title ?: $deal?->deal_name ?: 'Proposal'),
            'version' => $proposal->proposal_version ?: 1, 'client_name' => $proposal->client_approved_by_name ?: $deal?->primary_contact_name ?: $deal?->contact?->full_name ?: 'Client',
            'company' => $deal?->company_name ?: $deal?->company ?: 'Individual client',
            'responded_at' => $proposal->client_submitted_at?->toIso8601String()];
    }

    /** Called inside the existing decision transaction, with the proposal row locked. */
    public function finalize(DealProposal $proposal, string $clientName): array
    {
        $proposal->client_approved_by_name = $clientName;
        $summary = $this->summary($proposal);
        $summary['client_name'] = $clientName;
        $summary['responded_at'] = now()->toIso8601String();
        $fields = ['status' => $summary['status'], 'client_approved_by_name' => $clientName];
        if (Schema::hasColumn('deal_proposals', 'client_submitted_at')) {
            $fields += ['client_decision_status' => $summary['status'], 'client_submitted_at' => now(),
                'client_submitted_email' => $proposal->client_form_sent_to_email, 'decision_summary_snapshot' => $summary];
        }
        $proposal->forceFill($fields)->save();
        if ($deal = $proposal->deal) {
            $payload = ['proposal_decision' => match ($summary['status']) {
                'approved' => 'Approved','rejected' => 'Declined',default => 'Partially Approved'
            }];
            if (Schema::hasColumn('deals', 'proposal_status')) {
                $payload['proposal_status'] = $summary['status_label'];
            }
            if (Schema::hasColumn('deals', 'approval_date') && $summary['status'] === 'approved') {
                $payload['approval_date'] = now();
            }
            $deal->update($payload);
        }

        return $summary;
    }

    /** Resolve only the recorded sender; legacy proposals can fall back to their recorded preparer. */
    public function handler(DealProposal $proposal): ?array
    {
        if ($proposal->handler_email) {
            return ['name' => $proposal->handler_name ?: 'Proposal Handler', 'email' => $proposal->handler_email];
        }
        $user = is_numeric($proposal->prepared_by_id) ? User::find($proposal->prepared_by_id) : null;
        if (! $user && $proposal->prepared_by_name) {
            $matches = User::where('name', $proposal->prepared_by_name)->limit(2)->get();
            if ($matches->count() === 1) {
                $user = $matches->first();
            }
        }

        return $user && filled($user->email) ? ['name' => $user->name, 'email' => $user->email] : null;
    }

    /** Email is sent after decision commit. Failures are recorded and can be retried. */
    public function notify(DealProposal $proposal): bool
    {
        return DB::transaction(function () use ($proposal): bool {
            $proposal = DealProposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            if ($proposal->decision_notified_at) {
                return true;
            }
            $handler = $this->handler($proposal);
            $summary = $proposal->decision_summary_snapshot ?: $this->summary($proposal);
            try {
                if (! $handler) {
                    throw new \RuntimeException('The recorded proposal handler has no deliverable email address.');
                }
                $html = view('emails.deals.proposal-decision', ['handler' => $handler, 'summary' => $summary, 'proposal' => $proposal, 'proposalUrl' => route('deals.proposal.show', [$proposal->deal_id, 'proposal_version' => $proposal->id])])->render();
                $result = app(EmailNotificationService::class)->sendHtml($handler['email'], 'Proposal Decision Received — '.$summary['reference'], $html);
                if (! $result['success']) {
                    throw new \RuntimeException($result['error'] ?? 'Email delivery failed.');
                }
                if (Schema::hasColumn('deal_proposals', 'decision_notified_at')) {
                    $proposal->forceFill(['decision_notified_at' => now(), 'decision_notification_error' => null])->save();
                }

                return true;
            } catch (Throwable $error) {
                if (Schema::hasColumn('deal_proposals', 'decision_notification_error')) {
                    $proposal->forceFill(['decision_notification_error' => $error->getMessage()])->save();
                }
                Log::warning('Proposal decision saved; handler email requires retry.', ['proposal_id' => $proposal->id, 'error' => $error->getMessage()]);

                return false;
            }
        });
    }
}
