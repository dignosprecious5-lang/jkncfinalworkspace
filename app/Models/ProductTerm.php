<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'content',
        'sort_order',
        'status',
        'scope',
        'scope_service_area',
        'scope_category',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductTerm $term) {
            $scope = \App\Services\ProductCatalogConfiguration::scope($term->scope);
            if ($scope === '') {
                throw \Illuminate\Validation\ValidationException::withMessages(['scope' => 'Select a valid term scope.']);
            }
            $term->scope = $scope;
            if (! $term->exists || $term->isDirty('scope')) {
                $product = $term->product;
                $area = $product?->service_area;
                $category = $product?->category;
                if ($scope === 'service_area' && blank($area)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['scope' => 'Configure the product service area first.']);
                }
                if ($scope === 'category' && blank($category)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['scope' => 'Configure the product category first.']);
                }
                $term->scope_service_area = is_array($area) ? json_encode($area) : $area;
                $term->scope_category = is_array($category) ? json_encode($category) : $category;
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}