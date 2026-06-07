<?php

namespace App\Http\Controllers\Concerns;

use App\Models\HumanCapitalChangeRequest;
use App\Models\User;
use App\Notifications\HumanCapitalWorkflowNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

trait RequestsHumanCapitalApproval
{
    protected function requestHumanCapitalChange(
        Request $request,
        string $module,
        string $action,
        Model $model,
        ?array $newValues = null,
        ?string $subjectName = null
    ): HumanCapitalChangeRequest {
        $user = $request->user();

        $changeRequest = HumanCapitalChangeRequest::create([
            'module' => $module,
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'subject_name' => $subjectName ?: $this->humanCapitalSubjectName($model),
            'old_values' => collect($model->getAttributes())->except(['created_at', 'updated_at'])->all(),
            'new_values' => $newValues,
            'status' => 'Pending Approval',
            'requested_by' => $user?->id,
            'requested_by_name' => $user?->name ?? $user?->email ?? 'System User',
            'requested_at' => now(),
        ]);

        $this->notifyHumanCapitalAdmins(
            title: $module . ' change request submitted',
            message: ($changeRequest->requested_by_name ?: 'A user') . ' submitted a ' . $action . ' request for ' . ($changeRequest->subject_name ?: 'a Human Capital record') . '.',
            module: $module,
            recordTitle: $changeRequest->subject_name ?: '',
            actorName: $changeRequest->requested_by_name ?: 'System User'
        );

        return $changeRequest;
    }

    protected function humanCapitalSubjectName(Model $model): string
    {
        foreach (['full_name', 'name', 'title', 'level_name', 'code', 'employee_code'] as $field) {
            if (filled($model->{$field} ?? null)) {
                return (string) $model->{$field};
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    protected function notifyHumanCapitalAdmins(
        string $title,
        string $message,
        string $module = 'Human Capital',
        string $recordTitle = '',
        string $actorName = '',
        ?string $url = null
    ): void {
        $admins = User::query()
            ->get()
            ->filter(function (User $user) {
                if (method_exists($user, 'isDisabled') && $user->isDisabled()) {
                    return false;
                }

                return $user->isAdmin() || $user->isSuperAdmin();
            })
            ->values();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new HumanCapitalWorkflowNotification(
            $title,
            $message,
            $url ?: route('admin.human-capital.dashboard'),
            $module,
            $recordTitle,
            $actorName
        ));
    }
}
