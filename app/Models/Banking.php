<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banking extends Model
{
    protected $fillable = [
        'company_id',
        'company_name',
        'date_uploaded',
        'user',
        'submitted_by',
        'client',
        'tin',
        'bank',
        'bank_doc',
        'document_date',
        'status',
        'draft_documents',
        'approved_documents',
        'uploaded_by',
        'date_uploaded_at',
        'last_updated_by',
        'last_updated_at',
        'workflow_status',
        'approval_status',
        'approved_by',
        'approved_at',
        'review_note',
        'document_name',
        'document_path',
    ];

    protected $casts = [
        'date_uploaded' => 'date',
        'document_date' => 'date',
        'draft_documents' => 'array',
        'approved_documents' => 'array',
        'date_uploaded_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
