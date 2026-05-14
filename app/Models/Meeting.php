<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'title', 'owner', 'date', 'time', 'duration', 'location', 'attendees', 'related_to', 'status', 'description', 'has_video', 'has_audio', 'has_transcript', 'has_minutes', 'video_path', 'audio_path'
    ];

    protected $appends = ['video_url', 'audio_url'];

    public function getVideoUrlAttribute()
    {
        if (!$this->video_path) return null;
        return str_starts_with($this->video_path, 'http') ? $this->video_path : url($this->video_path);
    }

    public function getAudioUrlAttribute()
    {
        if (!$this->audio_path) return null;
        return str_starts_with($this->audio_path, 'http') ? $this->audio_path : url($this->audio_path);
    }

    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
