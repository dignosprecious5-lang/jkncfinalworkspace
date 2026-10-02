<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Deal extends Model
{
    protected $table = 'deals';

    protected $fillable = [
        'account_id',
        'company_id',
        'contact_id',
        'deal_code',
        'stage_id',
        'created_by',
        'deal_name',
        'deal_title',
        'amount',
        'stage',
        'pipeline_stage',
        'commercial_stage',
        'client_search',
        'owner_name',
        'qualification_result',
        'qualification_notes',
        'deal_status',
        'delete_request_status',
        'delete_requested_by',
        'delete_requested_at',
        'approved_at',
        'approved_by_name',
        'rejected_at',
        'rejected_by_name',
        'rejection_reason',
        'service_area',
        'service_areas',
        'services',
        'services_products',
        'total_service_fee',
        'products',
        'total_product_fee',
        'deal_discount',
        'discount',
        'scope_of_work',
        'engagement_type',
        'requirements_status',
        'client_requirements',
        'required_actions',
        'estimated_professional_fee',
        'est_professional_fee',
        'estimated_government_fees',
        'est_government_fee',
        'estimated_service_support_fee',
        'est_service_support_fee',
        'other_fees',
        'services_pricing_guide',
        'products_pricing_guide',
        'total_estimated_engagement_value',
        'payment_terms',
        'payment_terms_other',
        'planned_start_date',
        'estimated_duration',
        'estimated_duration_days',
        'estimated_completion_date',
        'client_preferred_completion_date',
        'confirmed_delivery_date',
        'timeline_notes',

        // Stage tracking
        'stage_entered_at',

        // Inquiry / Consultation tracking
        'inquiry_source',
        'inquiry_date',
        'inquiry_details',
        'inquiry_records',
        'consultation_records',

        'service_complexity',
        'support_required',
        'professional_support_required',
        'complexity_notes',
        'proposal_decision',
        'decline_reason',
        'assigned_consultant',
        'assigned_associate',
        'assigned_finance_user_id',
        'service_department',
        'service_department_unit',
        'consultant_notes',
        'associate_notes',
        'customer_type',
        'salutation',
        'first_name',
        'middle_initial',
        'middle_name',
        'last_name',
        'name_extension',
        'sex',
        'date_of_birth',
        'email',
        'mobile',
        'mobile_number',
        'address',
        'company',
        'company_name',
        'company_address',
        'position',
        'primary_contact_name',
        'prepared_by',
        'reviewed_by',
        'approval_name',
        'approval_date',
        'internal_name',
        'internal_date',
        'client_fullname_signature',
        'referred_by',
        'referred_closed_by',
        'sales_marketing',
        'internal_sales_marketing',
        'lead_consultant',
        'lead_associate',
        'lead_associate_assigned',
        'finance',
        'internal_finance',
        'president',
        'internal_president',
        'expected_close',
        'record_custodian',
        'date_recorded',
        'date_signed',

        // Stage Workflow Requirements
        'client_need',
        'decision_maker',
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

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'internal_date' => 'date:Y-m-d',
        'planned_start_date' => 'date:Y-m-d',
        'estimated_completion_date' => 'date:Y-m-d',
        'client_preferred_completion_date' => 'date:Y-m-d',
        'confirmed_delivery_date' => 'date:Y-m-d',
        'expected_close' => 'date:Y-m-d',
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

        // Stage tracking
        'stage_entered_at' => 'datetime',

        // JSON repeater arrays
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

        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'delete_requested_at' => 'datetime',
        'estimated_professional_fee' => 'decimal:2',
        'est_professional_fee' => 'decimal:2',
        'estimated_government_fees' => 'decimal:2',
        'est_government_fee' => 'decimal:2',
        'estimated_service_support_fee' => 'decimal:2',
        'est_service_support_fee' => 'decimal:2',
        'total_service_fee' => 'decimal:2',
        'total_product_fee' => 'decimal:2',
        'deal_discount' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount' => 'decimal:2',
        'proposal_value' => 'decimal:2',
        'final_deal_value' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'total_estimated_engagement_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Deal $deal): void {
            if (! static::hasValidDealCode($deal->deal_code)) {
                $deal->deal_code = static::generateNextDealCode();
            }

            // Synchronize stage and pipeline_stage
            if (! $deal->pipeline_stage && $deal->stage) {
                $deal->pipeline_stage = $deal->stage;
            } elseif (! $deal->stage && $deal->pipeline_stage) {
                $deal->stage = $deal->pipeline_stage;
            }

            // Start the stage timer when the Deal is created
            if (! $deal->stage_entered_at) {
                $deal->stage_entered_at = now();
            }
        });

        static::created(function (Deal $deal): void {
            if (! class_exists(DealStageHistory::class)) {
                return;
            }

            $currentStage = $deal->pipeline_stage ?: ($deal->stage ?: 'Inquiry');

            DealStageHistory::firstOrCreate(
                [
                    'deal_id' => $deal->id,
                    'stage' => $currentStage,
                    'ended_at' => null,
                ],
                [
                    'started_at' => $deal->stage_entered_at ?: ($deal->created_at ?: now()),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()?->name ?: ($deal->created_by ?: ($deal->owner_name ?: 'System')),
                    'notes' => 'Initial deal creation stage',
                ]
            );
        });

        static::updating(function (Deal $deal): void {
            // Keep stage and pipeline_stage in sync
            if ($deal->isDirty('pipeline_stage') && ! $deal->isDirty('stage')) {
                $deal->stage = $deal->pipeline_stage;
            } elseif ($deal->isDirty('stage') && ! $deal->isDirty('pipeline_stage')) {
                $deal->pipeline_stage = $deal->stage;
            }
        });
    }

    public static function generateNextDealCode(
        ?int $year = null,
        ?int $ignoreDealId = null
    ): string {
        $year ??= (int) now()->format('Y');

        $prefix = sprintf('CONDEAL-%d-', $year);

        $nextNumber = DB::transaction(function () use (
            $prefix,
            $ignoreDealId
        ): int {
            $latestCode = static::query()
                ->when(
                    $ignoreDealId,
                    fn (Builder $query) =>
                        $query->whereKeyNot($ignoreDealId)
                )
                ->where('deal_code', 'like', $prefix . '%')
                ->whereNotNull('deal_code')
                ->orderByDesc('deal_code')
                ->lockForUpdate()
                ->value('deal_code');

            if (! static::hasValidDealCode($latestCode)) {
                return 1;
            }

            $lastNumber = (int) substr((string) $latestCode, -3);

            return $lastNumber + 1;
        });

        return $prefix . str_pad(
            (string) $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    public static function hasValidDealCode(?string $dealCode): bool
    {
        return is_string($dealCode)
            && preg_match(
                '/^CONDEAL-\d{4}-\d{3}$/',
                $dealCode
            ) === 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function dealContacts(): HasMany
    {
        return $this->hasMany(DealContact::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DealStage::class, 'stage_id');
    }

    public function assignedFinance(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_finance_user_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class)
            ->where(function (Builder $workspaceQuery): void {
                $workspaceQuery
                    ->whereNull('engagement_type')
                    ->orWhereRaw(
                        'LOWER(engagement_type) NOT LIKE ?',
                        ['%regular%']
                    );
            })
            ->latestOfMany();
    }

    public function regularProject(): HasOne
    {
        return $this->hasOne(Project::class)
            ->whereRaw(
                'LOWER(COALESCE(engagement_type, "")) LIKE ?',
                ['%regular%']
            )
            ->latestOfMany();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function proposal(): HasOne
    {
        return $this->hasOne(DealProposal::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DealHistory::class)->latestFirst();
    }

    public function projectScopeItems(): HasMany
    {
        return $this->hasMany(DealContact::class, 'deal_id')->whereRaw('1 = 0');
    }

    public function getProjectScopeItemsAttribute()
    {
        return collect([]);
    }

    public function getStartRecordsAttribute()
    {
        return collect([]);
    }

    public function getClientActionRequestsAttribute()
    {
        return collect([]);
    }

    /*
    |--------------------------------------------------------------------------
    | Stage History & Timer Methods
    |--------------------------------------------------------------------------
    */

    public function stageHistories(): HasMany
    {
        return $this->hasMany(DealStageHistory::class)
            ->orderBy('started_at', 'asc')
            ->orderBy('id', 'asc');
    }

    public function currentStageHistory(): HasOne
    {
        return $this->hasOne(DealStageHistory::class)
            ->whereNull('ended_at')
            ->latestOfMany('started_at');
    }

    public function getCurrentStageStartedAtAttribute(): ?\Carbon\Carbon
    {
        if ($this->stage_entered_at) {
            return $this->stage_entered_at;
        }

        $active = $this->stageHistories()->whereNull('ended_at')->latest('started_at')->first();
        if ($active && $active->started_at) {
            return $active->started_at;
        }

        return $this->created_at ?? now();
    }

    public function getCurrentStageDurationAttribute(): string
    {
        $startedAt = $this->current_stage_started_at;
        if (! $startedAt) {
            return '0s';
        }

        $elapsed = max(0, $startedAt->diffInSeconds(now()));
        return DealStageHistory::formatDuration($elapsed);
    }

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

        $currentStage = $this->pipeline_stage ?: ($this->stage ?: 'Inquiry');
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

    public static function formatStageDuration(int $seconds): string
    {
        return DealStageHistory::formatDuration($seconds);
    }

    public function userCanAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        $userTokens = collect([
            $user->name,
            $user->email,
            (string) $user->id,
        ])
            ->filter()
            ->map(
                fn ($value): string =>
                    mb_strtolower(trim((string) $value))
            )
            ->all();

        $dealRelationTokens = collect([
            $this->created_by,
            $this->owner_name,
            $this->assigned_consultant,
            $this->assigned_associate,
            $this->prepared_by,
            $this->reviewed_by,
            $this->internal_name,
            $this->lead_consultant,
            $this->lead_associate,
            $this->lead_associate_assigned,
            $this->finance,
            $this->internal_finance,
            $this->assignedFinance?->name,
            $this->assignedFinance?->email,
            $this->assigned_finance_user_id
                ? (string) $this->assigned_finance_user_id
                : null,
        ])
            ->filter()
            ->map(
                fn ($value): string =>
                    mb_strtolower(trim((string) $value))
            )
            ->all();

        return count(
            array_intersect($userTokens, $dealRelationTokens)
        ) > 0;
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(DealProposal::class, 'deal_id');
    }
}