<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Deal;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DealKanbanDragDropTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        \DB::purge('sqlite');
        \DB::reconnect('sqlite');
    }

    private array $createdDealIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->createdDealIds)) {
            Deal::whereIn('id', $this->createdDealIds)->forceDelete();
        }
        parent::tearDown();
    }

    private function createDeal(string $stage = 'Inquiry', array $attributes = []): Deal
    {
        $account = Account::first() ?? Account::create([
            'account_code' => 'ACC-TEST',
            'account_name' => 'Acme Corporation',
            'account_type' => 'Business',
            'status' => 'Active',
        ]);

        $deal = Deal::create(array_merge([
            'account_id' => $account->id,
            'deal_code' => 'CONDEAL-' . date('Y') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8),
            'deal_title' => 'Test Stage Deal',
            'customer_type' => 'Business',
            'pipeline_stage' => $stage,
            'owner_name' => 'John Kelly Abalde',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@acme.com',
            'mobile_number' => '0917-111-2222',
            'company' => 'Acme Corporation',
            'amount' => 50000,
        ], $attributes));

        $this->createdDealIds[] = $deal->id;

        return $deal;
    }

    public function test_same_stage_drag_returns_success_and_no_change(): void
    {
        $deal = $this->createDeal('Inquiry');

        $response = $this->patchJson(route('deals.stage.update', $deal->id), [
            'pipeline_stage' => 'Inquiry',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'old_stage' => 'Inquiry',
            'new_stage' => 'Inquiry',
        ]);

        $this->assertEquals('Inquiry', $deal->fresh()->pipeline_stage);
    }

    public function test_incomplete_inquiry_requirements_rejects_drop_to_qualification(): void
    {
        $deal = $this->createDeal('Inquiry', [
            'inquiry_source' => '', // missing
            'inquiry_details' => '', // missing
        ]);

        $response = $this->patchJson(route('deals.stage.update', $deal->id), [
            'pipeline_stage' => 'Qualification',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Complete the Inquiry requirements before moving this deal to Qualification.',
        ]);

        $this->assertEquals('Inquiry', $deal->fresh()->pipeline_stage);
    }

    public function test_complete_inquiry_requirements_allows_drop_to_qualification(): void
    {
        $deal = $this->createDeal('Inquiry', [
            'inquiry_source' => 'Website',
            'inquiry_date' => '2026-09-18',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'mobile_number' => '09171234567',
            'deal_title' => 'Sample Inquiry Deal',
            'inquiry_details' => 'Detailed inquiry text',
        ]);

        $response = $this->patchJson(route('deals.stage.update', $deal->id), [
            'pipeline_stage' => 'Qualification',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'old_stage' => 'Inquiry',
            'new_stage' => 'Qualification',
        ]);

        $this->assertEquals('Qualification', $deal->fresh()->pipeline_stage);
    }

    public function test_stage_skipping_from_inquiry_to_proposal_is_rejected(): void
    {
        $deal = $this->createDeal('Inquiry', [
            'inquiry_source' => 'Website',
            'inquiry_date' => '2026-09-18',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'mobile_number' => '09171234567',
            'deal_title' => 'Sample Inquiry Deal',
            'inquiry_details' => 'Detailed inquiry text',
        ]);

        $response = $this->patchJson(route('deals.stage.update', $deal->id), [
            'pipeline_stage' => 'Proposal',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Stage skipping is not permitted. Complete intermediate stages sequentially before moving to Proposal.',
        ]);

        $this->assertEquals('Inquiry', $deal->fresh()->pipeline_stage);
    }

    public function test_stage_skipping_from_inquiry_to_closed_won_is_rejected(): void
    {
        $deal = $this->createDeal('Inquiry');

        $response = $this->patchJson(route('deals.stage.update', $deal->id), [
            'pipeline_stage' => 'Closed Won',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Please complete all previous stages through Activation before moving to Closed Won.',
        ]);

        $this->assertEquals('Inquiry', $deal->fresh()->pipeline_stage);
    }

    public function test_backward_movement_from_proposal_to_consultation_is_allowed(): void
    {
        $deal = $this->createDeal('Proposal', [
            'proposal_number' => 'PROP-2026-001',
            'proposal_date' => '2026-09-18',
            'proposal_value' => 50000,
            'proposal_valid_until' => '2026-10-18',
            'proposal_status' => 'Sent to Client',
            'proposal_notes' => 'Proposal drafted and sent.',
        ]);

        $response = $this->patchJson(route('deals.stage.update', $deal->id), [
            'pipeline_stage' => 'Consultation',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'old_stage' => 'Proposal',
            'new_stage' => 'Consultation',
        ]);

        $this->assertEquals('Consultation', $deal->fresh()->pipeline_stage);
        // Ensure previously entered proposal number is not deleted
        $this->assertEquals('PROP-2026-001', $deal->fresh()->proposal_number);
    }
}
