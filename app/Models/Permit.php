<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Permit extends Model
{
    protected $fillable = [
        'company_id',
        'company_name',
        'province',
        'city_municipality',
        'barangay',
        'permit_type',
        'document_type',
        'permit_number',
        'date_of_registration',
        'renewal_date',
        'total_permit_fee',
        'approved_date_of_registration',
        'expiration_date_of_registration',
        'user',
        'tin',
        'document_name',
        'document_path',
        'draft_documents',
        'approved_documents',
        'uploaded_by',
        'date_uploaded_at',
        'last_updated_by',
        'last_updated_at',
        'approval_status',
        'workflow_status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'review_note',
    ];

    protected $appends = ['status'];

    protected $casts = [
        'date_of_registration' => 'date:Y-m-d',
        'renewal_date' => 'date:Y-m-d',
        'approved_date_of_registration' => 'date:Y-m-d',
        'expiration_date_of_registration' => 'date:Y-m-d',
        'draft_documents' => 'array',
        'approved_documents' => 'array',
        'date_uploaded_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getStatusAttribute()
    {
        $deadline = $this->renewal_date ?: $this->expiration_date_of_registration;

        if (!$deadline) {
            return 'Active';
        }

        $days = Carbon::today()->diffInDays($deadline, false);

        return match (true) {
            $days < 0 => 'Expired',
            $days <= 30 => 'Expiring Soon',
            $days <= 90 => 'For Renewal',
            default => 'Active',
        };
    }
}
