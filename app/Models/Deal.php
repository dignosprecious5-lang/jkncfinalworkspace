<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        'casa_stage',
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
        'client_kyc_requirements',
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
        'assigned_user_id',
        'responsible_user_id',
        'reviewer_id',
        'approver_id',
        'assigned_user_ids',
        'assigned_by',
        'assigned_at',
        'start_batches',
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
        'start_batches' => 'array',
        'client_kyc_requirements' => 'array',
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

    public function getResolvedPaymentTermsAttribute(): ?string
    {
        if (filled($this->payment_terms)) {
            return (string) $this->payment_terms;
        }

        foreach (array_reverse($this->services_products ?? []) as $item) {
            if (is_array($item) && filled($item['billing'] ?? null)) {
                return (string) $item['billing'];
            }
        }

        return null;
    }

    protected static function booted(): void
    {
        static::creating(function (Deal $deal): void {
            if (! static::hasValidDealCode($deal->deal_code)) {
                $deal->deal_code = static::generateNextDealCode();
            }

            // Ensure deal_name is populated
            if (empty($deal->deal_name)) {
                $deal->deal_name = $deal->deal_title ?: ($deal->deal_code ?: 'New Deal');
            }
            if (empty($deal->deal_title)) {
                $deal->deal_title = $deal->deal_name ?: ($deal->deal_code ?: 'New Deal');
            }

            // Synchronize stage
            if (! $deal->stage) {
                $deal->stage = 'Inquiry / Consultation';
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

            $currentStage = $deal->stage ?: 'Inquiry';

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

        static::saving(function (Deal $deal): void {
            if ($deal->exists && \Illuminate\Support\Facades\Schema::hasTable('deal_proposals')
                && ((($deal->isDirty('pipeline_stage') || $deal->isDirty('stage'))
                    && (in_array($deal->pipeline_stage, ['Activation', 'Closed Won'], true) || in_array($deal->stage, ['Activation', 'Closed Won'], true)))
                    || ($deal->isDirty('activation_date') && filled($deal->activation_date)))) {
                app(\App\Services\DealPaymentEligibility::class)->assertActivationAllowed($deal);
            }
            if (!empty($deal->pipeline_stage) && empty($deal->stage)) {
                $deal->stage = $deal->pipeline_stage;
            } elseif (!empty($deal->stage) && empty($deal->pipeline_stage)) {
                $deal->pipeline_stage = $deal->stage;
            }

            // Map alias attributes to physical SQLite columns
            $columnMap = [
                'client_requirements' => 'requirements_status',
                'est_government_fee' => 'estimated_government_fees',
                'est_service_support_fee' => 'estimated_service_support_fee',
                'estimated_duration_days' => 'estimated_duration',
                'professional_support_required' => 'support_required',
                'service_department' => 'service_department_unit',
                'approval_name' => 'approved_by_name',
            ];

            foreach ($columnMap as $alias => $real) {
                if (array_key_exists($alias, $deal->attributes)) {
                    if (!array_key_exists($real, $deal->attributes) || empty($deal->attributes[$real])) {
                        $deal->attributes[$real] = $deal->attributes[$alias];
                    }
                    unset($deal->attributes[$alias]);
                }
            }

            // Clean up attributes that have no column in the deals table
            $ignoreAttrs = ['services_pricing_guide', 'products_pricing_guide'];
            foreach ($ignoreAttrs as $attr) {
                if (array_key_exists($attr, $deal->attributes)) {
                    unset($deal->attributes[$attr]);
                }
            }
        });
    }

    public function getPipelineStageAttribute(): string
    {
        $val = $this->attributes['pipeline_stage'] ?? ($this->attributes['stage'] ?? 'Inquiry');
        return match (trim((string) $val)) {
            'Inquiry / Consultation', 'Inquiry', 'Intake' => 'Inquiry',
            'Qualification' => 'Qualification',
            'Consultation' => 'Consultation',
            'Solutioning / Proposal', 'Proposal' => 'Proposal',
            'Negotiation / Finalizing', 'Negotiation' => 'Negotiation',
            'Payment' => 'Payment',
            'Activated', 'Activation' => 'Activation',
            'Closed Won', 'approved' => 'Closed Won',
            'Closed Lost', 'rejected' => 'Closed Lost',
            default => (string) $val ?: 'Inquiry',
        };
    }

    public static function generateNextDealCode(
        ?int $year = null,
        ?int $ignoreDealId = null
    ): string {
        $year ??= (int) now()->format('Y');
        $prefix = sprintf('CONDEAL-%d-', $year);

        $nextNumber = DB::transaction(function () use ($prefix, $ignoreDealId): int {
            $codes = static::query()
                ->when($ignoreDealId, fn (Builder $query) => $query->whereKeyNot($ignoreDealId))
                ->where('deal_code', 'like', $prefix . '%')
                ->whereNotNull('deal_code')
                ->lockForUpdate()
                ->pluck('deal_code');

            $max = 0;
            foreach ($codes as $code) {
                if (preg_match('/^CONDEAL-\d{4}-(\d+)$/', (string) $code, $matches)) {
                    $num = (int) $matches[1];
                    if ($num > $max) {
                        $max = $num;
                    }
                }
            }

            return $max + 1;
        });

        return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
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
        return $this->hasOne(DealProposal::class)
            ->where('is_current', true)
            ->orderByDesc('proposal_version');
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
        $batches = $this->start_batches ?? [];
        if (is_string($batches)) {
            $batches = json_decode($batches, true) ?: [];
        }
        
        return collect($batches)->map(function ($batch) {
            $b = is_array($batch) ? (object) $batch : $batch;
            if (isset($b->created_at) && is_string($b->created_at)) {
                try {
                    $b->created_at = \Carbon\Carbon::parse($b->created_at);
                } catch (\Throwable $e) {}
            }
            if (isset($b->assignments) && (is_array($b->assignments) || $b->assignments instanceof \Illuminate\Support\Collection)) {
                $b->assignments = collect($b->assignments)->map(function ($a) {
                    $obj = is_array($a) ? (object) $a : $a;
                    if (isset($obj->acknowledged_at) && is_string($obj->acknowledged_at)) {
                        try {
                            $obj->acknowledged_at = \Carbon\Carbon::parse($obj->acknowledged_at);
                        } catch (\Throwable $e) {}
                    }
                    return $obj;
                });
            } else {
                $b->assignments = collect([]);
            }
            return $b;
        });
    }

    public function getClientActionRequestsAttribute()
    {
        return collect([]);
    }

    /**
     * Determine if the START workflow has been completed and activated for this deal.
     */
    public function isStartCompleted(): bool
    {
        // 1. Check progressive START activation batches
        $batches = $this->start_batches ?? [];
        if (is_string($batches)) {
            $batches = json_decode($batches, true) ?: [];
        }

        $hasAnyBatch = !empty($batches);

        if ($hasAnyBatch) {
            $activeBatches = array_filter($batches, function ($batch) {
                $status = strtolower((string) (is_array($batch) ? ($batch['status'] ?? '') : ($batch->status ?? '')));
                return !in_array($status, ['cancelled', 'returned'], true)
                    && app(\App\Services\DealPaymentEligibility::class)->batchEligible($this, (array) (((array) $batch)['activated_items'] ?? []));
            });

            if (empty($activeBatches)) {
                return false;
            }

            // Every active START batch must have its own issued memo record and PDF.
            foreach ($activeBatches as $batch) {
                $bArr = (array) $batch;
                if (! app(\App\Services\DealPaymentEligibility::class)->batchEligible($this, (array) ($bArr['activated_items'] ?? []))) {
                    return false;
                }
                $memoRef = $bArr['service_memo_ref'] ?? null;
                $memoData = $bArr['service_memo_data'] ?? null;
                $pdfPath = $bArr['service_memo_pdf_path'] ?? null;
                if (empty($memoRef) || empty($memoData) || empty($pdfPath) || !$this->isDownloadableServiceMemo((string) $pdfPath)) {
                    return false;
                }

                // All required assignees across active batches must explicitly accept their assignment.
                $assignments = $bArr['assignments'] ?? [];
                if (empty($assignments) || !is_array($assignments)) {
                    return false;
                }

                $hasRequired = false;
                foreach ($assignments as $asgn) {
                    $asgnArr = (array) $asgn;
                    $isRequired = !isset($asgnArr['is_required']) || (bool) $asgnArr['is_required'];
                    if (!$isRequired) {
                        continue;
                    }

                    $hasRequired = true;
                    $assignee = trim((string) ($asgnArr['assignee'] ?? ($asgnArr['assigned_to'] ?? '')));
                    if ($assignee === '' || strcasecmp($assignee, 'unassigned') === 0) {
                        return false;
                    }

                    $status = strtolower(trim((string) ($asgnArr['status'] ?? '')));
                    $response = strtolower(trim((string) ($asgnArr['response'] ?? '')));
                    $acknowledgedAt = $asgnArr['acknowledged_at'] ?? null;

                    $isAccepted = in_array($status, ['acknowledged', 'accepted', 'completed'], true)
                        && $response === 'accepted'
                        && filled($acknowledgedAt);

                    if (!$isAccepted) {
                        return false;
                    }
                }

                if (!$hasRequired) {
                    return false;
                }
            }

            // Both conditions satisfied!
            return true;
        }

        // 2. Check Project records & ProjectStart forms created from this deal
        if ($this->relationLoaded('projects') && $this->projects->isNotEmpty()) {
            foreach ($this->projects as $proj) {
                if ($this->isProjectRecordStartCompleted($proj)) {
                    return true;
                }
            }
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('project_starts')) {
            $starts = \App\Models\ProjectStart::query()
                ->whereIn('project_id', function ($query) {
                    $query->select('id')->from('projects')->where('deal_id', $this->id);
                })
                ->whereIn('status', ['approved', 'completed', 'active'])
                ->get();

            foreach ($starts as $start) {
                if ($this->isProjectStartRecordCompleted($start)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isDownloadableServiceMemo(string $path): bool
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($path) || $disk->size($path) < 5) {
                return false;
            }

            $stream = $disk->readStream($path);
            if (!is_resource($stream)) {
                return false;
            }

            $signature = fread($stream, 5);
            fclose($stream);

            return $signature === '%PDF-';
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function getStartOutstandingRequirements(): array
    {
        if ($this->isStartCompleted()) {
            return [];
        }

        $batches = $this->start_batches ?? [];
        if (is_string($batches)) {
            $batches = json_decode($batches, true) ?: [];
        }

        $outstanding = [];
        foreach ((array) $batches as $batch) {
            $batch = (array) $batch;
            $batchStatus = strtolower((string) ($batch['status'] ?? ''));
            if (in_array($batchStatus, ['cancelled', 'returned'], true)) {
                continue;
            }

            $batchLabel = (string) ($batch['start_code'] ?? $batch['batch_name'] ?? 'START batch');
            foreach ((array) ($batch['assignments'] ?? []) as $assignment) {
                $assignment = (array) $assignment;
                if (isset($assignment['is_required']) && !(bool) $assignment['is_required']) {
                    continue;
                }
                $role = trim((string) ($assignment['role'] ?? 'Required assignee'));
                $assignee = trim((string) ($assignment['assignee'] ?? $assignment['assigned_to'] ?? 'Unassigned'));
                $accepted = in_array(strtolower(trim((string) ($assignment['status'] ?? ''))), ['acknowledged', 'accepted', 'completed'], true)
                    && strtolower(trim((string) ($assignment['response'] ?? ''))) === 'accepted'
                    && filled($assignment['acknowledged_at'] ?? null)
                    && $assignee !== ''
                    && strcasecmp($assignee, 'unassigned') !== 0;
                if (!$accepted) {
                    $outstanding[] = "{$role} — {$assignee} must accept ({$batchLabel})";
                }
            }

            $memoRef = $batch['service_memo_ref'] ?? null;
            $memoData = $batch['service_memo_data'] ?? null;
            $pdfPath = $batch['service_memo_pdf_path'] ?? null;
            if (empty($memoRef) || empty($memoData) || empty($pdfPath) || !$this->isDownloadableServiceMemo((string) $pdfPath)) {
                $outstanding[] = "Service Memo for {$batchLabel} must be generated and downloadable";
            }
        }

        if ($outstanding === []) {
            $outstanding[] = 'Complete the required START approval and Service Memo verification.';
        }

        return array_values(array_unique($outstanding));
    }

    /**
     * Check if a Project record's START workflow is completed and service memo downloadable.
     */
    public function isProjectRecordStartCompleted(\App\Models\Project $project): bool
    {
        $starts = $project->starts()->whereIn('status', ['approved', 'completed', 'active'])->get();
        foreach ($starts as $start) {
            if ($this->isProjectStartRecordCompleted($start)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a ProjectStart record is approved and has a downloadable service memo file on disk.
     */
    public function isProjectStartRecordCompleted(\App\Models\ProjectStart $start): bool
    {
        $status = strtolower((string) $start->status);
        if (!in_array($status, ['approved', 'completed', 'active'], true) || empty($start->approved_at)) {
            return false;
        }

        if (is_array($this->proposal?->line_items_snapshot)) {
            try {
                app(\App\Services\DealPaymentEligibility::class)->assertActivationAllowed($this);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                return false;
            }
        }
        $attachments = (array) ($start->attachments ?? []);
        $pdfPath = (string) ($attachments['service_memo_pdf_path'] ?? '');
        if ($pdfPath === '' || !$this->isDownloadableServiceMemo($pdfPath)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if this deal entails a project engagement.
     */
    public function hasProjectEngagement(): bool
    {
        $raw = strtolower(trim((string) ($this->engagement_type ?? '')));
        if (empty($raw) && $this->project) {
            $raw = strtolower(trim((string) ($this->project->engagement_type ?? '')));
        }
        if (empty($raw)) {
            return false;
        }
        return str_contains($raw, 'project') || str_contains($raw, 'hybrid');
    }

    /**
     * Determine if this deal entails a regular/retainer engagement.
     */
    public function hasRegularEngagement(): bool
    {
        $raw = strtolower(trim((string) ($this->engagement_type ?? '')));
        if (empty($raw) && $this->regularProject) {
            $raw = strtolower(trim((string) ($this->regularProject->engagement_type ?? '')));
        }
        if (empty($raw)) {
            return false;
        }
        return str_contains($raw, 'regular') || str_contains($raw, 'retainer') || str_contains($raw, 'hybrid');
    }

    /**
     * Determine if this deal is a hybrid engagement (both Project and Regular).
     */
    public function isHybridEngagement(): bool
    {
        $raw = strtolower(trim((string) ($this->engagement_type ?? '')));
        return str_contains($raw, 'hybrid') || ($this->hasProjectEngagement() && $this->hasRegularEngagement() && $raw !== 'project' && !str_contains($raw, 'regular'));
    }

    /** Whether the configured engagement workspace(s) have reached their final state. */
    public function areEngagementWorkspacesComplete(): bool
    {
        if (! $this->isStartCompleted()) {
            return false;
        }

        $workspaces = $this->projects()->get();
        $needsProject = $this->hasProjectEngagement();
        $needsRegular = $this->hasRegularEngagement();
        if (! $needsProject && ! $needsRegular) {
            return false;
        }

        $projectWorkspace = $workspaces->first(fn ($workspace) => ! $workspace->isRegular());
        $regularWorkspace = $workspaces->first(fn ($workspace) => $workspace->isRegular());

        if ($needsProject && (! $projectWorkspace || (strcasecmp((string) $projectWorkspace->status, 'Completed') !== 0
            && strcasecmp((string) data_get($projectWorkspace->metadata ?? [], 'coc.approval_status'), 'approved') !== 0))) {
            return false;
        }
        if ($needsRegular && (! $regularWorkspace || strcasecmp((string) $regularWorkspace->status, 'Completed') !== 0)) {
            return false;
        }

        return true;
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

        if ($this->relationLoaded('stageHistories')) {
            $active = $this->stageHistories->whereNull('ended_at')->sortByDesc('started_at')->first();
        } else {
            $active = $this->stageHistories()->whereNull('ended_at')->latest('started_at')->first();
        }
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
            $this->assigned_user_id ? (string) $this->assigned_user_id : null,
            $this->responsible_user_id ? (string) $this->responsible_user_id : null,
            $this->reviewer_id ? (string) $this->reviewer_id : null,
            $this->approver_id ? (string) $this->approver_id : null,
            $this->assignedUser?->name,
            $this->assignedUser?->email,
            $this->responsibleUser?->name,
            $this->responsibleUser?->email,
            $this->reviewer?->name,
            $this->reviewer?->email,
            $this->approver?->name,
            $this->approver?->email,
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function getQualificationResultAttribute($value): ?string
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
        $currentStage = $this->attributes['pipeline_stage'] ?? 'Inquiry';
        $currentIdx = array_search($currentStage, $allStages, true);
        if ($currentIdx === false) {
            $currentIdx = 0;
        }

        if (filled($value)) {
            $normalized = strtolower(str_replace(['-', ' '], '_', trim((string) $value)));
            if (in_array($normalized, ['not_started', 'pending', 'in_progress'], true) && $currentIdx >= 2) {
                return 'Qualified';
            }
            return ucfirst(str_replace('_', ' ', $value));
        }

        if ($currentIdx >= 2) {
            return 'Qualified';
        }

        return null;
    }

    public function setQualificationResultAttribute($value): void
    {
        $this->attributes['qualification_result'] = filled($value) ? strtolower(str_replace(' ', '_', (string) $value)) : null;
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(DealProposal::class, 'deal_id');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function companyRecord(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getResolvedQualificationResult(): string
    {
        $raw = $this->attributes['qualification_result'] ?? null;
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

        if (filled($raw)) {
            $normalized = strtolower(str_replace(['-', ' '], '_', trim((string) $raw)));
            if (in_array($normalized, ['not_started', 'pending', 'in_progress'], true) && $currentIdx >= 2) {
                return 'Qualified';
            }

            return match ($normalized) {
                'qualified' => 'Qualified',
                'not_qualified', 'unqualified' => 'Not Qualified',
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'not_started' => 'Not Started',
                default => ucwords(str_replace('_', ' ', $normalized)),
            };
        }

        if ($currentStage === 'Inquiry') {
            return 'Not Started';
        }

        if ($currentStage === 'Qualification') {
            return 'Pending';
        }

        if ($currentIdx >= 2) {
            return 'Qualified';
        }

        return 'Not Started';
    }

    public function getResolvedQualificationNotes(): string
    {
        if (filled($this->qualification_notes)) {
            return $this->qualification_notes;
        }

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

        if ($currentIdx >= 2) {
            return 'Client qualification verified.';
        }

        return '—';
    }

    public function getResolvedContactPosition(): string
    {
        if (filled($this->position)) {
            return $this->position;
        }

        $contact = $this->contact ?: $this->primaryContact;
        if ($contact && filled($contact->position)) {
            return $contact->position;
        }

        if ($contact && filled($contact->job_title)) {
            return $contact->job_title;
        }

        $primary = $this->dealContacts->firstWhere('is_primary', true) ?: $this->dealContacts->first();
        if ($primary && filled($primary->position)) {
            return $primary->position;
        }

        return 'Not provided';
    }

    public function getResolvedContactNumber(): string
    {
        $raw = null;
        if (filled($this->mobile_number)) {
            $raw = $this->mobile_number;
        } elseif (filled($this->mobile)) {
            $raw = $this->mobile;
        } else {
            $contact = $this->contact ?: $this->primaryContact;
            if ($contact && filled($contact->phone)) {
                $raw = $contact->phone;
            } elseif ($contact && filled($contact->phone_number)) {
                $raw = $contact->phone_number;
            } else {
                $primary = $this->dealContacts->firstWhere('is_primary', true) ?: $this->dealContacts->first();
                if ($primary && filled($primary->mobile_number)) {
                    $raw = $primary->mobile_number;
                }
            }
        }

        if (filled($raw)) {
            $formatted = \App\Services\PhoneFormatter::format($raw);
            return filled($formatted) ? $formatted : $raw;
        }

        return 'Not provided';
    }

    public function setMobileAttribute($value): void
    {
        $this->attributes['mobile'] = filled($value) ? (\App\Services\PhoneFormatter::normalize($value) ?? $value) : null;
    }

    public function setMobileNumberAttribute($value): void
    {
        $this->attributes['mobile_number'] = filled($value) ? (\App\Services\PhoneFormatter::normalize($value) ?? $value) : null;
    }

    public function getMobileAttribute($value): ?string
    {
        return filled($value) ? (\App\Services\PhoneFormatter::format($value) ?? $value) : null;
    }

    public function getMobileNumberAttribute($value): ?string
    {
        return filled($value) ? (\App\Services\PhoneFormatter::format($value) ?? $value) : null;
    }

    public function getResolvedIndustry(): string
    {
        if ($this->customer_type === 'Individual') {
            return 'Not applicable';
        }

        if ($this->company_id && \Illuminate\Support\Facades\Schema::hasTable('company_bifs')) {
            $bif = \App\Models\CompanyBif::where('company_id', $this->company_id)->latest('updated_at')->first();
            if ($bif) {
                $industries = [];
                if ($bif->industry_services) $industries[] = 'Services';
                if ($bif->industry_financial_services) $industries[] = 'Financial Services';
                if ($bif->industry_manufacturing) $industries[] = 'Manufacturing';
                if ($bif->industry_wholesale_retail_trade) $industries[] = 'Wholesale & Retail Trade';
                if ($bif->industry_distribution) $industries[] = 'Distribution';
                if ($bif->industry_transportation) $industries[] = 'Transportation';
                if ($bif->industry_export_import) $industries[] = 'Export & Import';
                if ($bif->industry_education) $industries[] = 'Education';
                if ($bif->industry_government) $industries[] = 'Government';
                if ($bif->industry_other && filled($bif->industry_other_text)) $industries[] = $bif->industry_other_text;
                if (!empty($industries)) {
                    return implode(', ', $industries);
                }
            }
        }

        $comp = $this->companyRecord;
        if ($comp && filled($comp->description)) {
            return $comp->description;
        }

        $contact = $this->contact ?: $this->primaryContact;
        if ($contact && filled($contact->nature_of_business)) {
            return $contact->nature_of_business;
        }

        return 'Not provided';
    }

    /*
    |--------------------------------------------------------------------------
    | DYNAMIC STATUS RESOLVERS (KYC, Payment, START, Proposal, Commercial, CASA)
    |--------------------------------------------------------------------------
    */

    public function getResolvedKycStatus(): string
    {
        $hasKycRecord = false;
        $allKycRecordsVerified = true;
        $hasVerifiedKycRecord = false;

        if ($this->company_id && \Illuminate\Support\Facades\Schema::hasTable('company_bifs')) {
            $bif = \App\Models\CompanyBif::query()
                ->where('company_id', $this->company_id)
                ->latest('updated_at')
                ->first();

            if ($bif) {
                $hasKycRecord = true;
                $verified = strtolower((string) $bif->status) === 'approved';
                $allKycRecordsVerified = $allKycRecordsVerified && $verified;
                $hasVerifiedKycRecord = $hasVerifiedKycRecord || $verified;
            }
        }

        $contact = $this->contact ?: $this->primaryContact;
        if ($contact) {
            $cifStatus = strtolower(trim((string) ($contact->cif_status ?? '')));
            $kycStatus = strtolower(trim((string) ($contact->kyc_status ?? '')));
            if ($cifStatus !== '' || $kycStatus !== '') {
                $hasKycRecord = true;
                $verified = in_array($cifStatus, ['approved', 'verified', 'complete', 'completed'], true)
                    && ($kycStatus === '' || in_array($kycStatus, ['verified', 'complete', 'completed', 'approved'], true));
                $allKycRecordsVerified = $allKycRecordsVerified && $verified;
                $hasVerifiedKycRecord = $hasVerifiedKycRecord || $verified;
            }
        }

        $dealKycStatus = strtolower(trim((string) ($this->kyc_status ?? '')));
        if ($dealKycStatus !== '') {
            $hasKycRecord = true;
            $verified = in_array($dealKycStatus, ['verified', 'complete', 'completed', 'approved'], true);
            $allKycRecordsVerified = $allKycRecordsVerified && $verified;
            $hasVerifiedKycRecord = $hasVerifiedKycRecord || $verified;
        }

        return $hasKycRecord && $allKycRecordsVerified && $hasVerifiedKycRecord
            ? 'Complete'
            : 'Incomplete';
    }

    public function getResolvedPaymentStatus(): string
    {
        if (!empty($this->payment_status)) {
            $payStatus = trim((string) $this->payment_status);
            return match (strtolower($payStatus)) {
                'completed', 'paid', 'verified', 'verified / confirmed' => 'Completed',
                'partial', 'partially_paid', 'partially paid' => 'Partial',
                'pending', 'unpaid', 'awaiting_payment' => 'Pending',
                default => ucfirst($payStatus),
            };
        }

        if (filled($this->payment_reference) || filled($this->payment_date)) {
            return 'Completed';
        }

        $currentStage = $this->pipeline_stage ?: 'Inquiry';

        if ($currentStage === 'Payment') {
            return 'Pending';
        }

        return 'Not Started';
    }

    public function getResolvedProposalStatus(): string
    {
        $proposal = $this->proposal;
        if ($proposal?->client_decision_status) {
            return \App\Services\DealProposalDecisions::LABELS[$proposal->client_decision_status] ?? 'Pending Client Review';
        }
        $proposalStatus = strtolower(trim((string) ($proposal?->status ?? '')));
        $dealProposalStatus = strtolower(trim((string) ($this->proposal_status ?? '')));
        $decision = strtolower(trim((string) ($this->proposal_decision ?? '')));
        $hasApprovedTimestamp = filled($proposal?->client_approved_at) || filled($this->approval_date) || filled($this->approved_at);
        $hasActiveStart = (!empty($this->start_batches) && count((array)$this->start_batches) > 0) || $this->isStartCompleted();

        // 1. Check Approved state first
        if (in_array($proposalStatus, ['approved', 'accepted', 'signed'], true)
            || in_array($decision, ['approved', 'accepted', 'signed'], true)
            || in_array($dealProposalStatus, ['approved', 'accepted', 'signed'], true)
            || $hasApprovedTimestamp
            || ($hasActiveStart && !in_array($decision, ['declined', 'rejected'], true))) {
            return 'Approved';
        }

        // 2. Check Declined / Rejected state
        if (in_array($proposalStatus, ['declined', 'rejected'], true)
            || in_array($decision, ['declined', 'rejected'], true)
            || in_array($dealProposalStatus, ['declined', 'rejected'], true)) {
            return 'Declined';
        }

        // 3. Revision Requested
        if (in_array($decision, ['revision requested', 'revision_requested', 'revision'], true)
            || in_array($proposalStatus, ['revision requested', 'revision_requested', 'revision'], true)) {
            return 'Revision Requested';
        }

        // 4. Client Review state
        if (in_array($proposalStatus, ['sent', 'under_review', 'under_client_review', 'awaiting_decision', 'delivered'], true)
            || in_array($decision, ['sent', 'under_review', 'under_client_review'], true)
            || in_array($dealProposalStatus, ['sent', 'under_review', 'under_client_review', 'awaiting_decision', 'delivered'], true)) {
            return 'Under Client Review';
        }

        if ($proposalStatus === 'in_review') {
            return 'In Review';
        }

        if (in_array($proposalStatus, ['draft', 'saved'], true) || in_array($dealProposalStatus, ['draft', 'saved'], true)) {
            return 'Draft';
        }

        return filled($this->proposal_status) ? ucfirst((string) $this->proposal_status) : 'Not Started';
    }

    public function getResolvedStartStatus(): string
    {
        if ($this->isStartCompleted()) {
            return 'Complete';
        }

        $batches = $this->start_batches ?? [];
        if (is_string($batches)) {
            $batches = json_decode($batches, true) ?: [];
        }

        // 1. A batch means START has been initiated, but not necessarily completed.
        if (!empty($batches)) {
            return 'Ongoing';
        }

        // 2. Check Project records & ProjectStart forms
        if (\Illuminate\Support\Facades\Schema::hasTable('project_starts')
            && \Illuminate\Support\Facades\Schema::hasTable('projects')) {
            $hasActiveStart = \App\Models\ProjectStart::query()
                ->whereIn('project_id', function ($query): void {
                    $query->select('id')->from('projects')->where('deal_id', $this->id);
                })
                ->exists();

            if ($hasActiveStart) {
                return 'Ongoing';
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('projects')) {
            $activeProjectExists = \App\Models\Project::query()
                ->where('deal_id', $this->id)
                ->where('status', 'not like', '%shell%')
                ->whereIn('status', ['NTP', 'IN_PROGRESS', 'EXECUTION', 'COMPLETED'])
                ->exists();
            if ($activeProjectExists) {
                return 'Ongoing';
            }
        }

        return 'Not Ready';
    }

    /**
     * Resolve the team-confirmation stage from assignment responses, repairing stale display states such as Ready.
     */
    public function resolveStartBatchTeamStatus(array $batch, ?bool $confirmationSent = null): string
    {
        $currentStatus = trim((string) ($batch['status'] ?? 'Draft'));
        $teamWorkflowStatuses = [
            'Draft', 'Structuring', 'Ready', 'For Team Confirmation',
            'Partially Acknowledged', 'Needs Reassignment', 'Team Confirmed',
        ];
        if (!in_array($currentStatus, $teamWorkflowStatuses, true)) {
            return $currentStatus;
        }

        $confirmationSent ??= in_array($currentStatus, [
            'For Team Confirmation', 'Partially Acknowledged', 'Needs Reassignment', 'Team Confirmed', 'Ready',
        ], true);

        $requiredAssignments = array_values(array_filter(
            (array) ($batch['assignments'] ?? []),
            fn ($assignment) => !array_key_exists('is_required', (array) $assignment)
                || (bool) ((array) $assignment)['is_required']
        ));
        if ($requiredAssignments === []) {
            return $confirmationSent ? 'For Team Confirmation' : $currentStatus;
        }

        $hasDeclined = false;
        $acceptedCount = 0;
        foreach ($requiredAssignments as $assignment) {
            $assignment = (array) $assignment;
            $status = strtolower(trim((string) ($assignment['status'] ?? '')));
            $response = strtolower(trim((string) ($assignment['response'] ?? '')));
            if (in_array($status, ['declined', 'cannot accept'], true) || $response === 'declined') {
                $hasDeclined = true;
                continue;
            }

            $assignee = trim((string) ($assignment['assignee'] ?? $assignment['assigned_to'] ?? ''));
            $accepted = in_array($status, ['acknowledged', 'accepted', 'completed'], true)
                && $response === 'accepted'
                && filled($assignment['acknowledged_at'] ?? null)
                && $assignee !== ''
                && strcasecmp($assignee, 'unassigned') !== 0;
            if ($accepted) {
                $acceptedCount++;
            }
        }

        if ($hasDeclined) {
            return 'Needs Reassignment';
        }
        if ($acceptedCount === count($requiredAssignments)) {
            return 'Team Confirmed';
        }
        if ($acceptedCount > 0) {
            return 'Partially Acknowledged';
        }

        return $confirmationSent ? 'For Team Confirmation' : $currentStatus;
    }

    public function hasAllRequiredStartAssignmentsAccepted(array $batch): bool
    {
        $requiredAssignments = array_values(array_filter(
            (array) ($batch['assignments'] ?? []),
            fn ($assignment) => !array_key_exists('is_required', (array) $assignment)
                || (bool) ((array) $assignment)['is_required']
        ));
        if ($requiredAssignments === []) {
            return false;
        }

        foreach ($requiredAssignments as $assignment) {
            $assignment = (array) $assignment;
            $assignee = trim((string) ($assignment['assignee'] ?? $assignment['assigned_to'] ?? ''));
            if (!in_array(strtolower(trim((string) ($assignment['status'] ?? ''))), ['acknowledged', 'accepted', 'completed'], true)
                || strtolower(trim((string) ($assignment['response'] ?? ''))) !== 'accepted'
                || !filled($assignment['acknowledged_at'] ?? null)
                || $assignee === ''
                || strcasecmp($assignee, 'unassigned') === 0) {
                return false;
            }
        }

        return true;
    }

    public function getResolvedCommercialStatus(): string
    {
        $stage = $this->commercial_stage ?: ($this->pipeline_stage ?: ($this->stage ?: 'Inquiry'));

        return match (strtolower(trim((string) $stage))) {
            'closed_won', 'closed won' => 'Closed Won',
            'closed_lost', 'closed lost' => 'Closed Lost',
            'activation', 'activated' => 'Activation',
            'under_client_review' => 'Under Client Review',
            default => ucwords(str_replace('_', ' ', (string) $stage)),
        };
    }

    public function getResolvedCasaStatus(): string
    {
        $casaStage = strtolower(trim((string) ($this->casa_stage ?? '')));
        $pipelineStage = strtolower(trim((string) ($this->pipeline_stage ?: $this->stage ?: '')));

        // 1. If explicitly marked completed/activated or actual activation completed
        if (in_array($casaStage, ['active', 'activated', 'completed', 'complete'], true)) {
            return 'Activated';
        }

        if ($this->isStartCompleted()) {
            return 'Activated';
        }

        // 2. If CASA is currently undergoing activation:
        // Current workflow stage is Activation, or casa_stage is activation/ongoing/in_progress,
        // or START batch has been initiated
        if (in_array($casaStage, ['activation', 'in_progress', 'ongoing', 'undergoing_activation'], true)
            || in_array($pipelineStage, ['activation', 'closed won', 'closed_won'], true)
            || (!empty($this->start_batches) && count((array)$this->start_batches) > 0)) {
            return 'Activation';
        }

        // 3. If casa_stage has another specific value
        if ($casaStage !== '') {
            return match ($casaStage) {
                'pending', 'pending_activation', 'pending activation' => 'Pending',
                default => ucwords(str_replace('_', ' ', (string) $this->casa_stage)),
            };
        }

        return 'Pending';
    }
}
