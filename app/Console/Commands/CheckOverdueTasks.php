<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:check-overdue-tasks {--dry-run : Show overdue tasks without updating their status}')]
#[Description('Mark incomplete operational tasks as overdue when their deadline has passed.')]
class CheckOverdueTasks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('operational_tasks')) {
            $this->warn('No operational_tasks table found. Nothing to check.');

            return self::SUCCESS;
        }

        $columns = Schema::getColumnListing('operational_tasks');

        if (! in_array('status', $columns, true) || ! in_array('created_at', $columns, true)) {
            $this->error('The operational_tasks table must include status and created_at columns.');

            return self::FAILURE;
        }

        $hasDueDate = in_array('due_date', $columns, true);
        $hasExpectedDays = in_array('expected_days', $columns, true);

        if (! $hasDueDate && ! $hasExpectedDays) {
            $this->error('The operational_tasks table must include either due_date or expected_days.');

            return self::FAILURE;
        }

        $tasks = DB::table('operational_tasks')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        $overdueCount = 0;
        $updatedCount = 0;

        foreach ($tasks as $task) {
            $dueAt = $hasDueDate && filled($task->due_date)
                ? Carbon::parse($task->due_date)->endOfDay()
                : Carbon::parse($task->created_at)->addDays(max(1, (int) $task->expected_days));

            if ($dueAt->isFuture()) {
                continue;
            }

            $overdueCount++;

            if ($task->status !== 'overdue' && ! $this->option('dry-run')) {
                DB::table('operational_tasks')
                    ->where('id', $task->id)
                    ->update([
                        'status' => 'overdue',
                        'updated_at' => now(),
                    ]);

                $updatedCount++;
            }

            $this->line(sprintf(
                '#%s %s — overdue since %s',
                $task->id,
                $task->title ?? 'Untitled task',
                $dueAt->toDateString(),
            ));
        }

        if ($this->option('dry-run')) {
            $this->info("Found {$overdueCount} overdue task(s). No statuses were changed.");
        } else {
            $this->info("Found {$overdueCount} overdue task(s); marked {$updatedCount} as overdue.");
        }

        return self::SUCCESS;
    }
}
