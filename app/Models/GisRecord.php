<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GisRecord extends Model
{
    protected $fillable = [
        'uploaded_by',
        'submission_status',
        'receive_on',
        'period_date',
        'company_reg_no',
        'corporation_name',
        'annual_meeting',
        'meeting_type',
        'file',
        'notary_file_path',
        'logo_path',

        'date_registered',
        'trade_name',
        'fiscal_year_end',
        'tin',
        'website',
        'email',
        'principal_address',
        'business_address',
        'official_mobile',
        'alternate_mobile',
        'auditor',
        'industry',
        'geo_code',

        'parent_company_name',
        'parent_company_sec_no',
        'parent_company_address',
        'subsidiary_name',
        'subsidiary_sec_no',
        'subsidiary_address',

        'approval_status',
        'workflow_status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'review_note',
    ];

    public function authorizedCapital()
    {
        return $this->hasMany(AuthorizedCapitalStock::class, 'gis_id');
    }

    public function subscribedCapital()
    {
        return $this->hasMany(SubscribedCapital::class, 'gis_id');
    }

    public function paidUpCapital()
    {
        return $this->hasMany(PaidUpCapital::class, 'gis_id');
    }

    public function directors()
    {
        return $this->hasMany(DirectorOfficer::class, 'gis_id');
    }

    public function stockholders()
    {
        return $this->hasMany(Stockholder::class, 'gis_id');
    }

    public function ubos()
    {
        return $this->hasMany(UltimateBeneficialOwner::class, 'gis_id');
    }
}
