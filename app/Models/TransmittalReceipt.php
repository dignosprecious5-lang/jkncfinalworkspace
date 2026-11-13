<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransmittalReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_id',
        'receipt_no',
        'receipt_date',
        'mode',
        'from_name',
        'to_name',
        'office_name',
        'delivery_type',
        'delivery_detail',
        'recipient_email',
        'actions_summary',
        'prepared_by_name',
        'approved_by_name',
        'approved_position',
        'document_custodian',
        'delivered_by',
        'received_by',
        'received_at',
        'generated_by',
        'recipient_id',
        'signed_name',
        'comments',
        'receipt_code',
        'snapshot',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'received_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function transmittal()
    {
        return $this->belongsTo(Transmittal::class);
    }

    public function recipient()
    {
        return $this->belongsTo(TransmittalRecipient::class, 'recipient_id');
    }
}
