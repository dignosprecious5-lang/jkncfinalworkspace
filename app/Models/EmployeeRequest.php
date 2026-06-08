<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmployeeRequest extends Model
{
    use HasFactory;

    protected $appends = [
        'attachment_url',
    ];

    protected $fillable = [
        'user_id',
        'employee_name',
        'request_type',
        'request_type_other',
        'department',
        'request_date',
        'overtime_date',
        'start_time',
        'end_time',
        'total_hours',
        'leave_type',
        'start_date',
        'end_date',
        'number_of_days',
        'with_pay',
        'attendance_date',
        'correction_type',
        'correct_time',
        'time_affected',
        'purpose',
        'coe_type',
        'coe_purpose_other',
        'date_needed',
        'number_of_copies',
        'reason',
        'remarks',
        'status',
        'absence_type',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'attachment_path',
        'attachment_original_name',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
