<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\ProjectWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_create_and_run_hybrid_engagement(): void
    {
        // 1. Create a Hybrid engagement
        $input = [
            'type' => 'hybrid',
            'title' => 'Hybrid Compliance and Retainer',
            'business' => 'Acme Global Corp',
            'lead' => 'John Kelly Abalde',
            'workOrder' => 'WO-HYB-2026-001',
            'target' => '2026-12-31',
            'deal' => 'DEAL-999',
            'client' => 'Acme CEO',
        ];

        $response = $this->post('/projects', $input);
        $response->assertRedirect();

        $hybrid = Project::latest('id')->first();
        $this->assertNotNull($hybrid);
        $this->assertSame('hybrid', $hybrid->data['type']);
        $this->assertStringStartsWith('HYB-', $hybrid->data['ref']);
        $this->assertFalse($hybrid->data['workOrderApproved']);
        $this->assertSame('Work Order', ProjectWorkflow::stage($hybrid->data));

        $s = app(ProjectWorkflow::class);

        // 2. Approve Work Order
        $s->apply($hybrid->id, 'work-order-approve', []);
        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertTrue($hybrid->data['workOrderApproved']);
        $this->assertSame('Plan', ProjectWorkflow::stage($hybrid->data));

        // 3. Save outline and submit scope
        $s->apply($hybrid->id, 'scope-outline', [
            'outline' => "Monthly Compliance\n  Tax filing\n  Audit review\nAdvisory\n  Quarterly consultation"
        ]);
        $s->apply($hybrid->id, 'scope-save', [
            'scope' => "Monthly Compliance — Tax filing\nMonthly Compliance — Audit review\nAdvisory — Quarterly consultation",
            'exclusions' => 'Excludes litigation'
        ]);
        $s->apply($hybrid->id, 'scope-submit', []);

        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertSame('Review', ProjectWorkflow::stage($hybrid->data));

        // 4. Approve review by all required roles
        foreach (['Management', 'Lead Consultant', 'Lead Associate', 'Operations', 'Accounting'] as $reviewer) {
            $s->apply($hybrid->id, 'scope-approve', compact('reviewer'));
        }

        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertSame('NTP', ProjectWorkflow::stage($hybrid->data));

        // 5. Issue and approve NTP
        $s->apply($hybrid->id, 'ntp-issue', ['ntpType' => 'original']);
        $s->apply($hybrid->id, 'ntp-approve', ['note' => 'Approved by client board', 'ntpType' => 'original']);

        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertSame('Execution', ProjectWorkflow::stage($hybrid->data));

        // 6. Complete some tasks and deliverables
        $p = $hybrid->data;
        $taskId = $p['tasks'][0]['id'];
        $s->apply($hybrid->id, 'task-done', ['id' => $taskId]);
        $s->apply($hybrid->id, 'deliverable-add', ['name' => 'Monthly Filing Form 1702']);
        $p = Project::findOrFail($hybrid->id)->data;
        $deliverableId = $p['deliverables'][0]['id'];
        $s->apply($hybrid->id, 'deliverable-toggle', ['id' => $deliverableId]);

        // 7. Save and approve periodic report
        $s->apply($hybrid->id, 'period-save', ['period' => 'September 2026']);
        $s->apply($hybrid->id, 'report-save', ['issues' => 'All clear for cycle 1', 'selected' => [$taskId]]);
        $s->apply($hybrid->id, 'report-approve', []);

        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertTrue($hybrid->data['report']['approved']);
        $this->assertCount(1, $hybrid->data['regular']['reports']);

        // 8. Transmittal
        $s->apply($hybrid->id, 'transmittal', []);
        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertNotNull($hybrid->data['transmittal']);

        // 9. Cycle complete
        $s->apply($hybrid->id, 'cycle-complete', []);
        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertSame('Cycle Completed', $hybrid->data['regular']['status']);
        $this->assertCount(1, $hybrid->data['regular']['archives']);

        // 10. Start next cycle
        $s->apply($hybrid->id, 'cycle-next', ['period' => 'October 2026']);
        $hybrid = Project::findOrFail($hybrid->id);
        $this->assertSame(2, $hybrid->data['regular']['cycle']);
        $this->assertSame('Active', $hybrid->data['regular']['status']);
        $this->assertSame('Plan', ProjectWorkflow::stage($hybrid->data));
    }

    public function test_suspend_and_resume_flow(): void
    {
        $s = app(ProjectWorkflow::class);
        $model = Project::findOrFail(120);

        // Suspend
        $s->apply(120, 'suspend', ['note' => 'Client requested pause during audit']);
        $p = Project::findOrFail(120)->data;
        $this->assertTrue($p['closed']);
        $this->assertSame('Suspended', $p['regular']['status']);
        $this->assertSame('Client requested pause during audit', $p['regular']['reason']);

        // Timer cannot start while suspended
        $this->post('/projects/120/actions/timer-start')->assertSessionHasErrors('workflow');

        // Resume
        $s->apply(120, 'resume', []);
        $p = Project::findOrFail(120)->data;
        $this->assertFalse($p['closed']);
        $this->assertSame('Active', $p['regular']['status']);
    }

    public function test_export_includes_all_types(): void
    {
        $response = $this->get('/reports/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
