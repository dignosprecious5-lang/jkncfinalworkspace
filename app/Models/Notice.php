<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Notice extends Model
{
    protected $fillable = [
        'company_id',
        'notice_number',
        'date_of_notice',
        'governing_body',
        'type_of_meeting',
        'date_of_meeting',
        'time_started',
        'location',
        'meeting_no',
        'chairman',
        'secretary',

        // Meeting procedure fields
        'meeting_mode',
        'meeting_platform',
        'meeting_link_details',
        'authorized_meeting_officer',
        'confirmation_email',
        'confirmation_phone',
        'office_address',
        'email_phone_confirmation_deadline',
        'physical_submission_deadline',
        'authority_calling_meeting',

        'uploaded_by',
        'date_updated',
        'body_html',
        'body_mode',
        'document_path',
    ];

    protected $casts = [
        'date_of_notice' => 'date',
        'date_of_meeting' => 'date',
        'date_updated' => 'date',
    ];

    public function minutes()
    {
        if (Schema::hasColumn('minutes', 'notice_id')) {
            return $this->hasMany(Minute::class, 'notice_id');
        }

        return $this->hasMany(Minute::class, 'notice_ref', 'notice_number');
    }

    public function resolutions()
    {
        if (Schema::hasColumn('resolutions', 'notice_id')) {
            return $this->hasMany(Resolution::class, 'notice_id');
        }

        return $this->hasMany(Resolution::class, 'notice_ref', 'notice_number');
    }

    public function secretaryCertificates()
    {
        if (Schema::hasColumn('secretary_certificates', 'notice_id')) {
            return $this->hasMany(SecretaryCertificate::class, 'notice_id');
        }

        return $this->hasMany(SecretaryCertificate::class, 'notice_ref', 'notice_number');
    }
}
