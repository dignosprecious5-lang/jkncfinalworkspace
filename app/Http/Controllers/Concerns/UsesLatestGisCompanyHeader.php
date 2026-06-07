<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GisRecord;
use Illuminate\Support\Facades\Schema;

trait UsesLatestGisCompanyHeader
{
    protected function latestGisCompanyHeader(?int $companyId = null): array
    {
        $gisRecord = $this->latestApprovedHeaderGisRecord($companyId);

        return [
            'company_name' => $gisRecord?->corporation_name ?: 'JOHN KELLY & COMPANY (JK&C INC)',
            'company_address' => $gisRecord?->principal_address ?: '3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000',
            'logo_url' => $this->latestGisLogoUrl($gisRecord),
        ];
    }

    protected function latestApprovedHeaderGisRecord(?int $companyId = null): ?GisRecord
    {
        if (! class_exists(GisRecord::class) || ! Schema::hasTable((new GisRecord())->getTable())) {
            return null;
        }

        $gisTable = (new GisRecord())->getTable();

        $query = GisRecord::query()
            ->where(function ($q) {
                $q->where('approval_status', 'Approved')
                    ->orWhere('workflow_status', 'Accepted')
                    ->orWhere('workflow_status', 'Approved');
            });

        if (Schema::hasColumn($gisTable, 'company_id')) {
            if ($companyId !== null) {
                $query->where('company_id', $companyId);
            } else {
                $query->where(function ($nested) {
                    $nested->whereNull('company_id')
                        ->orWhere('company_id', 0);
                });
            }
        }

        return $query
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->first();
    }

    protected function latestGisLogoUrl(?GisRecord $gisRecord): string
    {
        $fallback = asset('images/jk-logo.png');

        if (! $gisRecord || empty($gisRecord->logo_path)) {
            return $fallback;
        }

        $path = ltrim($gisRecord->logo_path, '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }
}
