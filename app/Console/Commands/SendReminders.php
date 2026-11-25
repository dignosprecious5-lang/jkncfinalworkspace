<?php

namespace App\Console\Commands;

use App\Models\OperationalTask;
use App\Models\User;
use App\Notifications\WorkspaceUpdatedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendReminders extends Command
{
    protected $signature = 'services:send-reminders';

    protected $description = 'Send idempotent internal task and client follow-up reminders for active services';

    public function handle(): int
    {
        $lock = Cache::lock('command:services:send-reminders', 300);
        if (! $lock->get()) {
            $this->warn('Another services:send-reminders process is already running.');
            return self::SUCCESS;
        }

        try {
            $this->info('Running reminder automation...');

            $tz = config('app.timezone', 'Asia/Manila');
            $today = now($tz)->startOfDay();
            $processed = 0;

            if (! Schema::hasTable('operational_tasks') || ! Schema::hasTable('task_reminders')) {
                $this->warn('Required reminder tables are not present.');
                return self::SUCCESS;
            }

            $tasks = OperationalTask::query()
                ->whereNotNull('due_date')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->with([
                    'engagementPeriod.serviceVersion',
                    'engagement.serviceVersion',
                ])
                ->get();

            $recipients = $this->eligibleRecipients();

            if ($recipients->isEmpty()) {
                $this->warn('No eligible active service recipients found.');
                return self::SUCCESS;
            }

            foreach ($tasks as $task) {
                $version = $task->engagementPeriod?->serviceVersion
                    ?? $task->engagement?->serviceVersion;

                if (! $version) {
                    continue;
                }

                $dueDate = Carbon::parse($task->due_date, $tz)->startOfDay();
                $channel = strtolower(trim((string) $version->notification_channel));
                $cadence = strtolower(trim((string) $version->client_reminder_cadence));

                $validChannel = in_array($channel, ['app_only', 'email_only', 'email_and_app'], true);

                if (
                    $version->send_due_reminders
                    && $version->auto_send_due_reminders
                    && $validChannel
                ) {
                    $triggerDays = max(0, (int) ($version->internal_reminder_days ?? 3));
                    $internalDate = $dueDate->copy()->subDays($triggerDays);

                    if ($today->greaterThanOrEqualTo($internalDate)) {
                        if ($this->sendOnce(
                            task: $task,
                            dueDate: $dueDate,
                            reminderType: 'internal',
                            label: 'Service Task Reminder',
                            recipients: $recipients,
                            channel: $channel,
                            today: $today
                        )) {
                            $processed++;
                        }
                    }
                }

                $intervalDays = match ($cadence) {
                    'daily' => 1,
                    'every_3_days', 'every 3 days' => 3,
                    'weekly' => 7,
                    default => null,
                };

                if ($intervalDays === null || ! $validChannel || $today->lt($dueDate)) {
                    continue;
                }

                $lastFollowup = DB::table('task_reminders')
                    ->where('operational_task_id', $task->id)
                    ->where('reminder_type', 'client_followup')
                    ->whereDate('target_due_date', $dueDate->toDateString())
                    ->orderByDesc('created_at')
                    ->first();

                if ($lastFollowup) {
                    $nextDate = Carbon::parse($lastFollowup->created_at, $tz)->startOfDay()->addDays($intervalDays);
                    if ($today->lt($nextDate)) {
                        continue;
                    }
                }

                if ($this->sendOnce(
                    task: $task,
                    dueDate: $dueDate,
                    reminderType: 'client_followup',
                    label: 'Service Client Follow-up',
                    recipients: $recipients,
                    channel: $channel,
                    today: $today
                )) {
                    $processed++;
                }
            }

            $this->info("Reminder automation completed. Processed: {$processed}");
            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function eligibleRecipients()
    {
        return User::query()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->whereNull('disabled_at')
            ->whereNull('archived_at')
            ->get()
            ->filter(fn (User $u) => ! $u->isClient() && ($u->isAdmin() || $u->isSuperAdmin() || $u->hasPermission('access_services')))
            ->values();
    }

    private function sendOnce(
        OperationalTask $task,
        Carbon $dueDate,
        string $reminderType,
        string $label,
        $recipients,
        string $channel,
        Carbon $today
    ): bool {
        return DB::transaction(function () use ($task, $dueDate, $reminderType, $label, $recipients, $channel, $today) {
            $alreadySentToday = DB::table('task_reminders')
                ->where('operational_task_id', $task->id)
                ->where('reminder_type', $reminderType)
                ->whereDate('target_due_date', $dueDate->toDateString())
                ->whereDate('created_at', $today->toDateString())
                ->exists();

            if ($alreadySentToday) {
                return false;
            }

            DB::table('task_reminders')->insert([
                'operational_task_id' => $task->id,
                'reminder_type' => $reminderType,
                'target_due_date' => $dueDate->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (in_array($channel, ['app_only', 'email_and_app'], true)) {
                $url = \Illuminate\Support\Facades\Route::has('services.index') ? route('services.index') : '/services';
                foreach ($recipients as $user) {
                    $user->notify(new WorkspaceUpdatedNotification(
                        $label,
                        $task->id,
                        "{$task->title} (Due {$dueDate->toDateString()})",
                        'System Automation',
                        $url
                    ));
                }
            }

            return true;
        });
    }
}
