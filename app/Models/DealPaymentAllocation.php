<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealPaymentAllocation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'allocations' => 'array'];
}
