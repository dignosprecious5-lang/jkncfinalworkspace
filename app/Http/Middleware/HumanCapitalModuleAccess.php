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

        $myHcPermissions = [
            'access_hc_attendance',
            'access_hc_obf',
            'access_hc_employee_requests',
            'access_hc_employee_relations',
            'access_hc_memos',
            'access_hc_training',
            'access_hc_performance',
            'access_hc_awards',
        ];

        if (
            $user->isSuperAdmin()
            || $user->isAdmin()
            || $user->hasPermission($permission)
            || (in_array($permission, $myHcPermissions, true) && $user->hasPermission('access_hc_my_hc'))
        ) {
            return $next($request);
        }

        if (filter_var($employeeOwnAccess, FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        abort(403);
    }
}
