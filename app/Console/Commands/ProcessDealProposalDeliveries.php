<?php

namespace App\Console\Commands;

use App\Services\DealProposalDeliveryService;
use Illuminate\Console\Command;
use InvalidArgumentException;

/** Explicitly invoked; intentionally absent from the scheduler. */
class ProcessDealProposalDeliveries extends Command
{
    protected $signature = 'ordo:process-deal-proposal-deliveries {--limit=25 : Maximum deliveries to claim (1-100)}';

    protected $description = 'Process a bounded batch of issued Deal proposal email deliveries.';

    public function handle(DealProposalDeliveryService $deliveryService): int
    {
        $rawLimit = $this->option('limit');
        if (! is_string($rawLimit) && ! is_int($rawLimit)) {
            $this->error('The batch limit must be an integer from 1 to 100.');

            return self::FAILURE;
        }
        $limit = filter_var($rawLimit, FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 100) {
            $this->error('The batch limit must be an integer from 1 to 100.');

            return self::FAILURE;
        }

        try {
            $summary = $deliveryService->processDue($limit);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Proposal deliveries: claimed=%d sent=%d failed=%d cancelled=%d.',
            $summary['claimed'],
            $summary['sent'],
            $summary['failed'],
            $summary['cancelled'],
        ));

        return self::SUCCESS;
    }
}
