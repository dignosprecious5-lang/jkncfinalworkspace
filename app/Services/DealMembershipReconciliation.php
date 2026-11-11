<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Builds a read-only, deterministic report for legacy Deal assignment data.
 * Consultant and associate fields may be proposed when identity is unique;
 * legacy lead fields are always held for an administrator decision.
 */
class DealMembershipReconciliation
{
    public function dryRun(): array
    {
        foreach (['deals', 'users', 'deal_memberships'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table '{$table}' is missing.");
            }
        }

        $users = User::query()->orderBy('id')->get(['id', 'name', 'email', 'role', 'is_active', 'disabled_at', 'archived_at']);
        $usersById = $users->keyBy(fn (User $user): int => (int) $user->id);
        $usersByToken = [];

        foreach ($users as $user) {
            foreach ([$user->name, $user->email, (string) $user->id] as $value) {
                $token = $this->normalize($value);
                if ($token !== '') {
                    $usersByToken[$token][(int) $user->id] = (int) $user->id;
                }
            }
        }

        $activeKeys = DB::table('deal_memberships')
            ->where('assignment_status', DealMembership::STATUS_ACTIVE)
            ->get(['deal_id', 'user_id', 'membership_role'])
            ->mapWithKeys(fn ($membership): array => [
                $this->key((int) $membership->deal_id, (int) $membership->user_id, (string) $membership->membership_role) => true,
            ])
            ->all();

        $proposed = [];
        $alreadyActive = [];
        $unresolved = [];
        $leadReview = [];
        $unassignedDeals = [];
        $deals = Deal::query()->orderBy('id')->get();

        foreach ($deals as $deal) {
            $hasAnyAssignment = false;

            foreach ([
                'assigned_consultant' => DealMembership::ROLE_CONSULTANT,
                'assigned_associate' => DealMembership::ROLE_ASSOCIATE,
            ] as $field => $role) {
                $value = trim((string) $deal->getAttribute($field));
                if ($value === '') {
                    continue;
                }

                $hasAnyAssignment = true;
                $ids = array_values($usersByToken[$this->normalize($value)] ?? []);
                if (count($ids) !== 1) {
                    $unresolved[] = [
                        'deal_id' => (int) $deal->id,
                        'source_field' => $field,
                        'legacy_value' => $value,
                        'reason' => count($ids) === 0 ? 'no_exact_user_match' : 'ambiguous_user_match',
                        'candidate_user_ids' => $ids,
                    ];
                    continue;
                }

                $user = $usersById[$ids[0]];
                if ($user->isDisabled() || $user->isClient()) {
                    $unresolved[] = [
                        'deal_id' => (int) $deal->id,
                        'source_field' => $field,
                        'legacy_value' => $value,
                        'reason' => 'matched_user_is_not_eligible',
                        'candidate_user_ids' => $ids,
                    ];
                    continue;
                }

                if ($deal->isArchived()) {
                    $unresolved[] = [
                        'deal_id' => (int) $deal->id,
                        'source_field' => $field,
                        'legacy_value' => $value,
                        'reason' => 'deal_is_archived',
                        'candidate_user_ids' => $ids,
                    ];
                    continue;
                }

                $mapping = [
                    'deal_id' => (int) $deal->id,
                    'user_id' => $ids[0],
                    'user_name' => (string) $user->name,
                    'membership_role' => $role,
                    'source_field' => $field,
                ];

                if (isset($activeKeys[$this->key((int) $deal->id, $ids[0], $role)])) {
                    $alreadyActive[] = $mapping;
                } else {
                    $proposed[] = $mapping;
                }
            }

            foreach (['lead_consultant', 'lead_associate_assigned'] as $field) {
                $value = trim((string) $deal->getAttribute($field));
                if ($value === '') {
                    continue;
                }

                $hasAnyAssignment = true;
                $leadReview[] = [
                    'deal_id' => (int) $deal->id,
                    'source_field' => $field,
                    'legacy_value' => $value,
                    'reason' => 'lead_roles_require_administrator_assignment',
                ];
            }

            if (! $hasAnyAssignment) {
                $unassignedDeals[] = (int) $deal->id;
            }
        }

        return [
            'mode' => 'dry_run',
            'deal_count' => $deals->count(),
            'proposed_memberships' => $proposed,
            'already_active_memberships' => $alreadyActive,
            'unresolved_assignments' => $unresolved,
            'lead_assignments_requiring_admin_review' => $leadReview,
            'unassigned_deal_ids' => $unassignedDeals,
            'summary' => [
                'proposed' => count($proposed),
                'already_active' => count($alreadyActive),
                'unresolved' => count($unresolved),
                'lead_review' => count($leadReview),
                'unassigned_deals' => count($unassignedDeals),
            ],
        ];
    }

    private function normalize(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function key(int $dealId, int $userId, string $role): string
    {
        return $dealId.':'.$userId.':'.$role;
    }
}
