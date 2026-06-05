<?php

namespace App\Http\Controllers\Concerns;

use App\Models\HumanCapitalChangeRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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

        return HumanCapitalChangeRequest::create([
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
}
