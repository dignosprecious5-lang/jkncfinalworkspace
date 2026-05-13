<?php

namespace App\Http\Controllers;

use App\Models\Award;

class AwardController extends Controller
{
    public function index()
    {
        $awards = Award::with([
                'employee',
                'training',
                'assignment'
            ])
            ->latest()
            ->get();

        return view('human-capital.awards', compact('awards'));
    }
}