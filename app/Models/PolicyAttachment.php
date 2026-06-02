<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyAttachment extends Model
{
    protected $fillable = [
        'policy_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
