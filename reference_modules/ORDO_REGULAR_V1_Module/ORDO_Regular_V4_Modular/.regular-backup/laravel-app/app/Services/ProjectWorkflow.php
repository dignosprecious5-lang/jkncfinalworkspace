<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectWorkflow
{
    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }

    public static function seconds(array $timer): int
    {
        return (int) $timer['seconds'] + (! empty($timer['started']) ? max(0, (int) floor((microtime(true) * 1000 - $timer['started']) / 1000)) : 0);
    }

    private function stop(array &$timer): void
    {
        $timer = ['seconds' => self::seconds($timer), 'started' => null];
    }

    public static function ready(array $p): bool
    {
        return count($p['tasks']) > 0 && collect($p['tasks'])->every('done', true)
            && count($p['deliverables']) > 0 && collect($p['deliverables'])->every('ready', true) && $p['report']['approved'];
    }

    public static function stage(array $p): string
    {
        return $p['closed'] ? 'Completed' : ($p['transmittal'] ? 'Delivery' : ($p['report']['approved'] ? 'Reporting' : ($p['ntp'] ? 'Execution' : ($p['sow'] === 'approved' ? 'NTP' : ($p['sow'] === 'review' ? 'Review' : 'SOW')))));
    }

    public function apply(int $id, string $action, array $input): void
    {
        DB::transaction(function () use ($id, $action, $input) {
            // Acquire project rows in the same order for every timer/workflow mutation.
            $models = Project::orderBy('id')->lockForUpdate()->get();
            $model = $models->firstWhere('id', $id);
            abort_unless($model, 404);
            $p = $model->data;
            $this->require(! $p['closed'], 'This project is closed.');
            $item = $input['id'] ?? '';
            $unlocked = $p['sow'] === 'approved' && $p['ntp'];
            $taskIndex = collect($p['tasks'])->search(fn ($t) => $t['id'] === $item);
            if (in_array($action, ['task-done', 'task-duplicate']) || (str_starts_with($action, 'timer-') && $item)) {
                $this->require($taskIndex !== false, 'Task not found.');
            }
            if (in_array($action, ['task-add', 'task-done', 'task-duplicate', 'timer-start', 'deliverable-add', 'deliverable-toggle', 'report-approve'])) {
                $this->require($unlocked, 'Approve the SOW and NTP before execution.');
            }
            if (in_array($action, ['task-add', 'task-done', 'task-duplicate', 'deliverable-add', 'deliverable-toggle', 'report-save'])) {
                $this->require(! $p['transmittal'], 'Delivery has already been issued.');
            }
            switch ($action) {
                case 'scope-outline':
                    $this->require($p['sow'] === 'draft', 'Only draft scope can be edited.');
                    $group = null;
                    $tasks = [];
                    foreach (preg_split('/\R/', $input['outline']) as $line) {
                        if (! trim($line)) {
                            continue;
                        }
                        if (! preg_match('/^\s/', $line)) {
                            $group = trim($line);

                            continue;
                        }
                        $this->require($group !== null, 'Begin the outline with a main workstream.');
                        $tasks[] = ['id' => (string) Str::uuid(), 'group' => $group, 'title' => trim($line), 'assignee' => $p['lead'], 'done' => false, 'timer' => ['seconds' => 0, 'started' => null]];
                    }
                    $this->require(count($tasks) > 0, 'Add an indented task below a workstream.');
                    $p['tasks'] = $tasks;
                    $p['scopeOutline'] = $input['outline'];
                    $p['report']['selected'] = [];
                    $p['report']['approved'] = false;
                    break;
                case 'scope-save':
                    $this->require($p['sow'] === 'draft', 'Only draft scope can be edited.');
                    $p['scope'] = $input['scope'];
                    $p['exclusions'] = $input['exclusions'] ?? '';
                    break;
                case 'scope-submit':
                    $this->require($p['sow'] === 'draft' && trim($p['scope']) !== '', 'Save a scope before submitting.');
                    $p['sow'] = 'review';
                    $p['reviewers'] = array_fill_keys(['Management', 'Lead Consultant', 'Lead Associate', 'Operations', 'Accounting'], false);
                    $p['reviewStartedAt'] = now()->toIso8601String();
                    $p['reviewResponses'] = [];
                    break;
                case 'scope-approve':
                    $this->require($p['sow'] === 'review', 'Submit the SOW first.');
                    $this->require(array_key_exists($input['reviewer'], $p['reviewers']), 'Invalid reviewer.');
                    $p['reviewers'][$input['reviewer']] = true;
                    $p['reviewResponses'][$input['reviewer']] = now()->toIso8601String();
                    if (! in_array(false, $p['reviewers'], true)) {
                        $p['sow'] = 'approved';
                    } break;
                case 'scope-revert':
                    $this->require($p['sow'] === 'review', 'Only scope in review can be returned.');
                    $p['sow'] = 'draft';
                    $p['reviewNote'] = $input['note'];
                    break;
                case 'ntp-issue':
                    $this->require($p['sow'] === 'approved' && ! $p['transmittal'], 'Approve the SOW before issuing NTP.');
                    $p['ntpStatus'] = 'issued';
                    $p['ntpIssuedAt'] = now()->toIso8601String();
                    $p['ntpType'] = $input['ntpType'];
                    $p['changePackage'] = $input['changePackage'] ?? '';
                    if ($p['ntpType'] === 'original') {
                        $p['ntp'] = false;
                    }
                    break;
                case 'ntp-approve':
                    $this->require($p['sow'] === 'approved', 'Approve the SOW first.');
                    $this->require(($p['ntpStatus'] ?? '') === 'issued', 'Issue the NTP before recording approval.');
                    $this->require($p['ntpType'] === $input['ntpType'] && $p['changePackage'] === ($input['changePackage'] ?? ''), 'Reissue the NTP if its type or change package changes.');
                    $p['ntpStatus'] = 'approved';
                    $p['ntpNote'] = $input['note'];
                    $p['ntp'] = true;
                    $p['ntpType'] = $input['ntpType'];
                    $p['changePackage'] = $input['changePackage'] ?? '';
                    break;
                case 'task-add':
                    $p['tasks'][] = ['id' => (string) Str::uuid(), 'title' => $input['title'], 'assignee' => $input['assignee'], 'done' => false, 'timer' => ['seconds' => 0, 'started' => null]];
                    $p['report']['approved'] = false;
                    break;
                case 'task-duplicate':
                    $task = $p['tasks'][$taskIndex];
                    $task['id'] = (string) Str::uuid();
                    $task['title'] .= ' (Copy)';
                    $task['done'] = false;
                    $task['timer'] = ['seconds' => 0, 'started' => null];
                    $p['tasks'][] = $task;
                    $p['report']['approved'] = false;
                    break;
                case 'task-done':
                    $this->stop($p['tasks'][$taskIndex]['timer']);
                    $p['tasks'][$taskIndex]['done'] = ! $p['tasks'][$taskIndex]['done'];
                    $p['report']['approved'] = false;
                    break;
                case 'timer-start':
                    $this->require(! $item || ! $p['tasks'][$taskIndex]['done'], 'Completed tasks cannot be timed.');
                    foreach ($models as $other) {
                        $data = $other->id === $id ? $p : $other->data;
                        $this->stop($data['timer']);
                        foreach ($data['tasks'] as &$task) {
                            $this->stop($task['timer']);
                        } unset($task);
                        if ($other->id === $id) {
                            $p = $data;
                        } else {
                            $other->update(['data' => $data]);
                        }
                    }
                    if ($item) {
                        $p['tasks'][$taskIndex]['timer']['started'] = (int) (microtime(true) * 1000);
                    } else {
                        $p['timer']['started'] = (int) (microtime(true) * 1000);
                    } break;
                case 'timer-pause': case 'timer-stop':
                    if ($item) {
                        $this->stop($p['tasks'][$taskIndex]['timer']);
                    } else {
                        $this->stop($p['timer']);
                    } break;
                case 'deliverable-add':
                    $p['deliverables'][] = ['id' => (string) Str::uuid(), 'name' => $input['name'], 'ready' => false];
                    break;
                case 'deliverable-toggle':
                    $i = collect($p['deliverables'])->search(fn ($d) => $d['id'] === $item);
                    $this->require($i !== false, 'Deliverable not found.');
                    $p['deliverables'][$i]['ready'] = ! $p['deliverables'][$i]['ready'];
                    break;
                case 'report-save':
                    $selected = array_values(array_intersect(array_column($p['tasks'], 'id'), $input['selected'] ?? []));
                    $p['report'] = ['issues' => $input['issues'] ?? '', 'recommendations' => $input['recommendations'] ?? '', 'way' => $input['way'] ?? '', 'selected' => $selected, 'approved' => false];
                    break;
                case 'report-approve':
                    $this->require(count($p['tasks']) > 0 && collect($p['tasks'])->every('done', true), 'Complete all tasks first.');
                    $this->require(count($p['report']['selected']) > 0, 'Select report tasks and save the report first.');
                    $p['report']['approved'] = true;
                    break;
                case 'transmittal': $this->require(self::ready($p) && ! $p['transmittal'], 'Complete tasks, deliverables and report approval first.');
                    $p['transmittal'] = 'TRN-'.$id;
                    break;
                case 'coc': $this->require(self::ready($p) && (bool) $p['transmittal'] && ! $p['coc'], 'Issue the transmittal first.');
                    $p['coc'] = 'COC-'.$id;
                    break;
                case 'close':
                    $this->require(self::ready($p) && $p['transmittal'] && $p['coc'], 'Complete all delivery gates first.');
                    $this->stop($p['timer']);
                    foreach ($p['tasks'] as &$task) {
                        $this->stop($task['timer']);
                    } unset($task);
                    $p['closed'] = true;
                    break;
                case 'update': array_unshift($p['updates'], ['text' => $input['text'], 'at' => now()->toIso8601String()]);
                    break;
                case 'client-add': $p['actions'][] = ['id' => (string) Str::uuid(), 'subject' => $input['subject'], 'done' => false];
                    break;
                case 'client-done':
                    $i = collect($p['actions'])->search(fn ($a) => $a['id'] === $item);
                    $this->require($i !== false, 'Action not found.');
                    $p['actions'][$i]['done'] = ! $p['actions'][$i]['done'];
                    break;
                case 'file-add': $p['files'][] = $input['file'];
                    break;
                default: abort(404);
            }
            array_unshift($p['history'], ['at' => now()->toIso8601String(), 'action' => str_replace('-', ' ', $action)]);
            $model->update(['data' => $p]);
        }, 3);
    }
}
