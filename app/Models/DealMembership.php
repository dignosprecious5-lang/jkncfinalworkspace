<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DealMembership extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const ROLE_CONSULTANT = 'consultant';
    public const ROLE_ASSOCIATE = 'associate';
    public const ROLE_LEAD_CONSULTANT = 'lead_consultant';
    public const ROLE_LEAD_ASSOCIATE = 'lead_associate';

    public const ROLES = [
        self::ROLE_CONSULTANT,
        self::ROLE_ASSOCIATE,
        self::ROLE_LEAD_CONSULTANT,
        self::ROLE_LEAD_ASSOCIATE,
    ];

    protected $fillable = [
        'deal_id',
        'user_id',
        'membership_role',
        'assignment_status',
        'assigned_by_user_id',
        'status_changed_by_user_id',
        'assigned_at',
        'status_changed_at',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('Deal membership history cannot be deleted. Revoke the assignment instead.');
        });
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by_user_id');
    }
}
