<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TownHallApprovalAudit extends Model
{
    protected $table = 'townhall_approval_audits';

    protected $fillable = [
        'townhall_communication_id',
        'communication_ref_no',
        'communication_subject',
        'requestor_user_id',
        'requestor_name',
        'action',
        'approval_level',
        'approver_user_id',
        'approver_name',
        'approver_position',
        'approver_department',
        'approval_status',
        'remarks',
        'acted_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function communication()
    {
        return $this->belongsTo(TownHallCommunication::class, 'townhall_communication_id');
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
