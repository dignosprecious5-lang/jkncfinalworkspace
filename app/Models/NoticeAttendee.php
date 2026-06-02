<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticeAttendee extends Model
{
    protected $fillable = [
        'notice_id',
        'name',
        'position',
        'email',
        'source_type',
        'source_id',
        'is_selected',
        'sent_at',
        'sort_order',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }
}
