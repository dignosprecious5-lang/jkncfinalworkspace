<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesHumanCapitalRecords
{
    protected function canManageHumanCapitalModule(string $permission, bool $myHc = false): bool
    {
        $user = Auth::user();

        return $user
            && (
                $user->isSuperAdmin()
                || $user->isAdmin()
                || $user->hasPermission($permission)
                || ($myHc && $user->hasPermission('access_hc_my_hc'))
            );
    }

    protected function currentHumanCapitalEmployee(?User $user = null): ?Employee
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        return Employee::with('department')
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email)
                    ->orWhere('work_email', $user->email)
                    ->orWhere('company_email', $user->email);
            })
            ->first();
    }

    protected function humanCapitalEmployeeIdentity(?Employee $employee, ?User $user = null): array
    {
        $user ??= Auth::user();

        return [
            'user_id' => $user?->id,
            'employee_id' => $employee?->id,
            'employee_code' => $employee?->employee_code,
            'name' => $employee?->full_name ?: $user?->name,
            'emails' => collect([
                $user?->email,
                $employee?->email,
                $employee?->work_email,
                $employee?->company_email,
                $employee?->personal_email,
            ])->filter()->map(fn ($email) => strtolower(trim((string) $email)))->unique()->values()->all(),
        ];
    }

    protected function applyEmployeeIdentityScope(Builder $query, array $identity, array $employeeColumns = ['employee_id'], array $userColumns = ['user_id'], array $nameColumns = [], array $emailColumns = []): Builder
    {
        return $query->where(function (Builder $scope) use ($identity, $employeeColumns, $userColumns, $nameColumns, $emailColumns) {
            if (! empty($identity['employee_id'])) {
                foreach ($employeeColumns as $column) {
                    $scope->orWhere($column, $identity['employee_id']);
                }
            }

            if (! empty($identity['user_id'])) {
                foreach ($userColumns as $column) {
                    $scope->orWhere($column, $identity['user_id']);
                }
            }

            if (! empty($identity['name'])) {
                foreach ($nameColumns as $column) {
                    $scope->orWhere($column, $identity['name'])
                        ->orWhere($column, 'like', '%' . $identity['name'] . '%');
                }
            }

            foreach ($identity['emails'] ?? [] as $email) {
                foreach ($emailColumns as $column) {
                    $scope->orWhere($column, $email);
                }
            }
        });
    }
}
