<?php

namespace App\Providers;

use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIntakeItem;
use App\Policies\RecordClassificationPolicy;
use App\Policies\RecordIntakeItemPolicy;
use App\Policies\RecordPolicy;
use App\Services\CompanyContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * The company context is request state, so it is bound as a scoped
         * instance rather than resolved fresh on each injection. Every scoped
         * query in the app asks it for the tenant and company ids, and it holds
         * the resolved company for the life of the request so those calls cost
         * one query rather than dozens.
         *
         * This binding is what makes that safe. Auto-wiring would hand each
         * collaborator its own instance, and setting the context through one of
         * them would leave every other instance holding the previous company —
         * which is a tenant isolation failure, not just a stale read. A single
         * scoped instance means set() and forget() are seen by every reader.
         */
        $this->app->scoped(CompanyContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('fileSize', fn (string $expression): string => "<?php echo \\App\\Support\\FileSize::humanize({$expression}); ?>");

        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        $this->registerPolicies();
        $this->registerRateLimiters();
    }

    /**
     * Record permissions. Registered explicitly because the convention-based
     * auto-discovery expects App\Models\Record -> App\Policies\RecordPolicy.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Record::class, RecordPolicy::class);
        Gate::policy(RecordIntakeItem::class, RecordIntakeItemPolicy::class);
        Gate::policy(RecordClassification::class, RecordClassificationPolicy::class);

        /*
         * Abilities that take no model cannot be resolved through a policy,
         * because the Gate has nothing to infer the policy class from. They are
         * defined here and delegate to the same policy methods, so there is still
         * one place that decides what a role may do.
         */
        Gate::define('export', fn (?object $user) => $this->policy(RecordPolicy::class)->export($user));
        Gate::define('viewConfidential', fn (?object $user) => $this->policy(RecordPolicy::class)->viewConfidential($user));
        Gate::define('usePicker', fn (?object $user) => $this->policy(RecordPolicy::class)->usePicker($user));
        Gate::define('bulkFile', fn (?object $user) => $this->policy(RecordIntakeItemPolicy::class)->bulkFile($user));
        Gate::define('manageScheme', fn (?object $user) => $this->policy(RecordClassificationPolicy::class)->create($user));
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function policy(string $class): object
    {
        return app($class);
    }

    /**
     * Named limiters for the two expensive reads: bulk exports and the record
     * picker. Limits come from config so they can be tuned per environment.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('exports', fn (Request $request) => Limit::perMinute(
            (int) config('ordo.rate_limits.exports', 30)
        )->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('picker', fn (Request $request) => Limit::perMinute(
            (int) config('ordo.rate_limits.picker', 60)
        )->by($request->user()?->id ?: $request->ip()));
    }
}
