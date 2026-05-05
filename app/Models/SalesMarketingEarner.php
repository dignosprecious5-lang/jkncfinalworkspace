<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesMarketingEarner extends Model
{
    protected $table = 'sales_marketing_earners';

    protected $fillable = [
        'source_type',
        'source_id',
        'full_name',
        'email',
        'mobile_number',
        'bank_name',
        'account_name',
        'account_number',
        'tin',
        'status',
        'created_by',
    ];

    public function allocations()
    {
        return $this->hasMany(SalesMarketingIdaAllocation::class, 'earner_id');
    }
}