<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transmittal extends Model
{
    use HasFactory;

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
}
