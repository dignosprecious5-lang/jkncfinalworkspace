<?php

namespace App\Models\Concerns;

use App\Services\TransmittalWorkflowBoundary;
use Illuminate\Database\Eloquent\Model;

/** Applies only to V2 child models, never the shared V1 parent/item/receipt models. */
trait RequiresTransmittalV2Parent
{
    public static function bootRequiresTransmittalV2Parent(): void
    {
        static::saving(function (Model $record): void {
            TransmittalWorkflowBoundary::assertV2ChildWrite($record);
        });

        static::deleting(function (Model $record): void {
            TransmittalWorkflowBoundary::assertV2ChildWrite($record);
        });
    }
}
