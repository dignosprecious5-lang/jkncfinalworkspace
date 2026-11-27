<?php

namespace App\Models;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealProposal extends Model
{
    protected $fillable = [
        'deal_id',
        'handler_user_id', 'handler_name', 'handler_email', 'client_decision_status', 'client_submitted_at', 'client_submitted_email', 'decision_summary_snapshot', 'decision_notified_at', 'decision_notification_error',
        'proposal_version',
        'is_current',
        'revision_request_key',
        'reference_id',
        'crud_id',
        'subject',
        'introduction',
        'scope',
        'terms',
        'discount',
        'tax',
        'proposal_date',
        'location',
        'service_type',
        'scope_of_service',
        'what_you_will_receive',
        'our_proposal_text',
        'requirements_sole',
        'requirements_juridical',
        'requirements_optional',
        'price_regular',
        'price_discount',
        'price_subtotal',
        'price_tax',
        'price_total',
        'price_down',
        'price_balance',
        'prepared_by_name',
        'prepared_by_id',
        'document_html',
        'status',
        'client_access_token',
        'client_access_expires_at',
        'client_form_sent_to_email',
        'client_form_sent_at',
        'client_approved_at',
        'client_approved_by_name',
        'client_approval_note',
        'line_items_snapshot',
        'catalog_snapshot',
        'quotation_status',
        'quotation_finance_file_path',
        'quotation_client_file_path',
        'quotation_finance_started_at',
        'quotation_approved_at',
        'quotation_approved_by_name',
        'invoice_status',
        'invoice_file_path',
        'invoice_generated_at',
        'invoice_uploaded_at',
        'payment_confirmed_at',
        'payment_confirmed_by_name',
    ];

    protected $casts = [
        'handler_user_id' => 'integer',
        'client_submitted_at' => 'datetime',
        'decision_notified_at' => 'datetime',
        'decision_summary_snapshot' => 'array',
        'proposal_version' => 'integer',
        'is_current' => 'boolean',
        'proposal_date' => 'date',
        'price_regular' => 'decimal:2',
        'price_discount' => 'decimal:2',
        'price_subtotal' => 'decimal:2',
        'price_tax' => 'decimal:2',
        'price_total' => 'decimal:2',
        'price_down' => 'decimal:2',
        'price_balance' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'client_access_expires_at' => 'datetime',
        'client_form_sent_at' => 'datetime',
        'client_approved_at' => 'datetime',
        'line_items_snapshot' => 'array',
        'catalog_snapshot' => 'array',
        'quotation_finance_started_at' => 'datetime',
        'quotation_approved_at' => 'datetime',
        'invoice_generated_at' => 'datetime',
        'invoice_uploaded_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::replicating(function (DealProposal $proposal): void {
            foreach (['handler_user_id','handler_name','handler_email','client_decision_status','client_submitted_at','client_submitted_email','decision_summary_snapshot','decision_notified_at','decision_notification_error'] as $field) {
                unset($proposal->{$field});
            }
        });
        static::saving(function (DealProposal $proposal) {
            if ($proposal->exists && $proposal->getOriginal('client_submitted_at')
                && $proposal->isDirty(['line_items_snapshot','document_html','subject','price_total','price_regular','price_discount','price_tax'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['proposal'=>'Create a new revision to change a submitted proposal.']);
            }
            $issuing = $proposal->client_form_sent_at || $proposal->client_approved_at
                || in_array(strtolower((string) $proposal->status), ['sent', 'approved', 'accepted', 'contracted']);
            if (! $issuing || is_array($proposal->catalog_snapshot)
                || ! \Illuminate\Support\Facades\Schema::hasColumn('deal_proposals', 'catalog_snapshot')) {
                return;
            }
            $previouslyIssued = $proposal->getOriginal('client_form_sent_at') || $proposal->getOriginal('client_approved_at')
                || in_array(strtolower((string) $proposal->getOriginal('status')), ['sent', 'approved', 'accepted', 'contracted']);
            if ($previouslyIssued) {
                $proposal->catalog_snapshot = ['products' => [], 'terms' => [], 'conflicts' => [], 'legacy_issued' => true];
                return;
            }
            if ($deal = $proposal->deal) {
                $snapshot = app(\App\Services\ProductCatalogConfiguration::class)->forDeal($deal);
                if ($snapshot['conflicts']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['product_terms' => 'Resolve conflicting product term headings before issuing: '.implode(', ', $snapshot['conflicts'])]);
                }
                $proposal->catalog_snapshot = $snapshot;
                if (! is_array($proposal->line_items_snapshot)) {
                    $proposal->line_items_snapshot = collect(app(\App\Services\DealProposalAggregationService::class)->resolveDealLineItems($deal))->map(fn ($item) => collect($item)->except('model')->all())->all();
                }
            }
        });
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function itemResponses(): HasMany
    {
        return $this->hasMany(DealProposalItemResponse::class);
    }
}
