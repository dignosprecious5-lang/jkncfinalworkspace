<?php

namespace App\Models;

use App\Models\Concerns\RequiresTransmittalV2Parent;
use App\Services\TransmittalWorkflowBoundary;
use App\Services\TransmittalWorkspaceItemSyncService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransmittalWorkspaceItem extends Model
{
    use RequiresTransmittalV2Parent;

    protected $fillable = [
        'transmittal_id',
        'document_id',
        'position',
        'line_number',
        'particular',
        'unique_id',
        'quantity',
        'description',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'string',
        ];
    }

    protected static function booted(): void
    {
        $guard = static function (self $item): void {
            $parent = TransmittalWorkflowBoundary::v2Query()->whereKey($item->transmittal_id)
                ->lockForUpdate()->firstOrFail();
            (new TransmittalWorkspaceItemSyncService())->assertEditable($parent);
        };
        static::saving($guard);
        static::deleting($guard);
    }

    public function transmittal(): BelongsTo
    {
        return $this->belongsTo(Transmittal::class)->where('transmittals.workflow_version', 'v2');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(TransmittalDocument::class);
    }
}
