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
        'stage_entered_at',
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
        'consultation_records',

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

        // Stage Workflow Requirements
        'inquiry_source',
        'inquiry_date',
        'inquiry_details',
        'inquiry_records',
        'qualification_result',
        'client_need',
        'decision_maker',
        'qualification_notes',
        'consultation_date',
        'consultation_type',
        'requirements_confirmed',
        'proposal_number',
        'proposal_date',
        'proposal_value',
        'proposal_valid_until',
        'proposal_status',
        'proposal_notes',
        'negotiation_status',
        'final_deal_value',
        'pricing_model',
        'negotiation_notes',
        'payment_method',
        'payment_amount',
        'payment_date',
        'payment_status',
        'payment_reference',
        'activation_date',
        'assigned_team',
        'assigned_person',
        'service_start_date',
        'activation_notes',
        'closed_won_date',
        'closed_lost_date',
        'lost_reason',
        'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'service_areas' => 'array',
            'services_products' => 'array',
            'client_requirements' => 'array',
            'required_actions' => 'array',
            'inquiry_records' => 'array',
            'consultation_records' => 'array',
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

            'inquiry_date' => 'date:Y-m-d',
            'consultation_date' => 'date:Y-m-d',
            'proposal_date' => 'date:Y-m-d',
            'proposal_valid_until' => 'date:Y-m-d',
            'payment_date' => 'date:Y-m-d',
            'activation_date' => 'date:Y-m-d',
            'service_start_date' => 'date:Y-m-d',
            'closed_won_date' => 'date:Y-m-d',
            'closed_lost_date' => 'date:Y-m-d',
            'stage_entered_at' => 'datetime',
            'proposal_value' => 'decimal:2',
            'final_deal_value' => 'decimal:2',
            'payment_amount' => 'decimal:2',
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

    /*
    |--------------------------------------------------------------------------
    | Stage Histories / Duration Tracking
    |--------------------------------------------------------------------------
    */

    public function stageHistories(): HasMany
    {
        return $this->hasMany(DealStageHistory::class)->orderBy('started_at', 'asc')->orderBy('id', 'asc');
    }

    public function currentStageHistory(): HasOne
    {
        return $this->hasOne(DealStageHistory::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    /**
     * Start timestamp of current stage.
     */
    public function getCurrentStageStartedAtAttribute(): \Carbon\Carbon
    {
        if ($this->stage_entered_at) {
            return $this->stage_entered_at;
        }

        $activeStageHistory = $this->stageHistories()->whereNull('ended_at')->latest('started_at')->first();
        if ($activeStageHistory && $activeStageHistory->started_at) {
            return $activeStageHistory->started_at;
        }

        return $this->created_at ?? now();
    }

    /**
     * Current formatted duration of active stage.
     */
    public function getCurrentStageDurationAttribute(): string
    {
        $startedAt = $this->current_stage_started_at;
        $elapsed = max(0, $startedAt->diffInSeconds(now()));
        return DealStageHistory::formatDuration($elapsed);
    }

    /**
     * Map of stage durations for the pipeline bar.
     */
    public function getStageDurationsMap(): array
    {
        $allStages = [
            'Inquiry',
            'Qualification',
            'Consultation',
            'Proposal',
            'Negotiation',
            'Payment',
            'Activation',
            'Closed Won',
            'Closed Lost',
        ];

        $currentStage = $this->pipeline_stage ?: 'Inquiry';
        $currentIdx = array_search($currentStage, $allStages, true);
        if ($currentIdx === false) {
            $currentIdx = 0;
        }

        $histories = $this->stageHistories;
        $map = [];

        foreach ($allStages as $idx => $stg) {
            $stageRecords = $histories->filter(fn($h) => $h->stage === $stg);

            if ($stg === $currentStage) {
                $runningRecord = $stageRecords->firstWhere('ended_at', null);
                if ($runningRecord) {
                    $map[$stg] = DealStageHistory::formatHumanDuration($runningRecord->elapsed_duration_seconds);
                } else {
                    $elapsed = max(0, $this->current_stage_started_at->diffInSeconds(now()));
                    $map[$stg] = DealStageHistory::formatHumanDuration($elapsed);
                }
            } elseif ($idx < $currentIdx || ($currentStage === 'Closed Won' && $idx < 7) || ($currentStage === 'Closed Lost' && $idx < 7)) {
                $completedRecord = $stageRecords->whereNotNull('ended_at')->last();
                if ($completedRecord && $completedRecord->duration_seconds !== null) {
                    $map[$stg] = DealStageHistory::formatHumanDuration($completedRecord->duration_seconds);
                } elseif ($completedRecord && !empty($completedRecord->duration_formatted)) {
                    $map[$stg] = $completedRecord->duration_formatted;
                } else {
                    $map[$stg] = '0s';
                }
            } else {
                $map[$stg] = '-';
            }
        }

        return $map;
    }

    /*
    |--------------------------------------------------------------------------
    | Universal Client Action Requests
    |--------------------------------------------------------------------------
    */

    public function clientActionRequests(): HasMany
    {
        return $this->hasMany(ClientActionRequest::class)->latestFirst();
    }

    public function pendingClientActionRequests(): HasMany
    {
        return $this->hasMany(ClientActionRequest::class)->pending()->latestFirst();
    }

    /*
    |--------------------------------------------------------------------------
    | Histories / Audit Trail
    |--------------------------------------------------------------------------
    */

    public function histories(): HasMany
    {
        return $this->hasMany(DealHistory::class)->latestFirst();
    }
}