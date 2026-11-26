<?php

namespace App\Services;

use App\Models\Transmittal;
use App\Models\User;

final class TransmittalV1Authorization
{
    public static function isAdministrator(?User $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->isSuperAdmin());
    }

    public static function canView(?User $user, Transmittal $transmittal): bool
    {
        if ($user === null || TransmittalWorkflowBoundary::isV2($transmittal)) {
            return false;
        }

        return self::isAdministrator($user)
            || self::ownsPreparerAssignment($user, $transmittal)
            || self::ownsReviewerAssignment($user, $transmittal);
    }

    public static function canPrepare(?User $user, Transmittal $transmittal): bool
    {
        return $user !== null
            && ! TransmittalWorkflowBoundary::isV2($transmittal)
            && (self::isAdministrator($user) || self::ownsPreparerAssignment($user, $transmittal));
    }

    public static function canApprove(?User $user, Transmittal $transmittal): bool
    {
        return $user !== null
            && ! TransmittalWorkflowBoundary::isV2($transmittal)
            && $user->hasPermission('approve_corporate')
            && self::ownsReviewerAssignment($user, $transmittal)
            && ! self::ownsPreparerAssignment($user, $transmittal);
    }

    public static function canManageAssignments(?User $user): bool
    {
        return self::isAdministrator($user);
    }

    public static function ownsPreparerAssignment(User $user, Transmittal $transmittal): bool
    {
        return $user->getKey() !== null
            && (string) $transmittal->v1_preparer_user_id === (string) $user->getKey();
    }

    public static function ownsReviewerAssignment(User $user, Transmittal $transmittal): bool
    {
        return $user->getKey() !== null
            && (string) $transmittal->v1_assigned_reviewer_id === (string) $user->getKey()
            && $user->hasPermission('approve_corporate');
    }
}
