<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class Transmittal extends Model
{

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
            throw new LogicException('Unable to create transmittal record.');
        }

        return $transmittal;
    }

    protected $fillable = [
        'tracking_number',
        'transmittal_no',
        'external_reference',
        'subject',
        'purpose',
        'direction',
        'mode',
        'transmittal_date',
        'party_name',
        'origin_office',
        'office_name',
        'recipient_address',
        'address',
        'related_company',
        'delivery_method',
        'delivery_type',
        'by_person_who',
        'registered_mail_provider',
        'electronic_method',
        'recipient_email',
        'delivery_actions',
        'action_delivery',
        'action_pick_up',
        'action_drop_off',
        'action_email',
        'prepared_by_name',
        'prepared_at',
        'approved_by_name',
        'approved_position',
        'operations_manager',
        'document_custodian',
        'delivered_by',
        'received_by',
        'receiver_affiliation',
        'processing_company',
        'received_at',
        'confidentiality',
        'status',
        'workflow_status',
        'approval_status',
        'review_note',
        'submitted_by',
        'prepared_by',
        'approved_by',
        'approved_at',
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

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'approved_snapshot' => 'array',
            'transmittal_date' => 'date',
            'delivery_actions' => 'array',
            'action_delivery' => 'boolean',
            'action_pick_up' => 'boolean',
            'action_drop_off' => 'boolean',
            'action_email' => 'boolean',
            'prepared_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getFromValueAttribute(): string
    {
        $direction = strtolower($this->direction ?? ($this->attributes['mode'] ?? 'send'));
        if ($direction === 'receive') {
            $party = $this->party_name ?? ($this->recipients->first()?->name ?: ($this->recipients->first()?->email ?? ''));
            return (string) ($party ?: ($this->attributes['from_value'] ?? ''));
        }

        return (string) ($this->origin_office ?: ($this->office_name ?: ($this->attributes['from_value'] ?? 'John Kelly & Company')));
    }

    public function getToValueAttribute(): string
    {
        $direction = strtolower($this->direction ?? ($this->attributes['mode'] ?? 'send'));
        if ($direction === 'receive') {
            return (string) ($this->origin_office ?: ($this->office_name ?: ($this->attributes['to_value'] ?? 'John Kelly & Company')));
        }

        $party = $this->party_name ?? ($this->recipients->first()?->name ?: ($this->recipients->first()?->email ?: ($this->related_company ?? '')));
        return (string) ($party ?: ($this->attributes['to_value'] ?? ''));
    }

    public function getDeliverySummaryAttribute(): string
    {
        $deliveryType = $this->delivery_type ?: ($this->attributes['delivery_type'] ?? '');
        if ($deliveryType) {
            $normalized = strtolower(str_replace(' ', '_', (string) $deliveryType));
            $label = match ($normalized) {
                'by_person' => 'By Person',
                'registered_mail' => 'Registered Mail',
                'electronic' => 'Electronic',
                default => ucwords(str_replace('_', ' ', (string) $deliveryType)),
            };
            $detail = match ($normalized) {
                'by_person' => $this->by_person_who,
                'registered_mail' => $this->registered_mail_provider,
                'electronic' => $this->electronic_method,
                default => null,
            };

            return $detail ? "{$label} - {$detail}" : $label;
        }

        $method = $this->delivery_method ?: ($this->attributes['delivery_method'] ?? '');
        if ($method) {
            $label = ucwords(str_replace('_', ' ', (string) $method));
            if (strtolower((string) $method) === 'electronic' && !empty($this->electronic_method)) {
                return "{$label} - {$this->electronic_method}";
            }

            return $label;
        }

        return '';
    }

    public function getActionsSummaryAttribute(): string
    {
        $actions = $this->delivery_actions;
        if (is_array($actions) && count($actions) > 0) {
            return collect($actions)->map(fn ($a) => ucwords(str_replace('_', ' ', (string) $a)))->implode(', ');
        }

        $legacyActions = [];
        if (!empty($this->action_delivery)) $legacyActions[] = 'Delivery';
        if (!empty($this->action_pick_up)) $legacyActions[] = 'Pick Up';
        if (!empty($this->action_drop_off)) $legacyActions[] = 'Drop Off';
        if (!empty($this->action_email)) $legacyActions[] = 'Email';

        return implode(', ', $legacyActions);
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    protected static function booted(): void
    {
        static::saving(function (Transmittal $transmittal): void {
            $canSyncV2 = $transmittal->getOriginal('approved_snapshot') === null;

            if (! empty($transmittal->tracking_number) && ! str_starts_with($transmittal->tracking_number, 'PENDING-')) {
                $transmittal->transmittal_no = $transmittal->tracking_number;
            } elseif (! empty($transmittal->transmittal_no) && empty($transmittal->tracking_number) && $canSyncV2) {
                $transmittal->tracking_number = $transmittal->transmittal_no;
            }

            if (! empty($transmittal->direction)) {
                $transmittal->mode = strtoupper((string) $transmittal->direction);
            } elseif (! empty($transmittal->mode) && $canSyncV2) {
                $transmittal->direction = strtolower((string) $transmittal->mode);
            }

            if (! empty($transmittal->origin_office)) {
                $transmittal->office_name = $transmittal->origin_office;
            } elseif (! empty($transmittal->office_name) && $canSyncV2) {
                $transmittal->origin_office = $transmittal->office_name;
            }

            if (! empty($transmittal->recipient_address)) {
                $transmittal->address = $transmittal->recipient_address;
            } elseif (! empty($transmittal->address) && $canSyncV2) {
                $transmittal->recipient_address = $transmittal->address;
            }

            if (empty($transmittal->party_name) && ! empty($transmittal->related_company)) {
                $transmittal->party_name = $transmittal->related_company;
            } elseif (empty($transmittal->related_company) && ! empty($transmittal->party_name) && $canSyncV2) {
                $transmittal->related_company = $transmittal->party_name;
            }

            if (! empty($transmittal->operations_manager)) {
                $transmittal->approved_position = $transmittal->operations_manager;
            } elseif (! empty($transmittal->approved_position) && $canSyncV2) {
                $transmittal->operations_manager = $transmittal->approved_position;
            }

            if (! empty($transmittal->delivered_by)) {
                $transmittal->document_custodian = $transmittal->delivered_by;
            } elseif (! empty($transmittal->document_custodian) && $canSyncV2) {
                $transmittal->delivered_by = $transmittal->document_custodian;
            }

            if (! empty($transmittal->processing_company)) {
                $transmittal->receiver_affiliation = $transmittal->processing_company;
            } elseif (! empty($transmittal->receiver_affiliation) && $canSyncV2) {
                $transmittal->processing_company = $transmittal->receiver_affiliation;
            }

            if (empty($transmittal->submitted_by) && ! empty($transmittal->prepared_by)) {
                $transmittal->submitted_by = $transmittal->prepared_by;
            }

            if (is_array($transmittal->delivery_actions)) {
                $transmittal->action_delivery = in_array('delivery', $transmittal->delivery_actions, true);
                $transmittal->action_pick_up = in_array('pick_up', $transmittal->delivery_actions, true);
                $transmittal->action_drop_off = in_array('drop_off', $transmittal->delivery_actions, true);
                $transmittal->action_email = in_array('email', $transmittal->delivery_actions, true);
            }

            if (! empty($transmittal->rejection_reason) && empty($transmittal->review_note)) {
                $transmittal->review_note = $transmittal->rejection_reason;
            }

            if (! empty($transmittal->status)) {
                [$wf, $app] = match ($transmittal->status) {
                    'draft' => ['Uploaded', 'Draft'],
                    'pending_approval' => ['Submitted', 'Pending'],
                    'approved', 'sent', 'acknowledged' => ['Accepted', 'Approved'],
                    'rejected' => ['Reverted', 'Rejected'],
                    'cancelled' => ['Archived', 'Cancelled'],
                    default => [$transmittal->workflow_status ?: 'Uploaded', $transmittal->approval_status ?: 'Draft'],
                };
                $transmittal->workflow_status = $wf;
                $transmittal->approval_status = $app;
            }
        });

        static::updating(function (Transmittal $transmittal): void {
            if ($transmittal->getOriginal('approved_snapshot') !== null
                && $transmittal->isDirty([
                    'tracking_number',
                    'external_reference',
                    'subject',
                    'purpose',
                    'direction',
                    'transmittal_date',
                    'origin_office',
                    'recipient_address',
                    'related_company',
                    'delivery_method',
                    'delivery_type',
                    'delivery_actions',
                    'prepared_by_name',
                    'prepared_at',
                    'approved_by_name',
                    'operations_manager',
                    'delivered_by',
                    'received_by',
                    'processing_company',
                    'edit_token_hash',
                    'confidentiality',
                    'prepared_by',
                    'approved_by',
                    'approved_at',
                    'approved_snapshot',
                    'approval_authority_code',
                    'rejected_by',
                    'rejected_at',
                    'rejection_reason',
                ])) {
                throw new LogicException('Approved transmittal details and snapshots are immutable.');
            }

            if ($transmittal->getOriginal('status') === 'rejected' && $transmittal->isDirty()) {
                $editableFields = [
                    'transmittal_no',
                    'external_reference',
                    'subject',
                    'purpose',
                    'direction',
                    'mode',
                    'transmittal_date',
                    'party_name',
                    'origin_office',
                    'office_name',
                    'recipient_address',
                    'address',
                    'related_company',
                    'delivery_method',
                    'delivery_type',
                    'by_person_who',
                    'registered_mail_provider',
                    'electronic_method',
                    'recipient_email',
                    'delivery_actions',
                    'action_delivery',
                    'action_pick_up',
                    'action_drop_off',
                    'action_email',
                    'approved_by_name',
                    'approved_position',
                    'operations_manager',
                    'document_custodian',
                    'delivered_by',
                    'received_by',
                    'receiver_affiliation',
                    'processing_company',
                    'received_at',
                    'confidentiality',
                    'prepared_by_name',
                    'prepared_at',
                    'edit_token_hash',
                    'status',
                    'workflow_status',
                    'approval_status',
                    'review_note',
                    'submitted_by',
                    'cancelled_by',
                    'cancelled_from_status',
                    'cancelled_at',
                    'cancellation_reason',
                ];

                if (! in_array($transmittal->status, ['rejected', 'pending_approval', 'cancelled'], true)
                    || array_diff(array_keys($transmittal->getDirty()), $editableFields) !== []) {
                    throw new LogicException('Rejected transmittals may only be edited when resubmitted for review.');
                }
            }

            if ($transmittal->getOriginal('approved_snapshot') !== null && $transmittal->isDirty('status')) {
                $allowedStatuses = match ($transmittal->getOriginal('status')) {
                    'approved' => ['sent'],
                    'sent' => ['acknowledged'],
                    default => [],
                };

                if (! in_array($transmittal->status, $allowedStatuses, true)) {
                    throw new LogicException('An approved transmittal cannot leave its delivery workflow.');
                }
            }

            if ($transmittal->getOriginal('status') === 'cancelled' && $transmittal->isDirty('status')) {
                throw new LogicException('Cancelled transmittals must be restored through the authorized restore action.');
            }
        });

        static::deleting(function (Transmittal $transmittal): void {
            if ($transmittal->isForceDeleting()
                && ($transmittal->getOriginal('approved_snapshot') !== null
                    || $transmittal->proofs()->exists()
                    || $transmittal->receipts()->exists()
                    || $transmittal->acknowledgments()->exists())) {
                throw new LogicException('A transmittal with an approval, proof, receipt, or acknowledgment cannot be permanently deleted.');
            }
        });
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(TransmittalRecipient::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TransmittalDocument::class);
    }

    public function workspaceItems(): HasMany
    {
        return $this->hasMany(TransmittalWorkspaceItem::class)->orderBy('position')->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransmittalItem::class)->orderBy('item_no');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(ProofOfTransmission::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(TransmittalReceipt::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TransmittalHistory::class);
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(ExternalAcknowledgment::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(TransmittalReceipt::class)->latestOfMany();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransmittalDocument::class);
    }

    public function authorityCode(): HasOne
    {
        return $this->hasOne(ApprovalAuthorityCode::class);
    }
}
