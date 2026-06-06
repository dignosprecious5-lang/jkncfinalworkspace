<?php

namespace App\Http\Controllers;

use App\Models\AccountAuditLog;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminUserAccountController extends Controller
{
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

    public function update(Request $request, $id)
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->canEditUserAccount()) {
            abort(403, 'You do not have permission to edit user accounts.');
        }

        $user = User::findOrFail($id);

        if ($user->isSuperAdmin() && (int) $user->id !== (int) $authUser->id) {
            abort(403, 'You cannot edit another SuperAdmin account from this screen.');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(['Admin', 'Employee', 'Client'])],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'editAccount')
                ->withInput()
                ->with('edit_account_user_id', $user->id);
        }

        $validated = $validator->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'can_edit_user_roles' => $request->boolean('can_edit_user_roles'),
            'can_delete_users' => $request->boolean('can_delete_users'),
        ];

        if ($user->email !== $validated['email']) {
            $payload['email_verified_at'] = null;
        }

        $remarks = 'Account details updated.';

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
            $payload['must_change_password'] = true;
            $payload['temporary_password_expires_at'] = now()->addDays(7);
            $remarks .= ' Password changed by administrator and marked for first-login reset.';
        }

        $user->forceFill($payload)->save();

        $this->syncLinkedProfileToUser($user);

        AccountAuditLog::record('User Edited', $user, $remarks, $authUser, $request->ip());

        return redirect()
            ->back()
            ->with('success', 'Account updated successfully for ' . $user->name . '.')
            ->with('edit_account_success_user_id', $user->id);
    }
}
