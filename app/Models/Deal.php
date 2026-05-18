<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Deal extends Model
{
    protected $fillable = [
        'contact_id',
        'deal_code',
        'stage_id',
        'created_by',
        'deal_name',
        'stage',
        'qualification_result',
        'qualification_notes',
        'deal_status',
        'approved_at',
        'approved_by_name',
        'rejected_at',
        'rejected_by_name',
        'rejection_reason',
        'service_area',
        'services',
        'total_service_fee',
        'products',
        'total_product_fee',
        'deal_discount',
        'scope_of_work',
        'engagement_type',
        'requirements_status',
        'required_actions',
        'estimated_professional_fee',
        'estimated_government_fees',
        'estimated_service_support_fee',
        'other_fees',
        'total_estimated_engagement_value',
        'payment_terms',
        'payment_terms_other',
        'planned_start_date',
        'estimated_duration',
        'estimated_completion_date',
        'client_preferred_completion_date',
        'confirmed_delivery_date',
        'timeline_notes',
        'service_complexity',
        'support_required',
        'complexity_notes',
        'proposal_decision',
        'decline_reason',
        'assigned_consultant',
        'assigned_associate',
        'assigned_finance_user_id',
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
        'address',
        'company_name',
        'company_address',
        'position',
        'prepared_by',
        'reviewed_by',
        'internal_name',
        'internal_date',
        'client_fullname_signature',
        'referred_closed_by',
        'internal_sales_marketing',
        'lead_consultant',
        'lead_associate_assigned',
        'internal_finance',
        'internal_president',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'internal_date' => 'date',
        'planned_start_date' => 'date',
        'estimated_completion_date' => 'date',
        'client_preferred_completion_date' => 'date',
        'confirmed_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'estimated_professional_fee' => 'decimal:2',
        'estimated_government_fees' => 'decimal:2',
        'estimated_service_support_fee' => 'decimal:2',
        'total_service_fee' => 'decimal:2',
        'total_product_fee' => 'decimal:2',
        'deal_discount' => 'decimal:2',
        'other_fees' => 'array',
        'total_estimated_engagement_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Deal $deal): void {
            if (! static::hasValidDealCode($deal->deal_code)) {
                $deal->deal_code = static::generateNextDealCode();
            }
        });
    }

    public static function generateNextDealCode(?int $year = null, ?int $ignoreDealId = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = sprintf('CONDEAL-%d-', $year);

        $nextNumber = DB::transaction(function () use ($prefix, $ignoreDealId): int {
            $latestCode = static::query()
                ->when($ignoreDealId, fn (Builder $query) => $query->whereKeyNot($ignoreDealId))
                ->where('deal_code', 'like', $prefix.'%')
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

        return $prefix.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public static function hasValidDealCode(?string $dealCode): bool
    {
        return is_string($dealCode)
            && preg_match('/^CONDEAL-\d{4}-\d{3}$/', $dealCode) === 1;
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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
                    ->orWhereRaw('LOWER(engagement_type) NOT LIKE ?', ['%regular%']);
            })
            ->latestOfMany();
    }

    public function regularProject(): HasOne
    {
        return $this->hasOne(Project::class)
            ->whereRaw('LOWER(COALESCE(engagement_type, "")) LIKE ?', ['%regular%'])
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
            ->map(fn ($value): string => mb_strtolower(trim((string) $value)))
            ->all();

        $dealRelationTokens = collect([
            $this->created_by,
            $this->assigned_consultant,
            $this->assigned_associate,
            $this->prepared_by,
            $this->reviewed_by,
            $this->internal_name,
            $this->lead_consultant,
            $this->lead_associate_assigned,
            $this->internal_finance,
            $this->assignedFinance?->name,
            $this->assignedFinance?->email,
            $this->assigned_finance_user_id ? (string) $this->assigned_finance_user_id : null,
        ])
            ->filter()
            ->map(fn ($value): string => mb_strtolower(trim((string) $value)))
            ->all();

        return count(array_intersect($userTokens, $dealRelationTokens)) > 0;
    }
}
