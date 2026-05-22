<?php

namespace App\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'business_date',
        'intake_date',
        'customer_type',
        'client_status',
        'salutation',
        'first_name',
        'middle_initial',
        'middle_name',
        'last_name',
        'name_extension',
        'sex',
        'date_of_birth',
        'company_name',
        'company_address',
        'contact_address',
        'position',
        'business_type_organization',
        'organization_type',
        'organization_type_other',
        'nature_of_business',
        'capitalization_amount',
        'ownership_structure',
        'previous_year_revenue',
        'years_operating',
        'projected_current_year_revenue',
        'ownership_flag',
        'foreign_business_nature',
        'service_inquiry_types',
        'service_inquiry_other',
        'service_inquiry_type',
        'inquiry',
        'jknc_notes',
        'sales_marketing',
        'consultant_lead',
        'lead_associate',
        'recommendation_options',
        'recommendation_other',
        'lead_source_channels',
        'lead_source_other',
        'referred_by',
        'lead_stage',
        'recommendation',
        'email',
        'phone',
        'kyc_status',
        'cif_status',
        'cif_submitted_at',
        'cif_reviewed_at',
        'cif_reviewed_by',
        'cif_rejection_reason',
        'owner_name',
        'last_activity_at',
        'lead_source',
        'description',
        'cif_no',
        'tin',
        'created_by',
        'cif_access_token',
        'cif_access_expires_at',
        'cif_form_sent_to_email',
        'cif_form_sent_at',
        'specimen_access_token',
        'specimen_access_expires_at',
        'specimen_form_sent_to_email',
        'specimen_form_sent_at',
        'cif_data',
        'cif_documents',
        'kyc_requirement_documents',
    ];

    protected $casts = [
        'business_date' => 'date',
        'intake_date' => 'date',
        'date_of_birth' => 'date',
        'last_activity_at' => 'datetime',
        'cif_access_expires_at' => 'datetime',
        'cif_form_sent_at' => 'datetime',
        'cif_submitted_at' => 'datetime',
        'cif_reviewed_at' => 'datetime',
        'specimen_access_expires_at' => 'datetime',
        'specimen_form_sent_at' => 'datetime',
        'capitalization_amount' => 'decimal:2',
        'previous_year_revenue' => 'decimal:2',
        'projected_current_year_revenue' => 'decimal:2',
        'service_inquiry_types' => 'array',
        'recommendation_options' => 'array',
        'lead_source_channels' => 'array',
        'cif_data' => 'array',
        'cif_documents' => 'array',
        'kyc_requirement_documents' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function primaryCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'primary_contact_id');
    }

    public function specimenSignature(): HasOne
    {
        return $this->hasOne(SpecimenSignature::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_initial,
            $this->last_name,
            $this->name_extension,
        ])));
    }
}
