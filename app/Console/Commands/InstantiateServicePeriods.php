<?php

namespace App\Console\Commands;

use App\Services\ServiceAutomationManager;
use Illuminate\Console\Command;

class InstantiateServicePeriods extends Command
{
    protected $signature = 'services:instantiate-periods';
    protected $description = 'Automatically generate upcoming engagement periods and corresponding operational tasks.';

    public function handle(ServiceAutomationManager $automationManager): int
    {
        $this->info('Running service period and task instantiation automation...');
        
        $automationManager->processActiveEngagements();

        $this->info('Automation completed successfully.');
        return self::SUCCESS;
    }
}