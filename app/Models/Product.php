<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductTerm;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        // =========================================================
        // PRODUCT BASIC INFORMATION
        // =========================================================
        'name',
        'short_name',
        'sku',
        'service_area',
        'category',
        'pricing_type',
        'tax_treatment',

        // =========================================================
        // INVENTORY BASIC INFORMATION
        // =========================================================
        'inventory_type',
        'inventory_stock',

        // =========================================================
        // PRODUCT OVERVIEW
        // =========================================================
        'description',
        'internal_description',
        'client_description',
        'purpose',
        'when_to_use',
        'what_product_is_not',
        'expected_turnaround',
        'effective_date',

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
        'unit_measure',
        'stock_tracking',
        'reorder_level',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'warehouse_location',
        'storage_location',
        'stock_status',

        // =========================================================
        // STEP 6 — PRODUCT COMMERCIALS
        // =========================================================
        'pricing_model',
        'currency',
        'price',
        'minimum_price',
        'maximum_price',
        'cost_per_unit',
        'expected_margin',
        'discount_allowed',
        'expected_hours',
        'payment_structure',
        'payment_structure_custom',
        'payment_notes',

        // =========================================================
        // STEP 7 — ENGAGEMENT
        // =========================================================
        'instantiation_execution_mode',
        'recurrence_frequency',
        'recurrence_frequency_custom',
        'billing_frequency',
        'billing_frequency_custom',
        'reporting_frequency',
        'reporting_frequency_custom',
        'auto_carryover',

        // =========================================================
        // STEP 8 — REPORTING
        // =========================================================
        'project_reporting_type',
        'report_scope',

        // =========================================================
        // STEP 9 — AUTOMATION
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

        // =========================================================
        // STATUS
        // =========================================================
        'status',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'inventory_stock' => 'integer',
        'reorder_level' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',

        // =========================================================
        // STEP 6 — PRODUCT COMMERCIALS
        // =========================================================
        'minimum_price' => 'decimal:2',
        'maximum_price' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'expected_margin' => 'decimal:2',
        'expected_hours' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::updated(function ($product) {
            if (class_exists(\App\Models\AuditLog::class)) {
                try {
                    \App\Models\AuditLog::log(
                        'Workspace Saved',
                        'Workspace',
                        'Updated information in product workspace.'
                    );
                } catch (\Exception $e) {
                    // Fallback to create if AuditLog::log signature differs
                    \App\Models\AuditLog::create([
                        'product_id' => $product->id,
                        'action'     => 'Workspace Saved',
                        'details'    => 'Updated information in product workspace.',
                        'user_name'  => auth()->user()->name ?? 'System User',
                    ]);
                }
            }
        });
    }

    // =========================================================
    // PRODUCT ACTIVITIES / WORKFLOW
    // =========================================================

    public function activities(): HasMany
    {
        return $this->hasMany(ProductActivity::class)
            ->orderBy('sequence');
    }

    // =========================================================
    // PRODUCT REQUIREMENTS
    // =========================================================

    public function requirements(): HasMany
    {
        return $this->hasMany(ProductRequirement::class)
            ->orderBy('sequence');
    }

    // =========================================================
    // PRODUCT VERSIONS / HISTORY
    // =========================================================

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class)
            ->orderByDesc('id');
    }

    // =========================================================
    // PRODUCT COMMERCIAL & PERFORMANCE RELATIONSHIPS
    // =========================================================

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'product_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'product_id');
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(Engagement::class, 'product_id');
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class, 'product_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'product_id');
    }

    // =========================================================
    // PRODUCT TERMS & AGREEMENTS
    // =========================================================

    public function terms(): HasMany
    {
        return $this->hasMany(ProductTerm::class)
            ->orderBy('sort_order');
    }
}