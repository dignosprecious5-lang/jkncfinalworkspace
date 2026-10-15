<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Engagement;
use App\Models\EngagementPeriod;
use App\Models\ServiceVersion;
use App\Models\ServiceActivity;
use App\Models\OperationalTask;

class RunMyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_runs_task_automation_successfully()
    {
        $serviceVersion = ServiceVersion::factory()->create();
        $parentActivity = ServiceActivity::factory()->create([
            'service_version_id' => $serviceVersion->id, 
            'sequence' => 1
        ]);

        $engagement = Engagement::factory()->create(['service_version_id' => $serviceVersion->id]);
        $period = EngagementPeriod::factory()->create(['engagement_id' => $engagement->id]);

        $this->artisan('services:instantiate-periods');

        $this->assertDatabaseHas('operational_tasks', [
            'engagement_period_id' => $period->id,
            'service_activity_id' => $parentActivity->id,
        ]);
        
        $this->assertTrue(true);
    }
}