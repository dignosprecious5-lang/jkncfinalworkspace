<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DealContact;
use App\Models\Company;

class Deal extends Model
{
    use HasFactory;

    protected $table = 'deals';

    protected $fillable = [
        /*
        |--------------------------------------------------------------------------
        | R1 — Account
        |--------------------------------------------------------------------------
        */

        'account_id',

        'deal_code',
        'deal_title',
        'amount',
        'customer_type',
        'pipeline_stage',
        'commercial_stage',
        'client_search',
        'owner_name',
        'created_by',

        // Contact Information
        'salutation',
        'sex',
        'first_name',
        'middle_initial',
        'last_name',
        'name_extension',
        'date_of_birth',
        'email',
        'mobile_number',
        'address',
        'company',
        'company_address',
        'position',

        // Client / Deal
        'primary_contact_name',
        'company_name',

        // Services
        'service_areas',
        'services_products',
        'scope_of_work',
        'engagement_type',

        // Requirements / Actions
        'client_requirements',
        'required_actions',

        // Fees
        'est_professional_fee',
        'est_government_fee',
        'est_service_support_fee',
        'total_service_fee',
        'total_product_fee',
        'discount',
        'total_estimated_engagement_value',
        'services_pricing_guide',
        'products_pricing_guide',
        'other_fees',

        // Payment
        'payment_terms',

        // Timeline
        'planned_start_date',
        'estimated_duration_days',
        'estimated_completion_date',
        'client_preferred_completion_date',
        'confirmed_delivery_date',
        'timeline_notes',

        // Complexity
        'service_complexity',
        'professional_support_required',
        'complexity_notes',

        // Proposal
        'proposal_decision',

        // Assignment
        'assigned_consultant',
        'assigned_associate',
        'service_department',

        // Notes
        'consultant_notes',
        'associate_notes',

        // Approval
        'prepared_by',
        'reviewed_by',
        'approval_name',
        'approval_date',
        'client_fullname_signature',

        // Referral / Team
        'referred_by',
        'sales_marketing',
        'lead_consultant',
        'lead_associate',
        'finance',
        'president',

        // Dashboard
        'expected_close',

        // Record
        'record_custodian',
        'date_recorded',
        'date_signed',
    ];

    protected function casts(): array
    {
        return [
            'service_areas' => 'array',
            'services_products' => 'array',
            'client_requirements' => 'array',
            'required_actions' => 'array',
            'services_pricing_guide' => 'array',
            'products_pricing_guide' => 'array',
            'other_fees' => 'array',
            'professional_support_required' => 'array',

            'date_of_birth' => 'date:Y-m-d',
            'expected_close' => 'date:Y-m-d',
            'planned_start_date' => 'date:Y-m-d',
            'estimated_completion_date' => 'date:Y-m-d',
            'client_preferred_completion_date' => 'date:Y-m-d',
            'confirmed_delivery_date' => 'date:Y-m-d',
            'approval_date' => 'date:Y-m-d',
            'date_recorded' => 'date:Y-m-d',
            'date_signed' => 'date:Y-m-d',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | R1 — Account
    |--------------------------------------------------------------------------
    |
    | Every Deal belongs to an Account Party.
    |
    */

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    |
    | A Deal may be linked to an existing Company.
    | Company master data remains the source of truth for Industry.
    |
    */

    public function company(): BelongsTo
    {
           return $this->belongsTo(Company::class, 'company_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Proposal
    |--------------------------------------------------------------------------
    */

    public function proposal(): HasOne
    {
        return $this->hasOne(Proposal::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Deal Contacts
    |--------------------------------------------------------------------------
    */

    public function dealContacts(): HasMany
    {
        return $this->hasMany(DealContact::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Project Scope Items
    |--------------------------------------------------------------------------
    */

    public function projectScopeItems(): HasMany
    {
        return $this->hasMany(ProjectScopeItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | START RECORDS
    |--------------------------------------------------------------------------
    */

    public function startRecords(): HasMany
    {
        return $this->hasMany(StartRecord::class);
    }
}