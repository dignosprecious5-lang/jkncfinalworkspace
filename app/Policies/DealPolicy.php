<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\DealMembership;
use App\Models\User;

class DealPolicy
{
    public function view(User $user, Deal $deal): bool
    {
        if ($user->isDisabled()) {
            return false;
        }

        return $user->isAdmin()
            || $user->isSuperAdmin()
            || $deal->userCanAccess($user);
    }

    public function update(User $user, Deal $deal): bool
    {
        if ($deal->isArchived() || $user->isDisabled()) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isClient()) {
            return false;
        }

        return $deal->activeMemberships()
            ->where('user_id', $user->id)
            ->whereIn('membership_role', DealMembership::ROLES)
            ->exists();
    }

    public function manageMemberships(User $user, Deal $deal, string $role): bool
    {
        if ($deal->isArchived() || ! in_array($role, DealMembership::ROLES, true) || $user->isDisabled() || $user->isClient()) {
            return false;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if (in_array($role, [DealMembership::ROLE_LEAD_CONSULTANT, DealMembership::ROLE_LEAD_ASSOCIATE], true)) {
            return false;
        }

        return $deal->activeMemberships()
            ->where('user_id', $user->id)
            ->whereIn('membership_role', [DealMembership::ROLE_LEAD_CONSULTANT, DealMembership::ROLE_LEAD_ASSOCIATE])
            ->exists();
    }

    public function manageLeadRoles(User $user, Deal $deal): bool
    {
        return ! $deal->isArchived()
            && ! $user->isDisabled()
            && ! $user->isClient()
            && ($user->isAdmin() || $user->isSuperAdmin());
    }
}
