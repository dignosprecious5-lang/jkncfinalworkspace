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
        $trainings = Training::latest()->get();

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
            'duration' => 'nullable|string|max:255',
        ]);

        Training::create([
            'title' => $request->title,
            'description' => $request->description,
            'provider' => $request->provider,
            'duration' => $request->duration,
        ]);

        return redirect()->back()->with('success', 'Training added successfully.');
    }
}