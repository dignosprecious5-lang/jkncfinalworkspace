<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\ProjectWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_workspace_and_portfolio_screen_renders(): void
    {
        $this->get('/')->assertOk();
        foreach (array_keys(config('ordo.modules')) as $module) {
            $this->get('/portfolio/'.$module)->assertOk();
        }
        foreach (Project::all() as $project) {
            $this->get('/projects/'.$project->id)->assertOk()->assertSee($project->data['title']);
            foreach (array_keys(config('ordo.sections')) as $section) {
                $this->get('/projects/'.$project->id.'/'.$section)->assertOk();
            }
        }
    }

    public function test_execution_is_rejected_without_ntp(): void
    {
        $this->post('/projects/119/actions/timer-start')->assertSessionHasErrors('workflow');
        $this->assertNull(Project::findOrFail(119)->data['timer']['started']);
    }

    public function test_stop_preserves_time_and_projects_are_isolated(): void
    {
        $service = app(ProjectWorkflow::class);
        $model = Project::findOrFail(120);
        $p = $model->data;
        $p['timer'] = ['seconds' => 123, 'started' => null];
        $model->update(['data' => $p]);
        $service->apply(120, 'timer-stop', []);
        $this->assertSame(123, Project::findOrFail(120)->data['timer']['seconds']);
        $this->assertSame(0, Project::findOrFail(118)->data['timer']['seconds']);
        $service->apply(120, 'timer-start', []);
        $service->apply(118, 'timer-start', []);
        $this->assertNull(Project::findOrFail(120)->data['timer']['started']);
        $this->assertNotNull(Project::findOrFail(118)->data['timer']['started']);
    }

    public function test_full_workflow_closes_only_after_all_gates(): void
    {
        $s = app(ProjectWorkflow::class);
        $this->post('/projects/119/actions/close')->assertSessionHasErrors('workflow');
        $s->apply(119, 'scope-save', ['scope' => 'Approved scope', 'exclusions' => 'Excluded work']);
        $s->apply(119, 'scope-submit', []);
        foreach (['Management', 'Lead Consultant', 'Lead Associate', 'Operations', 'Accounting'] as $reviewer) {
            $s->apply(119, 'scope-approve', compact('reviewer'));
        }
        $s->apply(119, 'ntp-issue', ['ntpType' => 'original']);
        $s->apply(119, 'ntp-approve', ['note' => 'Signed client approval', 'ntpType' => 'original']);
        $p = Project::findOrFail(119)->data;
        foreach ($p['tasks'] as $t) {
            $s->apply(119, 'task-done', ['id' => $t['id']]);
        }
        foreach ($p['deliverables'] as $d) {
            $s->apply(119, 'deliverable-toggle', ['id' => $d['id']]);
        }
        $s->apply(119, 'report-save', ['issues' => 'No issues', 'selected' => array_column($p['tasks'], 'id')]);
        $s->apply(119, 'report-approve', []);
        foreach (['transmittal', 'coc', 'close'] as $action) {
            $s->apply(119, $action, []);
        }
        $this->assertTrue(Project::findOrFail(119)->data['closed']);
        $this->post('/projects/119/actions/task-add', ['title' => 'Late task', 'assignee' => 'Owner'])->assertSessionHasErrors('workflow');
    }

    public function test_creation_requires_work_order_and_persists_a_distinct_record(): void
    {
        $input = ['title' => 'New engagement', 'business' => 'Example company', 'lead' => 'Case owner', 'target' => '2026-10-01'];
        $this->post('/projects', $input)->assertSessionHasErrors('workOrder');
        $this->post('/projects', $input + ['workOrder' => 'WO-2026-001'])->assertRedirect();
        $p = Project::latest('id')->first();
        $this->assertSame('New engagement', $p->data['title']);
        $this->assertFalse($p->data['ntp']);
        $this->get('/projects/'.$p->id)->assertOk()->assertSee('New engagement');
    }

    public function test_attachments_are_private_and_scoped_to_the_selected_project(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('evidence.txt', 'Project evidence');
        $this->post('/projects/120/actions/file-add', ['attachment' => $file])->assertSessionHasNoErrors();
        $entry = Project::findOrFail(120)->data['files'][0];
        $this->get('/projects/120/files/'.$entry['id'])->assertDownload('evidence.txt');
        $this->get('/projects/119/files/'.$entry['id'])->assertNotFound();
    }

    public function test_report_edits_persist_and_supplemental_ntp_requires_a_package(): void
    {
        $this->post('/projects/120/actions/report-save', ['issues' => 'A saved observation', 'selected' => ['t1']])->assertSessionHasNoErrors();
        $p = Project::findOrFail(120)->data;
        $this->assertSame('A saved observation', $p['report']['issues']);
        $this->assertSame(['t1'], $p['report']['selected']);
        $this->post('/projects/120/actions/ntp-approve', ['note' => 'Signed approval', 'ntpType' => 'supplemental'])->assertSessionHasErrors('changePackage');
    }
}
