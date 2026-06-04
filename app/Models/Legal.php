<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Legal extends Model
{
    protected $fillable = [
        'company_id',
        'company_name',
        'legal_type',
        'client',
        'tin',
        'date',
        'document_type',
        'document_title',
        'effective_date',
        'expiration_date',
        'record_status',
        'document_name',
        'document_path',
        'draft_documents',
        'approved_documents',
        'uploaded_by',
        'date_uploaded_at',
        'last_updated_by',
        'last_updated_at',
        'user',
        'submitted_by',
        'workflow_status',
        'approval_status',
        'approved_by',
        'approved_at',
        'review_note',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'effective_date' => 'date:Y-m-d',
        'expiration_date' => 'date:Y-m-d',
        'draft_documents' => 'array',
        'approved_documents' => 'array',
        'date_uploaded_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getStatusAttribute($value): string
    {
        return $this->record_status ?: ($value ?: ($this->document_path ? 'Executed' : 'Pending'));
    }
}
