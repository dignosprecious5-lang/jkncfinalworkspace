<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesMarketingIdaAllocation extends Model
{
    protected $table = 'sales_marketing_ida_allocations';

    protected $fillable = [
        'ida_id',
        'earner_id',
        'role',
        'commission_category',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'status',
        'requested_at',
        'requested_by',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function ida()
    {
        return $this->belongsTo(SalesMarketingIda::class, 'ida_id');
    }

    public function earner()
    {
        return $this->belongsTo(SalesMarketingEarner::class, 'earner_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}