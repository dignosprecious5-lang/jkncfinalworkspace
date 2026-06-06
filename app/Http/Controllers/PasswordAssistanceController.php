<?php

namespace App\Http\Controllers;

use App\Mail\PasswordAssistanceRequestMail;
use App\Models\AccountAuditLog;
use App\Models\PasswordAssistanceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PasswordAssistanceController extends Controller
{
    public function create()
    {
        return view('auth.password-assistance');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'registered_email' => ['required', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $assistanceRequest = PasswordAssistanceRequest::create([
            ...$validated,
            'submitted_at' => now(),
            'ip_address' => $request->ip(),
            'status' => 'Pending',
        ]);

        AccountAuditLog::record(
            'Password Assistance Request Submitted',
            null,
            'Password assistance request submitted for '.$validated['registered_email'],
            null,
            $request->ip()
        );

        $recipients = User::query()
            ->with('userPermission')
            ->where(function ($query) {
                $query->whereRaw('LOWER(role) = ?', ['superadmin'])
                    ->orWhereHas('userPermission', function ($permissionQuery) {
                        $permissionQuery->where('reset_user_password', true);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('archived_at')
                    ->where(function ($inner) {
                        $inner->whereNull('disabled_at')
                            ->where(function ($active) {
                                $active->whereNull('is_active')->orWhere('is_active', true);
                            });
                    });
            })
            ->get();

        foreach ($recipients as $recipient) {
            if ($recipient->email) {
                Mail::to($recipient->email)->send(new PasswordAssistanceRequestMail($assistanceRequest));
            }
        }

        return redirect()
            ->route('login')
            ->with('success', 'Your password assistance request was submitted. An authorized administrator will review it.');
    }
}
