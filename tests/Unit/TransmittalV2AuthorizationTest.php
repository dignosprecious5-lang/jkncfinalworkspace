<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Transmittal;
use App\Models\User;
use App\Policies\TransmittalV2Policy;
use App\Services\TransmittalV2Authorization;
use PHPUnit\Framework\TestCase;

final class TransmittalV2AuthorizationTest extends TestCase
{
    public function test_guests_and_null_owner_records_do_not_match(): void
    {
        self::assertFalse(TransmittalV2Authorization::canApprove(null));
        self::assertFalse(TransmittalV2Authorization::ownsTransmittal(null, null));

        $guestLike = new User();
        self::assertFalse(TransmittalV2Authorization::ownsTransmittal($guestLike, null));
    }

    public function test_local_admin_roles_are_recognized_without_shared_user_changes(): void
    {
        $admin = new User();
        $admin->setAttribute('id', 10);
        $admin->setAttribute('role', 'admin');

        $superAdmin = new User();
        $superAdmin->setAttribute('id', 11);
        $superAdmin->setAttribute('role', 'superadmin');

        $employee = new User();
        $employee->setAttribute('id', 12);
        $employee->setAttribute('role', 'employee');

        self::assertTrue(TransmittalV2Authorization::canApprove($admin));
        self::assertTrue(TransmittalV2Authorization::canApprove($superAdmin));
        self::assertFalse(TransmittalV2Authorization::canApprove($employee));
        self::assertFalse(TransmittalV2Authorization::ownsTransmittal($admin, null));
    }

    public function test_policy_preserves_separation_of_duties_and_record_ownership(): void
    {
        $owner = new User();
        $owner->setAttribute('id', 21);
        $owner->setAttribute('role', 'employee');

        $admin = new User();
        $admin->setAttribute('id', 22);
        $admin->setAttribute('role', 'admin');

        $record = new Transmittal();
        $record->setAttribute('id', 31);
        $record->setAttribute('prepared_by', 21);
        $record->setAttribute('status', 'pending_approval');
        $record->setAttribute('workflow_version', 'v2');
        $record->exists = true;
        $record->syncOriginal();

        $policy = new TransmittalV2Policy();
        self::assertTrue($policy->view($owner, $record));
        self::assertFalse($policy->approve($owner, $record));
        self::assertTrue($policy->view($admin, $record));
        self::assertTrue($policy->approve($admin, $record));

        $foreign = new Transmittal();
        $foreign->setAttribute('id', 32);
        $foreign->setAttribute('prepared_by', 99);
        $foreign->setAttribute('status', 'pending_approval');
        $foreign->setAttribute('workflow_version', 'v2');
        $foreign->exists = true;
        $foreign->syncOriginal();
        self::assertFalse($policy->view($owner, $foreign));
    }
    public function test_every_v2_policy_denies_historical_v1_unclassified_and_unsaved_parents(): void
    {
        $admin = new User();
        $admin->setAttribute('id', 22);
        $admin->setAttribute('role', 'admin');
        $policy = new TransmittalV2Policy();

        foreach ([null, 'v1', 'V2', ''] as $version) {
            $record = new Transmittal();
            $record->setRawAttributes(['id' => 31, 'prepared_by' => 21,
                'workflow_version' => $version, 'status' => 'pending_approval'], true);
            $record->exists = true;
            foreach (['view', 'viewAttachments', 'approve', 'reject', 'transmit', 'uploadProof'] as $ability) {
                self::assertFalse($policy->{$ability}($admin, $record));
            }
            // Changing only hydrated attributes must not forge persisted classification.
            $record->setAttribute('workflow_version', 'v2');
            self::assertFalse($policy->view($admin, $record));
        }

        $unsaved = new Transmittal();
        $unsaved->setRawAttributes(['workflow_version' => 'v2'], true);
        self::assertFalse($policy->view($admin, $unsaved));
    }

}
