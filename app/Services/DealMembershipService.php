<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealMembership;
use App\Models\User;
use App\Policies\DealPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DealMembershipService
{
    public function __construct(private readonly DealPolicy $policy)
    {
    }

    public function assign(Deal $deal, User $actor, int $userId, string $role): DealMembership
    {
        $this->assertRole($role);
        $this->authorizeRole($actor, $deal, $role);

        return DB::transaction(function () use ($deal, $actor, $userId, $role): DealMembership {
            $lockedDeal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $this->authorizeRole($actor, $lockedDeal, $role);

            $target = User::query()->lockForUpdate()->find($userId);
            if (! $target || $target->isDisabled() || $target->isClient()) {
                throw ValidationException::withMessages(['user_id' => 'The selected account is not eligible for Deal assignment.']);
            }
            if ((int) $target->id === (int) $actor->id && ! $actor->isAdmin() && ! $actor->isSuperAdmin()) {
                throw new AuthorizationException('Self-assignment requires Admin or SuperAdmin authority.');
            }

            $active = DealMembership::query()
                ->where('deal_id', $lockedDeal->id)
                ->where('user_id', $target->id)
                ->where('membership_role', $role)
                ->where('assignment_status', DealMembership::STATUS_ACTIVE)
                ->lockForUpdate()
                ->exists();

            if ($active) {
                throw ValidationException::withMessages(['user_id' => 'This user already has that active Deal role.']);
            }

            $now = now();

            return DealMembership::query()->create([
                'deal_id' => $lockedDeal->id,
                'user_id' => $target->id,
                'membership_role' => $role,
                'assignment_status' => DealMembership::STATUS_ACTIVE,
                'assigned_by_user_id' => $actor->id,
                'status_changed_by_user_id' => $actor->id,
                'assigned_at' => $now,
                'status_changed_at' => $now,
            ]);
        });
    }

    public function revoke(Deal $deal, User $actor, int $membershipId): DealMembership
    {
        return DB::transaction(function () use ($deal, $actor, $membershipId): DealMembership {
            $lockedDeal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $membership = DealMembership::query()
                ->where('deal_id', $lockedDeal->id)
                ->whereKey($membershipId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeRole($actor, $lockedDeal, $membership->membership_role);

            if ($membership->assignment_status !== DealMembership::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['membership' => 'This membership is already inactive.']);
            }

            if (in_array($membership->membership_role, [DealMembership::ROLE_LEAD_CONSULTANT, DealMembership::ROLE_LEAD_ASSOCIATE], true)) {
                $otherActiveLeadExists = DealMembership::query()
                    ->where('deal_id', $lockedDeal->id)
                    ->where('assignment_status', DealMembership::STATUS_ACTIVE)
                    ->whereIn('membership_role', [DealMembership::ROLE_LEAD_CONSULTANT, DealMembership::ROLE_LEAD_ASSOCIATE])
                    ->whereHas('user', fn ($query) => $query->where('is_active', true)->whereNull('disabled_at')->whereNull('archived_at')->where('role', '!=', 'Client'))
                    ->whereKeyNot($membership->id)
                    ->lockForUpdate()
                    ->exists();

                if (! $otherActiveLeadExists) {
                    throw ValidationException::withMessages(['membership' => 'Assign an active replacement Deal lead before removing the final active lead.']);
                }
            }

            $membership->forceFill([
                'assignment_status' => DealMembership::STATUS_INACTIVE,
                'status_changed_by_user_id' => $actor->id,
                'status_changed_at' => now(),
            ])->save();

            return $membership;
        });
    }

    private function authorizeRole(User $actor, Deal $deal, string $role): void
    {
        if (! $this->policy->manageMemberships($actor, $deal, $role)) {
            throw new AuthorizationException('You are not authorized to manage this Deal membership.');
        }
    }

    private function assertRole(string $role): void
    {
        if (! in_array($role, DealMembership::ROLES, true)) {
            throw ValidationException::withMessages(['membership_role' => 'The selected Deal membership role is invalid.']);
        }
    }
}
