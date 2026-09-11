<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentLibrary extends Model
{
    use HasFactory;

    protected $table = 'content_libraries';

    protected $fillable = [
        'scope_level', // 'global', 'service_area', 'category', 'service'
        'scope_value', // e.g., 'Corporate & Regulatory Advisory', 'Tax Services', or service_id
        'content_type', // 'terms_and_conditions', 'confidentiality', 'exclusions', 'payment_clause'
        'title',
        'body_text',
        'is_active',
    ];

    /**
     * SECTION 11 INHERITANCE RESOLVER LOGIC
     * Hierarchy rule: Global -> Service Area -> Category -> Service-Specific
     */
    public static function getInheritedContent($serviceArea = null, $category = null, $serviceId = null, $contentType = null)
    {
        $query = self::where('is_active', true);

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        $allClauses = $query->get();

        // I-sort ayon sa priority ranking ng Section 11
        return $allClauses->sortBy(function ($item) use ($serviceArea, $category, $serviceId) {
            if ($item->scope_level === 'service' && $item->scope_value == $serviceId) {
                return 1; // Priority 1: Service-Specific
            }
            if ($item->scope_level === 'category' && $item->scope_value == $category) {
                return 2; // Priority 2: Category
            }
            if ($item->scope_level === 'service_area' && $item->scope_value == $serviceArea) {
                return 3; // Priority 3: Service Area
            }
            return 4; // Priority 4: Global
        })->groupBy('content_type')->map(function ($group) {
            return $group->first();
        });
    }
}