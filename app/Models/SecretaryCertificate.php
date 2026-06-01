<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SecretaryCertificate extends Model
{
    protected $fillable = [
        'company_id',
        'certificate_no',
        'date_uploaded',
        'uploaded_by',
        'governing_body',
        'type_of_meeting',
        'minute_id',
        'notice_id',
        'notice_ref',
        'meeting_no',
        'minutes_ref',
        'resolution_id',
        'resolution_no',
        'resolution_body',
        'date_issued',
        'purpose',
        'date_of_meeting',
        'location',
        'secretary',
        'secretary_address',
        'secretary_tin',
        'notarial_place',
        'notary_doc_no',
        'notary_page_no',
        'notary_book_no',
        'notary_series_no',
        'notary_public',
        'document_path',
    ];

    protected $casts = [
        'date_uploaded' => 'date',
        'date_issued' => 'date',
        'date_of_meeting' => 'date',
    ];

    public function notice()
    {
        if (Schema::hasColumn('secretary_certificates', 'notice_id')) {
            return $this->belongsTo(Notice::class, 'notice_id');
        }

        return $this->belongsTo(Notice::class, 'notice_ref', 'notice_number');
    }

    public function resolution()
    {
        if (Schema::hasColumn('secretary_certificates', 'resolution_id')) {
            return $this->belongsTo(Resolution::class, 'resolution_id');
        }

        return $this->belongsTo(Resolution::class, 'resolution_no', 'resolution_no');
    }

    public function minute()
    {
        if (Schema::hasColumn('secretary_certificates', 'minute_id')) {
            return $this->belongsTo(Minute::class, 'minute_id');
        }

        return $this->belongsTo(Minute::class, 'minutes_ref', 'minutes_ref');
    }
}
