<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accounting extends Model
{
    protected $fillable = [
        'company_id',
        'company_name',
        'statement_type',
        'client',
        'tin',
        'date',
        'reporting_period_from',
        'reporting_period_to',
        'user',
        'submitted_by',
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
        'date' => 'date',
        'reporting_period_from' => 'date',
        'reporting_period_to' => 'date',
        'draft_documents' => 'array',
        'approved_documents' => 'array',
        'date_uploaded_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
