<?php

namespace App\Services\TransmittalV2;

/** Fail-closed provider/feature gate shared by routes and the worker. */
final class TransmittalDispatchProviderAvailability
{
    public static function isEnabled(): bool
    {
        if (! (bool) config('transmittal_v2.enabled', false)
            || ! (bool) config('transmittal_v2.dispatch.enabled', false)) {
            return false;
        }

        return match (config('transmittal_v2.dispatch.provider')) {
            'mock' => app()->environment(['local', 'testing']),
            'brevo' => (bool) config('transmittal_v2.brevo.outbox_enabled', false),
            default => false,
        };
    }
}
