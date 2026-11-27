<?php

namespace App\Console\Commands;

use App\Models\DealProposal;
use App\Services\DealProposalDecisions;
use Illuminate\Console\Command;

class RetryProposalDecisionEmails extends Command
{
    protected $signature = 'proposals:retry-decision-emails {--limit=25}';

    protected $description = 'Retry unsent decision summaries for recorded proposal handlers';

    public function handle(DealProposalDecisions $decisions): int
    {
        $failed = 0;
        foreach (DealProposal::whereNotNull('client_submitted_at')->whereNull('decision_notified_at')->orderBy('id')->limit(max(1, (int) $this->option('limit')))->get() as $proposal) {
            if (! $decisions->notify($proposal)) {
                $failed++;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
