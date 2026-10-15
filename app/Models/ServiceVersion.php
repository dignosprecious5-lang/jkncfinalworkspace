<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ServiceVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Updated to include all workspace form fields across all tabs.
     */
    protected $fillable = [
        'service_id',
        'version_number',
        'standard_price',
        'expected_hours',
        'is_active',
        'status',
        'created_by',
        'approved_by',

        // (1) Overview Tab Fields
        'short_name',
        'expected_turnaround',
        'effective_date',
        'internal_description',
        'client_description',
        'about_service',
        'purpose',
        'when_to_use',
        'what_it_is_not',

        // (2) Proposal Content Tab Fields
        'scope_of_work',
        'deliverables',
        'client_responsibilities',
        'exclusions',

        // (5) Commercials & Costing Fields
        'pricing_model',
        'currency',
        'unit_rate',
        'tax_treatment',
        'min_price',
        'min_cap',
        'discount_allowed',
        'max_discount',
        'cost_of_service',
        'payment_structure',
        'payment_notes',
        'reimbursables',

        // (6) Engagement & Recurrence Fields
        'instantiation_mode',
        'recurrence_frequency',
        'billing_frequency',
        'reporting_frequency',
        'instantiation_lead_days',
        'auto_carryover',

        // (7) Reporting Fields
        'project_reporting',
        'report_content',

        // (8) Automation Rules Fields
        'base_reference_date',
        'default_assignment_rule',
        'internal_reminder_days',
        'client_reminder_cadence',
        'notification_channel',
        'escalation_threshold_days',
        'escalation_target_role',
        'requirement_gate_rule',
    ];

    /**
     * Attribute type casting.
     */
    protected $casts = [
        'is_active'                 => 'boolean',
        'auto_carryover'            => 'boolean',
        'effective_date'            => 'date',
        'standard_price'            => 'decimal:2',
        'unit_rate'                 => 'decimal:2',
        'min_price'                 => 'decimal:2',
        'min_cap'                   => 'decimal:2',
        'max_discount'              => 'decimal:2',
        'cost_of_service'           => 'decimal:2',
        'reimbursables'             => 'array',
        'report_content'            => 'array',
        'instantiation_lead_days'   => 'integer',
        'internal_reminder_days'    => 'integer',
        'escalation_threshold_days' => 'integer',
        'expected_hours'            => 'integer',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'status'                    => 'draft',
        'is_active'                 => true,
        'currency'                  => 'PHP',
        'pricing_model'             => 'Fixed Fee',
        'tax_treatment'             => 'VAT Exclusive',
        'payment_structure'         => 'Full Advance',
        'instantiation_mode'        => 'automatic',
        'auto_carryover'            => true,
        'instantiation_lead_days'   => 15,
        'internal_reminder_days'    => 3,
        'escalation_threshold_days' => 2,
    ];

    // =======================================================
    // RELATIONSHIPS
    // =======================================================

    /**
     * Parent Service catalog entry.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * User who created this service version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Manager/User who approved this service version.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Audit logs linked to this version.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

    /**
     * Requirements checklist linked to this version.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ServiceRequirement::class);
    }

    /**
     * Main Activities (top-level workflow steps without parent_id).
     */
    public function mainActivities(): HasMany
    {
        return $this->hasMany(ServiceActivity::class)
                    ->whereNull('parent_id')
                    ->orderBy('sequence');
    }

    /**
     * All Activities (including sub-activities).
     */
    public function allActivities(): HasMany
    {
        return $this->hasMany(ServiceActivity::class)->orderBy('sequence');
    }

    /**
     * Alias for all activities to support automation managers.
     */
    public function serviceActivities(): HasMany
    {
        return $this->hasMany(ServiceActivity::class);
    }

    /**
     * Terms & Agreements linked to this service version.
     */
    public function serviceTerms(): HasMany
    {
        return $this->hasMany(ServiceTerm::class);
    }

    // =======================================================
    // ACCESSORS & COMPUTED ATTRIBUTES
    // =======================================================

    /**
     * Calculate Target Profit Margin Percentage dynamically.
     */
    public function getTargetMarginAttribute(): float
    {
        $price = (float) $this->standard_price;
        $cost  = (float) $this->cost_of_service;

        if ($price <= 0) {
            return 100.0;
        }

        return round((($price - $cost) / $price) * 100, 1);
    }

    /**
     * Resolve terms hierarchically: Global -> Service Area -> Category -> Service Specific
     */
    public function getResolvedTermsAttribute()
    {
        $service = $this->service;
        $serviceAreaId = $service?->service_area_id;
        $category = $service?->category;

        // 1. Fetch Global terms
        $globalTerms = ServiceTermLibrary::where('scope', 'global')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($term) {
                $term->resolved_source = 'GLOBAL';
                return $term;
            });

        // 2. Fetch Service Area terms
        $serviceAreaTerms = collect();
        if ($serviceAreaId) {
            $serviceAreaTerms = ServiceTermLibrary::where('scope', 'service_area')
                ->where('service_area_id', $serviceAreaId)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($term) {
                    $term->resolved_source = 'SERVICE AREA';
                    return $term;
                });
        }

        // 3. Fetch Category terms
        $categoryTerms = collect();
        if ($category) {
            $categoryTerms = ServiceTermLibrary::where('scope', 'category')
                ->where('category', $category)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($term) {
                    $term->resolved_source = 'CATEGORY';
                    return $term;
                });
        }

        // 4. Fetch Direct Service Specific terms
        $serviceSpecificTerms = $this->serviceTerms()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($term) {
                $term->resolved_source = 'SERVICE SPECIFIC';
                return $term;
            });

        // Combine all collections
        $allTerms = $globalTerms->concat($serviceAreaTerms)->concat($categoryTerms)->concat($serviceSpecificTerms);

        // Apply Priority Override Rule (service_specific > category > service_area > global)
        $mapByTitle = [];
        foreach ($allTerms as $term) {
            $t = strtolower(trim($term->title));
            $weight = match($term->resolved_source) {
                'GLOBAL' => 1,
                'SERVICE AREA' => 2,
                'CATEGORY' => 3,
                'SERVICE SPECIFIC' => 4,
                default => 0,
            };

            if (!isset($mapByTitle[$t]) || $weight >= $mapByTitle[$t]['weight']) {
                $mapByTitle[$t] = ['term' => $term, 'weight' => $weight];
            }
        }

        return collect($mapByTitle)->pluck('term')->sortBy(function($term) {
            return match($term->resolved_source) {
                'GLOBAL' => 1,
                'SERVICE AREA' => 2,
                'CATEGORY' => 3,
                'SERVICE SPECIFIC' => 4,
                default => 5,
            };
        })->values();
    }

    // =======================================================
    // DYNAMIC COMPLETENESS CHECKERS FOR UI & APPROVAL GATE
    // =======================================================

    public function isOverviewComplete(): bool
    {
        return !empty(trim($this->internal_description ?? ''));
    }

    public function isProposalComplete(): bool
    {
        return !empty(trim($this->scope_of_work ?? ''));
    }

    /**
     * Check if all mandatory requirements are satisfied.
     */
    public function isRequirementsComplete(): bool
    {
        $requirements = $this->requirements()->get();

        // If the ServiceVersion has no requirement records at all, it is incomplete
        if ($requirements->isEmpty()) {
            return false;
        }

        return $this->getMissingRequirements()->isEmpty();
    }

    /**
     * Get mandatory requirements that are missing or unsatisfied.
     */
    public function getMissingRequirements()
    {
        $requirements = $this->requirements()->get();

        if ($requirements->isEmpty()) {
            return $requirements;
        }

        return $requirements->filter(function ($req) {
            // Non-mandatory requirements do not block the gate
            if (!$req->is_mandatory) {
                return false;
            }

            // If file is required, file_path must be non-empty
            if ($req->file_required && empty($req->file_path)) {
                return true;
            }

            // Status must not be rejected
            if (strcasecmp($req->status ?? '', 'rejected') === 0) {
                return true;
            }

            // Must not have an active rejection reason
            if (!empty($req->rejection_reason)) {
                return true;
            }

            return false;
        });
    }

    public function isWorkflowComplete(): bool
    {
        return $this->mainActivities()->count() > 0;
    }

    public function isCommercialsComplete(): bool
    {
        $pricingModel = $this->pricing_model;
        $unitModels = ['Per Hour', 'Per Day', 'Per Transaction', 'Per Employee', 'Per Branch', 'Per Document', 'Per Filing', 'Per Property', 'Per Session'];

        if (in_array($pricingModel, $unitModels)) {
            return (float) $this->unit_rate > 0;
        }

        return (float) $this->standard_price > 0;
    }

    /**
     * Determine if Reporting configuration has been explicitly completed and saved.
     */
    public function isReportingComplete(): bool
    {
        return !empty(trim($this->project_reporting ?? '')) 
            || !empty($this->report_content);
    }

    /**
     * Determine if Automation configuration has been explicitly completed and saved beyond defaults.
     */
    public function isAutomationComplete(): bool
    {
        return !empty($this->base_reference_date)
            || !empty($this->default_assignment_rule)
            || ($this->internal_reminder_days !== 3)
            || ($this->escalation_threshold_days !== 2)
            || !empty($this->notification_channel);
    }

    public function isFullyComplete(): bool
    {
        return $this->isOverviewComplete()
            && $this->isProposalComplete()
            && $this->isRequirementsComplete()
            && $this->isWorkflowComplete()
            && $this->isCommercialsComplete()
            && $this->isReportingComplete()
            && $this->isAutomationComplete();
    }

    // =======================================================
    // PHASE 3: AUTOMATION, RECURRENCE, REMINDERS & ESCALATION
    // =======================================================

    /**
     * Calculate instantiation target date based on lead days and base reference date.
     */
    public function calculateInstantiationDate(?string $referenceDate = null): ?Carbon
    {
        $base = $referenceDate ? Carbon::parse($referenceDate) : ($this->effective_date ?? now());
        $leadDays = $this->instantiation_lead_days ?? 15;

        return $base->copy()->subDays($leadDays);
    }

    /**
     * Determine if internal reminders are due based on reminder configuration.
     */
    public function getIsInternalReminderDueAttribute(): bool
    {
        if (!$this->effective_date) {
            return false;
        }

        $reminderDays = $this->internal_reminder_days ?? 3;
        return now()->diffInDays($this->effective_date, false) <= $reminderDays;
    }

    /**
     * Determine if escalation is required due to threshold breach.
     */
    public function getIsEscalationDueAttribute(): bool
    {
        if (!$this->effective_date) {
            return false;
        }

        $threshold = $this->escalation_threshold_days ?? 2;
        return now()->greaterThan($this->effective_date->copy()->addDays($threshold));
    }

    /**
     * Hook to downstream integration: Instantiate workflow / tasks for a new Engagement or Project.
     */
    public function instantiateWorkflowForEngagement(Model $engagement): int
    {
        $activities = $this->allActivities;
        $count = 0;

        foreach ($activities as $activity) {
            // Downstream engagement task instantiation logic can hook here
            // e.g. EngagementTask::create([...]);
            $count++;
        }

        return $count;
    }
}