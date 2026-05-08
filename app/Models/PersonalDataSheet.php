<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalDataSheet extends Model
{
    protected $fillable = [
        'job_offer_id',
        'full_name',
        'position',
        'email',
        'phone',
        'status',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function jobOffer()
    {
        return $this->belongsTo(JobOffer::class, 'job_offer_id');
    }

    public function checklist()
    {
        return $this->hasOne(OnboardingChecklist::class, 'personal_data_sheet_id');
    }
}
