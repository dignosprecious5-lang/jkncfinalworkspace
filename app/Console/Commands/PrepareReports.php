<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EngagementPeriod;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrepareReports extends Command
{
    protected $signature = 'services:prepare-reports';
    protected $description = 'Identify engagement periods ready for report preparation based on service version reporting configuration.';

    public function handle()
    {
        $this->info('Running report preparation automation...');

        $today = Carbon::today();
        $preparedCount = 0;

        // Query ALL EngagementPeriod records with relationships (no status filter)
        $periods = EngagementPeriod::with(['engagement.serviceVersion', 'serviceVersion'])
            ->get();

        foreach ($periods as $period) {
            try {
                // Resolve service version
                $serviceVersion = $period->serviceVersion ?? ($period->engagement->serviceVersion ?? null);

                if (!$serviceVersion) {
                    continue;
                }

                $frequency = $serviceVersion->reporting_frequency;

                // Skip if empty or "None"
                if (empty($frequency) || strcasecmp($frequency, 'None') === 0) {
                    continue;
                }

                // Parse period_end_date
                $periodEndDate = $period->period_end_date ? Carbon::parse($period->period_end_date) : null;

                if (!$periodEndDate) {
                    continue;
                }

                // Skip if today is before period_end_date (future period)
                if ($today->lt($periodEndDate)) {
                    continue;
                }

                $reportType = 'Regular Progress Report';
                $reportingPeriod = $period->period_name ?? ($periodEndDate->format('Y-m'));

                // Check whether an engagement_reports record already exists
                $exists = DB::table('engagement_reports')
                    ->where('engagement_period_id', $period->id)
                    ->where('report_type', $reportType)
                    ->exists();

                // Print useful output for every due period
                $this->line("Period ID: {$period->id} | Reporting Frequency: {$frequency} | Period End Date: {$period->period_end_date} | Report Already Exists: " . ($exists ? 'Yes' : 'No'));

                if (!$exists) {
                    // Insert report record
                    DB::table('engagement_reports')->insert([
                        'engagement_id' => $period->engagement_id,
                        'engagement_period_id' => $period->id,
                        'report_type' => $reportType,
                        'reporting_period' => $reportingPeriod,
                        'preparation_status' => 'prepared',
                        'prepared_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $preparedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to prepare report for period #{$period->id}: " . $e->getMessage());
            }
        }

        $this->info("Total reports prepared: {$preparedCount}");

        return 0;
    }
}