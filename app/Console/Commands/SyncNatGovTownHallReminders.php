<?php

namespace App\Console\Commands;

use App\Http\Controllers\Concerns\SyncsDeadlineTownHallMemo;
use App\Models\NatGov;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncNatGovTownHallReminders extends Command
{
    use SyncsDeadlineTownHallMemo;

    protected $signature = 'natgov:sync-reminders';

    protected $description = 'Create or update Town Hall reminders for NatGov renewal dates';

    public function handle(): int
    {
        if (!Schema::hasTable('nat_govs')) {
            $this->info('NatGov table does not exist. Nothing to sync.');

            return self::SUCCESS;
        }

        $records = NatGov::query()
            ->where(function ($query) {
                $query->whereNotNull('renewal_date')
                    ->orWhereNotNull('deadline_date');
            })
            ->get();

        foreach ($records as $record) {
            $deadline = $record->renewal_date?->toDateString() ?: $record->deadline_date?->toDateString();
            if (!$deadline) {
                continue;
            }

            $this->syncNatGovReminderSeries(
                $record,
                $deadline,
                'NatGov',
                trim(($record->agency ?: 'NatGov filing') . ' - ' . ($record->client ?: $record->registration_no ?: 'Untitled Record'), ' -'),
                'natgov.preview'
            );
        }

        $this->info('NatGov Town Hall reminders synced.');

        return self::SUCCESS;
    }
}
