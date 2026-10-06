<?php
namespace Tests\Feature;

use App\Models\Project;
use App\Services\ProjectWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegularCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_periodic_reporting_preserves_pending_work_and_requires_new_approval_next_cycle(): void
    {
        $this->seed();
        $model=Project::findOrFail(120); $p=$model->data;
        $p['sow']='approved';$p['ntp']=true;$p['closed']=false;$p['transmittal']=null;$p['coc']=null;
        foreach($p['tasks'] as &$task){$task['done']=false;}unset($task);
        $model->update(['data'=>$p]);$service=app(ProjectWorkflow::class);
        $service->apply(120,'period-save',['period'=>'September 2026']);
        $service->apply(120,'report-save',['selected'=>[$p['tasks'][0]['id']],'issues'=>'Work continues']);
        $service->apply(120,'report-approve',[]);
        $this->assertTrue(Project::findOrFail(120)->data['report']['approved']);
        $service->apply(120,'transmittal',[]);
        $service->apply(120,'cycle-complete',[]);
        $closedCycle=Project::findOrFail(120)->data;
        $this->assertFalse($closedCycle['closed']);
        $this->assertNull($closedCycle['coc']);
        $this->assertSame('Cycle Completed',$closedCycle['regular']['status']);
        $this->assertFalse($closedCycle['regular']['archives'][0]['snapshot']['tasks'][0]['done']);
        $service->apply(120,'cycle-next',['period'=>'October 2026']);
        $next=Project::findOrFail(120)->data;
        $this->assertSame(2,$next['regular']['cycle']);
        $this->assertSame('September 2026',$next['regular']['archives'][0]['period']);
        $this->assertFalse($next['ntp']);
        $this->assertSame('draft',$next['sow']);
        $this->assertSame('Plan',ProjectWorkflow::stage($next));
        $this->post('/projects/120/actions/timer-start')->assertSessionHasErrors('workflow');
        $this->post('/projects/120/actions/cycle-complete')->assertSessionHasErrors('workflow');
    }

    public function test_new_work_order_blocks_planning_until_approved(): void
    {
        $this->seed();$model=Project::findOrFail(119);$p=$model->data;$p['workOrderApproved']=false;$p['sow']='draft';$p['ntp']=false;$model->update(['data'=>$p]);
        $this->assertSame('Work Order',ProjectWorkflow::stage($p));
        $this->post('/projects/119/actions/scope-submit')->assertSessionHasErrors('workflow');
        app(ProjectWorkflow::class)->apply(119,'work-order-approve',[]);
        $this->assertSame('Plan',ProjectWorkflow::stage(Project::findOrFail(119)->data));
    }
}
