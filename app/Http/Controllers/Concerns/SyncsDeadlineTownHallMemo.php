<?php

namespace App\Http\Controllers\Concerns;

use App\Models\TownHallCommunication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait SyncsDeadlineTownHallMemo
{
    protected function syncDeadlineTownHallMemo(
        Model $record,
        ?string $deadlineDate,
        string $moduleLabel,
        string $recordLabel,
        string $previewRouteName
    ): void {
        if (!$deadlineDate) {
            $this->deleteDeadlineTownHallMemo($record);

            return;
        }

        $deadline = Carbon::parse($deadlineDate)->startOfDay();
        $today = now()->startOfDay();
        $daysUntilDeadline = $today->diffInDays($deadline, false);

        $status = $daysUntilDeadline < 0 ? 'Overdue' : 'Open';
        $timingLabel = $daysUntilDeadline < 0 ? 'Overdue' : 'Incoming';
        $deadlineText = $deadline->format('F d, Y');

        $message = collect([
            "This is an automated Town Hall memo for an {$moduleLabel} deadline.",
            "Record: {$recordLabel}",
            "Deadline: {$deadlineText}",
            $daysUntilDeadline < 0
                ? 'Status: The deadline has already passed and needs immediate attention.'
                : "Status: The deadline is approaching in {$daysUntilDeadline} day(s).",
            'Preview: ' . route($previewRouteName, $record),
        ])->implode("\n");

        $this->upsertTownHallReminderCommunication(
            $record,
            $deadline,
            $moduleLabel,
            $recordLabel,
            'deadline_tracker',
            "{$moduleLabel} Deadline {$timingLabel}: {$recordLabel}",
            $message,
            $status,
            'Deadline tracking memo',
            $today
        );
    }

    protected function syncNatGovReminderSeries(
        Model $record,
        ?string $renewalDate,
        string $moduleLabel,
        string $recordLabel,
        string $previewRouteName
    ): void {
        if (!$renewalDate) {
            return;
        }

        $deadline = Carbon::parse($renewalDate)->startOfDay();
        $today = now()->startOfDay();
        $daysUntilDeadline = $today->diffInDays($deadline, false);
        $deadlineText = $deadline->format('F d, Y');

        $rules = [
            90 => 'First Reminder',
            60 => 'Second Reminder',
            30 => 'Third Reminder',
            15 => 'Fourth Reminder',
            7 => 'Final Reminder',
            0 => 'Due Today Reminder',
        ];

        foreach ($rules as $daysBefore => $label) {
            if ($daysUntilDeadline !== $daysBefore) {
                continue;
            }

            $message = collect([
                "This is an automated Town Hall {$label} for an {$moduleLabel} compliance item.",
                "Record: {$recordLabel}",
                "Renewal Date: {$deadlineText}",
                "Reminder Stage: {$label}",
                'Preview: ' . route($previewRouteName, $record),
            ])->implode("\n");

            $this->upsertTownHallReminderCommunication(
                $record,
                $deadline,
                $moduleLabel,
                $recordLabel,
                'natgov_' . strtolower(str_replace(' ', '_', $label)),
                "{$moduleLabel} {$label}: {$recordLabel}",
                $message,
                $daysBefore === 0 ? 'Due Today' : 'Open',
                'Automated compliance reminder',
                $today
            );
        }

        if ($daysUntilDeadline < 0) {
            $overdueKey = 'natgov_overdue_' . $today->format('Ymd');
            $overdueDays = abs($daysUntilDeadline);
            $message = collect([
                "This is an automated Overdue Reminder for an {$moduleLabel} compliance item.",
                "Record: {$recordLabel}",
                "Renewal Date: {$deadlineText}",
                "Status: This item is overdue by {$overdueDays} day(s) and still requires action.",
                'Preview: ' . route($previewRouteName, $record),
            ])->implode("\n");

            $this->upsertTownHallReminderCommunication(
                $record,
                $deadline,
                $moduleLabel,
                $recordLabel,
                $overdueKey,
                "{$moduleLabel} Overdue Reminder: {$recordLabel}",
                $message,
                'Overdue',
                'Automated overdue compliance reminder',
                $today
            );
        }
    }

    protected function deleteDeadlineTownHallMemo(Model $record): void
    {
        TownHallCommunication::query()
            ->where('source_type', $record::class)
            ->where('source_id', $record->getKey())
            ->delete();
    }

    private function upsertTownHallReminderCommunication(
        Model $record,
        Carbon $deadline,
        string $moduleLabel,
        string $recordLabel,
        string $reminderKey,
        string $subject,
        string $message,
        string $status,
        string $additional,
        Carbon $triggerDate
    ): void {
        $fromName = Auth::user()?->name ?: 'Corporate Compliance System';
        $hasApprovalStatusColumn = Schema::hasColumn('townhall_communications', 'approval_status');
        $hasWorkflowStatusColumn = Schema::hasColumn('townhall_communications', 'workflow_status');
        $hasApprovedByColumn = Schema::hasColumn('townhall_communications', 'approved_by');
        $hasApprovedAtColumn = Schema::hasColumn('townhall_communications', 'approved_at');
        $hasApprovalNotesColumn = Schema::hasColumn('townhall_communications', 'approval_notes');
        $hasPostedAtColumn = Schema::hasColumn('townhall_communications', 'posted_at');
        $hasPostedByColumn = Schema::hasColumn('townhall_communications', 'posted_by');
        $hasReminderKeyColumn = Schema::hasColumn('townhall_communications', 'reminder_key');
        $hasReminderTriggerDateColumn = Schema::hasColumn('townhall_communications', 'reminder_trigger_date');

        $lookup = [
            'source_type' => $record::class,
            'source_id' => $record->getKey(),
        ];

        if ($hasReminderKeyColumn) {
            $lookup['reminder_key'] = $reminderKey;
        }

        $communication = TownHallCommunication::query()->firstOrNew($lookup);

        $payload = [
            'communication_date' => $triggerDate->toDateString(),
            'from_name' => $fromName,
            'department_stakeholder' => $moduleLabel,
            'to_for' => 'Town Hall',
            'priority' => $status === 'Overdue' ? 'High' : 'Normal',
            'status' => $status,
            'subject' => $subject,
            'message' => $message,
            'additional' => $additional,
            'created_by' => Auth::id(),
            'deadline_date' => $deadline->toDateString(),
        ];

        if ($hasApprovalStatusColumn) {
            $payload['approval_status'] = 'Approved';
        }

        if ($hasWorkflowStatusColumn) {
            $payload['workflow_status'] = 'Posted';
        }

        if ($hasApprovedByColumn) {
            $payload['approved_by'] = Auth::id();
        }

        if ($hasApprovedAtColumn) {
            $payload['approved_at'] = now();
        }

        if ($hasApprovalNotesColumn) {
            $payload['approval_notes'] = 'System-generated deadline memo.';
        }

        if ($hasPostedAtColumn) {
            $payload['posted_at'] = now();
        }

        if ($hasPostedByColumn) {
            $payload['posted_by'] = Auth::id();
        }

        if ($hasReminderTriggerDateColumn) {
            $payload['reminder_trigger_date'] = $triggerDate->toDateString();
        }

        if ($hasReminderKeyColumn) {
            $payload['reminder_key'] = $reminderKey;
        }

        $communication->fill($payload);
        $communication->source_type = $record::class;
        $communication->source_id = $record->getKey();
        $communication->deadline_date = $deadline->toDateString();
        if ($hasReminderKeyColumn) {
            $communication->reminder_key = $reminderKey;
        }
        if ($hasReminderTriggerDateColumn) {
            $communication->reminder_trigger_date = $triggerDate->toDateString();
        }
        $communication->save();

        if (!$communication->ref_no) {
            $communication->ref_no = 'TH-' . str_pad((string) $communication->id, 5, '0', STR_PAD_LEFT);
            $communication->save();
        }
    }
}
