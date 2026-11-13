<?php

namespace App\Services;

use App\Models\User;

/**
 * V2-only authorization helpers. This class does not change shared User behavior.
 */
final class TransmittalV2Authorization
{
    public static function canApprove(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->isAdmin()
            || $user->isSuperAdmin();
    }

    public static function ownsTransmittal(?User $user, mixed $preparedBy): bool
    {
        return $user !== null
            && $user->getKey() !== null
            && $preparedBy !== null
            && (string) $preparedBy === (string) $user->getKey();
    }
}
