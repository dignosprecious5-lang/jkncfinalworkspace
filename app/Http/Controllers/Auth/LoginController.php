<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->must_change_password) {
                return redirect()->route('password.change');
            }

            if ($user->isSuperAdmin() || $user->isAdmin()) {
                return redirect()->route('townhall');
            }

            if ($user->isClient()) {
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

            if ($user->isDisabled()) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'Your account has been disabled or archived. Please contact your administrator.'])
                    ->onlyInput('email');
            }

            if ($user->must_change_password) {
                return redirect()
                    ->route('password.change')
                    ->with('warning', 'Please change your temporary password before accessing the system.');
            }

            if ($user->isSuperAdmin() || $user->isAdmin()) {
                return redirect()->route('townhall');
            }

            if ($user->isClient()) {
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
