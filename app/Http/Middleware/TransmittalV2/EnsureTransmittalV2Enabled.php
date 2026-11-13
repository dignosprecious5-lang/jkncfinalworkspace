<?php

namespace App\Http\Middleware\TransmittalV2;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default-disabled V2 route gate. This does not replace authentication or authorization.
 */
class EnsureTransmittalV2Enabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('transmittal_v2.enabled', false), 404);

        return $next($request);
    }
}
