<?php

namespace App\Console\Commands;

use App\Models\OperationalTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendReminders extends Command
{
    protected $signature = 'services:send-reminders';

    protected $description = 'Send internal task and client follow-up reminders';

    public function handle(): int
    {
        $this->info('Running reminder automation...');

        $today = now()->startOfDay();
        $processed = 0;

        $tasks = OperationalTask::query()
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with([
                'engagementPeriod.serviceVersion',
                'engagement.serviceVersion',
            ])
            ->get();

        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('No users found.');
            return self::SUCCESS;
        }

        foreach ($tasks as $task) {
            $version = $task->engagementPeriod?->serviceVersion
                ?? $task->engagement?->serviceVersion;

            if (!$version) {
                $this->warn("Task #{$task->id}: ServiceVersion not found.");
                continue;
            }

            $dueDate = Carbon::parse($task->due_date)->startOfDay();

            $channel = strtolower(trim(
                (string) $version->notification_channel
            ));

            $cadence = strtolower(trim(
                (string) $version->client_reminder_cadence
            ));

            $validChannel = in_array($channel, [
                'app_only',
                'email_only',
                'email_and_app',
            ], true);

            /*
            |--------------------------------------------------------------------------
            | INTERNAL REMINDER
            |--------------------------------------------------------------------------
            | Sends one internal reminder when the configured trigger date arrives.
            */

            if (
                $version->send_due_reminders
                && $version->auto_send_due_reminders
                && $validChannel
            ) {
                $triggerDays = max(
                    0,
                    (int) ($version->internal_reminder_days ?? 3)
                );

                $internalDate = $dueDate->copy()->subDays($triggerDays);

                if ($today->greaterThanOrEqualTo($internalDate)) {
                    $this->sendOnce(
                        task: $task,
                        dueDate: $dueDate,
                        reminderType: 'internal',
                        message: "Task '{$task->title}' is due on "
                            . $dueDate->toDateString() . '.',
                        users: $users,
                        channel: $channel
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CLIENT FOLLOW-UP CADENCE
            |--------------------------------------------------------------------------
            | Starts on the due date and repeats at the selected interval.
            */

            $intervalDays = match ($cadence) {
                'daily' => 1,
                'every_3_days' => 3,
                'weekly' => 7,
                default => null,
            };

            if (
                $intervalDays === null
                || !$validChannel
                || $today->lt($dueDate)
            ) {
                continue;
            }

            $lastFollowup = DB::table('task_reminders')
                ->where('operational_task_id', $task->id)
                ->where('reminder_type', 'client_followup')
                ->whereDate('target_due_date', $dueDate->toDateString())
                ->orderByDesc('created_at')
                ->first();

            if ($lastFollowup) {
                $nextDate = Carbon::parse(
                    $lastFollowup->created_at
                )->startOfDay()->addDays($intervalDays);

                if ($today->lt($nextDate)) {
                    continue;
                }
            }

            $this->sendOnce(
                task: $task,
                dueDate: $dueDate,
                reminderType: 'client_followup',
                message: "Client follow-up reminder for task "
                    . "'{$task->title}'. Due date: "
                    . $dueDate->toDateString() . '.',
                users: $users,
                channel: $channel
            );

            $processed++;
        }

        $this->info("Reminder automation completed. Processed: {$processed}");

        return self::SUCCESS;
    }

    private function sendOnce(
        OperationalTask $task,
        Carbon $dueDate,
        string $reminderType,
        string $message,
        $users,
        string $channel
    ): void {
        DB::transaction(function () use (
            $task,
            $dueDate,
            $reminderType,
            $message,
            $users,
            $channel
        ) {
            $alreadySentToday = DB::table('task_reminders')
                ->where('operational_task_id', $task->id)
                ->where('reminder_type', $reminderType)
                ->whereDate('target_due_date', $dueDate->toDateString())
                ->whereDate('created_at', today()->toDateString())
                ->exists();

            if ($alreadySentToday) {
                return;
            }

            DB::table('task_reminders')->insert([
                'operational_task_id' => $task->id,
                'reminder_type' => $reminderType,
                'target_due_date' => $dueDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // App notifications appear in the Laravel notification bell.
            if (in_array($channel, ['app_only', 'email_and_app'], true)) {
                foreach ($users as $user) {
                    DB::table('notifications')->insert([
                        'id' => (string) Str::uuid(),
                        'type' => 'App\\Notifications\\InternalTaskReminder',
                        'notifiable_type' => get_class($user),
                        'notifiable_id' => $user->id,
                        'data' => json_encode([
                            'title' => $reminderType === 'client_followup'
                                ? 'Client Follow-up'
                                : 'Task Reminder',
                            'message' => $message,
                            'operational_task_id' => $task->id,
                            'url' => '/services',
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}