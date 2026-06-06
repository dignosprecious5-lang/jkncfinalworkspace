<?php

namespace App\Http\Controllers;

use App\Models\AccountAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserStatusController extends Controller
{
    private function ensureCanDisableEnableAccounts(): void
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->canDisableEnableUserAccount()) {
            abort(403, 'Unauthorized to enable or disable user accounts.');
        }
    }

    public function disable(Request $request, $id)
    {
        $this->ensureCanDisableEnableAccounts();

        $authUser = Auth::user();
        $user = User::findOrFail($id);

        if ((int) $authUser->id === (int) $user->id) {
            return back()->with('error', 'You cannot disable your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'SuperAdmin accounts cannot be disabled from this screen.');
        }

        $reason = $request->input('disabled_reason');

        $user->forceFill([
            'is_active' => false,
            'disabled_at' => now(),
            'disabled_by' => $authUser->id,
            'disabled_reason' => $reason,
        ])->save();

        AccountAuditLog::record('User Disabled', $user, $reason ?: 'Account disabled.', $authUser, $request->ip());

        return back()->with('success', 'Account disabled successfully for ' . $user->name . '.');
    }

    public function enable(Request $request, $id)
    {
        $this->ensureCanDisableEnableAccounts();

        $authUser = Auth::user();
        $user = User::findOrFail($id);

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'SuperAdmin accounts cannot be enabled or disabled from this screen.');
        }

        $user->forceFill([
            'is_active' => true,
            'disabled_at' => null,
            'disabled_by' => null,
            'disabled_reason' => null,
        ])->save();

        AccountAuditLog::record('User Enabled', $user, 'Account enabled.', $authUser, $request->ip());

        return back()->with('success', 'Account enabled successfully for ' . $user->name . '.');
    }
}
