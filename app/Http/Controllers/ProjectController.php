<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController
{
    private function record(Project $model): array
    {
        $p = $model->data;
        $type = ProjectWorkflow::type($p);
        $p['type'] = $type;
        if ($type !== 'project' || isset($p['regular'])) {
            $p['regular'] ??= ['cycle' => 1, 'period' => '', 'status' => 'Active', 'archives' => [], 'reports' => []];
        }
        $p['id'] = $model->id;
        $p['stage'] = ProjectWorkflow::stage($p);
        $p['progress'] = count($p['tasks'] ?? []) ? (int) round(100 * collect($p['tasks'])->where('done', true)->count() / count($p['tasks'])) : 0;

        return $p;
    }

    public function index(?string $module = 'all-projects')
    {
        $viewer = request()->cookie('ordo_viewer', 'John Kelly Abalde');
        $labels = config('ordo.modules');
        abort_unless(isset($labels[$module]), 404);
        $projects = Project::cursor()->map(fn ($p) => $this->record($p))->values();
        if ($module === 'for-me') {
            $projects = $projects->filter(fn ($p) => $p['lead'] === $viewer || collect($p['tasks'] ?? [])->contains('assignee', $viewer));
        }

        return view('projects.index', ['projects' => $projects, 'activeModule' => $module, 'pageTitle' => $labels[$module] ?? 'Registry', 'viewer' => $viewer]);
    }

    public function workspace(Project $project, ?string $section = null)
    {
        $p = $this->record($project);
        $section ??= 'project-dashboard';
        $allSections = config('ordo.sections');
        abort_unless(isset($allSections[$section]), 404);

        $sections = match ($p['type']) {
            'regular' => config('ordo.regular_sections', $allSections),
            'hybrid' => config('ordo.hybrid_sections', $allSections),
            default => config('ordo.project_sections', $allSections),
        };

        $total = ProjectWorkflow::seconds($p['timer'] ?? ['seconds' => 0, 'started' => null]);
        foreach ($p['tasks'] ?? [] as $t) {
            $total += ProjectWorkflow::seconds($t['timer'] ?? ['seconds' => 0, 'started' => null]);
        }
        $unlocked = ($p['sow'] ?? '') === 'approved' && ($p['ntp'] ?? false);

        return view('projects.workspace', [
            'record' => $p,
            'p' => $p,
            'sections' => $sections,
            'section' => $section,
            'total' => $total,
            'unlocked' => $unlocked,
        ]);
    }

    public function create(Request $request)
    {
        $input = $request->validate([
            'title' => 'required|string|max:255',
            'business' => 'required|string|max:255',
            'lead' => 'required|string|max:255',
            'workOrder' => 'required|string|max:255',
            'target' => 'required|date',
            'client' => 'nullable|string|max:255',
            'deal' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:project,regular,hybrid,Project,Regular,Hybrid',
        ]);

        $type = strtolower($input['type'] ?? 'project');
        $prefix = match ($type) {
            'regular' => 'REG-',
            'hybrid' => 'HYB-',
            default => 'PROJ-',
        };

        $actionDesc = match ($type) {
            'regular' => 'Regular service created from '.$input['workOrder'],
            'hybrid' => 'Hybrid engagement created from '.$input['workOrder'],
            default => 'Project created from '.$input['workOrder'],
        };

        $p = array_merge($input, [
            'type' => $type,
            'health' => 'On Track',
            'collaborators' => [],
            'scope' => '',
            'exclusions' => '',
            'sow' => 'draft',
            'reviewers' => [],
            'ntp' => false,
            'tasks' => [],
            'deliverables' => [],
            'files' => [],
            'actions' => [],
            'updates' => [],
            'history' => [['at' => now()->toIso8601String(), 'action' => $actionDesc]],
            'report' => ['issues' => '', 'recommendations' => '', 'way' => '', 'selected' => [], 'approved' => false],
            'timer' => ['seconds' => 0, 'started' => null],
            'transmittal' => null,
            'coc' => null,
            'closed' => false,
        ]);

        if ($type !== 'project') {
            $p['workOrderApproved'] = false;
            $p['regular'] = ['cycle' => 1, 'period' => '', 'status' => 'Active', 'archives' => [], 'reports' => []];
        }

        $model = Project::create(['data' => $p]);
        $p['ref'] = $prefix.now()->year.'-'.$model->id;
        $model->update(['data' => $p]);

        $statusMsg = match ($type) {
            'regular' => 'Regular service created.',
            'hybrid' => 'Hybrid engagement created.',
            default => 'Project created.',
        };

        return redirect()->route('projects.workspace', $model)->with('status', $statusMsg);
    }

    public function action(Request $request, Project $project, string $action, ProjectWorkflow $workflow)
    {
        $rules = match ($action) {
            'rsat-save' => ['rowsJson' => 'required|string|max:200000', 'rsatSummary' => 'nullable|string|max:10000'],
            'scope-outline' => ['outline' => 'required|string|max:20000'],
            'suspend' => ['note' => 'required|string|max:2000'],
            'period-save' => ['period' => 'required|string|max:255'],
            'cycle-next' => ['period' => 'nullable|string|max:255'],
            'scope-save' => [
                'scope' => 'required|string|max:20000',
                'exclusions' => 'nullable|string|max:20000',
                'schedule' => 'nullable|array',
                'schedule.*.frequency' => 'required|string|max:100',
                'schedule.*.reminder' => 'required|string|max:100',
                'schedule.*.deadline' => 'required|date',
            ],
            'scope-approve' => ['reviewer' => 'required|string|max:100'],
            'scope-revert' => ['note' => 'required|string|max:2000'],
            'ntp-approve' => ['note' => 'required|string|max:2000', 'ntpType' => 'required|in:original,supplemental', 'changePackage' => 'required_if:ntpType,supplemental|nullable|string|max:2000'],
            'ntp-issue' => ['ntpType' => 'required|in:original,supplemental', 'changePackage' => 'nullable|string|max:2000'],
            'task-add' => ['title' => 'required|string|max:255', 'assignee' => 'required|string|max:255'],
            'task-done', 'task-duplicate', 'timer-start', 'timer-pause', 'timer-stop', 'deliverable-toggle', 'client-done' => ['id' => 'nullable|string|max:100'],
            'deliverable-add' => ['name' => 'required|string|max:255'],
            'report-save' => ['issues' => 'nullable|string|max:20000', 'recommendations' => 'nullable|string|max:20000', 'way' => 'nullable|string|max:20000', 'selected' => 'array', 'selected.*' => 'string|max:100'],
            'update' => ['text' => 'required|string|max:2000'],
            'client-add' => ['subject' => 'required|string|max:255'],
            'file-add' => ['attachment' => 'required|file|max:10240'],
            'work-order-approve', 'resume', 'cycle-complete', 'scope-submit', 'report-approve', 'transmittal', 'coc', 'close' => [],
            default => abort(404),
        };

        $input = $request->validate($rules);
        $path = null;
        $originalName = null;
        $size = 0;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $size = $file->getSize();
            $path = $file->store('project-files/'.$project->id, 'local');
        }

        $payload = $input;
        if ($path) {
            $payload['file'] = [
                'id' => (string) Str::uuid(),
                'name' => $originalName,
                'path' => $path,
                'size' => $size,
                'at' => now()->toIso8601String(),
            ];
        }

        $workflow->apply($project->id, $action, $payload);

        return back()->with('status', 'Action completed.');
    }

    public function download(Project $project, string $file)
    {
        $p = $project->data;
        $entry = collect($p['files'] ?? [])->firstWhere('id', $file);
        abort_unless($entry && Storage::disk('local')->exists($entry['path']), 404);

        return Storage::disk('local')->download($entry['path'], $entry['name']);
    }

    public function settings(Request $request)
    {
        $input = $request->validate(['viewer' => 'required|string|max:255']);
        Cookie::queue('ordo_viewer', $input['viewer'], 60 * 24 * 365);

        return back()->with('status', 'Preferences saved.');
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Type', 'Title', 'Company', 'Owner', 'Stage', 'Target']);
            foreach (Project::cursor() as $model) {
                $p = $this->record($model);
                $row = [$p['ref'], ucfirst($p['type'] ?? 'project'), $p['title'], $p['business'], $p['lead'], $p['stage'], $p['target']];
                fputcsv($out, array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, 'ordo-registry.csv', ['Content-Type' => 'text/csv']);
    }
}
