<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyAudit extends Model
{
    protected $fillable = [
        'policy_id',
        'user_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
