<?php

namespace App\Http\Controllers\TransmittalV2;

use App\Http\Controllers\Controller;
use App\Services\TransmittalV2\TransmittalProviderEventProcessor;
use App\Services\TransmittalV2\TransmittalBrevoEventProcessor;
use App\Services\TransmittalV2\TransmittalDispatchProviderAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransmittalProviderEventController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        TransmittalProviderEventProcessor $processor,
        TransmittalBrevoEventProcessor $brevoProcessor
    ): JsonResponse
    {
        abort_unless(TransmittalDispatchProviderAvailability::isEnabled(), 404);

        if ($provider === 'brevo' && config('transmittal_v2.dispatch.provider') === 'brevo'
            && (bool) config('transmittal_v2.brevo.outbox_enabled', false)) {
            $token = $request->bearerToken() ?? '';
            $expected = config('transmittal_v2.dispatch.event_secret');
            abort_unless(is_string($expected) && strlen($expected) >= 32 && $token !== ''
                && hash_equals($expected, $token), 403);
            try {
                $accepted = $brevoProcessor->process($request->getContent(), $token);
            } catch (\RuntimeException) {
                return response()->json(['accepted' => false], 422);
            }
            return response()->json(['accepted' => $accepted], $accepted ? 202 : 200);
        }

        abort_unless($provider === 'mock'
            && app()->environment(['local', 'testing'])
            && config('transmittal_v2.dispatch.provider') === 'mock', 404);

        $timestamp = (string) $request->header('X-ORDO-Event-Timestamp', '');
        $signature = (string) $request->header('X-ORDO-Event-Signature', '');
        $accepted = $processor->process($request->getContent(), $timestamp, $signature, $provider);

        return response()->json(['accepted' => $accepted], $accepted ? 202 : 200);
    }
}
