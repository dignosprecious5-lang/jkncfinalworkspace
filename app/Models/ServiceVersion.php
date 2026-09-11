<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'status'                   => 'draft',
        'is_active'                => true,
        'currency'                 => 'PHP',
        'pricing_model'            => 'Fixed Fee',
        'tax_treatment'            => 'VAT Exclusive',
        'payment_structure'        => 'Full Advance',
        'instantiation_mode'       => 'automatic',
        'auto_carryover'           => true,
        'instantiation_lead_days'  => 15,
        'internal_reminder_days'   => 3,
        'escalation_threshold_days'=> 2,
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

    // =======================================================
    // DYNAMIC COMPLETENESS CHECKERS FOR UI & APPROVAL GATE
    // =======================================================

    /**
     * Check if Overview Tab mandatory fields are filled.
     */
    public function isOverviewComplete(): bool
    {
        return !empty(trim($this->internal_description ?? ''));
    }

    /**
     * Check if Proposal Content Tab mandatory fields are filled.
     */
    public function isProposalComplete(): bool
    {
        return !empty(trim($this->scope_of_work ?? ''));
    }

    /**
     * Check if at least one requirement is attached.
     */
    public function isRequirementsComplete(): bool
    {
        return $this->requirements()->count() > 0;
    }

    /**
     * Check if at least one workflow main activity exists.
     */
    public function isWorkflowComplete(): bool
    {
        return $this->mainActivities()->count() > 0;
    }

    /**
     * Check if commercial pricing is properly set.
     */
    public function isCommercialsComplete(): bool
    {
        return (float) $this->standard_price > 0;
    }

    /**
     * Overall Gate Check: Returns true only if all mandatory sections are satisfied.
     */
    public function isFullyComplete(): bool
    {
        return $this->isOverviewComplete() 
            && $this->isProposalComplete() 
            && $this->isRequirementsComplete() 
            && $this->isWorkflowComplete() 
            && $this->isCommercialsComplete();
    }
}