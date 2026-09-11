<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'user_name',
        'action',
        'details',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}