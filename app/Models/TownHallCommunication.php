<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TownHallCommunication extends Model
{
    protected $table = 'townhall_communications';

    protected $fillable = [
        'ref_no',
        'communication_date',
        'from_name',
        'department_stakeholder',
        'recipient_label',
        'recipient_type',
        'recipient_user_id',
        'recipient_user_ids',
        'to_for',
        'priority',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'subject',
        'message',
        'cc',
        'additional',
        'attachment',
        'created_by',
        'ack_deadline_at',
        'expires_at',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'communication_date' => 'datetime',
        'approved_at' => 'datetime',
        'ack_deadline_at' => 'datetime',
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_archived' => 'boolean',
        'recipient_user_ids' => 'array',
    ];

    public function acknowledgements()
    {
        return $this->hasMany(TownHallAcknowledgement::class, 'townhall_communication_id');
    }

    public function hasBeenAcknowledgedBy($userId): bool
    {
        return $this->acknowledgements()->where('user_id', $userId)->exists();
    }

    public function getIsExpiredAttribute(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at->lte(now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function getRecipientNamesAttribute(): string
    {
        if (($this->recipient_type ?? 'all') === 'all') {
            return 'All Employees';
        }

        $ids = $this->recipient_user_ids ?? [];

        if (!empty($ids)) {
            return User::whereIn('id', $ids)
                ->orderBy('name')
                ->pluck('name')
                ->implode(', ');
        }

        return $this->recipientUser->name ?? $this->to_for ?? 'Selected Employee';
    }
}
