<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesMarketingIda extends Model
{
    protected $table = 'sales_marketing_idas';

    protected $fillable = [
        'deal_id',
        'condeal_ref_no',
        'client_name',
        'business_name',
        'service_area',
        'product_engagement_structure',
        'deal_value',
        'workflow_status',
        'created_by',
        'submitted_at',
        'submitted_by',
        'accepted_at',
        'accepted_by',
        'reverted_at',
        'reverted_by',
    ];

    protected $casts = [
        'deal_value' => 'decimal:2',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'reverted_at' => 'datetime',
    ];

    public function allocations()
    {
        return $this->hasMany(SalesMarketingIdaAllocation::class, 'ida_id');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function revertedBy()
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }
}