<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class TransmittalV1AssignmentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transmittal_id',
        'assignment_type',
        'action',
        'actor_user_id',
        'previous_assignee_id',
        'new_assignee_id',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('V1 assignment history is immutable.'));
        static::deleting(fn () => throw new LogicException('V1 assignment history is immutable.'));
    }
}
