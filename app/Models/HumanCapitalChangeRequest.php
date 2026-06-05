<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HumanCapitalChangeRequest extends Model
{
    protected $fillable = [
        'module',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'old_values',
        'new_values',
        'status',
        'requested_by',
        'requested_by_name',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
