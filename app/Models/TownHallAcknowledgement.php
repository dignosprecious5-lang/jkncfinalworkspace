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
