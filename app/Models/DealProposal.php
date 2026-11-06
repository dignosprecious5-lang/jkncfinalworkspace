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
        'reference_id',
        'crud_id',
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
        'proposal_date' => 'date',
        'price_regular' => 'decimal:2',
        'price_discount' => 'decimal:2',
        'price_subtotal' => 'decimal:2',
        'price_tax' => 'decimal:2',
        'price_total' => 'decimal:2',
        'price_down' => 'decimal:2',
        'price_balance' => 'decimal:2',
        'client_access_expires_at' => 'datetime',
        'client_form_sent_at' => 'datetime',
        'client_approved_at' => 'datetime',
        'quotation_finance_started_at' => 'datetime',
        'quotation_approved_at' => 'datetime',
        'invoice_generated_at' => 'datetime',
        'invoice_uploaded_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DealProposalVersion::class, 'deal_proposal_id');
    }
}
