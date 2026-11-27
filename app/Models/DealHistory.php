<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class DealHistory extends Model
{
    use HasFactory;

    protected $table = 'deal_histories';

    protected $fillable = [
        'deal_id',
        'user_id',
        'user_name',
        'activity_type',
        'title',
        'description',
        'old_stage',
        'new_stage',
        'old_value',
        'new_value',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPE_DEAL_CREATED = 'deal_created';
    public const TYPE_STAGE_CHANGED = 'stage_changed';
    public const TYPE_DEAL_UPDATED = 'deal_updated';
    public const TYPE_CONTACT_UPDATED = 'contact_updated';
    public const TYPE_NOTE_ADDED = 'note_added';
    public const TYPE_DOCUMENT_UPLOADED = 'document_uploaded';
    public const TYPE_DOCUMENT_DELETED = 'document_deleted';
    public const TYPE_PROPOSAL_GENERATED = 'proposal_generated';
    public const TYPE_PROPOSAL_SENT = 'proposal_sent';
    public const TYPE_PROPOSAL_ACCEPTED = 'proposal_accepted';
    public const TYPE_PROPOSAL_REJECTED = 'proposal_rejected';
    public const TYPE_ASSIGNMENT_CHANGED = 'assignment_changed';

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if ($type && $type !== 'all') {
            return $query->where('activity_type', $type);
        }
        return $query;
    }

    public function scopeFilterByUser(Builder $query, ?int $userId): Builder
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    public function scopeFilterByDateRange(Builder $query, string $range, ?string $fromDate = null, ?string $toDate = null): Builder
    {
        if ($range === 'all' || empty($range)) {
            return $query;
        }

        return match ($range) {
            'today' => $query->whereDate('created_at', now()->today()),
            'yesterday' => $query->whereDate('created_at', now()->yesterday()),
            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'custom' => $query->when($fromDate, fn($q) => $q->whereDate('created_at', '>=', $fromDate))
                              ->when($toDate, fn($q) => $q->whereDate('created_at', '<=', $toDate)),
            default => $query,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->activity_type) {
            self::TYPE_DEAL_CREATED => 'Deal Created',
            self::TYPE_STAGE_CHANGED => 'Stage Changed',
            self::TYPE_DEAL_UPDATED => 'Information Updated',
            self::TYPE_CONTACT_UPDATED => 'Contact Updated',
            self::TYPE_NOTE_ADDED => 'Note Added',
            self::TYPE_DOCUMENT_UPLOADED => 'Document Uploaded',
            self::TYPE_DOCUMENT_DELETED => 'Document Deleted',
            self::TYPE_PROPOSAL_GENERATED => 'Proposal Generated',
            self::TYPE_PROPOSAL_SENT => 'Proposal Sent',
            self::TYPE_PROPOSAL_ACCEPTED => 'Proposal Accepted',
            self::TYPE_PROPOSAL_REJECTED => 'Proposal Rejected',
            self::TYPE_ASSIGNMENT_CHANGED => 'Assignment Changed',
            default => ucwords(str_replace('_', ' ', $this->activity_type)),
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->activity_type) {
            self::TYPE_DEAL_CREATED => 'history-badge-green',
            self::TYPE_STAGE_CHANGED => 'history-badge-blue',
            self::TYPE_DEAL_UPDATED => 'history-badge-teal',
            self::TYPE_CONTACT_UPDATED => 'history-badge-teal',
            self::TYPE_NOTE_ADDED => 'history-badge-purple',
            self::TYPE_DOCUMENT_UPLOADED => 'history-badge-indigo',
            self::TYPE_DOCUMENT_DELETED => 'history-badge-red',
            self::TYPE_PROPOSAL_GENERATED => 'history-badge-orange',
            self::TYPE_PROPOSAL_SENT => 'history-badge-orange',
            self::TYPE_PROPOSAL_ACCEPTED => 'history-badge-green',
            self::TYPE_PROPOSAL_REJECTED => 'history-badge-red',
            self::TYPE_ASSIGNMENT_CHANGED => 'history-badge-teal',
            default => 'history-badge-blue',
        };
    }

    public function getFromStageAttribute(): ?string
    {
        return $this->old_stage ?? ($this->metadata['from_stage'] ?? null);
    }

    public function getToStageAttribute(): ?string
    {
        return $this->new_stage ?? ($this->metadata['to_stage'] ?? null);
    }

    public function getFromValueAttribute(): ?string
    {
        return $this->old_value ?? ($this->metadata['from_value'] ?? null);
    }

    public function getToValueAttribute(): ?string
    {
        return $this->new_value ?? ($this->metadata['to_value'] ?? null);
    }

    public function getOldValuesAttribute(): ?array
    {
        return $this->metadata['old_values'] ?? null;
    }

    public function getNewValuesAttribute(): ?array
    {
        return $this->metadata['new_values'] ?? null;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->metadata['notes'] ?? null;
    }

    public function getDocumentNameAttribute(): ?string
    {
        return $this->metadata['document_name'] ?? null;
    }

    public function getDocumentTypeAttribute(): ?string
    {
        return $this->metadata['document_type'] ?? null;
    }

    public function getIpAddressAttribute(): ?string
    {
        return $this->metadata['ip_address'] ?? '127.0.0.1';
    }
}
