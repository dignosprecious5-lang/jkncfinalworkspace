<?php

namespace App\Http\Controllers;

use App\Models\AccountAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->isSuperAdmin()) {
            abort(403, 'Only SuperAdmin can view the account audit trail.');
        }

        $query = AccountAuditLog::with(['affectedUser', 'performedBy'])
            ->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('action_performed', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('affectedUser', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('performedBy', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('action')) {
            $query->where('action_performed', $request->input('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(20)->withQueryString();

        $actions = AccountAuditLog::query()
            ->select('action_performed')
            ->whereNotNull('action_performed')
            ->distinct()
            ->orderBy('action_performed')
            ->pluck('action_performed');

        return view('admin.account-audit-trail', compact('logs', 'actions'));
    }
}
