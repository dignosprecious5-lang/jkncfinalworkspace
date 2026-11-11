<?php

namespace App\Console\Commands;

use App\Services\DealMembershipReconciliation;
use Illuminate\Console\Command;

class ReportDealMembershipReconciliation extends Command
{
    protected $signature = 'deals:memberships:reconcile {--dry-run : Required; only report proposed mappings and issues} {--json : Print the complete report as JSON}';

    protected $description = 'Report deterministic legacy Deal membership mappings without changing data.';

    public function handle(DealMembershipReconciliation $reconciliation): int
    {
        if (! $this->option('dry-run')) {
            $this->error('This command is read-only. Pass --dry-run to generate a report.');

            return self::FAILURE;
        }

        $report = $reconciliation->dryRun();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $summary = $report['summary'];
        $this->info(sprintf('Deals: %d; proposed: %d; already active: %d; unresolved: %d; lead review: %d; unassigned Deals: %d.',
            $report['deal_count'],
            $summary['proposed'],
            $summary['already_active'],
            $summary['unresolved'],
            $summary['lead_review'],
            $summary['unassigned_deals'],
        ));

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
