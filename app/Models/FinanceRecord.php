<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceRecord extends Model
{
    protected $fillable = [
        'module_key',
        'record_number',
        'record_title',
        'record_date',
        'amount',
        'status',
        'workflow_status',
        'approval_status',
        'relationship_status',
        'next_action',
        'disbursement_status',
        'transaction_progress',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'review_note',
        'data',
        'attachments',
        'share_token',
        'shared_at',
        'supplier_completed_at',
        'user',
    ];

    protected $casts = [
        'record_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'shared_at' => 'datetime',
        'supplier_completed_at' => 'datetime',
        'data' => 'array',
        'attachments' => 'array',
        'transaction_progress' => 'array',
        'amount' => 'decimal:2',
    ];
}
