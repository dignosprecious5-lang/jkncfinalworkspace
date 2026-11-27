<?php

namespace App\Console\Commands;

use App\Models\Deal;
use App\Services\ProjectProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillProjectsFromApprovedDeals extends Command
{
    protected $signature = 'projects:backfill-approved-deals {--dry-run : Show how many deals would be provisioned without creating records}';
    protected $description = 'Create missing project records for existing approved Project or Hybrid deals.';

    public function handle(ProjectProvisioner $provisioner): int
    {
        if (! Schema::hasTable('deals') || ! Schema::hasTable('projects')) {
            $this->error('Required tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $candidateDeals = Deal::query()
            ->where('deal_status', 'approved')
            ->where(function ($query) {
                $query->where('engagement_type', 'like', '%Project%')
                    ->orWhere('engagement_type', 'like', '%Hybrid%');
            })
            ->whereDoesntHave('projects', function ($query): void {
                $query->where(function ($workspaceQuery): void {
                    $workspaceQuery
                        ->whereNull('engagement_type')
                        ->orWhereRaw('LOWER(engagement_type) NOT LIKE ?', ['%regular%']);
                });
            })
            ->get();

        if ($candidateDeals->isEmpty()) {
            $this->info('No approved Project or Hybrid deals need backfilling.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf('%d approved deal(s) would be backfilled into project records.', $candidateDeals->count()));

            return self::SUCCESS;
        }

        $created = 0;

        foreach ($candidateDeals as $deal) {
            if ($provisioner->createFromApprovedDeal($deal)) {
                $created++;
                $this->line('Created project for '.$deal->deal_code);
            }
        }

        $this->info(sprintf('Backfill complete. Created %d project record(s).', $created));

        return self::SUCCESS;
    }
}
