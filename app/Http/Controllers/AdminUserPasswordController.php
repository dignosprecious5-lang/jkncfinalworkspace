<?php

namespace App\Http\Controllers;

use App\Models\AccountAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AdminUserPasswordController extends Controller
{
    private function ensureCanResetPasswords(): void
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->canResetUserPassword()) {
            abort(403, 'You do not have permission to reset user passwords.');
        }
    }

    public function update(Request $request, $id)
    {
        $this->ensureCanResetPasswords();

        $authUser = Auth::user();
        $user = User::findOrFail($id);

        if ($user->isSuperAdmin() && (int) $user->id !== (int) $authUser->id) {
            abort(403, 'You cannot reset another SuperAdmin password from this screen.');
        }

        if ($user->isDisabled()) {
            return back()->with('error', 'Password reset email was not sent because this account is disabled or archived.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            AccountAuditLog::record(
                'Password Reset Email Sent',
                $user,
                'Secure password reset link sent to '.$user->email,
                $authUser,
                $request->ip()
            );

            return redirect()
                ->back()
                ->with('success', 'Password reset email sent to '.$user->email.'.');
        }

        return redirect()
            ->back()
            ->with('error', __($status));
    }
}
