<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();

        if (!$authUser || !$authUser->hasPermission('manage_users')) {
            abort(403, 'Unauthorized.');
        }

        $users = User::with(['employeeProfile', 'contactProfile'])
            ->whereRaw('LOWER(role) != ?', ['superadmin'])
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

    public function store(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || !$authUser->hasPermission('manage_users')) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'account_source' => ['required', Rule::in(['manual', 'employee', 'client'])],
            'employee_id' => ['nullable', Rule::exists('employees', 'id')],
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['Admin', 'Employee', 'Client'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated) {
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
                $validated['name'] = $validated['name'] ?: ($contact->full_name ?: trim(($contact->first_name ?? '').' '.($contact->last_name ?? '')));
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
                'password' => Hash::make($validated['password']),
                'can_edit_user_roles' => false,
                'can_delete_users' => false,
            ]);

            if ($employee) {
                $employee->update([
                    'user_id' => $user->id,
                    'work_email' => $employee->work_email ?: $user->email,
                    'email' => $employee->work_email ?: $user->email,
                ]);
            }

            if ($contact) {
                $contact->update([
                    'user_id' => $user->id,
                ]);
            }
        });

        return redirect()
            ->route('admin.users')
            ->with('success', 'User created and linked successfully.');
    }

    public function update(Request $request, $id)
    {
        $authUser = auth()->user();
        $user = User::findOrFail($id);

        if (!$authUser || !$authUser->hasPermission('manage_users')) {
            abort(403, 'You do not have permission to edit user roles.');
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

        return redirect()
            ->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $authUser = auth()->user();
        $user = User::with(['employeeProfile', 'contactProfile'])->findOrFail($id);

        if (!$authUser || !$authUser->hasPermission('manage_users')) {
            abort(403, 'You do not have permission to delete users.');
        }

        if ($authUser->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Superadmin cannot be deleted here.');
        }

        DB::transaction(function () use ($user) {
            if ($user->employeeProfile) {
                $user->employeeProfile->update(['user_id' => null]);
            }

            if ($user->contactProfile) {
                $user->contactProfile->update(['user_id' => null]);
            }

            $user->delete();
        });

        return redirect()
            ->route('admin.users')
            ->with('success', 'User deleted successfully.');
    }
}
