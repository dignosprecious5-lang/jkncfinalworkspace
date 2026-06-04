<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HumanCapitalLog extends Model
{
    protected $fillable = [
        'module',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'description',
        'old_values',
        'new_values',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'logged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
