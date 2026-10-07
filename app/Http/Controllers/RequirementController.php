<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequirementController extends Controller
{
    public function index(Request $request)
    {
        return view('requirements.index');
    }
}