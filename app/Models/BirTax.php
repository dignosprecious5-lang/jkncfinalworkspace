<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class BirTax extends Model
{
    protected $fillable = [
        'tin',
        'tax_payer',
        'rdo',
        'registering_office',
        'registered_address',
        'tax_types',
        'form_type',
        'tax_due',
        'filing_frequency',
        'due_date',
        'status',
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
        'tax_due' => 'decimal:2',
        'due_date' => 'date',
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
        $storedStatus = trim((string) ($this->status ?? ''));
        if (in_array($storedStatus, ['Filed', 'Completed'], true)) {
            return $storedStatus;
        }

        if (!$this->due_date instanceof Carbon) {
            if ($storedStatus !== '') {
                return $storedStatus;
            }

            if (!$this->document_path && !$this->approved_document_path) {
                return 'Draft';
            }

            return 'Pending';
        }

        $today = now()->startOfDay();

        if ($this->due_date->lt($today)) {
            return 'Overdue';
        }

        if ($this->due_date->isSameDay($today)) {
            return 'Due Today';
        }

        return 'Upcoming';
    }
}
