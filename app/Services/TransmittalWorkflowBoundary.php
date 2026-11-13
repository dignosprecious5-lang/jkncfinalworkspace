<?php

namespace App\Services;

use App\Models\Transmittal;
use App\Models\TransmittalDocument;
use App\Models\TransmittalReceipt;
use App\Models\TransmittalRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Explicit workflow boundaries only; never registered as a global model scope. */
final class TransmittalWorkflowBoundary
{
    public static function v1Query(): Builder
    {
        $query = Transmittal::query();
        // Preserve legacy reads before the already-proposed classification migration.
        // Never cache this metadata across a migration or infer historical versions.
        if (! $query->getModel()->getConnection()->getSchemaBuilder()->hasColumn('transmittals', 'workflow_version')) {
            return $query;
        }

        return $query->where(function (Builder $legacy): void {
            $legacy->whereNull('transmittals.workflow_version')
                ->orWhere('transmittals.workflow_version', 'v1');
        });
    }

    public static function v2Query(): Builder
    {
        return Transmittal::query()->where('transmittals.workflow_version', 'v2');
    }

    public static function isV2(Transmittal $transmittal): bool
    {
        return $transmittal->exists
            && $transmittal->getRawOriginal('workflow_version') === 'v2'
            && $transmittal->getAttribute('workflow_version') === 'v2';
    }

    public static function requireV2(Transmittal $transmittal): Transmittal
    {
        abort_unless(self::isV2($transmittal), 404);

        return self::v2Query()->findOrFail($transmittal->getKey());
    }

    public static function v2Children(string $modelClass): Builder
    {
        return $modelClass::query()->whereHas('transmittal', function (Builder $parent): void {
            $parent->where('transmittals.workflow_version', 'v2');
        });
    }

    public static function assertChild(Transmittal $transmittal, Model $child): void
    {
        abort_unless(self::isV2($transmittal)
            && $child->exists
            && (string) $child->getAttribute('transmittal_id') === (string) $transmittal->getKey(), 404);
    }

    public static function assertV2ChildWrite(Model $child): void
    {
        $parentId = $child->getAttribute('transmittal_id');
        abort_unless($parentId !== null && self::v2Query()->whereKey($parentId)->exists(), 404);
        // A child must not be moved away from its original record, including a
        // historical NULL/v1 parent, to bypass workflow or association checks.
        if ($child->exists) {
            abort_unless((string) $child->getRawOriginal('transmittal_id') === (string) $parentId, 404);
        }

        $attributes = $child->getAttributes();
        foreach (['document_id' => TransmittalDocument::class, 'recipient_id' => TransmittalRecipient::class,
            'receipt_id' => TransmittalReceipt::class] as $attribute => $modelClass) {
            $id = $attributes[$attribute] ?? null;
            if ($id === null) {
                continue;
            }
            $related = self::v2Children($modelClass)->where('transmittal_id', $parentId)->whereKey($id);
            if ($attribute === 'receipt_id' && ($attributes['recipient_id'] ?? null) !== null) {
                $related->where('recipient_id', $attributes['recipient_id']);
            }
            abort_unless($related->exists(), 404);
        }
    }
}
