<?php

declare(strict_types=1);

namespace App\Http\Middleware\TransmittalV2;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RedirectStandaloneTransmittalV2Interface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('transmittal_v2.enabled', false)) {
            abort(404);
        }

        return redirect()->route('transmittal.index', [], 303);
    }
}
