<?php

namespace App\Providers;

use App\Models\Call;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\MessageConversation;
use App\Models\OfficialBusinessTrip;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIntakeItem;
use App\Models\Task;
use App\Models\TaskTimer;
use App\Observers\ActivityAuditObserver;
use App\Observers\TaskTimerAuditObserver;
use App\Policies\RecordClassificationPolicy;
use App\Policies\RecordIntakeItemPolicy;
use App\Policies\RecordPolicy;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CompanyContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Task::observe(ActivityAuditObserver::class);
        Event::observe(ActivityAuditObserver::class);
        Call::observe(ActivityAuditObserver::class);
        Meeting::observe(ActivityAuditObserver::class);
        MessageConversation::observe(ActivityAuditObserver::class);
        OfficialBusinessTrip::observe(ActivityAuditObserver::class);
        TaskTimer::observe(TaskTimerAuditObserver::class);

        Gate::policy(Record::class, RecordPolicy::class);
        Gate::policy(RecordIntakeItem::class, RecordIntakeItemPolicy::class);
        Gate::policy(RecordClassification::class, RecordClassificationPolicy::class);

        Gate::define('export', fn (?object $user) => $user !== null && app(RecordPolicy::class)->export($user));
        Gate::define('viewConfidential', fn (?object $user) => $user !== null && app(RecordPolicy::class)->viewConfidential($user));
        Gate::define('usePicker', fn (?object $user) => $user !== null && app(RecordPolicy::class)->usePicker($user));
        Gate::define('bulkFile', fn (?object $user) => $user !== null && app(RecordIntakeItemPolicy::class)->bulkFile($user));
        Gate::define('manageScheme', fn (?object $user) => $user !== null && app(RecordClassificationPolicy::class)->create($user));

        Blade::directive('fileSize', fn (string $expression): string => "<?php echo \\App\\Support\\FileSize::humanize({$expression}); ?>");

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
