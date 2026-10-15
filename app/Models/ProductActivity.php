<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'activity_level',
        'parent_id',
        'name',
        'description',
        'sequence',
        'expected_days',
        'working_hours',
        'is_billable',
        'is_mandatory',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'expected_days' => 'decimal:2',
        'working_hours' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    /**
     * Product this activity belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Parent activity.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            ProductActivity::class,
            'parent_id'
        );
    }

    /**
     * Child sub-activities.
     */
    public function children(): HasMany
    {
        return $this->hasMany(
            ProductActivity::class,
            'parent_id'
        )->orderBy('sequence');
    }
}