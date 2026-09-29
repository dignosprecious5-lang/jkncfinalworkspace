<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Deal;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DealStageWorkflowTest extends TestCase
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

    private function createTestDeal(string $stage = 'Inquiry'): Deal
    {
        $account = Account::first() ?? Account::create([
            'account_code' => 'ACC-TEST',
            'account_name' => 'Acme Corporation',
            'account_type' => 'Business',
            'status' => 'Active',
        ]);

        $deal = Deal::create([
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
        ]);

        $this->createdDealIds[] = $deal->id;

        return $deal;
    }

    public function test_inquiry_stage_requires_all_fields_and_fails_when_missing(): void
    {
        $deal = $this->createTestDeal('Inquiry');

        $response = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Inquiry',
            'inquiry_source' => '', // missing
            'inquiry_date' => '2026-09-18',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@acme.com',
            'mobile_number' => '0917-111-2222',
            'deal_title' => 'Test Deal',
            'inquiry_details' => '', // missing
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['inquiry_source', 'inquiry_details']);
    }

    public function test_cannot_skip_stages(): void
    {
        $deal = $this->createTestDeal('Inquiry');

        // Try to jump from Inquiry to Proposal
        $response = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Proposal',
            'proposal_number' => 'PROP-001',
            'proposal_date' => '2026-09-18',
            'proposal_value' => 50000,
            'proposal_valid_until' => '2026-10-18',
            'proposal_status' => 'Sent to Client',
            'proposal_notes' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Please complete the current stage first. Stage skipping is not permitted.'
        ]);
    }

    public function test_sequential_progression_from_inquiry_to_closed_won(): void
    {
        $deal = $this->createTestDeal('Inquiry');

        // 1. Complete Inquiry -> should move to Qualification
        $r1 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Inquiry',
            'inquiry_source' => 'Website Inquiry',
            'inquiry_date' => '2026-09-18',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'mobile_number' => '0917-123-4567',
            'deal_title' => 'Corporate Compliance Engagement',
            'inquiry_details' => 'Client needs complete annual compliance and tax advisory.',
        ]);
        $r1->assertOk();
        $r1->assertJson([
            'success' => true,
            'current_stage' => 'Qualification',
            'next_stage' => 'Qualification',
        ]);
        $deal->refresh();
        $this->assertEquals('Qualification', $deal->pipeline_stage);

        // 2. Complete Qualification -> should move to Consultation
        $r2 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Qualification',
            'qualification_result' => 'Qualified',
            'client_need' => 'Full corporate compliance and tax audit assistance.',
            'amount' => 75000,
            'decision_maker' => 'Managing Director',
            'expected_close' => '2026-10-15',
            'qualification_notes' => 'Strong fit, budget confirmed.',
        ]);
        $r2->assertOk();
        $r2->assertJson([
            'success' => true,
            'current_stage' => 'Consultation',
            'next_stage' => 'Consultation',
        ]);
        $deal->refresh();
        $this->assertEquals('Consultation', $deal->pipeline_stage);

        // 3. Complete Consultation -> should move to Proposal
        $r3 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Consultation',
            'consultation_date' => '2026-09-19',
            'consultation_type' => 'Online Video Call (Zoom / Teams)',
            'requirements_confirmed' => 'Yes - Scope and Deliverables Confirmed',
            'scope_of_work' => 'Corporate bookkeeping, tax compliance filings, and annual report preparation.',
            'consultant_notes' => 'Meeting concluded with client agreement on timeline.',
        ]);
        $r3->assertOk();
        $r3->assertJson([
            'success' => true,
            'current_stage' => 'Proposal',
            'next_stage' => 'Proposal',
        ]);
        $deal->refresh();
        $this->assertEquals('Proposal', $deal->pipeline_stage);

        // 4. Complete Proposal -> should move to Negotiation
        $r4 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Proposal',
            'proposal_number' => 'PROP-2026-001',
            'proposal_date' => '2026-09-20',
            'proposal_value' => 75000,
            'proposal_valid_until' => '2026-10-20',
            'proposal_status' => 'Sent to Client',
            'proposal_notes' => 'Formal proposal delivered to decision maker.',
        ]);
        $r4->assertOk();
        $r4->assertJson([
            'success' => true,
            'current_stage' => 'Negotiation',
            'next_stage' => 'Negotiation',
        ]);
        $deal->refresh();
        $this->assertEquals('Negotiation', $deal->pipeline_stage);

        // 5. Complete Negotiation -> should move to Payment
        $r5 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Negotiation',
            'negotiation_status' => 'Terms Agreed',
            'final_deal_value' => 75000,
            'payment_terms' => '50% Downpayment / 50% Upon Completion',
            'pricing_model' => 'Fixed Project Fee',
            'negotiation_notes' => 'Client accepted 50/50 payment term structure.',
        ]);
        $r5->assertOk();
        $r5->assertJson([
            'success' => true,
            'current_stage' => 'Payment',
            'next_stage' => 'Payment',
        ]);
        $deal->refresh();
        $this->assertEquals('Payment', $deal->pipeline_stage);

        // 6. Complete Payment -> should move to Activation
        $r6 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Payment',
            'payment_method' => 'Bank Transfer (BDO)',
            'payment_amount' => 37500,
            'payment_date' => '2026-09-21',
            'payment_status' => 'Verified / Confirmed',
            'payment_reference' => 'TXN-98234710',
        ]);
        $r6->assertOk();
        $r6->assertJson([
            'success' => true,
            'current_stage' => 'Activation',
            'next_stage' => 'Activation',
        ]);
        $deal->refresh();
        $this->assertEquals('Activation', $deal->pipeline_stage);

        // 7. Complete Activation -> should allow Final Outcome
        $r7 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Activation',
            'activation_date' => '2026-09-22',
            'assigned_team' => 'Management Consulting',
            'assigned_person' => 'John Kelly Abalde',
            'service_start_date' => '2026-09-23',
            'activation_notes' => 'Assigned engagement team and prepared START batch.',
        ]);
        $r7->assertOk();

        // 8. Final Outcome: Closed Won
        $r8 = $this->postJson(route('deals.stage-workflow.save', $deal->id), [
            'stage' => 'Closed Won',
            'closed_won_date' => '2026-09-24',
            'final_deal_value' => 75000,
            'closing_notes' => 'Deal successfully closed won and service completed.',
        ]);
        $r8->assertOk();
        $r8->assertJson([
            'success' => true,
            'current_stage' => 'Closed Won',
        ]);
        $deal->refresh();
        $this->assertEquals('Closed Won', $deal->pipeline_stage);
    }

    public function test_search_autocomplete_returns_matching_deals()
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $deal = $this->createTestDeal('Qualification');
        $deal->update([
            'deal_code' => 'CONDEAL-2026-AUTO1',
            'deal_title' => 'Corporate Legal Advisory Autocomplete',
            'company_name' => 'Autocomplete Corp',
            'amount' => 45000,
        ]);

        // 1. Empty query -> empty array
        $resEmpty = $this->getJson(route('deals.autocomplete', ['q' => '']));
        $resEmpty->assertOk()->assertExactJson([]);

        // 2. Query matching company name prefix
        $resComp = $this->getJson(route('deals.autocomplete', ['q' => 'Autocomplete Corp']));
        $resComp->assertOk()
            ->assertJsonFragment([
                'id' => $deal->id,
                'client_name' => 'Autocomplete Corp',
            ]);

        // 3. Test Customer/Client Name Prefix Matching:
        // - "Acme Test Corporation" starts with "Ac" -> Matches "Ac"
        // - "Ricardo Acosta" starts with "R" -> Does NOT match "Ac"
        // - "Black Corporation" starts with "B" -> Does NOT match "Ac"
        $dealAcme = $this->createTestDeal('Inquiry');
        $dealAcme->update([
            'deal_code' => 'CONDEAL-2026-ACME1',
            'deal_title' => 'Consulting Services',
            'company_name' => 'Acme Test Corporation',
            'first_name' => null,
            'last_name' => null,
        ]);

        $dealRicardoAcosta = $this->createTestDeal('Inquiry');
        $dealRicardoAcosta->update([
            'account_id' => null,
            'deal_code' => 'CONDEAL-2026-RIC1',
            'deal_title' => 'Tax Audit',
            'company_name' => null,
            'company' => null,
            'client_search' => null,
            'primary_contact_name' => null,
            'first_name' => 'Ricardo',
            'last_name' => 'Acosta', // starts with 'R'
        ]);

        $dealBlackCorp = $this->createTestDeal('Inquiry');
        $dealBlackCorp->update([
            'account_id' => null,
            'deal_code' => 'CONDEAL-2026-BLK1',
            'deal_title' => 'Financial Plan',
            'company_name' => 'Black Corporation', // starts with 'B'
            'company' => null,
            'client_search' => null,
            'primary_contact_name' => null,
            'first_name' => null,
            'last_name' => null,
        ]);

        $resAc = $this->getJson(route('deals.autocomplete', ['q' => 'Ac']));
        $resAc->assertOk();
        $results = $resAc->json();

        $ids = array_column($results, 'id');
        $this->assertContains($dealAcme->id, $ids, "Acme Test Corporation starts with 'Ac' and should match");
        $this->assertNotContains($dealRicardoAcosta->id, $ids, "Ricardo Acosta starts with 'R' and should NOT match 'Ac'");
        $this->assertNotContains($dealBlackCorp->id, $ids, "Black Corporation starts with 'B' and should NOT match 'Ac'");
    }

    public function test_can_add_multiple_inquiries_to_deal(): void
    {
        $deal = $this->createTestDeal('Inquiry');

        // Add 1st inquiry
        $res1 = $this->postJson(route('deals.inquiries.store', $deal->id), [
            'subject' => 'Bookkeeping Monthly Services',
            'type' => 'Service',
            'client_inquiry' => 'Client wants bookkeeping assistance for 2026.',
            'budget' => '25000',
            'target_date' => '2026-11-01',
            'notes' => 'Priority client.',
        ]);

        $res1->assertOk();
        $this->assertTrue($res1->json('success'));
        $this->assertCount(2, $res1->json('records')); // includes initial deal inquiry + 1st added

        // Add 2nd inquiry
        $res2 = $this->postJson(route('deals.inquiries.store', $deal->id), [
            'subject' => 'Tax Filing Consultation',
            'type' => 'Service',
            'client_inquiry' => 'Quarterly BIR 1702Q filing inquiry.',
            'budget' => '15000',
            'target_date' => '2026-12-01',
            'notes' => 'Needs urgent callback.',
        ]);

        $res2->assertOk();
        $this->assertTrue($res2->json('success'));
        $this->assertCount(3, $res2->json('records')); // now 3 inquiries

        // Delete 1 inquiry
        $records = $res2->json('records');
        $targetId = $records[0]['id'];
        $res3 = $this->deleteJson(route('deals.inquiries.destroy', ['id' => $deal->id, 'inquiryId' => $targetId]));

        $res3->assertOk();
        $this->assertTrue($res3->json('success'));
        $this->assertCount(2, $res3->json('records'));
    }

    public function test_can_add_multiple_line_items_in_services_and_pricing(): void
    {
        $deal = $this->createTestDeal('Consultation');

        // Add 1st line item
        $res1 = $this->postJson(route('deals.line-items.store', $deal->id), [
            'type' => 'SERVICE',
            'name' => 'AFS Preparation',
            'description' => 'Annual Audited Financial Statements',
            'qty' => 1,
            'unit' => 'lot',
            'unit_price' => 25000,
            'discount' => 2000,
            'tax' => 0,
            'billing' => 'Full Payment Before Service',
            'route' => 'Regular',
        ]);

        $res1->assertOk();
        $this->assertTrue($res1->json('success'));
        $this->assertCount(1, $res1->json('items'));
        $this->assertEquals(23000, $res1->json('totals.commercial_total'));

        // Add 2nd line item
        $res2 = $this->postJson(route('deals.line-items.store', $deal->id), [
            'type' => 'PRODUCT',
            'name' => 'Stock Certificate Printing',
            'description' => 'Official corporate stock certificates',
            'qty' => 10,
            'unit' => 'certificate',
            'unit_price' => 300,
            'discount' => 0,
            'tax' => 0,
            'billing' => 'Full Payment Before Service',
            'route' => 'Regular',
        ]);

        $res2->assertOk();
        $this->assertTrue($res2->json('success'));
        $this->assertCount(2, $res2->json('items'));
        $this->assertEquals(26000, $res2->json('totals.commercial_total'));

        // Delete 1st line item
        $items = $res2->json('items');
        $delId = $items[0]['id'];
        $res3 = $this->deleteJson(route('deals.line-items.destroy', ['id' => $deal->id, 'itemId' => $delId]));

        $res3->assertOk();
        $this->assertTrue($res3->json('success'));
        $this->assertCount(1, $res3->json('items'));
        $this->assertEquals(3000, $res3->json('totals.commercial_total'));
    }
}
