<?php

namespace App\Providers;

use App\Models\Transmittal;
use App\Models\User;
use App\Policies\TransmittalV2Policy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use App\Services\TransmittalV2\MockTransmittalDispatchProvider;
use App\Services\TransmittalV2\BrevoTransmittalDispatchProvider;
use App\Services\TransmittalV2\BrevoTransmittalService;
use App\Services\TransmittalV2\TransmittalDispatchProvider;

/**
 * Namespaced V2 abilities only. The V2 feature gate remains disabled by default.
 */
final class TransmittalV2AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TransmittalDispatchProvider::class, function (): TransmittalDispatchProvider {
            if (app()->environment(['local', 'testing'])
                && config('transmittal_v2.dispatch.provider') === 'mock') {
                return new MockTransmittalDispatchProvider;
            }
            if (config('transmittal_v2.dispatch.provider') === 'brevo'
                && (bool) config('transmittal_v2.brevo.outbox_enabled', false)) {
                return new BrevoTransmittalDispatchProvider($this->app->make(BrevoTransmittalService::class));
            }
            throw new RuntimeException('No production V2 email provider adapter is approved or configured.');
        });
    }

    public function boot(): void
    {
        $policy = $this->app->make(TransmittalV2Policy::class);

        foreach ([
            'view' => 'view',
            'view-attachments' => 'viewAttachments',
            'approve' => 'approve',
            'reject' => 'reject',
            'transmit' => 'transmit',
            'upload-proof' => 'uploadProof',
        ] as $ability => $method) {
            Gate::define('transmittal-v2.'.$ability, static function (User $user, Transmittal $transmittal) use ($policy, $method): bool {
                return $policy->{$method}($user, $transmittal);
            });
        }
    }
}
