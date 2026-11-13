<?php

namespace App\Services\TransmittalV2;

use App\Models\User;

/** Fail-closed user-id designation; role labels alone grant no resend authority. */
final class TransmittalManualResendAuthorization
{
    public function allows(?User $user): bool
    {
        if ($user === null || ! $user->exists || $user->isDisabled()) {
            return false;
        }
        $id = (int) $user->getKey();
        $operationsManagers = array_map('intval', (array) config('transmittal_v2.dispatch.operations_manager_user_ids', []));
        $designatedAdmins = array_map('intval', (array) config('transmittal_v2.dispatch.designated_admin_user_ids', []));

        return in_array($id, $operationsManagers, true)
            || (($user->isAdmin() || $user->isSuperAdmin()) && in_array($id, $designatedAdmins, true));
    }
}
