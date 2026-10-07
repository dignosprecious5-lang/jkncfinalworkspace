<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EngagementPeriod;
use App\Models\OperationalTask;

class InstantiateEngagementPeriods extends Command
{
    protected $signature = 'services:instantiate-periods';
    protected $description = 'Instantiate service periods and operational tasks based on service versions and activities.';

    public function handle()
{
    $this->info('Running service period and task instantiation automation...');

    // Remove invalid generated tasks.
    OperationalTask::whereNotNull('engagement_period_id')
        ->whereNull('service_activity_id')
        ->delete();

    $engagementPeriods = EngagementPeriod::with([
        'engagement.serviceVersion.activities',
        'serviceVersion.activities',
    ])->get();

    foreach ($engagementPeriods as $period) {
        $engagement = $period->engagement;

        if (!$engagement) {
            continue;
        }

        $serviceVersion = $period->serviceVersion ?? $engagement->serviceVersion;

        if (!$serviceVersion) {
            continue;
        }

        $activities = $serviceVersion->activities()
            ->orderBy('sequence')
            ->get();

        if ($activities->isEmpty()) {
            continue;
        }

        $createdTaskMap = [];

        foreach ($activities as $activity) {

            // Prevent duplicate task for the same period + activity.
            $task = OperationalTask::where('engagement_period_id', $period->id)
                ->where('service_activity_id', $activity->id)
                ->first();

            if ($task) {
                $createdTaskMap[$activity->id] = $task->id;
                continue;
            }

            // Resolve parent task from the current period.
            $parentTaskId = null;

            if ($activity->parent_id) {
                $parentTaskId = $createdTaskMap[$activity->parent_id] ?? null;
            }

            $dueDate = null;

            if (
                $period->period_start_date &&
                $activity->due_offset_days !== null
            ) {
                $dueDate = $period->period_start_date
                    ->copy()
                    ->addDays($activity->due_offset_days);
            }

            $task = OperationalTask::create([
                'engagement_id'          => $engagement->id,
                'engagement_period_id'   => $period->id,
                'service_activity_id'    => $activity->id,
                'parent_task_id'         => $parentTaskId,
                'title'                  => $activity->name,
                'description'            => $activity->description,
                'expected_days'          => $activity->expected_days ?? 1,
                'expected_working_hours' => $activity->expected_working_hours ?? 0,
                'is_mandatory'           => $activity->is_mandatory ?? true,
                'is_billable'            => $activity->is_billable ?? true,
                'status'                 => 'pending',
                'due_date'               => $dueDate,
            ]);

            $createdTaskMap[$activity->id] = $task->id;
        }
    }

    $this->info('Automation completed successfully.');

    return 0;
}
}