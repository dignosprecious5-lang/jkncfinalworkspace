<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OperationalTask;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SendReminders extends Command
{
    protected $signature = 'services:send-reminders';
    protected $description = 'Send internal reminders for operational tasks based on configured service version settings.';

    public function handle()
    {
        $this->info('Running internal task reminders automation...');

        // Kunin ang lahat ng active tasks na may due date
        $tasks = OperationalTask::whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        $sentCount = 0;
        $today = now()->toDateString();

        foreach ($tasks as $task) {
            // Gumamit ng default na 3 days reminder lead time
            $reminderDays = 3;

            // Kalkulahin ang target date (due_date minus 3 days)
            $dueDate = \Carbon\Carbon::parse($task->due_date);
            $targetReminderDate = $dueDate->copy()->subDays($reminderDays)->toDateString();

            $this->info("Checking Task #{$task->id}: Due {$task->due_date} | Target Reminder Date: {$targetReminderDate} | Today: {$today}");

            // Kung ngayon na ang eksaktong araw ng reminder
            if ($today === $targetReminderDate) {
                // Suriin kung naipadala na ang reminder para sa due date na ito
                $exists = DB::table('task_reminders')
                    ->where('operational_task_id', $task->id)
                    ->where('reminder_type', 'internal')
                    ->where('target_due_date', $task->due_date)
                    ->exists();

                if (!$exists) {
                    // I-record sa task_reminders table
                    DB::table('task_reminders')->insert([
                        'operational_task_id' => $task->id,
                        'reminder_type' => 'internal',
                        'target_due_date' => $task->due_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Magpadala ng notification sa notifications table
                    $users = User::all();
                    $message = "Task '{$task->title}' is due in {$reminderDays} days ({$task->due_date}).";

                    foreach ($users as $user) {
                        DB::table('notifications')->insert([
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'type' => 'App\Notifications\InternalTaskReminder',
                            'notifiable_type' => get_class($user),
                            'notifiable_id' => $user->id,
                            'data' => json_encode([
                                'title' => 'Internal Task Reminder',
                                'message' => $message,
                                'operational_task_id' => $task->id,
                            ]),
                            'read_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $this->info("Dispatched internal reminder for Task #{$task->id}");
                    $sentCount++;
                }
            }
        }

        $this->info("Reminder automation completed. Total reminders sent: {$sentCount}");
        return 0;
    }
}