<?php


namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\TransmittalV1AssignmentHistory;
use Tests\Support\TransmittalV1Fixture;
use Tests\TestCase;

final class TransmittalV1AttachmentAuthorizationTest extends TestCase
{
    use TransmittalV1Fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTransmittalV1Fixture();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('transmittal_v1_regression');
        parent::tearDown();
    }

    public function test_assigned_v1_users_can_stream_item_and_supporting_attachments_privately(): void
    {
        $owner = $this->v1User();
        $record = $this->v1Record($owner);
        $item = $record->items()->firstOrFail();
        $supporting = $record->attachments()->firstOrFail();

        $itemResponse = $this->actingAs($owner)
            ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename=fixture-'.$record->id.'.pdf');
        $this->assertStringContainsString('private',(string) $itemResponse->headers->get('Cache-Control'));
            $this->assertStringContainsString('no-store',(string) $itemResponse->headers->get('Cache-Control'));
        $this->assertSame('isolated legacy item fixture bytes', $itemResponse->streamedContent());

        $supportingResponse = $this->actingAs($owner)
            ->get(route('transmittal.attachments.show', [$record->id, 'supporting', $supporting->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private',(string) $supportingResponse->headers->get('Cache-Control'));
            $this->assertStringContainsString('no-store',(string) $supportingResponse->headers->get('Cache-Control'));
        $this->assertSame('isolated legacy supporting attachment', $supportingResponse->streamedContent());
    }

    public function test_unassigned_v1_users_cannot_retrieve_attachments_by_direct_record_and_attachment_ids(): void
    {
        $owner = $this->v1User();
        $unassigned = $this->v1User();
        $record = $this->v1Record($owner);
        $item = $record->items()->firstOrFail();
        $supporting = $record->attachments()->firstOrFail();

        $this->actingAs($unassigned)
            ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
            ->assertNotFound();
        $this->actingAs($unassigned)
            ->get(route('transmittal.attachments.show', [$record->id, 'supporting', $supporting->id]))
            ->assertNotFound();
    }

    public function test_v1_reviewer_attachment_access_requires_both_record_assignment_and_approve_permission(): void
    {
        $owner = $this->v1User();
        $assignedReviewer = $this->v1User(true);
        $assignedRecord = $this->v1Record($owner, ['v1_assigned_reviewer_id' => $assignedReviewer->id]);
        $assignedItem = $assignedRecord->items()->firstOrFail();
        $this->actingAs($assignedReviewer)
            ->get(route('transmittal.attachments.show', [$assignedRecord->id, 'item', $assignedItem->id]))
            ->assertOk();

        $withoutPermission = $this->v1User();
        $deniedRecord = $this->v1Record($owner, ['v1_assigned_reviewer_id' => $withoutPermission->id]);
        $deniedItem = $deniedRecord->items()->firstOrFail();
        $this->actingAs($withoutPermission)
            ->get(route('transmittal.attachments.show', [$deniedRecord->id, 'item', $deniedItem->id]))
            ->assertNotFound();

        $permissionOnly = $this->v1User(true);
        $unassignedRecord = $this->v1Record($owner);
        $unassignedItem = $unassignedRecord->items()->firstOrFail();
        $this->actingAs($permissionOnly)
            ->get(route('transmittal.attachments.show', [$unassignedRecord->id, 'item', $unassignedItem->id]))
            ->assertNotFound();
    }

    public function test_v1_attachment_lookup_rejects_cross_parent_child_ids_and_malformed_stored_paths(): void
    {
        $firstOwner = $this->v1User();
        $secondOwner = $this->v1User();
        $first = $this->v1Record($firstOwner);
        $second = $this->v1Record($secondOwner);
        $firstItem = $first->items()->firstOrFail();
        $secondItem = $second->items()->firstOrFail();

        $this->actingAs($firstOwner)
            ->get(route('transmittal.attachments.show', [$first->id, 'item', $secondItem->id]))
            ->assertNotFound();

        DB::table('transmittal_items')->where('id', $firstItem->id)->update(['attachment_path' => '../private/secret.txt']);
        $this->actingAs($firstOwner)
            ->get(route('transmittal.attachments.show', [$first->id, 'item', $firstItem->id]))
            ->assertNotFound();
    }

    public function test_v1_attachment_stream_rejects_a_symlink_escape_from_the_public_disk(): void
    {
        $owner = $this->v1User();
        $record = $this->v1Record($owner);
        $item = $record->items()->firstOrFail();
        $publicRoot = Storage::disk('public')->path('');
        $privateRoot = Storage::disk('local')->path('');
        $linkDirectory = $publicRoot.DIRECTORY_SEPARATOR.'transmittal'.DIRECTORY_SEPARATOR.'links';
        $linkPath = $linkDirectory.DIRECTORY_SEPARATOR.'escape.txt';
        $target = $privateRoot.DIRECTORY_SEPARATOR.'private-target.txt';
        @mkdir($linkDirectory, 0775, true);
        @mkdir($privateRoot, 0775, true);
        file_put_contents($target, 'private test target');

        if (! @symlink($target, $linkPath)) {
            @unlink($target);
            $this->markTestSkipped('This Windows test identity cannot create a filesystem symlink.');
        }

        try {
            DB::table('transmittal_items')->where('id', $item->id)->update([
                'attachment_path' => 'transmittal/links/escape.txt',
            ]);
            $this->actingAs($owner)
                ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
                ->assertNotFound();
        } finally {
            @unlink($linkPath);
            @rmdir($linkDirectory);
            @unlink($target);
        }
    }

    public function test_v1_preview_and_data_response_use_authorized_attachment_routes_instead_of_public_urls(): void
    {
        $owner = $this->v1User();
        $record = $this->v1Record($owner);
        $item = $record->items()->firstOrFail();
        $expected = route('transmittal.attachments.show', [$record->id, 'item', $item->id]);

        $this->actingAs($owner)
            ->get('/transmittal/'.$record->id.'/preview')
            ->assertOk()
            ->assertSee($expected, false)
            ->assertDontSee('storage/'.$item->attachment_path, false);

        $this->actingAs($owner)
            ->getJson('/transmittal/data?workflow_status=uploaded')
            ->assertOk()
            ->assertJsonPath('data.0.items.0.attachment_url', $expected)
            ->assertDontSee("asset('storage/", false);
    }

    public function test_authorized_v1_email_delivery_still_attaches_both_stored_attachment_types(): void
    {
        $owner = $this->v1User();
        $record = $this->v1Record($owner);
        $item = $record->items()->firstOrFail();
        $supporting = $record->attachments()->firstOrFail();
        $expectedFiles = [
            [Storage::disk('public')->path($item->attachment_path), basename($item->attachment_path)],
            [Storage::disk('public')->path($supporting->file_path), $supporting->original_name],
        ];

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        Pdf::shouldReceive('loadView')->once()->with('transmittal.preview-pdf', Mockery::type('array'))->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->once()->with(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->andReturnSelf();
        $pdf->shouldReceive('output')->once()->andReturn('isolated transmittal PDF bytes');

        Mail::shouldReceive('send')->once()->andReturnUsing(function ($view, $data, $callback) use ($record, $expectedFiles): void {
            $this->assertSame('emails.transmittal-sent', $view);
            $message = Mockery::mock();
            $message->shouldReceive('to')->once()->with('recipient@example.test')->andReturnSelf();
            $message->shouldReceive('subject')->once()->with('Transmittal Form '.$record->transmittal_no)->andReturnSelf();
            $message->shouldReceive('attachData')->once()->with(
                'isolated transmittal PDF bytes',
                'transmittal-'.\Illuminate\Support\Str::slug((string) $record->transmittal_no, '-').'.pdf',
                ['mime' => 'application/pdf']
            )->andReturnSelf();
            foreach ($expectedFiles as [$path, $name]) {
                $message->shouldReceive('attach')->once()->with($path, ['as' => $name])->andReturnSelf();
            }
            $callback($message);
        });

        $this->actingAs($owner)->post('/transmittal/'.$record->id.'/send-email')->assertRedirect();
    }

    public function test_v1_reviewer_assignment_is_admin_only_audited_and_revoked_access_takes_effect_immediately(): void
    {
        $owner = $this->v1User();
        $admin = $this->v1User();
        $admin->forceFill(['role' => 'admin'])->save();
        $reviewer = $this->v1User(true);
        $replacement = $this->v1User(true);
        $record = $this->v1Record($owner);
        $item = $record->items()->firstOrFail();

        $this->actingAs($owner)->post(route('transmittal.v1.assignment.update', $record), [
            'assignment_type' => 'reviewer', 'assignee_id' => $reviewer->id, 'reason' => 'Assign reviewed staff',
        ])->assertForbidden();
        $this->assertDatabaseCount('transmittal_v1_assignment_histories', 0);

        $this->actingAs($admin)->post(route('transmittal.v1.assignment.update', $record), [
            'assignment_type' => 'reviewer', 'assignee_id' => $reviewer->id, 'reason' => 'Assign reviewed staff',
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($reviewer)
            ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
            ->assertOk();

        $this->actingAs($admin)->post(route('transmittal.v1.assignment.update', $record), [
            'assignment_type' => 'reviewer', 'assignee_id' => $replacement->id, 'reason' => 'Reassign reviewer',
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($reviewer)
            ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
            ->assertNotFound();
        $this->actingAs($replacement)
            ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
            ->assertOk();

        $this->actingAs($admin)->post(route('transmittal.v1.assignment.update', $record), [
            'assignment_type' => 'reviewer', 'assignee_id' => null, 'reason' => 'Revoke reviewer access',
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($replacement)
            ->get(route('transmittal.attachments.show', [$record->id, 'item', $item->id]))
            ->assertNotFound();

        $history = TransmittalV1AssignmentHistory::query()->where('transmittal_id', $record->id)->orderBy('id')->get();
        $this->assertSame(['assigned', 'reassigned', 'revoked'], $history->pluck('action')->all());
        $this->assertSame([$admin->id], $history->pluck('actor_user_id')->unique()->all());
        $this->assertSame($reviewer->id, $history[1]->previous_assignee_id);
        $this->assertSame($replacement->id, $history[1]->new_assignee_id);
        $this->expectException(\LogicException::class);
            $history[0]->update(['reason' => 'tampered']);
    }

    public function test_only_an_admin_can_grant_the_v2_reviewer_permission_and_grants_default_to_false(): void
    {
        $admin = $this->v1User();
        $admin->forceFill(['role' => 'admin'])->save();
        DB::table('user_permissions')->where('user_id', $admin->id)->update(['manage_users' => true]);
        $target = $this->v1User();
        $this->assertDatabaseHas('user_permissions', ['user_id' => $target->id, 'transmittal_v2_review' => false]);

        $this->actingAs($admin)->post(route('admin.user-permissions.update', $target->id), [
            'manage_users' => '1', 'access_admin_dashboard' => '1', 'transmittal_v2_review' => '1',
        ])->assertRedirect(route('admin.user-permissions'));
        $this->assertDatabaseHas('user_permissions', ['user_id' => $target->id, 'transmittal_v2_review' => true]);

        $nonAdmin = $this->v1User();
        DB::table('user_permissions')->where('user_id', $nonAdmin->id)->update(['manage_users' => true]);
        $other = $this->v1User();
        $this->actingAs($nonAdmin)->post(route('admin.user-permissions.update', $other->id), [
            'manage_users' => '1', 'access_admin_dashboard' => '1', 'transmittal_v2_review' => '1',
        ])->assertForbidden();
        $this->assertDatabaseHas('user_permissions', ['user_id' => $other->id, 'transmittal_v2_review' => false]);
    }

}
