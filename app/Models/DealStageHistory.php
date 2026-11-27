<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DealStageHistory extends Model
{
    use HasFactory;

    protected $table = 'deal_stage_histories';

    protected $fillable = [
        'deal_id',
        'stage',
        'started_at',
        'ended_at',
        'duration_seconds',
        'duration_formatted',
        'user_id',
        'user_name',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format a duration in seconds to standard HH:MM:SS or Xd HH:MM:SS.
     */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 0) {
            $seconds = 0;
        }

        $days = (int) floor($seconds / 86400);
        $remainder = $seconds % 86400;

        $hours = (int) floor($remainder / 3600);
        $remainderMinutes = $remainder % 3600;
        $minutes = (int) floor($remainderMinutes / 60);
        $secs = (int) ($remainderMinutes % 60);

        $hms = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);

        if ($days > 0) {
            return sprintf('%dd %s', $days, $hms);
        }

        return $hms;
    }

    /**
     * Format duration into clear human-readable days, hours, minutes, and seconds (e.g. 1d 4h 25m, 1h 34m, 19m 22s).
     */
    public static function formatHumanDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0s';
        }

        $days = (int) floor($seconds / 86400);
        $remainder = $seconds % 86400;

        $hours = (int) floor($remainder / 3600);
        $remainderMinutes = $remainder % 3600;
        $minutes = (int) floor($remainderMinutes / 60);
        $secs = (int) ($remainderMinutes % 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}d";
            $parts[] = "{$hours}h";
            if ($minutes > 0) {
                $parts[] = "{$minutes}m";
            }
            return implode(' ', $parts);
        }

        if ($hours > 0) {
            $parts[] = "{$hours}h";
            $parts[] = "{$minutes}m";
            if ($secs > 0 && $hours < 2) {
                $parts[] = "{$secs}s";
            }
            return implode(' ', $parts);
        }

        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
            if ($secs > 0) {
                $parts[] = "{$secs}s";
            }
            return implode(' ', $parts);
        }

        return "{$secs}s";
    }

    public function getElapsedDurationSecondsAttribute(): int
    {
        if ($this->duration_seconds !== null) {
            return max(0, (int) $this->duration_seconds);
        }

        if (! $this->started_at) {
            return 0;
        }

        return max(
            0,
            $this->started_at->diffInSeconds($this->ended_at ?? now())
        );
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!empty($this->duration_formatted) && $this->ended_at !== null) {
            return $this->duration_formatted;
        }

        return self::formatDuration($this->elapsed_duration_seconds);
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->ended_at === null;
    }
}