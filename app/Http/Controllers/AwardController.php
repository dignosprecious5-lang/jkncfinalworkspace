<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Illuminate\Support\Facades\Auth;

class AwardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Award::with([
            'employee',
            'training',
            'assignment',
        ])->latest();

        /*
         * Admin / Super Admin:
         * - Can see all awards.
         *
         * Normal user:
         * - Can only see awards connected to their own Employee Profile.
         *
         * Important:
         * This uses users.email = employees.email.
         */
        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            $query->whereHas('employee', function ($employeeQuery) use ($user) {
                $employeeQuery->where('email', $user->email);
            });
        }

        $awards = $query->get();

        return view('human-capital.awards', compact('awards'));
    }
}