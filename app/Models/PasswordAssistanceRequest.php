<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordAssistanceRequest extends Model
{
    protected $fillable = [
        'full_name',
        'registered_email',
        'contact_number',
        'message',
        'submitted_at',
        'ip_address',
        'status',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'handled_at' => 'datetime',
    ];

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
