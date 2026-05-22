<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'product_type',
        'linked_service_id',
        'linked_service_ids',
        'deal_id',
        'product_area',
        'product_area_other',
        'product_description',
        'product_inclusions',
        'requirements',
        'requirement_category',
        'category',
        'pricing_type',
        'price',
        'cost',
        'is_discountable',
        'tax_type',
        'tax_treatment',
        'sku',
        'inventory_type',
        'stock_qty',
        'unit',
        'status',
        'owner_id',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'custom_field_values',
    ];

    protected $casts = [
        'product_area' => 'array',
        'linked_service_ids' => 'array',
        'requirements' => 'array',
        'is_discountable' => 'boolean',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'custom_field_values' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'linked_service_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
