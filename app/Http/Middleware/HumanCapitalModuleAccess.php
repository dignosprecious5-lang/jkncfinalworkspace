<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HumanCapitalModuleAccess
{
    public function handle(Request $request, Closure $next, string $permission = 'access_human_capital', string $employeeOwnAccess = 'false'): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        if ($user->isSuperAdmin() || $user->isAdmin() || $user->hasPermission($permission)) {
            return $next($request);
        }

        if (filter_var($employeeOwnAccess, FILTER_VALIDATE_BOOLEAN) && $user->isEmployee()) {
            return $next($request);
        }

        abort(403);
    }
}
