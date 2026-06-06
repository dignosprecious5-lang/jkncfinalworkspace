<?php

namespace App\Http\Controllers;

use App\Mail\UserAccountCreatedMail;
use App\Models\AccountAuditLog;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();

        if (!$authUser || !$authUser->hasPermission('manage_users')) {
            abort(403, 'Unauthorized.');
        }

        $users = User::with(['employeeProfile', 'contactProfile', 'userPermission'])
            ->whereRaw('LOWER(role) != ?', ['superadmin'])
            ->whereNull('archived_at')
            ->orderBy('name')
            ->paginate(10);

        $employeeOptions = Employee::with('user')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $contactOptions = Contact::with('user')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.users', compact('users', 'employeeOptions', 'contactOptions'));
    }

    private function splitUserName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '',
        ];
    }

    private function syncLinkedProfileToUser(User $user): void
    {
        $nameParts = $this->splitUserName((string) $user->name);

        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee) {
            $employee->update([
                'first_name' => $nameParts['first_name'] ?: $employee->first_name,
                'last_name' => $nameParts['last_name'] ?: $employee->last_name,
                'email' => $user->email,
                'work_email' => $user->email,
                'company_email' => $user->email,
            ]);
        }

        $contact = Contact::where('user_id', $user->id)->first();

        if ($contact) {
            $contact->update([
                'first_name' => $nameParts['first_name'] ?: $contact->first_name,
                'last_name' => $nameParts['last_name'] ?: $contact->last_name,
                'email' => $user->email,
            ]);
        }
    }

    private function generateTemporaryPassword(): string
    {
        return Str::password(14, true, true, false, false);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || !$authUser->canCreateUserAccount()) {
            abort(403, 'You do not have permission to create user accounts.');
        }

        $validated = $request->validate([
            'account_source' => ['required', Rule::in(['manual', 'employee', 'client'])],
            'employee_id' => ['nullable', Rule::exists('employees', 'id')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['Admin', 'Employee', 'Client'])],
        ]);

        $temporaryPassword = $this->generateTemporaryPassword();

        $user = DB::transaction(function () use ($validated, $temporaryPassword, $authUser, $request) {
            $employee = null;
            $contact = null;

            if ($validated['account_source'] === 'employee') {
                $employee = Employee::findOrFail($validated['employee_id']);

                if ($employee->user_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'employee_id' => 'This employee profile is already linked to a user account.',
                    ]);
                }

                if ($validated['role'] === 'Client') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'role' => 'Employee profiles cannot be linked to Client role.',
                    ]);
                }

                $validated['name'] = $validated['name'] ?: $employee->full_name;
                $validated['email'] = $validated['email'] ?: ($employee->work_email ?: $employee->email);
            }

            if ($validated['account_source'] === 'client') {
                $contact = Contact::findOrFail($validated['contact_id']);

                if ($contact->user_id) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'contact_id' => 'This contact is already linked to a client user account.',
                    ]);
                }

                $validated['role'] = 'Client';
                $validated['name'] = $validated['name'] ?: ($contact->full_name ?: trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')));
                $validated['email'] = $validated['email'] ?: $contact->email;
            }

            if (blank($validated['name'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => 'Full name is required.',
                ]);
            }

            if (blank($validated['email'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'email' => 'Email is required.',
                ]);
            }

            if (User::where('email', $validated['email'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'email' => 'This email is already used by another user account.',
                ]);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
                'is_active' => true,
                'can_edit_user_roles' => false,
                'can_delete_users' => false,
            ]);

            if ($employee) {
                $employee->update(['user_id' => $user->id]);
                $this->syncLinkedProfileToUser($user);
            }

            if ($contact) {
                $contact->update(['user_id' => $user->id]);
                $this->syncLinkedProfileToUser($user);
            }

            AccountAuditLog::record('User Created', $user, 'User account created and temporary password email sent.', $authUser, $request->ip());

            return $user;
        });

        Mail::to($user->email)->send(new UserAccountCreatedMail($user, $temporaryPassword, route('login')));

        return redirect()
            ->route('admin.users')
            ->with('success', 'User created successfully. A temporary password was emailed to ' . $user->email . '.');
    }

    public function update(Request $request, $id)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($id);

        if (!$authUser || !$authUser->canEditUserAccount()) {
            abort(403, 'You do not have permission to edit user accounts.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Superadmin role cannot be modified here.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['Admin', 'Employee', 'Client'])],
            'can_edit_user_roles' => ['nullable', 'boolean'],
            'can_delete_users' => ['nullable', 'boolean'],
        ]);

        $user->role = $validated['role'];

        if ($authUser->isSuperAdmin()) {
            $user->can_edit_user_roles = $request->boolean('can_edit_user_roles');
            $user->can_delete_users = $request->boolean('can_delete_users');
        }

        $user->save();

        $this->syncLinkedProfileToUser($user);

        AccountAuditLog::record('User Edited', $user, 'User account role/control values updated.', $authUser, $request->ip());

        return redirect()
            ->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $authUser = auth()->user();
        $user = User::with(['employeeProfile', 'contactProfile'])->findOrFail($id);

        if (!$authUser || !$authUser->canDeleteUserAccount()) {
            abort(403, 'You do not have permission to archive user accounts.');
        }

        if ($authUser->id === $user->id) {
            return back()->with('error', 'You cannot archive your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Superadmin cannot be archived here.');
        }

        $user->forceFill([
            'is_active' => false,
            'archived_at' => now(),
            'archived_by' => $authUser->id,
            'archived_reason' => 'Archived through User Account Management.',
        ])->save();

        AccountAuditLog::record('User Archived', $user, 'User archived instead of permanently deleted.', $authUser, $request->ip());

        return redirect()
            ->route('admin.users')
            ->with('success', 'User archived successfully. Records and audit history were preserved.');
    }
}
