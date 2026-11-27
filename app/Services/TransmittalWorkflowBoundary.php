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
        return Transmittal::query();
    }

    public static function isV2(Transmittal $transmittal): bool
    {
        return $transmittal->exists;
    }

    public static function requireV2(Transmittal $transmittal): Transmittal
    {
        return $transmittal;
    }

    public static function v2Children(string $modelClass): Builder
    {
        return $modelClass::query();
    }

    public static function assertChild(Transmittal $transmittal, Model $child): void
    {
        abort_unless($child->exists
            && (string) $child->getAttribute('transmittal_id') === (string) $transmittal->getKey(), 404);
    }

    public static function assertV2ChildWrite(Model $child): void
    {
        $parentId = $child->getAttribute('transmittal_id');
        abort_unless($parentId !== null && Transmittal::query()->whereKey($parentId)->exists(), 404);
        if ($child->exists) {
            abort_unless((string) $child->getRawOriginal('transmittal_id') === (string) $parentId, 404);
        }
    }
}
