<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TransmittalReceipt extends Model
{
    protected $fillable = [
        'transmittal_id',
        'recipient_id',
        'receipt_no',
        'receipt_date',
        'mode',
        'from_name',
        'to_name',
        'office_name',
        'delivery_type',
        'delivery_detail',
        'recipient_email',
        'actions_summary',
        'prepared_by_name',
        'approved_by_name',
        'approved_position',
        'document_custodian',
        'delivered_by',
        'signed_name',
        'received_by',
        'comments',
        'receipt_code',
        'snapshot',
        'received_at',
        'generated_by',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'snapshot' => 'array',
            'received_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TransmittalReceipt $receipt): void {
            if (empty($receipt->receipt_no)) {
                $nextId = ((int) static::query()->max('id')) + 1;
                $receipt->receipt_no = 'TRR-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
            }
            if (empty($receipt->receipt_date)) {
                $receipt->receipt_date = ($receipt->received_at ?? now())->toDateString();
            }
            if (empty($receipt->received_by) && ! empty($receipt->signed_name)) {
                $receipt->received_by = $receipt->signed_name;
            }
            if (empty($receipt->signed_name) && ! empty($receipt->received_by)) {
                $receipt->signed_name = $receipt->received_by;
            }
            if ($receipt->transmittal_id) {
                $t = Transmittal::query()->with('recipients')->find($receipt->transmittal_id);
                if ($t) {
                    $mode = strtoupper((string) ($t->mode ?: $t->direction ?: 'SEND'));
                    $party = $t->party_name ?: $t->recipients->first()?->name ?: $t->related_company ?: 'External Counterparty';
                    $office = $t->office_name ?: $t->origin_office ?: 'John Kelly & Company';
                    $receipt->mode ??= $mode;
                    $receipt->from_name ??= ($mode === 'RECEIVE' ? $party : $office);
                    $receipt->to_name ??= ($mode === 'RECEIVE' ? $office : $party);
                    $receipt->office_name ??= $office;
                    $receipt->delivery_type ??= $t->delivery_type;
                    $receipt->delivery_detail ??= $t->delivery_summary;
                    $receipt->recipient_email ??= ($t->recipient_email ?: $t->recipients->first()?->email);
                    $receipt->actions_summary ??= $t->actions_summary;
                    $receipt->prepared_by_name ??= $t->prepared_by_name;
                    $receipt->approved_by_name ??= $t->approved_by_name;
                    $receipt->approved_position ??= ($t->approved_position ?: $t->operations_manager);
                    $receipt->document_custodian ??= ($t->document_custodian ?: $t->delivered_by);
                    $receipt->delivered_by ??= $t->delivered_by;
                }
            }
        });

        static::updating(fn (TransmittalReceipt $receipt) => throw new LogicException('Submitted receipts are immutable.'));
        static::deleting(fn (TransmittalReceipt $receipt) => throw new LogicException('Submitted receipts are immutable.'));
    }

    public function transmittal(): BelongsTo
    {
        return $this->belongsTo(Transmittal::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(TransmittalRecipient::class, 'recipient_id');
    }
}
