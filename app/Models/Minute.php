<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Minute extends Model
{
    protected $fillable = [
        'company_id',
        'minutes_ref',
        'date_uploaded',
        'uploaded_by',
        'approved_by',
        'governing_body',
        'type_of_meeting',
        'meeting_mode',
        'notice_id',
        'notice_ref',
        'date_of_meeting',
        'time_started',
        'time_ended',
        'location',
        'call_link',
        'recording_notes',
        'script_text',
        'meeting_no',
        'chairman',
        'secretary',
        'document_path',
        'approved_minutes_path',
        'tentative_audio_path',
        'final_audio_path',
        'meeting_video_path',
        'script_file_path',
        'recording_clips',
    ];

    protected $casts = [
        'date_uploaded' => 'date',
        'date_of_meeting' => 'date',
        'recording_clips' => 'array',
    ];

    public function notice()
    {
        if (Schema::hasColumn('minutes', 'notice_id')) {
            return $this->belongsTo(Notice::class, 'notice_id');
        }

        return $this->belongsTo(Notice::class, 'notice_ref', 'notice_number');
    }
}
