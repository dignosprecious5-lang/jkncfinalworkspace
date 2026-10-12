<?php

namespace App\Console\Commands;

use App\Models\OperationalTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendWeeklySummary extends Command
{
    protected $signature = 'services:weekly-summary';

    protected $description =
        'Send weekly operational task summaries to assigned users.';

    public function handle(): int
    {
        $this->info('Preparing weekly task summaries...');

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $tasks = OperationalTask::with('assignedUser')
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get()
            ->groupBy('assigned_to');

        $sentCount = 0;

        foreach ($tasks as $userId => $userTasks) {
            $user = $userTasks->first()->assignedUser;

            if (!$user) {
                continue;
            }

            // Prevent duplicate weekly summaries for the same user.
            $alreadySent = DB::table('notifications')
                ->where('type', 'App\\Notifications\\WeeklyTaskSummary')
                ->where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->where('created_at', '>=', $weekStart)
                ->where('created_at', '<=', $weekEnd)
                ->exists();

            if ($alreadySent) {
                $this->line(
                    "Summary already sent to User #{$user->id}."
                );
                continue;
            }

            $overdue = $userTasks->filter(function ($task) {
                return $task->due_date
                    && $task->due_date->lt(today());
            })->count();

            $dueThisWeek = $userTasks->filter(function ($task) use (
                $weekStart,
                $weekEnd
            ) {
                if (!$task->due_date) {
                    return false;
                }

                return $task->due_date->between(
                    $weekStart,
                    $weekEnd,
                    true
                );
            })->count();

            $totalOpen = $userTasks->count();

            $message = "Your weekly task summary: "
                . "{$totalOpen} open task(s), "
                . "{$overdue} overdue task(s), and "
                . "{$dueThisWeek} task(s) due this week.";

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\WeeklyTaskSummary',
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Weekly Task Summary',
                    'message' => $message,
                    'open_tasks' => $totalOpen,
                    'overdue_tasks' => $overdue,
                    'due_this_week' => $dueThisWeek,
                    'url' => '/services',
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info(
                "Weekly summary sent to User #{$user->id}."
            );

            $sentCount++;
        }

        $this->info(
            "Weekly summary completed. "
            . "Total summaries sent: {$sentCount}"
        );

        return self::SUCCESS;
    }
}
