<?php

namespace App\Services;

use App\Models\Engagement;
use App\Models\EngagementPeriod;
use App\Models\OperationalTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ServiceAutomationManager
{
    /**
     * Process automation for all eligible active regular engagements.
     */
    public function processActiveEngagements(): void
    {
        $engagements = Engagement::with(['serviceVersion.serviceActivities', 'engagementPeriods'])
            ->where('status', 'active')
            ->get();

        foreach ($engagements as $engagement) {
            try {
                $this->processEngagement($engagement);
            } catch (\Exception $e) {
                Log::error("Failed to process automation for Engagement #{$engagement->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Process a single engagement for period and task instantiation.
     */
    public function processEngagement(Engagement $engagement): ?EngagementPeriod
    {
        $version = $engagement->serviceVersion;

        if (!$version || !$version->serviceActivities->count()) {
            return null;
        }

        if (!$this->isRegularEngagement($engagement)) {
            return null;
        }

        $periodData = $this->calculateNextPeriodDates($engagement, $version);
        if (!$periodData) {
            return null;
        }

        // Prevent Duplicate Engagement Period
        $existingPeriod = EngagementPeriod::where('engagement_id', $engagement->id)
            ->where('period_start_date', $periodData['start_date'])
            ->where('period_end_date', $periodData['end_date'])
            ->first();

        if ($existingPeriod) {
            $period = $existingPeriod;
        } else {
            $period = EngagementPeriod::create([
                'engagement_id' => $engagement->id,
                'service_version_id' => $version->id,
                'period_name' => $periodData['name'],
                'period_start_date' => $periodData['start_date'],
                'period_end_date' => $periodData['end_date'],
                'status' => 'pending',
                'generated_at' => Carbon::now(),
            ]);
        }

        $this->instantiateTasksForPeriod($engagement, $period, $version);

        return $period;
    }

    protected function isRegularEngagement(Engagement $engagement): bool
    {
        $type = strtolower($engagement->engagement_type ?? 'regular');
        return in_array($type, ['regular', 'recurring']);
    }

    protected function calculateNextPeriodDates(Engagement $engagement, $version): ?array
    {
        $frequency = strtolower($version->recurrence_frequency ?? 'monthly');
        
        $latestPeriod = EngagementPeriod::where('engagement_id', $engagement->id)
            ->orderBy('period_end_date', 'desc')
            ->first();

        if ($latestPeriod) {
            $startDate = Carbon::parse($latestPeriod->period_end_date)->addDay();
        } else {
            $startDate = $version->base_reference_date 
                ? Carbon::parse($version->base_reference_date) 
                : Carbon::parse($engagement->start_date ?? now());
        }

        $endDate = match($frequency) {
            'quarterly' => (clone $startDate)->addMonths(3)->subDay(),
            'semiannual' => (clone $startDate)->addMonths(6)->subDay(),
            'annual' => (clone $startDate)->addYear()->subDay(),
            default => (clone $startDate)->copy()->endOfMonth(),
        };
        
        if ($frequency === 'monthly') {
            $startDate->startOfMonth();
            $endDate->endOfMonth();
        }

        $periodName = $startDate->format('F Y');

        return [
            'name' => $periodName,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    protected function instantiateTasksForPeriod(Engagement $engagement, EngagementPeriod $period, $version): void
    {
       $activities = $version->serviceActivities()
    ->orderBy('sequence')
    ->get();

        foreach ($activities as $activity) {
            $exists = OperationalTask::where('engagement_period_id', $period->id)
                ->where('service_activity_id', $activity->id)
                ->exists();

            if ($exists) {
                continue;
            }

            OperationalTask::create([
                'engagement_id' => $engagement->id,
                'engagement_period_id' => $period->id,
                'service_activity_id' => $activity->id,
                'title' => $activity->name,
                'description' => $activity->description ?? null,
                'expected_days' => $activity->expected_days ?? 1,
                'expected_working_hours' => $activity->expected_working_hours ?? 8,
                'is_mandatory' => $activity->is_mandatory ?? 1,
                'is_billable' => $activity->is_billable ?? 1,
                'status' => 'pending',
                'due_date' => $period->period_end_date,
            ]);
        }
    }
}