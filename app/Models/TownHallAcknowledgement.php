<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TownHallAcknowledgement extends Model
{
    protected $table = 'townhall_acknowledgements';

    protected $fillable = [
        'townhall_communication_id',
        'user_id',
        'viewed_at',
        'acknowledged_at',
        'recipient_name',
        'recipient_position',
        'recipient_department',
        'user_account_id',
        'ip_address',
        'device_information',
        'browser_information',
        'operating_system',
        'communication_ref_no',
        'session_id',
        'acknowledgement_status',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function communication()
    {
        return $this->belongsTo(TownHallCommunication::class, 'townhall_communication_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
