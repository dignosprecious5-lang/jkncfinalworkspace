<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::first() ?: User::factory()->create(['role' => 'SuperAdmin']);
        $this->actingAs($user);
    }

    public function test_can_create_hybrid_engagement_with_hyb_prefix(): void
    {
        $response = $this->post(route('project.manual.store'), [
            'name' => 'Hybrid Compliance Engagement',
            'client_name' => 'John Doe',
            'business_name' => 'Acme Corporation',
            'engagement_type' => 'Hybrid',
            'planned_start_date' => '2026-10-01',
            'target_completion_date' => '2026-12-31',
        ]);

        $response->assertRedirect();

        $project = Project::latest('id')->first();
        $this->assertNotNull($project);
        $this->assertSame('Hybrid', $project->engagement_type);
        $this->assertStringStartsWith('HYB-', $project->project_code);
        $this->assertTrue($project->isHybrid());
        $this->assertTrue($project->isProject());
        $this->assertFalse($project->isRegular());
    }

    public function test_can_create_standard_project_with_proj_prefix(): void
    {
        $response = $this->post(route('project.manual.store'), [
            'name' => 'Standard Project Engagement',
            'client_name' => 'Jane Smith',
            'business_name' => 'Beta Corp',
            'engagement_type' => 'Project',
            'planned_start_date' => '2026-10-01',
            'target_completion_date' => '2026-11-30',
        ]);

        $response->assertRedirect();

        $project = Project::latest('id')->first();
        $this->assertNotNull($project);
        $this->assertSame('Project', $project->engagement_type);
        $this->assertStringStartsWith('PROJ-', $project->project_code);
        $this->assertFalse($project->isHybrid());
        $this->assertTrue($project->isProject());
        $this->assertFalse($project->isRegular());
    }

    public function test_can_create_standard_regular_service(): void
    {
        $response = $this->post(route('regular.manual.store'), [
            'name' => 'Standard Retainer Service',
            'client_name' => 'Bob Builder',
            'business_name' => 'Gamma LLC',
            'engagement_type' => 'Regular Retainer',
            'planned_start_date' => '2026-10-01',
            'target_completion_date' => '2027-10-01',
        ]);

        $response->assertRedirect();

        $regular = Project::latest('id')->first();
        $this->assertNotNull($regular);
        $this->assertSame('Regular Retainer', $regular->engagement_type);
        $this->assertStringStartsWith('REG-', $regular->project_code);
        $this->assertFalse($regular->isHybrid());
        $this->assertTrue($regular->isRegular());
        $this->assertFalse($regular->isProject());
    }

    public function test_deal_with_hybrid_engagement_provisions_both_workspaces(): void
    {
        $contact = \App\Models\Contact::create([
            'first_name' => 'Mark',
            'last_name' => 'Spencer',
            'email' => 'mark@acme.com',
            'company_name' => 'Acme Holdings',
        ]);

        $deal = Deal::create([
            'contact_id' => $contact->id,
            'deal_code' => 'DEAL-HYB-001',
            'deal_name' => 'Acme Hybrid Deal',
            'first_name' => 'Mark',
            'last_name' => 'Spencer',
            'company_name' => 'Acme Holdings',
            'engagement_type' => 'Hybrid Engagement',
            'stage' => 'Closed Won',
            'deal_status' => 'Won',
            'planned_start_date' => '2026-10-01',
            'estimated_completion_date' => '2027-03-31',
        ]);

        $provisioner = app(ProjectProvisioner::class);
        $provisioner->createOrSyncFromDeal($deal);

        $projects = Project::where('deal_id', $deal->id)->get();
        $this->assertCount(2, $projects);

        $projectSide = $projects->firstWhere('engagement_type', 'Hybrid');
        $regularSide = $projects->firstWhere('engagement_type', 'Hybrid Regular');

        $this->assertNotNull($projectSide);
        $this->assertNotNull($regularSide);

        $this->assertStringStartsWith('HYB-', $projectSide->project_code);
        $this->assertStringStartsWith('REG-', $regularSide->project_code);
    }
}
