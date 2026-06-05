<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Models\Award;
use App\Models\Employee;
use App\Http\Controllers\Concerns\RequestsHumanCapitalApproval;
use App\Http\Controllers\Concerns\ScopesHumanCapitalRecords;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    use RequestsHumanCapitalApproval;
    use ScopesHumanCapitalRecords;
    public function index(Request $request)
    {
        $user = auth()->user();
        $canManageTraining = $this->canManageHumanCapitalModule('access_hc_training', true);
        $currentEmployee = $canManageTraining
            ? null
            : $this->currentHumanCapitalEmployee($user);

        $trainings = Training::query()
            ->with(['assignments' => function ($query) use ($canManageTraining, $currentEmployee) {
                $query->with('employee');

                if (! $canManageTraining) {
                    $query->where('employee_id', $currentEmployee?->id ?: 0);
                }
            }])
            ->when(! $canManageTraining, function ($query) use ($currentEmployee) {
                $query->whereHas('assignments', fn ($assignmentQuery) => $assignmentQuery->where('employee_id', $currentEmployee?->id ?: 0));
            })
            ->latest()
            ->get();

        if ($request->wantsJson()) {
            return response()->json(
                $trainings->map(function ($training) {
                    return [
                        'id' => $training->id,
                        'title' => $training->title,
                        'description' => $training->description,
                        'provider' => $training->provider,
                        'duration_value' => $training->duration_value,
                        'duration_unit' => $training->duration_unit,
                        'formatted_duration' => $training->formatted_duration,
                    ];
                })->values()
            );
        }

        return view('human-capital.training', compact('trainings', 'canManageTraining'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks,months',
        ]);

        Training::create($validated);

        return back()->with('success', 'Training added successfully.');
    }

    public function update(Request $request, Training $training)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks,months',
        ]);

        $this->requestHumanCapitalChange($request, 'Training', 'update', $training, $validated);

        return back()->with('success', 'Training update submitted for admin approval.');
    }

    public function destroy(Request $request, Training $training)
    {
        $this->requestHumanCapitalChange($request, 'Training', 'delete', $training);

        return back()->with('success', 'Training deletion submitted for admin approval.');
    }

    public function markCompleted($id)
    {
        $assignment = TrainingAssignment::findOrFail($id);

        $assignment->update([
            'status' => 'Completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Training marked as completed.');
    }

    public function issueCertificate($id)
    {
        $assignment = TrainingAssignment::with('training', 'employee')->findOrFail($id);

        if (!$assignment->completed_at) {
            return back()->with('error', 'Training must be completed first.');
        }

        $code = 'CERT-' . strtoupper(uniqid());

        $assignment->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
            'certificate_code' => $code,
        ]);

        Award::create([
            'employee_id' => $assignment->employee_id,
            'training_id' => $assignment->training_id,
            'training_assignment_id' => $assignment->id,
            'certificate_code' => $code,
            'issued_at' => now(),
        ]);

        return back()->with('success', 'Certificate issued successfully.');
    }
}
