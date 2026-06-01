<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserPasswordController extends Controller
{
    public function update(Request $request, $id)
    {
        $authUser = Auth::user();

        if (!$authUser || !$authUser->isSuperAdmin()) {
            abort(403, 'Only SuperAdmin can reset user passwords.');
        }

        $user = User::findOrFail($id);

        if ($user->isSuperAdmin() && (int) $user->id !== (int) $authUser->id) {
            abort(403, 'You cannot reset another SuperAdmin password from this screen.');
        }

        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator, 'resetPassword')
                ->withInput()
                ->with('reset_password_user_id', $user->id);
        }

        $user->forceFill([
            'password' => Hash::make($validator->validated()['password']),
        ])->save();

        return redirect()
            ->back()
            ->with('success', 'Password reset successfully for ' . $user->name . '.')
            ->with('reset_password_success_user_id', $user->id);
    }
}
