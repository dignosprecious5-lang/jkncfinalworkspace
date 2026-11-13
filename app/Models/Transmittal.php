<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transmittal extends Model
{
    use HasFactory;

    public static function createForWorkflowVersion(string $version, array $attributes): static
    {
        if (! in_array($version, ['v1', 'v2'], true)) {
            throw new \InvalidArgumentException('The workflow version must be exactly v1 or v2.');
        }

        if (array_key_exists('workflow_version', $attributes)) {
            throw new \InvalidArgumentException('Workflow version cannot be supplied in the attributes array.');
        }

        $transmittal = new static($attributes);
        $transmittal->setAttribute('workflow_version', $version);

        if ($transmittal->save() === false) {
            throw new \RuntimeException('The classified transmittal could not be persisted.');
        }

        return $transmittal;
    }

    public function fill(array $attributes)
    {
        if (array_key_exists('workflow_version', $attributes)) {
            throw new \InvalidArgumentException('Workflow version cannot be mass assigned.');
        }

        return parent::fill($attributes);
    }

    protected static function booted(): void
    {
        // Model events do not protect direct SQL, query-builder updates or event
        // bypasses. Hydrated originals are not a concurrent database-value check.
        // Historical classification requires separate authorization, not a bypass here.
        static::saving(function (self $transmittal): void {
            $attributes = $transmittal->getAttributes();
            $hasVersion = array_key_exists('workflow_version', $attributes);
            $version = $attributes['workflow_version'] ?? null;

            if ($hasVersion && $version !== null && ! in_array($version, ['v1', 'v2'], true)) {
                throw new \InvalidArgumentException('The workflow version must be v1, v2 or null.');
            }

            if (! $transmittal->exists) {
                return;
            }

            $original = $transmittal->getRawOriginal();
            if (! array_key_exists('workflow_version', $original)) {
                if ($hasVersion) {
                    throw new \LogicException('The original workflow version must be loaded before classification can be checked.');
                }

                return;
            }

            if ($version !== $original['workflow_version']) {
                throw new \LogicException('An existing transmittal workflow version cannot be changed.');
            }
        });
    }

    protected $fillable = [
        'transmittal_no',
        'transmittal_date',
        'mode',
        'party_name',
        'office_name',
        'address',
        'delivery_type',
        'by_person_who',
        'registered_mail_provider',
        'electronic_method',
        'recipient_email',
        'action_delivery',
        'action_pick_up',
        'action_drop_off',
        'action_email',
        'prepared_by_name',
        'prepared_at',
        'approved_by_name',
        'approved_position',
        'document_custodian',
        'delivered_by',
        'received_by',
        'receiver_affiliation',
        'received_at',
        'workflow_status',
        'approval_status',
        'review_note',
        'submitted_by',
        'approved_by',
        'approved_at',
        // V2 metadata only. Explicit V2 writes must reconcile legacy fields;
        // model events must not silently synchronize or backfill existing rows.
        'tracking_number',
        'external_reference',
        'subject',
        'purpose',
        'direction',
        'origin_office',
        'recipient_address',
        'related_company',
        'delivery_method',
        'delivery_actions',
        'operations_manager',
        'processing_company',
        'confidentiality',
        'status',
        'prepared_by',
        'approved_snapshot',
        'approval_authority_code',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_from_status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'transmittal_date' => 'date',
        'prepared_at' => 'datetime',
        'received_at' => 'datetime',
        'approved_at' => 'datetime',
        'action_delivery' => 'boolean',
        'action_pick_up' => 'boolean',
        'action_drop_off' => 'boolean',
        'action_email' => 'boolean',
        'approved_snapshot' => 'array',
        'delivery_actions' => 'array',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * approved_by_name is the Operations Manager name written on the form.
     * It must not be replaced by the admin user who clicks approve.
     * The actual system approver is stored in approved_by and approved_at.
     */
    public function setApprovedByNameAttribute($value): void
    {
        $newValue = trim((string) ($value ?? ''));
        $currentValue = trim((string) ($this->attributes['approved_by_name'] ?? ''));

        if ($this->exists && $currentValue !== '' && $newValue !== '' && $currentValue !== $newValue) {
            return;
        }

        $this->attributes['approved_by_name'] = $newValue !== '' ? $newValue : null;
    }

    public function items()
    {
        return $this->hasMany(TransmittalItem::class)->orderBy('item_no');
    }

    public function getFromValueAttribute(): string
    {
        return $this->mode === 'SEND'
            ? ($this->office_name ?? '')
            : ($this->party_name ?? '');
    }

    public function getToValueAttribute(): string
    {
        return $this->mode === 'SEND'
            ? ($this->party_name ?? '')
            : ($this->office_name ?? '');
    }

    public function getDeliverySummaryAttribute(): string
    {
        return match ($this->delivery_type) {
            'By Person' => $this->by_person_who ? 'By Person - ' . $this->by_person_who : 'By Person',
            'Registered Mail' => $this->registered_mail_provider ? 'Registered Mail - ' . $this->registered_mail_provider : 'Registered Mail',
            'Electronic' => $this->electronic_method ? 'Electronic - ' . $this->electronic_method : 'Electronic',
            default => '',
        };
    }

    public function getActionsSummaryAttribute(): string
    {
        $actions = [];

        if ($this->action_delivery) $actions[] = 'Delivery';
        if ($this->action_pick_up) $actions[] = 'Pick Up';
        if ($this->action_drop_off) $actions[] = 'Drop Off';
        if ($this->action_email) $actions[] = 'Email';

        return implode(', ', $actions);
    }

    public function receipt()
    {
        return $this->hasOne(TransmittalReceipt::class);
    }

    public function attachments()
    {
        return $this->hasMany(TransmittalAttachment::class)->latest();
    }

    // V2 relationships are additive. Legacy items(), receipt() and attachments()
    // retain their model targets and ordering until controller reconciliation.
    public function workspaceItems()
    {
        return $this->hasMany(TransmittalWorkspaceItem::class)->orderBy('position')->orderBy('id');
    }

    public function recipients()
    {
        return $this->hasMany(TransmittalRecipient::class);
    }

    public function documents()
    {
        return $this->hasMany(TransmittalDocument::class);
    }

    public function proofs()
    {
        return $this->hasMany(ProofOfTransmission::class);
    }

    public function receipts()
    {
        return $this->hasMany(TransmittalReceipt::class);
    }

    public function histories()
    {
        return $this->hasMany(TransmittalHistory::class);
    }

    public function acknowledgments()
    {
        return $this->hasMany(ExternalAcknowledgment::class);
    }

    public function authorityCode()
    {
        return $this->hasOne(ApprovalAuthorityCode::class);
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
