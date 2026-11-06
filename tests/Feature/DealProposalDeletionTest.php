<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealProposalVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DealsMysqlTestCase;

class DealProposalDeletionTest extends DealsMysqlTestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
    }

    private function createDeal(): Deal
    {
        $contact = Contact::query()->create([
            'first_name' => 'Deletion',
            'last_name' => 'Test',
            'email' => 'deletion@example.invalid',
        ]);

        return Deal::query()->create([
            'contact_id' => $contact->id,
            'deal_name' => 'Proposal version deletion test',
            'stage' => 'Proposal',
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'Admin',
            'must_change_password' => false,
        ]);
    }

    private function addIssuedVersion(Deal $deal): void
    {
        $proposal = $deal->proposal()->create(['status' => 'sent']);

        DealProposalVersion::query()->create([
            'deal_proposal_id' => $proposal->id,
            'version_number' => 1,
            'snapshot_schema_version' => 1,
            'resolved_proposal_data' => ['client_name' => 'Deletion Test'],
            'commercial_values' => ['total' => '100.00'],
            'source_metadata' => ['captured_from' => 'test_fixture'],
            'document_identifiers' => ['reference_id' => 'DELETION-TEST'],
            'rendered_html' => '<html><body>Issued document</body></html>',
            'artifact_private_path' => 'deal-proposal-versions/'.$proposal->id.'/deletion-test.pdf',
            'artifact_sha256' => str_repeat('b', 64),
            'issued_at' => now(),
        ]);
    }

    public function test_deletion_request_with_issued_version_is_marked_pending_without_losing_history(): void
    {
        $deal = $this->createDeal();
        $this->addIssuedVersion($deal);
        $this->actingAs($this->createAdmin());

        $this->delete(route('deals.destroy', $deal->id))
            ->assertOk()
            ->assertJsonPath('message', 'Deal deletion requested successfully.');

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'delete_request_status' => 'pending',
            'delete_requested_by' => auth()->id(),
        ]);
        $this->assertSame(1, DealProposalVersion::query()->where('deal_proposal_id', $deal->proposal->id)->count());
    }

    public function test_approved_deletion_with_issued_version_archives_and_retains_history(): void
    {
        $deal = $this->createDeal();
        $this->addIssuedVersion($deal);
        $deal->forceFill(['delete_request_status' => 'pending'])->save();
        $this->actingAs($this->createAdmin());

        $this->post(route('deals.approve-deletion', $deal->id))
            ->assertRedirect(route('admin.dashboard.section', 'deals'))
            ->assertSessionHas('success', 'Deal archived successfully.');

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'delete_request_status' => 'archived',
        ]);
        $this->assertNotNull($deal->fresh()->archived_at);
        $this->assertSame(1, DealProposalVersion::query()->where('deal_proposal_id', $deal->proposal->id)->count());
    }

    public function test_deletion_request_without_issued_versions_keeps_existing_behavior(): void
    {
        $deal = $this->createDeal();
        $this->actingAs($this->createAdmin());

        $this->delete(route('deals.destroy', $deal->id))
            ->assertOk()
            ->assertJsonPath('message', 'Deal deletion requested successfully.');

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'delete_request_status' => 'pending',
        ]);
    }

    public function test_approved_deletion_without_issued_versions_archives_deal(): void
    {
        $deal = $this->createDeal();
        $this->actingAs($this->createAdmin());
        $this->delete(route('deals.destroy', $deal->id))
            ->assertOk()
            ->assertJsonPath('message', 'Deal deletion requested successfully.');

        $this->post(route('deals.approve-deletion', $deal->id))
            ->assertRedirect(route('admin.dashboard.section', 'deals'))
            ->assertSessionHas('success', 'Deal archived successfully.');

        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'delete_request_status' => 'archived']);
        $this->assertNotNull($deal->fresh()->archived_at);
    }
}
