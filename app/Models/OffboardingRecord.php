<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OffboardingRecord extends Model
{
    public const TERMINATION = 'termination';
    public const RESIGNATION = 'resignation';
    public const EXIT_INTERVIEW = 'exit-interview';
    public const CLEARANCE = 'clearance';
    public const TURNOVER = 'turnover';
    public const FINAL_PAY = 'final-pay';
    public const QUITCLAIM = 'quitclaim';

    public const TYPES = [
        self::TERMINATION,
        self::RESIGNATION,
        self::EXIT_INTERVIEW,
        self::CLEARANCE,
        self::TURNOVER,
        self::FINAL_PAY,
        self::QUITCLAIM,
    ];

    protected $fillable = [
        'reference_no',
        'form_type',
        'employee_id',
        'employee_code',
        'employee_name',
        'position',
        'department',
        'details',
        'attachment_path',
        'attachment_original_name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'attachment_url',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFormTypeLabelAttribute(): string
    {
        return match ($this->form_type) {
            self::TERMINATION => 'Termination',
            self::RESIGNATION => 'Resignation',
            self::EXIT_INTERVIEW => 'Exit Interview Form',
            self::CLEARANCE => 'Clearance Form',
            self::TURNOVER => 'Turnover Checklist',
            self::FINAL_PAY => 'Final Pay Computation Sheet',
            self::QUITCLAIM => 'Quitclaim',
            default => 'Offboarding Record',
        };
    }
}
