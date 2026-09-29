<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ClientActionRequest extends Model
{
    use HasFactory;

    protected $table = 'client_action_requests';

    // Supported Action Types
    public const ACTION_REVIEW = 'Review';
    public const ACTION_SELECT = 'Select';
    public const ACTION_ACCEPT = 'Accept';
    public const ACTION_APPROVE = 'Approve';
    public const ACTION_SIGN = 'Sign';
    public const ACTION_ACKNOWLEDGE = 'Acknowledge';
    public const ACTION_UPLOAD = 'Upload';
    public const ACTION_RESPOND = 'Respond';

    // Statuses
    public const STATUS_PENDING = 'Pending';
    public const STATUS_AWAITING_CLIENT = 'Awaiting Client';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_ACCEPTED = 'Accepted';
    public const STATUS_DECLINED = 'Declined';
    public const STATUS_ACKNOWLEDGED = 'Acknowledged';
    public const STATUS_UPLOADED = 'Uploaded';
    public const STATUS_SIGNED = 'Signed';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_EXPIRED = 'Expired';
    public const STATUS_CANCELLED = 'Cancelled';

    // Response Channels
    public const CHANNEL_PORTAL = 'Client Portal';
    public const CHANNEL_SECURE_LINK = 'Secure Link';
    public const CHANNEL_EMAIL = 'Email';
    public const CHANNEL_MANUAL_SIGNATURE = 'Manual / Wet Signature';
    public const CHANNEL_EXTERNAL_SIGNING = 'External Signing';

    protected $fillable = [
        'action_code',
        'deal_id',
        'actionable_type',
        'actionable_id',
        'document_type',
        'document_reference',
        'document_version',
        'document_title',
        'document_url',
        'account_id',
        'contact_id',
        'client_name',
        'client_email',
        'client_phone',
        'action_type',
        'title',
        'instructions',
        'status',
        'response_channel',
        'secure_token',
        'requires_otp',
        'otp_code',
        'otp_expires_at',
        'expires_at',
        'sent_at',
        'opened_at',
        'responded_at',
        'completed_at',
        'response_decision',
        'response_notes',
        'response_data',
        'evidence_type',
        'evidence_file_path',
        'evidence_file_name',
        'evidence_notes',
        'evidence_metadata',
        'signatory_name',
        'document_signed_date',
        'verification_state',
        'created_by_user_id',
        'recorded_by_user_id',
        'recorded_by_name',
        'verified_by_user_id',
        'audit_trail',
    ];

    protected $casts = [
        'requires_otp' => 'boolean',
        'otp_expires_at' => 'datetime',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'responded_at' => 'datetime',
        'completed_at' => 'datetime',
        'document_signed_date' => 'date',
        'response_data' => 'array',
        'evidence_metadata' => 'array',
        'audit_trail' => 'array',
    ];

    /**
     * Reusable MorphTo relation to target business record.
     */
    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Scope: Pending actions.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_AWAITING_CLIENT, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope: Completed / final actions.
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_APPROVED,
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_UPLOADED,
            self::STATUS_SIGNED,
        ]);
    }

    /**
     * Scope: Latest records first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Accessor: Formatted secure URL.
     */
    public function getSecureUrlAttribute(): string
    {
        return url('/client-action/' . $this->secure_token);
    }

    /**
     * Check if action is finalized.
     */
    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_APPROVED,
            self::STATUS_ACCEPTED,
            self::STATUS_DECLINED,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_UPLOADED,
            self::STATUS_SIGNED,
        ], true);
    }

    /**
     * Accessor: Badge CSS class for status.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED, self::STATUS_ACCEPTED, self::STATUS_COMPLETED, self::STATUS_SIGNED => 'uca-badge-completed',
            self::STATUS_AWAITING_CLIENT, self::STATUS_PENDING => 'uca-badge-pending',
            self::STATUS_IN_PROGRESS, self::STATUS_ACKNOWLEDGED, self::STATUS_UPLOADED => 'uca-badge-progress',
            self::STATUS_DECLINED, self::STATUS_CANCELLED, self::STATUS_EXPIRED => 'uca-badge-danger',
            default => 'uca-badge-pending',
        };
    }

    /**
     * Accessor: Channel badge styling.
     */
    public function getChannelBadgeClassAttribute(): string
    {
        return match ($this->response_channel) {
            self::CHANNEL_PORTAL => 'channel-badge-portal',
            self::CHANNEL_SECURE_LINK => 'channel-badge-link',
            self::CHANNEL_EMAIL => 'channel-badge-email',
            self::CHANNEL_MANUAL_SIGNATURE => 'channel-badge-manual',
            self::CHANNEL_EXTERNAL_SIGNING => 'channel-badge-external',
            default => 'channel-badge-default',
        };
    }

    /**
     * Append an event to the internal audit trail.
     */
    public function recordAuditEntry(string $action, string $description, ?int $userId = null, ?string $userName = null, array $metadata = []): void
    {
        $trail = $this->audit_trail ?: [];
        $trail[] = [
            'action' => $action,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
            'user_name' => $userName ?? (auth()->user()?->name ?? 'System / Client Action Engine'),
            'timestamp' => now()->toIso8601String(),
            'formatted_time' => now()->format('M d, Y · g:i A'),
            'metadata' => $metadata,
        ];

        $this->audit_trail = $trail;
        $this->saveQuietly();
    }
}
