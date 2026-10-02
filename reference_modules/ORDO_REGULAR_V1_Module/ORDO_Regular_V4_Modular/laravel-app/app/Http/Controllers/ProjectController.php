<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController
{
    private function record(Project $model): array
    {
        $p = $model->data;
        $p['regular'] ??= ['cycle'=>1,'period'=>'','status'=>'Active','archives'=>[],'reports'=>[]];
        $p['id'] = $model->id;
        $p['stage'] = ProjectWorkflow::stage($p);
        $p['progress'] = count($p['tasks']) ? (int) round(100 * collect($p['tasks'])->where('done', true)->count() / count($p['tasks'])) : 0;

        return $p;
    }

    public function index(Request $request, ?string $module = null)
    {
        $labels = config('ordo.modules');
        abort_if($module && ! isset($labels[$module]), 404);
        $projects = Project::orderByDesc('id')->get()->map(fn ($p) => $this->record($p));
        $viewer = $request->session()->get('viewer', 'John Kelly Abalde');
        if ($module === 'for-me') {
            $projects = $projects->filter(fn ($p) => $p['lead'] === $viewer || collect($p['tasks'])->contains('assignee', $viewer));
        }
        return view('projects.index', ['projects' => $projects, 'activeModule' => $module, 'pageTitle' => $labels[$module] ?? 'Regular Registry', 'viewer' => $viewer]);
    }

    public function workspace(Project $project, ?string $section = null)
    {
        $section ??= 'project-dashboard';
        $sections = config('ordo.sections');
        abort_unless(isset($sections[$section]), 404);
        $record = $this->record($project);

        return view('projects.workspace', compact('record', 'section', 'sections'));
    }

    public function create(Request $request)
    {
        $input = $request->validate(['title' => 'required|string|max:255', 'business' => 'required|string|max:255', 'lead' => 'required|string|max:255', 'workOrder' => 'required|string|max:255', 'target' => 'required|date', 'client' => 'nullable|string|max:255', 'deal' => 'nullable|string|max:255']);
        $p = array_merge($input, ['workOrderApproved' => false, 'regular' => ['cycle'=>1,'period'=>'','status'=>'Active','archives'=>[],'reports'=>[]], 'health' => 'On Track', 'collaborators' => [], 'scope' => '', 'exclusions' => '', 'sow' => 'draft', 'reviewers' => [], 'ntp' => false, 'tasks' => [], 'deliverables' => [], 'files' => [], 'actions' => [], 'updates' => [], 'history' => [['at' => now()->toIso8601String(), 'action' => 'Regular service created from '.$input['workOrder']]], 'report' => ['issues' => '', 'recommendations' => '', 'way' => '', 'selected' => [], 'approved' => false], 'timer' => ['seconds' => 0, 'started' => null], 'transmittal' => null, 'coc' => null, 'closed' => false]);
        $model = Project::create(['data' => $p]);
        $p['ref'] = 'REG-'.now()->year.'-'.$model->id;
        $model->update(['data' => $p]);

        return redirect()->route('projects.workspace', $model)->with('status', 'Regular service created.');
    }

    public function action(Request $request, Project $project, string $action, ProjectWorkflow $workflow)
    {
        $rules = match ($action) {
            'rsat-save' => ['rowsJson'=>'required|string|max:200000','rsatSummary'=>'nullable|string|max:10000'],
            'scope-outline' => ['outline' => 'required|string|max:20000'],

            'suspend' => ['note'=>'required|string|max:2000'],
            'scope-save' => ['schedule'=>'array','schedule.*.frequency'=>'required|string|max:100','schedule.*.reminder'=>'required|string|max:100','schedule.*.deadline'=>'required|date','scope' => 'required|string|max:20000', 'exclusions' => 'nullable|string|max:20000'],
            'scope-approve' => ['reviewer' => 'required|string|max:100'],
            'scope-revert' => ['note' => 'required|string|max:2000'],
            'ntp-approve' => ['note' => 'required|string|max:2000', 'ntpType' => 'required|in:original,supplemental', 'changePackage' => 'required_if:ntpType,supplemental|nullable|string|max:2000'],
            'ntp-issue' => ['ntpType' => 'required|in:original,supplemental', 'changePackage' => 'required_if:ntpType,supplemental|nullable|string|max:2000'],
            'task-add' => ['title' => 'required|string|max:255', 'assignee' => 'required|string|max:255'],
            'task-done','task-duplicate','deliverable-toggle','client-done' => ['id' => 'required|string|max:100'],
            'timer-start','timer-stop','timer-pause' => ['id' => 'nullable|string|max:100'],
            'deliverable-add' => ['name' => 'required|string|max:255'],
            'report-save' => ['issues' => 'nullable|string|max:20000', 'recommendations' => 'nullable|string|max:20000', 'way' => 'nullable|string|max:20000', 'selected' => 'array', 'selected.*' => 'string|max:100'],
            'update' => ['text' => 'required|string|max:2000'], 'client-add' => ['subject' => 'required|string|max:255'],
            'file-add' => ['attachment' => 'required|file|max:10240'],
            'work-order-approve','resume','scope-submit','report-approve','transmittal','coc','close' => [], default => abort(404),
        };
        $input = $request->validate($rules);
        $path = null;
        if ($action === 'file-add') {
            $file = $request->file('attachment');
            $path = $file->store('projects/'.$project->id, 'local');
            $input = ['file' => ['id' => (string) Str::uuid(), 'name' => $file->getClientOriginalName(), 'path' => $path]];
        }
        try {
            $workflow->apply($project->id, $action, $input);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }

        return back()->with('status', ucfirst(str_replace('-', ' ', $action)).' saved.');
    }

    public function download(Project $project, string $file)
    {
        $item = collect($project->data['files'])->firstWhere('id', $file);
        abort_unless($item, 404);

        return Storage::disk('local')->download($item['path'], $item['name'], ['X-Content-Type-Options' => 'nosniff']);
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['viewer' => 'required|string|max:255']);
        $request->session()->put('viewer', $data['viewer']);

        return back()->with('status', 'Preferences saved.');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Regular Service', 'Company', 'Owner', 'Stage', 'Target']);
            foreach (Project::cursor() as $model) {
                $p = $this->record($model);
                $row = [$p['ref'], $p['title'], $p['business'], $p['lead'], $p['stage'], $p['target']];
                fputcsv($out, array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v, $row));
            } fclose($out);
        }, 'regular-registry.csv', ['Content-Type' => 'text/csv']);
    }
}
