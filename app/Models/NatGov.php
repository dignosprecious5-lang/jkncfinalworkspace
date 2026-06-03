<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class NatGov extends Model
{
    protected $fillable = [
        'client',
        'agency',
        'registration_status',
        'registration_date',
        'renewal_date',
        'deadline_date',
        'registration_no',
        'status',
        'status_override',
        'uploaded_by',
        'date_uploaded',
        'document_path',
        'draft_documents',
        'approved_document_path',
        'approved_documents',
        'notes',
        'notes_visible_to',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'renewal_date' => 'date',
        'deadline_date' => 'date',
        'date_uploaded' => 'date',
        'draft_documents' => 'array',
        'approved_documents' => 'array',
    ];

    public function authorityNotes(): MorphMany
    {
        return $this->morphMany(AuthorityNote::class, 'noteable')->latest();
    }

    public function getDisplayStatusAttribute(): string
    {
        $overrideStatus = trim((string) ($this->status_override ?? ''));
        if (in_array($overrideStatus, $this->manualStatusOptions(), true)) {
            return $overrideStatus;
        }

        $storedStatus = trim((string) ($this->status ?? ''));
        if (in_array($storedStatus, $this->manualStatusOptions(), true)) {
            return $storedStatus;
        }

        $renewalDate = $this->renewal_date instanceof Carbon
            ? $this->renewal_date
            : ($this->deadline_date instanceof Carbon ? $this->deadline_date : null);

        return $this->deriveSystemStatus();
    }

    public function getDerivedStatusAttribute(): string
    {
        return $this->deriveSystemStatus();
    }

    private function deriveSystemStatus(): string
    {
        $renewalDate = $this->renewal_date instanceof Carbon
            ? $this->renewal_date
            : ($this->deadline_date instanceof Carbon ? $this->deadline_date : null);

        $today = now()->startOfDay();

        if (!$renewalDate) {
            return 'Pending';
        }

        if ($renewalDate->lt($today)) {
            return 'Expired';
        }

        $daysUntilRenewal = $today->diffInDays($renewalDate->copy()->startOfDay(), false);

        if ($daysUntilRenewal <= 30) {
            return 'Expiring Soon';
        }

        if ($daysUntilRenewal <= 90) {
            return 'For Renewal';
        }

        return 'Active';
    }

    private function manualStatusOptions(): array
    {
        return ['Pending', 'Approved', 'Suspended', 'Cancelled', 'Revoked'];
    }
}
