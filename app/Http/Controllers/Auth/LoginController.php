<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'SuperAdmin' || $user->role === 'Admin') {
                return redirect()->route('townhall');
            }

            if (strtolower((string) $user->role) === 'client') {
                return redirect()->route('contacts.index');
            }

            return redirect()->route('townhall');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->role === 'SuperAdmin' || $user->role === 'Admin') {
                return redirect()->route('townhall');
            }

            if (strtolower((string) $user->role) === 'client') {
                return redirect()->route('contacts.index');
            }

            return redirect()->route('townhall');
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
