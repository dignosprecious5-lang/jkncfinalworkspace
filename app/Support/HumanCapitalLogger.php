<?php

namespace App\Support;

use App\Models\HumanCapitalLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HumanCapitalLogger
{
    public static function log(Request $request, array $data): void
    {
        $user = $request->user() ?: Auth::user();

        HumanCapitalLog::create([
            'module' => $data['module'] ?? 'Human Capital',
            'action' => $data['action'] ?? 'updated',
            'subject_type' => $data['subject_type'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'subject_name' => $data['subject_name'] ?? null,
            'description' => $data['description'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? $user?->email ?? 'System User',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'logged_at' => now(),
        ]);
    }

    public static function logModelChange(
        Request $request,
        string $module,
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $subjectName = null,
        ?string $description = null
    ): void {
        self::log($request, [
            'module' => $module,
            'action' => $action,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'subject_name' => $subjectName ?: self::modelLabel($model),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    private static function modelLabel(Model $model): string
    {
        foreach (['name', 'title', 'level_name', 'code', 'employee_code'] as $field) {
            if (filled($model->{$field} ?? null)) {
                return (string) $model->{$field};
            }
        }

        if (filled($model->full_name ?? null)) {
            return (string) $model->full_name;
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
