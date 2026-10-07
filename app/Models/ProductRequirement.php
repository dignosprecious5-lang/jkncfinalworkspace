<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        // =====================================================
        // PRODUCT
        // =====================================================
        'product_id',

        // =====================================================
        // REQUIREMENT BASIC INFORMATION
        // =====================================================
        'requirement_type',
        'name',
        'description',

        // =====================================================
        // REQUIREMENT DETAILS
        // =====================================================
        'client_type',
        'source',
        'evidence_type',
        'file_required',
        'validity_expiration',
        'instructions',

        // =====================================================
        // CONTROL
        // =====================================================
        'is_mandatory',
        'sequence',
        'status',
    ];

    protected $casts = [
        'file_required' => 'boolean',
        'is_mandatory' => 'boolean',
        'sequence' => 'integer',
    ];

    // =====================================================
    // PRODUCT RELATIONSHIP
    // =====================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}