<?php

namespace App\Http\Controllers;

use App\Mail\EngagementCompletionReportMail;
use App\Models\AuditLog;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class WorkspaceController extends Controller
{
    /**
     * Update a milestone/task status and trigger broadcast & report automations.
     */
    public function updateStatus(Request $request, int $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled,overdue',
        ]);

        $taskRecord = DB::table('operational_tasks')->where('id', $task)->first();

        if (! $taskRecord) {
            return back()->with('error', 'Task not found.');
        }

        $previousStatus = $taskRecord->status;
        $newStatus = $validated['status'];

        DB::table('operational_tasks')
            ->where('id', $taskRecord->id)
            ->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        AuditLog::log(
            'TASK_STATUS_UPDATED',
            'Engagements',
            "Updated task '{$taskRecord->title}' status from '{$previousStatus}' to '{$newStatus}'."
        );

        // Check if all tasks are completed to trigger engagement completion & report generation
        $this->completeEngagementIfAllTasksAreComplete($taskRecord);

        // Broadcast status change if enabled in Automation Settings
        if ($previousStatus !== $newStatus) {
            $this->broadcastStatusChange($taskRecord, $previousStatus, $newStatus);
        }

        return back()->with('success', 'Task status updated successfully!');
    }

    /**
     * Complete engagement when all tasks are done and trigger Auto-generate Final Report if enabled.
     */
    private function completeEngagementIfAllTasksAreComplete(object $task): void
    {
        if (empty($task->engagement_id) || ! Schema::hasTable('engagements')) {
            return;
        }

        $openTaskCount = DB::table('operational_tasks')
            ->where('engagement_id', $task->engagement_id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        if ($openTaskCount === 0) {
            DB::table('engagements')
                ->where('id', $task->engagement_id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);

            $engagement = DB::table('engagements')->where('id', $task->engagement_id)->first();

            AuditLog::log(
                'ENGAGEMENT_COMPLETED',
                'Engagements',
                "Engagement ID #{$task->engagement_id} automatically completed as all tasks were finished."
            );

            // AUTOMATION RULE #4: Auto-generate Final Report
            if ($engagement && ! empty($engagement->service_id)) {
                $service = Service::with('activeVersion')->find($engagement->service_id);
                $activeVersion = ! empty($engagement->service_version_id)
                    ? $service?->versions()->find($engagement->service_version_id)
                    : $service?->activeVersion;

                if ($activeVersion && (bool) $activeVersion->auto_generate_completion_report) {
                    $totalCompletedTasks = DB::table('operational_tasks')
                        ->where('engagement_id', $engagement->id)
                        ->count();

                    $recipientEmail = $engagement->client_email ?? $service->client->email ?? 'management@company.com';

                    try {
                        Mail::to($recipientEmail)->send(new EngagementCompletionReportMail(
                            serviceName: $service->name ?? 'Service Engagement',
                            engagementId: $engagement->id,
                            totalTasks: $totalCompletedTasks,
                            completedAt: now()->toDayDateTimeString()
                        ));

                        $reportMessage = "AUTOMATION TRIGGERED: Final completion report auto-generated and emailed to {$recipientEmail} for Engagement #{$engagement->id}.";
                        Log::info($reportMessage);
                        AuditLog::log('AUTOMATION TRIGGERED', 'Engagements', $reportMessage);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to send Completion Report email: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Broadcast status change to stakeholders if enabled in Automation Settings.
     */
    private function broadcastStatusChange(object $task, string $previousStatus, string $newStatus): void
    {
        if (empty($task->engagement_id) || ! Schema::hasTable('engagements')) {
            return;
        }

        $engagement = DB::table('engagements')->where('id', $task->engagement_id)->first();

        if (! $engagement || empty($engagement->service_id)) {
            return;
        }

        $service = Service::with('activeVersion')->find($engagement->service_id);
        $activeVersion = ! empty($engagement->service_version_id)
            ? $service?->versions()->find($engagement->service_version_id)
            : $service?->activeVersion;

        // CHECK AUTOMATION SETTING: Status Change Broadcasts
        if (! $activeVersion || ! (bool) $activeVersion->auto_notify_status_change) {
            Log::info("AUTOMATION SKIPPED: auto_notify_status_change is disabled for Task #{$task->id}.");
            return;
        }

        // Gather emails from proposals or directly from engagement client_email fallback
        $stakeholderEmails = Schema::hasTable('proposals')
            ? DB::table('proposals')
                ->where('service_id', $service->id)
                ->whereIn('status', ['accepted', 'contracted'])
                ->pluck('client_email')
                ->filter()
                ->unique()
                ->values()
            : collect();

        if ($stakeholderEmails->isEmpty() && isset($engagement->client_email)) {
            $stakeholderEmails = collect([$engagement->client_email]);
        }

        $sentCount = 0;
        $taskTitle = e($task->title ?? 'Untitled task');
        $serviceName = e($service->name);
        $fromStatus = e(str_replace('_', ' ', $previousStatus));
        $toStatus = e(str_replace('_', ' ', $newStatus));

        foreach ($stakeholderEmails as $email) {
            try {
                Mail::html(
                    "<p>The status of <strong>{$taskTitle}</strong> for <strong>{$serviceName}</strong> changed from <strong>{$fromStatus}</strong> to <strong>{$toStatus}</strong>.</p>",
                    function ($message) use ($email, $serviceName): void {
                        $message->to($email)->subject("Status update: {$serviceName}");
                    }
                );
                $sentCount++;
            } catch (\Throwable $exception) {
                Log::warning('Status change broadcast email failed.', [
                    'task_id' => $task->id,
                    'recipient' => $email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        if ($sentCount > 0) {
            $message = "AUTOMATION TRIGGERED: Status change broadcast sent to {$sentCount} project stakeholder(s) for task '{$task->title}'.";
            Log::info($message, [
                'task_id' => $task->id,
                'service_id' => $service->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ]);
            AuditLog::log('AUTOMATION TRIGGERED', 'Engagements', $message);
        }
    }
}