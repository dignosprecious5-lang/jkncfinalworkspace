<?php

// app/Http/Controllers/TrainingController.php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Display training page
     */
    public function index()
    {
        $trainings = Training::with(['assignments.employee'])
            ->latest()
            ->get();

        if (request()->wantsJson()) {
            return response()->json(
                $trainings->map(fn (Training $training) => [
                    'id' => $training->id,
                    'title' => $training->title,
                    'description' => $training->description,
                    'provider' => $training->provider,
                    'duration_value' => $training->duration_value,
                    'duration_unit' => $training->duration_unit,
                    'formatted_duration' => $training->formatted_duration,
                ])
            );
        }

        return view('human-capital.training', compact('trainings'));
    }

    /**
     * Store new training
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:minutes,hours,days,weeks,months',
        ]);

        Training::create([
            'title' => $request->title,
            'description' => $request->description,
            'provider' => $request->provider,
            'duration_value' => $request->duration_value,
            'duration_unit' => $request->duration_unit,
        ]);

        return redirect()->back()->with('success', 'Training added successfully.');
    }
}
