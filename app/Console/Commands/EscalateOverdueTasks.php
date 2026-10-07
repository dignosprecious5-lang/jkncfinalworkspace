<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperationalTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EscalateOverdueTasks extends Command
{
    protected $signature = 'services:escalate-overdue';
    protected $description = 'Find overdue operational tasks and send escalations based on service version settings.';

    public function handle()
    {
        $this->info('Running overdue escalation automation...');

        $today = Carbon::today();
        
        // Kunin ang tasks nang walang strict eager loading para hindi ma-skip
        $tasks = OperationalTask::whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        $this->info("Total pending tasks with due date found: " . $tasks->count());

        $escalatedCount = 0;

        foreach ($tasks as $task) {
            try {
                $dueDate = Carbon::parse($task->due_date)->startOfDay();
                
                // Debug log para sa bawat task
                $this->line("Checking Task #{$task->id} | Due: {$dueDate->format('Y-m-d')} | Today: {$today->format('Y-m-d')}");

                // Dapat overdue na (due_date is before today)
                if ($dueDate->gte($today)) {
                    $this->line(" -> Skipped: Not overdue yet.");
                    continue;
                }

                $overdueDays = (int) $dueDate->diffInDays($today);

                // Ligtas na pagkuha ng ServiceVersion
                $serviceVersion = null;
                if ($task->engagementPeriod && method_exists($task->engagementPeriod, 'engagement') && $task->engagementPeriod->engagement) {
                    $serviceVersion = $task->engagementPeriod->engagement->serviceVersion ?? null;
                }
                if (!$serviceVersion && $task->engagement) {
                    $serviceVersion = $task->engagement->serviceVersion ?? null;
                }

                // Threshold: Default sa 2 kung null
                $thresholdDays = $serviceVersion && $serviceVersion->escalation_threshold_days !== null
                    ? (int) $serviceVersion->escalation_threshold_days
                    : 2;

                // Auto-escalate rule check
                $autoEscalate = true;
                if ($serviceVersion && Schema::hasColumn('service_versions', 'auto_escalate_overdue')) {
                    $autoEscalate = (bool) $serviceVersion->auto_escalate_overdue;
                }

                $this->line(" -> Overdue days: {$overdueDays} | Threshold: {$thresholdDays}");

                if (!$autoEscalate) {
                    $this->line(" -> Skipped: Auto-escalation is disabled.");
                    continue;
                }

                if ($overdueDays < $thresholdDays) {
                    $this->line(" -> Skipped: Overdue days is less than threshold.");
                    continue;
                }

                // Recipient Resolution
                $targetUserId = $task->assigned_to;
                if (!$targetUserId) {
                    $fallbackUser = User::first();
                    $targetUserId = $fallbackUser ? $fallbackUser->id : 1;
                }
                $recipient = User::find($targetUserId) ?: User::first();

                if (!$recipient) {
                    $this->line(" -> Skipped: No recipient user found.");
                    continue;
                }

                $escalationDate = $today->toDateString();

                // Duplicate prevention check sa task_escalations table
                $exists = DB::table('task_escalations')
                    ->where('operational_task_id', $task->id)
                    ->where('escalation_type', 'overdue')
                    ->where('escalation_date', $escalationDate)
                    ->exists();

                if ($exists) {
                    $this->line(" -> Skipped: Escalation already sent today.");
                    continue;
                }

                // I-record sa task_escalations table
                DB::table('task_escalations')->insert([
                    'operational_task_id' => $task->id,
                    'escalation_type' => 'overdue',
                    'target_user_id' => $recipient->id,
                    'escalation_date' => $escalationDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Lumikha ng in-app notification sa notifications table
                if (Schema::hasTable('notifications')) {
                    $taskName = $task->title ?? 'Operational Task';
                    $message = "Task '{$taskName}' (#{$task->id}) is overdue by {$overdueDays} days. Due date: {$task->due_date}.";

                    DB::table('notifications')->insert([
                        'id' => (string) Str::uuid(),
                        'type' => 'App\Notifications\OverdueTaskEscalation',
                        'notifiable_type' => get_class($recipient),
                        'notifiable_id' => $recipient->id,
                        'data' => json_encode([
                            'title' => 'Overdue Task Escalation',
                            'message' => $message,
                            'operational_task_id' => $task->id,
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->info(" -> Escalation SENT for Task #{$task->id} to User #{$recipient->id}.");
                $escalatedCount++;

            } catch (\Exception $e) {
                $this->error("Failed to process task #{$task->id}: " . $e->getMessage());
            }
        }

        $this->info("Overdue escalation automation completed.");
        $this->info("Total escalations sent: {$escalatedCount}");
        return 0;
    }
}