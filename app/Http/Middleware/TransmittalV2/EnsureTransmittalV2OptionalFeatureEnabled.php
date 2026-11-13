<?php

namespace App\Http\Middleware\TransmittalV2;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Keeps optional V2 capabilities unavailable unless each is explicitly enabled. */
final class EnsureTransmittalV2OptionalFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless((bool) config('transmittal_v2.enabled', false)
            && in_array($feature, ['ocr', 'smart_upload'], true)
            && (bool) config('transmittal_v2.optional_features.'.$feature, false), 404);

        return $next($request);
    }
}
