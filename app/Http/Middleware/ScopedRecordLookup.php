<?php

namespace App\Http\Middleware;

use App\Models\Record;
use App\Services\CompanyContext;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a record from the route under the active company's scope.
 *
 * Every controller repeats this lookup, and every repeat is a chance to forget a
 * scope clause. Doing it once here means a foreign record is a 404 by
 * construction rather than by careful copying.
 */
class ScopedRecordLookup
{
    public function __construct(private readonly CompanyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resolved = $request->route('record');

        // SubstituteBindings may already have bound the parameter to a model, and
        // that binding is unscoped. Re-query under the company scope regardless of
        // what arrived, so a foreign record is a 404 here rather than something a
        // controller could act on.
        $key = $resolved instanceof Record ? $resolved->getKey() : $resolved;

        $record = Record::query()
            ->where('tenant_id', $this->context->tenantId())
            ->where('company_id', $this->context->id())
            ->with(['classification', 'index', 'files', 'links', 'activityLogs.user'])
            ->find($key);

        if (! $record instanceof Record) {
            throw (new ModelNotFoundException)->setModel(Record::class);
        }

        // Re-bound under the scoped model so controllers cannot reach for the raw
        // route parameter and get the unscoped instance back.
        $request->route()->setParameter('record', $record);

        return $next($request);
    }
}
