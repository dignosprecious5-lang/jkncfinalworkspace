<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Models\Award;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index()
    {
        $trainings = Training::with(['assignments.employee'])
            ->latest()
            ->get();

        return view('human-capital.training', compact('trainings'));
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

        $training->update($validated);

        return back()->with('success', 'Training updated successfully.');
    }

    public function destroy(Training $training)
    {
        $training->delete();

        return back()->with('success', 'Training deleted successfully.');
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

        // generate certificate code
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