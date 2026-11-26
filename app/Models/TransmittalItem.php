<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TransmittalItem extends Model
{
    protected $fillable = [
        'transmittal_id',
        'document_id',
        'position',
        'item_no',
        'particular',
        'unique_id',
        'quantity',
        'qty',
        'description',
        'remarks',
        'attachment_path',
    ];

    protected static function booted(): void
    {
        static::saving(function (TransmittalItem $item): void {
            if ($item->item_no === null && $item->position !== null) {
                $item->item_no = ((int) $item->position) + 1;
            } elseif ($item->position === null && $item->item_no !== null) {
                $item->position = max(0, ((int) $item->item_no) - 1);
            }

            if ($item->quantity === null && $item->qty !== null) {
                $item->quantity = (string) $item->qty;
            } elseif ($item->qty === null && $item->quantity !== null) {
                $item->qty = is_numeric($item->quantity)
                    ? (int) $item->quantity
                    : (preg_match('/\d+/', (string) $item->quantity, $m) ? (int) $m[0] : 1);
            } elseif ($item->qty === null && $item->quantity === null) {
                $item->qty = 1;
                $item->quantity = '1';
            }

            if (empty($item->attachment_path) && ! empty($item->document_id)) {
                $item->attachment_path = TransmittalDocument::query()
                    ->whereKey($item->document_id)
                    ->value('storage_path');
            }
        });

        static::updating(function (TransmittalItem $item): void {
            if ($item->transmittal()->whereNotNull('approved_snapshot')->exists()) {
                throw new LogicException('Approved transmittal items are immutable.');
            }
        });

        static::deleting(function (TransmittalItem $item): void {
            if ($item->transmittal()->whereNotNull('approved_snapshot')->exists()) {
                throw new LogicException('Approved transmittal items are immutable.');
            }
        });
    }

    public function transmittal(): BelongsTo
    {
        return $this->belongsTo(Transmittal::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(TransmittalDocument::class);
    }
}
