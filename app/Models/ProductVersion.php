<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        // =========================================================
        // PRODUCT RELATIONSHIP
        // =========================================================
        'product_id',

        // =========================================================
        // BASIC PRODUCT INFORMATION
        // =========================================================
        'name',
        'sku',
        'service_area',
        'category',
        'pricing_type',
        'tax_treatment',

        // =========================================================
        // VERSION CONTROL
        // =========================================================
        'version_number',
        'is_active',
        'status',

        // =========================================================
        // VERSION OWNERSHIP / APPROVAL
        // =========================================================
        'created_by',
        'approved_by',

        // =========================================================
        // PRODUCT OVERVIEW
        // =========================================================
        'short_name',
        'expected_turnaround',
        'effective_date',
        'description',
        'internal_description',
        'client_description',
        'purpose',
        'when_to_use',
        'what_product_is_not',

        // =========================================================
        // PRODUCT CATALOG
        // =========================================================
        'scope_of_work',
        'deliverables',
        'client_responsibilities',
        'exclusions',

        // =========================================================
        // INVENTORY SPECIFICATIONS
        // =========================================================
        'inventory_type',
        'inventory_stock',
        'unit_measure',
        'stock_tracking',
        'reorder_level',
        'minimum_stock',
        'maximum_stock',
        'warehouse_location',
        'storage_location',
        'stock_status',

        // =========================================================
        // ENGAGEMENT & REPORTING
        // =========================================================
        'payment_structure',
        'payment_structure_custom',
        'instantiation_execution_mode',
        'recurrence_frequency',
        'recurrence_frequency_custom',
        'billing_frequency',
        'billing_frequency_custom',
        'reporting_frequency',
        'reporting_frequency_custom',
        'auto_carryover',

        // =========================================================
        // REPORTING
        // =========================================================
        'project_reporting_type',
        'report_scope',

        // =========================================================
        // AUTOMATION
        // =========================================================
        'base_reference_date',
        'lead_time_generation',
        'default_auto_assignment_rule',
        'auto_carryover_automation',
        'internal_reminder_trigger',
        'client_followup_cadence',
        'notification_channel',
        'overdue_escalation_threshold',
        'escalation_recipient_role',
        'missing_requirements_gate_rule',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_carryover' => 'boolean',
        'auto_carryover_automation' => 'boolean',
        'effective_date' => 'date',
        'inventory_stock' => 'integer',
        'reorder_level' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'lead_time_generation' => 'integer',
        'internal_reminder_trigger' => 'integer',
        'overdue_escalation_threshold' => 'integer',
        'report_scope' => 'array',
    ];

    protected $attributes = [
        'version_number' => 'V1.0',
        'is_active' => true,
        'status' => 'draft',
        'inventory_stock' => 0,
        'auto_carryover' => false,
        'auto_carryover_automation' => false,
    ];

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    /**
     * Parent Product catalog entry.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who created this Product Version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved this Product Version.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Audit logs associated with this Product Version.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest();
    }

    /**
     * Product Requirements linked to this version.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ProductRequirement::class);
    }

    /**
     * Product Activities linked to this version.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ProductActivity::class)
            ->orderBy('sequence');
    }

    /**
     * Product Terms linked to this version.
     */
    public function productTerms(): HasMany
    {
        return $this->hasMany(ProductTerm::class)
            ->orderBy('sort_order');
    }
}