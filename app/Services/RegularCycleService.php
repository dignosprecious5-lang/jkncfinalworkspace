<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectSow;
use App\Models\ProjectNtp;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RegularCycleService
{
    /**
     * Get cycle state and details for a regular engagement.
     */
    public function getCycleState(Project $regular): array
    {
        $metadata = (array) ($regular->metadata ?? []);
        $regularMeta = (array) ($metadata['regular_cycle'] ?? []);
        
        $cycleNumber = (int) ($regularMeta['cycle_number'] ?? 1);
        $period = $regularMeta['current_period'] ?? $this->calculateInitialPeriod($regular);
        $recurrence = $regularMeta['recurrence'] ?? 'Monthly';
        $cycleHistory = (array) ($regularMeta['history'] ?? []);
        $cycleStatus = $regularMeta['cycle_status'] ?? 'Active'; // Active, Transmitted, Completed, Suspended

        return [
            'cycle_number' => max(1, $cycleNumber),
            'current_period' => $period,
            'recurrence' => $recurrence,
            'cycle_status' => $cycleStatus,
            'history' => $cycleHistory,
            'total_completed_cycles' => count($cycleHistory),
        ];
    }

    /**
     * Calculate initial period string based on start date and recurrence.
     */
    protected function calculateInitialPeriod(Project $regular, string $recurrence = 'Monthly'): string
    {
        $start = $regular->start_date ? Carbon::parse($regular->start_date) : Carbon::now();
        
        switch (strtolower($recurrence)) {
            case 'quarterly':
                return 'Q' . $start->quarter . ' ' . $start->year;
            case 'annual':
            case 'yearly':
                return (string) $start->year;
            case 'semi-annual':
            case 'semi_annual':
                return ($start->month <= 6 ? 'H1 ' : 'H2 ') . $start->year;
            case 'monthly':
            default:
                return $start->format('F Y');
        }
    }

    /**
     * Calculate next period string based on current period and recurrence.
     */
    public function calculateNextPeriod(string $currentPeriod, string $recurrence = 'Monthly'): string
    {
        try {
            switch (strtolower($recurrence)) {
                case 'quarterly':
                    if (preg_match('/Q(\d)\s+(\d{4})/i', $currentPeriod, $m)) {
                        $q = (int) $m[1];
                        $y = (int) $m[2];
                        $q++;
                        if ($q > 4) {
                            $q = 1;
                            $y++;
                        }
                        return "Q{$q} {$y}";
                    }
                    return Carbon::now()->addQuarter()->format('\QQ Y');
                case 'annual':
                case 'yearly':
                    if (preg_match('/(\d{4})/', $currentPeriod, $m)) {
                        return (string) (((int) $m[1]) + 1);
                    }
                    return (string) (Carbon::now()->year + 1);
                case 'semi-annual':
                case 'semi_annual':
                    if (preg_match('/H(\d)\s+(\d{4})/i', $currentPeriod, $m)) {
                        $h = (int) $m[1];
                        $y = (int) $m[2];
                        if ($h === 1) {
                            return "H2 {$y}";
                        }
                        return "H1 " . ($y + 1);
                    }
                    return 'H2 ' . Carbon::now()->year;
                case 'monthly':
                default:
                    $date = Carbon::parse("1 " . $currentPeriod);
                    return $date->addMonth()->format('F Y');
            }
        } catch (\Throwable) {
            return Carbon::now()->addMonth()->format('F Y');
        }
    }

    /**
     * Advance the regular engagement to the next operational cycle.
     * Archives the current cycle snapshot and resets approvals for the new period.
     */
    public function advanceCycle(Project $regular, array $options = []): array
    {
        $metadata = (array) ($regular->metadata ?? []);
        $regularMeta = (array) ($metadata['regular_cycle'] ?? []);
        
        $currentCycle = (int) ($regularMeta['cycle_number'] ?? 1);
        $currentPeriod = $regularMeta['current_period'] ?? $this->calculateInitialPeriod($regular);
        $recurrence = $options['recurrence'] ?? ($regularMeta['recurrence'] ?? 'Monthly');
        $nextPeriod = $options['next_period'] ?? $this->calculateNextPeriod($currentPeriod, $recurrence);
        
        $rsat = $regular->sow;
        $ntp = $regular->ntps()->latest()->first();

        // 1. Snapshot the completed cycle
        $snapshot = [
            'cycle_number' => $currentCycle,
            'period' => $currentPeriod,
            'recurrence' => $recurrence,
            'completed_at' => Carbon::now()->toIso8601String(),
            'completed_by' => auth()->user()?->name ?? 'System Operator',
            'transmittal_ref' => $options['transmittal_ref'] ?? ($regularMeta['current_transmittal_ref'] ?? null),
            'notes' => $options['notes'] ?? '',
            'rsat_snapshot' => $rsat ? [
                'scope_summary' => $rsat->scope_summary,
                'requirements' => $rsat->engagement_requirements,
                'clearance' => $rsat->clearance,
                'status' => $rsat->status,
                'approved_at' => optional($rsat->approved_at)->toIso8601String(),
            ] : null,
            'ntp_snapshot' => $ntp ? [
                'reference_no' => $ntp->reference_no,
                'status' => $ntp->client_response_status,
                'approved_at' => optional($ntp->client_approved_at)->toIso8601String(),
            ] : null,
            'reports_count' => $regular->sowReports()->count(),
        ];

        $history = (array) ($regularMeta['history'] ?? []);
        $history[] = $snapshot;

        // 2. Prepare carried over requirements for the new cycle
        $carryOverRequirements = [];
        if ($rsat && !empty($rsat->engagement_requirements)) {
            foreach ($rsat->engagement_requirements as $req) {
                // Keep open or recurring items for the new cycle
                $reqCopy = $req;
                if (($reqCopy['status'] ?? '') === 'completed' && !empty($options['reset_all_requirements'])) {
                    $reqCopy['status'] = 'open';
                }
                $carryOverRequirements[] = $reqCopy;
            }
        }

        // 3. Update RSAT model for new cycle if requested or preserve existing structure
        if ($rsat && !empty($options['reset_approvals'])) {
            $rsat->update([
                'status' => 'draft',
                'approved_at' => null,
                'form_date' => Carbon::now(),
                'engagement_requirements' => $carryOverRequirements,
            ]);
        }

        // 4. Update Regular Project metadata
        $regularMeta['cycle_number'] = $currentCycle + 1;
        $regularMeta['current_period'] = $nextPeriod;
        $regularMeta['recurrence'] = $recurrence;
        $regularMeta['cycle_status'] = 'Active';
        $regularMeta['last_advanced_at'] = Carbon::now()->toIso8601String();
        $regularMeta['history'] = $history;
        $regularMeta['current_transmittal_ref'] = null;

        $metadata['regular_cycle'] = $regularMeta;
        $regular->forceFill(['metadata' => $metadata])->save();

        return [
            'success' => true,
            'previous_cycle' => $currentCycle,
            'new_cycle' => $currentCycle + 1,
            'new_period' => $nextPeriod,
            'archived_snapshots_count' => count($history),
        ];
    }
}
