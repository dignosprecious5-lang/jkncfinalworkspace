<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Resolution extends Model
{
    protected $fillable = [
        'company_id',
        'resolution_no',
        'date_uploaded',
        'uploaded_by',
        'governing_body',
        'type_of_meeting',
        'minute_id',
        'notice_id',
        'notice_ref',
        'meeting_no',
        'date_of_meeting',
        'location',
        'board_resolution',
        'resolution_body',
        'directors',
        'chairman',
        'secretary',
        'notary_doc_no',
        'notary_page_no',
        'notary_book_no',
        'notary_series_no',
        'notary_public',
        'notarized_on',
        'notarized_at',
        'draft_file_path',
        'notarized_file_path',
    ];

    protected $casts = [
        'date_uploaded' => 'date',
        'date_of_meeting' => 'date',
        'notarized_on' => 'date',
    ];

    public function notice()
    {
        if (Schema::hasColumn('resolutions', 'notice_id')) {
            return $this->belongsTo(Notice::class, 'notice_id');
        }

        return $this->belongsTo(Notice::class, 'notice_ref', 'notice_number');
    }

    public function minute()
    {
        if (Schema::hasColumn('resolutions', 'minute_id')) {
            return $this->belongsTo(Minute::class, 'minute_id');
        }

        return $this->belongsTo(Minute::class, 'notice_ref', 'notice_ref');
    }

    public function secretaryCertificates()
    {
        if (Schema::hasColumn('secretary_certificates', 'resolution_id')) {
            return $this->hasMany(SecretaryCertificate::class, 'resolution_id');
        }

        return $this->hasMany(SecretaryCertificate::class, 'resolution_no', 'resolution_no');
    }
}
