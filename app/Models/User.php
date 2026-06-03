<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'can_edit_user_roles',
        'can_delete_users',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_edit_user_roles' => 'boolean',
            'can_delete_users' => 'boolean',
        ];
    }

    public function setRoleAttribute($value): void
    {
        $normalized = strtolower(trim((string) $value));

        $this->attributes['role'] = match ($normalized) {
            'superadmin' => 'SuperAdmin',
            'admin' => 'Admin',
            'employee' => 'Employee',
            'client' => 'Client',
            default => trim((string) $value),
        };
    }

    public function userPermission()
    {
        return $this->hasOne(\App\Models\UserPermission::class);
    }

    public function employeeProfile()
    {
        return $this->hasOne(\App\Models\Employee::class, 'user_id');
    }

    public function contactProfile()
    {
        return $this->hasOne(\App\Models\Contact::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return strtolower((string) $this->role) === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return strtolower((string) $this->role) === 'admin';
    }

    public function isEmployee(): bool
    {
        return strtolower((string) $this->role) === 'employee';
    }

    public function isClient()
    {
        return strtolower((string) $this->role) === 'client';
    }

    public function canManageRoles(): bool
    {
        return $this->isSuperAdmin() || ($this->isAdmin() && $this->can_edit_user_roles);
    }

    public function canDeleteUsers(): bool
    {
        return $this->isSuperAdmin() || ($this->isAdmin() && $this->can_delete_users);
    }

    public function isFinanceTreasurer(): bool
    {
        return (bool) data_get($this->userPermission, 'finance_treasurer', false);
    }

    public function isFinancePresident(): bool
    {
        return (bool) data_get($this->userPermission, 'finance_president', false);
    }

    public function isFinanceApprover(): bool
    {
        return (bool) data_get($this->userPermission, 'finance_approver', false);
    }

    public function hasPermission(string $permission): bool
    {
        if (strtolower((string) $this->role) === 'superadmin') {
            return true;
        }

        $userPermission = $this->userPermission;

        if ($userPermission && isset($userPermission->{$permission})) {
            return (bool) $userPermission->{$permission};
        }

        $rolePermission = \App\Models\RolePermission::where('role', $this->role)->first();

        return $rolePermission ? (bool) $rolePermission->{$permission} : false;
    }
}
