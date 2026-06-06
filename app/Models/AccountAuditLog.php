<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AccountAuditLog extends Model
{
    protected $fillable = [
        'user_affected_id',
        'action_performed',
        'performed_by',
        'ip_address',
        'remarks',
    ];

    public static function record(
        string $action,
        ?User $affectedUser = null,
        ?string $remarks = null,
        ?User $performedBy = null,
        ?string $ipAddress = null
    ): void {
        try {
            static::create([
                'user_affected_id' => $affectedUser?->id,
                'action_performed' => $action,
                'performed_by' => $performedBy?->id ?? Auth::id(),
                'ip_address' => $ipAddress ?? request()?->ip(),
                'remarks' => $remarks,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function affectedUser()
    {
        return $this->belongsTo(User::class, 'user_affected_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
