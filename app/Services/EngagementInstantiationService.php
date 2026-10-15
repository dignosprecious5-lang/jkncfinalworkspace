<?php

namespace App\Services;

use App\Models\Engagement;
use App\Models\EngagementPeriod;
use Carbon\Carbon;

class EngagementInstantiationService
{
    /**
     * Otomatikong nagse-generate ng susunod na operational period (Section 10.1)
     */
    public function generateNextRegularPeriod(Engagement $engagement)
    {
        $frequency = $engagement->service_snapshot['activity_frequency'] ?? 'Monthly';
        $now = Carbon::now();

        $periodKey = match($frequency) {
            'Weekly' => $now->format('Y-\WW'),
            'Quarterly' => $now->format('Y-\Q') . ceil($now->month / 3),
            'Annual' => $now->format('Y'),
            default => $now->format('Y-\Mm'),
        };

        $existingPeriod = EngagementPeriod::where('engagement_id', $engagement->id)
            ->where('period_key', $periodKey)
            ->first();

        if ($existingPeriod) {
            return $existingPeriod;
        }

        return EngagementPeriod::create([
            'engagement_id' => $engagement->id,
            'period_key' => $periodKey,
            'period_start' => $now->startOfMonth()->toDateString(),
            'period_end' => $now->endOfMonth()->toDateString(),
        ]);
    }
}