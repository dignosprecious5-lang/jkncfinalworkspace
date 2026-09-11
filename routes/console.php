<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\EngagementInstantiationService;
use App\Models\Engagement;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Section 10: Automatic Operational Instantiation & Recurrence Schedule
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    $activeEngagements = Engagement::where('type', 'regular')
        ->where('status', 'active')
        ->get();

    $instantiator = app(EngagementInstantiationService::class);
    foreach ($activeEngagements as $engagement) {
        $instantiator->generateNextRegularPeriod($engagement);
    }
})->daily();