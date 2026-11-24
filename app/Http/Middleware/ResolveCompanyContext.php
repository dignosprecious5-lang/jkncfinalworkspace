<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompanyContext
{
    public function __construct(private readonly CompanyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() && ! app()->runningUnitTests()) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }
            if (\Illuminate\Support\Facades\Route::has('login')) {
                return redirect()->guest(route('login'));
            }
            abort(401, 'Authentication required to access JK&C ORDO Records Management.');
        }

        $wasSet = $this->context->has();

        try {
            $this->context->current();
        } catch (LogicException $e) {
            if ($wasSet) {
                abort(403, $e->getMessage());
            }

            $this->bootstrapDefaultCompany();
        }

        return $next($request);
    }

    /**
     * Resolve the active company scoped to the authenticated user's tenant.
     * Scoping stays server-side: every query still filters on tenant and company.
     */
    private function bootstrapDefaultCompany(): void
    {
        $user = auth()->user();
        $tenantId = $user?->tenant_id ?? null;

        $query = Company::query()->orderBy('created_at')->orderBy('id');
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $company = $query->first();

        if (! $company instanceof Company) {
            throw new LogicException('No active company found for user.');
        }

        $this->context->set($company);
    }
}
