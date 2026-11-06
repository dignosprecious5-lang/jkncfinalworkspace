<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\DealProposalApprovalEvent;
use App\Models\DealProposalVersion;
use App\Models\DealStage;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\DealsMysqlTestCase;

class DealClientProposalApprovalTest extends DealsMysqlTestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
    }

    private const TOKEN = 'client-approval-test-token';

    private const FIXTURE_TIME = '2026-10-29 09:00:00';

    private Deal $deal;

    private DealProposal $proposal;

    private function actingAsProposalEditor(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'Admin',
            'must_change_password' => false,
        ]));
    }

    private function createIssuedVersion(string $token = self::TOKEN, ?\DateTimeInterface $expiresAt = null): DealProposalVersion
    {
        $expiry = $expiresAt ?? now()->addDays(2);
        return DealProposalVersion::query()->create([
            'deal_proposal_id' => $this->proposal->id,
            'version_number' => 1,
            'snapshot_schema_version' => 1,
            'resolved_proposal_data' => ['client_name' => 'Snapshot Client'],
            'commercial_values' => ['total' => '100.00'],
            'source_metadata' => ['captured_from' => 'test-fixture'],
            'document_identifiers' => ['reference_id' => 'SYNTHETIC-APPROVAL'],
            'rendered_html' => '<article>Issued proposal fixture.</article>',
            'artifact_private_path' => 'deal-proposal-versions/'.$this->proposal->id.'/issued-approval.pdf',
            'artifact_sha256' => str_repeat('a', 64),
            'issued_token_sha256' => hash('sha256', $token),
            'issued_to_email' => 'client@example.invalid',
            'issued_at' => now(),
            'client_access_expires_at' => $expiry,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse(self::FIXTURE_TIME));

        $contact = Contact::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'client@example.invalid',
        ]);

        $this->deal = Deal::query()->create([
            'contact_id' => $contact->id,
            'deal_name' => 'Synthetic approval test deal',
            'stage' => 'Proposal',
        ]);

        $this->proposal = $this->deal->proposal()->create([
            'status' => 'sent',
            'client_access_token' => self::TOKEN,
            'client_access_expires_at' => now()->addDays(2),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_valid_approval_records_the_client_and_moves_the_deal_to_negotiation(): void
    {
        $approvedAt = Carbon::parse('2026-10-29 10:00:00');
        Carbon::setTestNow($approvedAt);

        $response = $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Original Client',
            'approval_note' => 'Approved as proposed.',
        ]);

        $response->assertRedirect(route('deals.proposal.client.show', ['token' => self::TOKEN]));
        $response->assertSessionHas('success', 'Proposal approved successfully. Thank you.');

        $this->proposal->refresh();
        $this->deal->refresh();

        $this->assertSame('approved', $this->proposal->status);
        $this->assertTrue($this->proposal->client_approved_at->equalTo($approvedAt));
        $this->assertSame('Original Client', $this->proposal->client_approved_by_name);
        $this->assertSame('Approved as proposed.', $this->proposal->client_approval_note);
        $this->assertSame('Approved', $this->deal->proposal_decision);
        $this->assertSame('Negotiation', $this->deal->stage);
        $this->assertSame(1, DealStage::query()->where('name', 'Negotiation')->count());
        $this->assertSame(0, DealProposalApprovalEvent::query()->count(), 'Legacy approval must not create version history.');
    }

    public function test_first_approval_of_issued_version_creates_event_for_exact_version(): void
    {
        $version = $this->createIssuedVersion();
        $approvedAt = Carbon::parse('2026-10-29 10:00:00');
        Carbon::setTestNow($approvedAt);

        $response = $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Issued Version Client',
            'approval_note' => 'Accepted this issued version.',
        ]);

        $response->assertRedirect(route('deals.proposal.client.show', ['token' => self::TOKEN]));
        $response->assertSessionHas('success', 'Proposal approved successfully. Thank you.');
        $event = DealProposalApprovalEvent::query()->sole();
        $this->assertSame($version->id, $event->deal_proposal_version_id);
        $this->assertSame('client_approved', $event->event_type);
        $this->assertSame('client-approval-version-'.$version->id, $event->idempotency_key);
        $this->assertSame('Issued Version Client', $event->actor_name);
        $this->assertSame('Accepted this issued version.', $event->note);
        $this->assertTrue($event->occurred_at->equalTo($approvedAt));
        $this->assertSame('Issued Version Client', $this->proposal->fresh()->client_approved_by_name);
        $this->assertSame('Accepted this issued version.', $this->proposal->fresh()->client_approval_note);
        $this->assertSame('Approved', $this->deal->fresh()->proposal_decision);
        $this->assertSame('Negotiation', $this->deal->fresh()->stage);
    }

    public function test_repeated_approval_preserves_the_first_record_and_does_not_repeat_stage_transition(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));
        $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Original Client',
            'approval_note' => 'Original approval note.',
        ])->assertRedirect(route('deals.proposal.client.show', ['token' => self::TOKEN]));

        $this->proposal->refresh();
        $originalTimestamp = $this->proposal->client_approved_at->copy();
        $originalName = $this->proposal->client_approved_by_name;
        $originalNote = $this->proposal->client_approval_note;

        Carbon::setTestNow(Carbon::parse('2026-10-30 11:00:00'));
        $response = $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Different Client',
            'approval_note' => 'Replacement note.',
        ]);

        $response->assertRedirect(route('deals.proposal.client.show', ['token' => self::TOKEN]));
        $response->assertSessionHas('success', 'Proposal approved successfully. Thank you.');

        $this->proposal->refresh();
        $this->deal->refresh();

        $this->assertTrue($this->proposal->client_approved_at->equalTo($originalTimestamp));
        $this->assertSame($originalName, $this->proposal->client_approved_by_name);
        $this->assertSame($originalNote, $this->proposal->client_approval_note);
        $this->assertSame('Approved', $this->deal->proposal_decision);
        $this->assertSame('Negotiation', $this->deal->stage);
        $this->assertSame(1, DealStage::query()->where('name', 'Negotiation')->count());
    }

    public function test_repeated_issued_version_approval_preserves_parent_fields_and_single_event(): void
    {
        $version = $this->createIssuedVersion();
        $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Original Version Client',
            'approval_note' => 'Original version note.',
        ])->assertRedirect(route('deals.proposal.client.show', ['token' => self::TOKEN]));
        $original = $this->proposal->fresh();
        $timestamp = $original->client_approved_at->copy();
        $event = DealProposalApprovalEvent::query()->sole();

        Carbon::setTestNow(Carbon::parse('2026-10-30 11:00:00'));
        $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Replacement Client',
            'approval_note' => 'Replacement note.',
        ])->assertSessionHas('success', 'Proposal approved successfully. Thank you.');

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertTrue($this->proposal->client_approved_at->equalTo($timestamp));
        $this->assertSame('Original Version Client', $this->proposal->client_approved_by_name);
        $this->assertSame('Original version note.', $this->proposal->client_approval_note);
        $this->assertSame(1, DealProposalApprovalEvent::query()->count());
        $this->assertSame($event->id, DealProposalApprovalEvent::query()->sole()->id);
        $this->assertSame($version->id, DealProposalApprovalEvent::query()->sole()->deal_proposal_version_id);
        $this->assertSame('Negotiation', $this->deal->stage);
        $this->assertSame(1, DealStage::query()->where('name', 'Negotiation')->count());
    }

    public function test_expired_token_is_rejected_without_approval_or_deal_changes(): void
    {
        $this->createIssuedVersion();
        $this->proposal->forceFill(['client_access_expires_at' => now()->subSecond()])->save();

        $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Late Client',
            'approval_note' => 'Too late.',
        ])->assertForbidden();

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertNull($this->proposal->client_approved_by_name);
        $this->assertNull($this->proposal->client_approval_note);
        $this->assertSame('sent', $this->proposal->status);
        $this->assertNull($this->deal->proposal_decision);
        $this->assertSame('Proposal', $this->deal->stage);
        $this->assertSame(0, DealProposalApprovalEvent::query()->count());
    }

    public function test_invalid_token_is_not_found_without_approval_or_deal_changes(): void
    {
        $this->createIssuedVersion();
        $this->post(route('deals.proposal.client.approve', ['token' => 'unknown-token']), [
            'client_name' => 'Unknown Client',
            'approval_note' => 'Must not be recorded.',
        ])->assertNotFound();

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertNull($this->proposal->client_approved_by_name);
        $this->assertNull($this->proposal->client_approval_note);
        $this->assertSame('sent', $this->proposal->status);
        $this->assertNull($this->deal->proposal_decision);
        $this->assertSame('Proposal', $this->deal->stage);
        $this->assertSame(0, DealProposalApprovalEvent::query()->count());
    }

    public function test_superseded_version_token_cannot_approve_or_create_an_event(): void
    {
        $this->createIssuedVersion();
        $this->proposal->forceFill([
            'client_access_token' => 'new-current-token',
            'client_access_expires_at' => now()->addDays(2),
        ])->save();

        $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Superseded Client',
            'approval_note' => 'Must not be recorded.',
        ])->assertNotFound();

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('new-current-token', $this->proposal->client_access_token);
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertSame('sent', $this->proposal->status);
        $this->assertNull($this->deal->proposal_decision);
        $this->assertSame('Proposal', $this->deal->stage);
        $this->assertSame(0, DealProposalApprovalEvent::query()->count());
    }

    public function test_expiry_is_rechecked_after_approval_acquires_deal_lock(): void
    {
        $expiry = now()->addMinute();
        $this->createIssuedVersion(self::TOKEN, $expiry);
        $this->proposal->forceFill(['client_access_expires_at' => $expiry])->save();
        $clockAdvanced = false;
        DB::listen(function (QueryExecuted $query) use (&$clockAdvanced): void {
            if (! $clockAdvanced && preg_match('/\bfrom\s+[\x60"]?deals\b/i', $query->sql) === 1) {
                $clockAdvanced = true;
                Carbon::setTestNow(Carbon::parse('2026-10-29 09:02:00'));
            }
        });

        $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
            'client_name' => 'Expired Version Client',
        ])->assertForbidden();

        $this->assertTrue($clockAdvanced, 'The clock must advance after the Deal lock is acquired.');
        $this->assertNull($this->proposal->fresh()->client_approved_at);
        $this->assertNull($this->deal->fresh()->proposal_decision);
        $this->assertSame(0, DealProposalApprovalEvent::query()->count());
    }

    public function test_approval_and_deal_updates_roll_back_if_the_deal_update_fails(): void
    {
        $negotiationStageCount = DealStage::query()->where('name', 'Negotiation')->count();
        $injectFailure = true;
        DB::listen(function (QueryExecuted $query) use (&$injectFailure): void {
            if ($injectFailure && preg_match('/^\s*update\s+[`"]?deals\b/i', $query->sql) === 1) {
                $injectFailure = false;
                throw new RuntimeException('Synthetic Deal update failure.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
                'client_name' => 'Rollback Client',
                'approval_note' => 'This approval must roll back.',
            ]);
            $this->fail('The synthetic Deal update failure should escape the request.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic Deal update failure.', $exception->getMessage());
        }

        $this->assertFalse($injectFailure, 'The synthetic failure must occur on the Deal update query.');
        $this->proposal->refresh();
        $this->deal->refresh();

        $this->assertSame('sent', $this->proposal->status);
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertNull($this->proposal->client_approved_by_name);
        $this->assertNull($this->proposal->client_approval_note);
        $this->assertNull($this->deal->proposal_decision);
        $this->assertSame('Proposal', $this->deal->stage);
        $this->assertSame($negotiationStageCount, DealStage::query()->where('name', 'Negotiation')->count());
    }

    public function test_version_approval_and_event_roll_back_if_event_insert_fails(): void
    {
        $negotiationStageCount = DealStage::query()->where('name', 'Negotiation')->count();
        $this->createIssuedVersion();
        $injectFailure = true;
        DB::listen(function (QueryExecuted $query) use (&$injectFailure): void {
            if (
                $injectFailure
                && preg_match('/^\s*insert\s+into\s+[\x60"]?deal_proposal_approval_events/i', $query->sql) === 1
            ) {
                $injectFailure = false;
                throw new RuntimeException('Synthetic approval event insertion failure.');
            }
        });

        $this->withoutExceptionHandling();
        try {
            $this->post(route('deals.proposal.client.approve', ['token' => self::TOKEN]), [
                'client_name' => 'Rollback Version Client',
                'approval_note' => 'Must roll back with event.',
            ]);
            $this->fail('The synthetic approval event insertion failure should escape the request.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic approval event insertion failure.', $exception->getMessage());
        }

        $this->assertFalse($injectFailure);
        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('sent', $this->proposal->status);
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertNull($this->proposal->client_approved_by_name);
        $this->assertNull($this->proposal->client_approval_note);
        $this->assertNull($this->deal->proposal_decision);
        $this->assertSame('Proposal', $this->deal->stage);
        $this->assertSame(0, DealProposalApprovalEvent::query()->count());
        $this->assertSame($negotiationStageCount, DealStage::query()->where('name', 'Negotiation')->count());
    }

    public function test_edit_after_client_approval_is_denied_without_changing_approval_or_deal_fields(): void
    {
        $this->actingAsProposalEditor();
        $approvedAt = Carbon::parse('2026-10-29 09:30:00');
        $expiresAt = $this->proposal->client_access_expires_at->copy();
        $this->proposal->forceFill([
            'status' => 'approved',
            'client_approved_at' => $approvedAt,
            'client_approved_by_name' => 'Original Client',
            'client_approval_note' => 'Original note.',
        ])->save();
        $this->deal->forceFill([
            'proposal_decision' => 'Approved',
            'stage' => 'Negotiation',
        ])->save();
        $proposalBefore = $this->proposal->fresh()->getAttributes();
        $dealBefore = $this->deal->fresh()->getAttributes();

        $response = $this->put(route('deals.proposal.update', $this->deal), [
            'reference_id' => 'Changed after approval',
        ]);

        $response->assertRedirect(route('deals.proposal.show', $this->deal));
        $response->assertSessionHas('error', 'Approved proposals are read-only and cannot be edited or resent.');

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('approved', $this->proposal->status);
        $this->assertSame(self::TOKEN, $this->proposal->client_access_token);
        $this->assertTrue($this->proposal->client_access_expires_at->equalTo($expiresAt));
        $this->assertTrue($this->proposal->client_approved_at->equalTo($approvedAt));
        $this->assertSame('Original Client', $this->proposal->client_approved_by_name);
        $this->assertSame('Original note.', $this->proposal->client_approval_note);
        $this->assertNull($this->proposal->reference_id);
        $this->assertSame('Approved', $this->deal->proposal_decision);
        $this->assertSame('Negotiation', $this->deal->stage);
        $this->assertSame($proposalBefore, $this->proposal->getAttributes());
        $this->assertSame($dealBefore, $this->deal->getAttributes());
    }

    public function test_resend_after_client_approval_is_denied_without_changing_approval_or_deal_fields(): void
    {
        $this->actingAsProposalEditor();
        $approvedAt = Carbon::parse('2026-10-29 09:30:00');
        $expiresAt = $this->proposal->client_access_expires_at->copy();
        $this->proposal->forceFill([
            'status' => 'approved',
            'client_approved_at' => $approvedAt,
            'client_approved_by_name' => 'Original Client',
            'client_approval_note' => 'Original note.',
        ])->save();
        $this->deal->forceFill([
            'proposal_decision' => 'Approved',
            'stage' => 'Negotiation',
        ])->save();
        $proposalBefore = $this->proposal->fresh()->getAttributes();
        $dealBefore = $this->deal->fresh()->getAttributes();

        $response = $this->post(route('deals.proposal.send', $this->deal), [
            'recipient_email' => 'replacement@example.invalid',
        ]);

        $response->assertRedirect(route('deals.proposal.show', $this->deal));
        $response->assertSessionHas('error', 'Approved proposals are read-only and cannot be edited or resent.');

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('approved', $this->proposal->status);
        $this->assertSame(self::TOKEN, $this->proposal->client_access_token);
        $this->assertTrue($this->proposal->client_access_expires_at->equalTo($expiresAt));
        $this->assertTrue($this->proposal->client_approved_at->equalTo($approvedAt));
        $this->assertSame('Original Client', $this->proposal->client_approved_by_name);
        $this->assertSame('Original note.', $this->proposal->client_approval_note);
        $this->assertNull($this->proposal->client_form_sent_to_email);
        $this->assertNull($this->proposal->client_form_sent_at);
        $this->assertSame('Approved', $this->deal->proposal_decision);
        $this->assertSame('Negotiation', $this->deal->stage);
        $this->assertSame($proposalBefore, $this->proposal->getAttributes());
        $this->assertSame($dealBefore, $this->deal->getAttributes());
    }

    public function test_internal_approved_status_also_uses_the_existing_read_only_rule(): void
    {
        $this->actingAsProposalEditor();
        $this->proposal->forceFill(['status' => 'approved'])->save();
        $this->deal->forceFill([
            'proposal_decision' => 'Approved',
            'stage' => 'Negotiation',
        ])->save();
        $proposalBefore = $this->proposal->fresh()->getAttributes();
        $dealBefore = $this->deal->fresh()->getAttributes();

        $this->put(route('deals.proposal.update', $this->deal), [
            'reference_id' => 'Changed after internal approval',
        ])->assertSessionHas('error', 'Approved proposals are read-only and cannot be edited or resent.');

        $this->post(route('deals.proposal.send', $this->deal), [
            'recipient_email' => 'replacement@example.invalid',
        ])->assertSessionHas('error', 'Approved proposals are read-only and cannot be edited or resent.');

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('approved', $this->proposal->status);
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertSame(self::TOKEN, $this->proposal->client_access_token);
        $this->assertNull($this->proposal->reference_id);
        $this->assertSame('Approved', $this->deal->proposal_decision);
        $this->assertSame('Negotiation', $this->deal->stage);
        $this->assertSame($proposalBefore, $this->proposal->getAttributes());
        $this->assertSame($dealBefore, $this->deal->getAttributes());
    }

    public function test_unapproved_proposal_can_still_be_edited(): void
    {
        $this->actingAsProposalEditor();

        $response = $this->put(route('deals.proposal.update', $this->deal), [
            'reference_id' => 'Updated while pending',
        ]);

        $response->assertRedirect(route('deals.proposal.show', $this->deal));
        $response->assertSessionHas('success', 'Saved current proposal changes. The downloadable PDF now uses these saved details.');

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('saved', $this->proposal->status);
        $this->assertSame('Updated while pending', $this->proposal->reference_id);
        $this->assertSame(self::TOKEN, $this->proposal->client_access_token);
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertSame('Proposal', $this->deal->stage);
    }

    public function test_unapproved_proposal_can_still_be_sent(): void
    {
        $this->actingAsProposalEditor();
        $originalToken = $this->proposal->client_access_token;
        $originalExpiry = $this->proposal->client_access_expires_at->copy();

        $response = $this->post(route('deals.proposal.send', $this->deal), [
            'recipient_email' => 'client@example.invalid',
        ]);

        $response->assertRedirect(route('deals.proposal.show', $this->deal));
        $response->assertSessionHas('success', 'Proposal saved link sent to client@example.invalid.');
        $response->assertSessionHas('proposal_client_link');

        $this->proposal->refresh();
        $this->deal->refresh();
        $this->assertSame('sent', $this->proposal->status);
        $this->assertNotSame($originalToken, $this->proposal->client_access_token);
        $this->assertTrue($this->proposal->client_access_expires_at->greaterThan($originalExpiry));
        $this->assertSame('client@example.invalid', $this->proposal->client_form_sent_to_email);
        $this->assertNotNull($this->proposal->client_form_sent_at);
        $this->assertNull($this->proposal->client_approved_at);
        $this->assertSame('Proposal', $this->deal->stage);
    }
}
