<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\PreventBackHistory;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealPaymentAllocation;
use App\Models\DealProposal;
use App\Models\Project;
use App\Models\User;
use App\Services\DealFinanceDocuments;
use App\Services\DealPaymentEligibility;
use App\Services\ProjectProvisioner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DealPaymentEligibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('deal_payment_allocations');

        Schema::dropIfExists('deal_proposal_item_responses');
        Schema::dropIfExists('deal_proposals');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('services');
        Schema::dropIfExists('products');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
        Schema::create('deals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('deal_name');
            $table->string('stage')->nullable();
            $table->string('pipeline_stage')->nullable();
            $table->string('proposal_decision')->nullable();
            $table->json('services_products')->nullable();
            $table->timestamps();
        });
        Schema::create('deal_proposals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedInteger('proposal_version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('status')->default('sent');
            $table->string('client_access_token')->nullable();
            $table->timestamp('client_access_expires_at')->nullable();
            $table->timestamp('client_form_sent_at')->nullable();
            $table->string('prepared_by_id')->nullable();
            $table->string('client_approved_by_name')->nullable();
            $table->timestamp('client_approved_at')->nullable();
            $table->text('client_approval_note')->nullable();
            $table->timestamps();
        });
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('status')->nullable();
            $table->string('service_name')->nullable();
            $table->json('service_area')->nullable();
            $table->string('service_area_other')->nullable();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->nullable();
            $table->string('product_name')->nullable();
            $table->json('product_area')->nullable();
            $table->string('product_area_other')->nullable();
        });

        $migration = require base_path('database/migrations/2026_10_13_000002_add_line_item_responses_to_deal_proposals.php');
        $migration->up();
    }

    private function fixture(): array
    {
        (require base_path('database/migrations/2026_10_19_000001_create_deal_payment_allocations_table.php'))->up();
        Schema::table('deals', fn (Blueprint $table) => $table->json('start_batches')->nullable());
        $contact = Contact::create(['first_name' => 'Client', 'last_name' => 'Example']);
        $items = [];
        foreach ([100, 200, 300, 400, 500] as $index => $price) {
            $items[] = ['id' => 'item-'.($index + 1), 'name' => 'Item '.($index + 1), 'line_total' => $price];
        }
        $deal = Deal::withoutEvents(fn () => Deal::create(['contact_id' => $contact->id, 'deal_name' => 'Allocation test', 'services_products' => $items]));
        $proposal = DealProposal::create(['deal_id' => $deal->id, 'is_current' => true, 'proposal_version' => 1,
            'status' => 'sent', 'line_items_snapshot' => $items]);
        $this->withoutMiddleware([EnsurePasswordChanged::class, PreventBackHistory::class]);
        $user = User::withoutEvents(fn () => User::forceCreate(['name' => 'Finance', 'email' => 'finance@example.test']));
        $this->actingAs($user);
        $permissions = \Mockery::mock(DealFinanceDocuments::class);
        $permissions->shouldReceive('canView')->andReturn(true);
        $permissions->shouldReceive('canUpload')->andReturn(true);
        $this->app->instance(DealFinanceDocuments::class, $permissions);

        return [$deal, $proposal];
    }

    private function decide(DealProposal $proposal, string $id, string $decision): void
    {
        $proposal->itemResponses()->create(['line_item_id' => $id, 'line_item_name' => $id,
            'decision' => $decision, 'client_name' => 'Client', 'responded_at' => now()]);
    }

    private function pay(Deal $deal, string $scope, ?string $id, float $amount)
    {
        return $this->postJson(route('deals.payment-allocations.store', $deal), [
            'request_id' => (string) Str::uuid(), 'scope' => $scope, 'itemId' => $id,
            'amount' => $amount, 'method' => 'Bank Transfer', 'ref' => 'OR-100',
        ]);
    }

    public function test_partial_approval_includes_only_three_approved_items(): void
    {
        [$deal, $proposal] = $this->fixture();
        foreach (['item-1', 'item-2', 'item-3'] as $id) {
            $this->decide($proposal, $id, 'accepted');
        }
        foreach (['item-4', 'item-5'] as $id) {
            $this->decide($proposal, $id, 'declined');
        }
        $this->getJson(route('deals.payment-allocations.index', $deal))->assertOk()
            ->assertJsonCount(3, 'items')->assertJsonPath('approved_total', 600)
            ->assertJsonPath('required_payment', 600);
        $this->pay($deal, 'entire', null, 300)->assertCreated()
            ->assertJsonPath('allocated_total', 300)->assertJsonPath('required_payment', 300);
        $this->assertCount(3, DealPaymentAllocation::first()->allocations);
    }

    public function test_rejected_pending_and_unknown_items_cannot_receive_payments(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $this->decide($proposal, 'item-2', 'declined');
        foreach (['item-2', 'item-3', 'missing'] as $id) {
            $this->pay($deal, 'item', $id, 100)->assertUnprocessable()->assertJsonValidationErrors('itemId');
        }
        $this->assertDatabaseCount('deal_payment_allocations', 0);
        $this->pay($deal, 'item', 'item-1', 50)->assertCreated()->assertJsonPath('items.0.ready', true);
    }

    public function test_internal_approval_does_not_approve_pending_items(): void
    {
        [$deal, $proposal] = $this->fixture();
        $proposal->update(['status' => 'approved']);
        $this->getJson(route('deals.payment-allocations.index', $deal))->assertJsonCount(0, 'items')->assertJsonPath('approved_total', 0);
        $this->pay($deal, 'entire', null, 100)->assertUnprocessable();
    }

    public function test_later_rejection_preserves_payment_and_decision_history_and_flags_review(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $this->decide($proposal, 'item-2', 'accepted');
        $this->pay($deal, 'entire', null, 150)->assertCreated();
        $original = DealPaymentAllocation::first()->getAttributes();
        $this->decide($proposal, 'item-2', 'declined');
        $state = $this->getJson(route('deals.payment-allocations.index', $deal))->assertOk()
            ->assertJsonCount(1, 'items')->assertJsonPath('approved_total', 100)
            ->assertJsonPath('allocated_total', 50)->assertJsonPath('review_required', true)
            ->assertJsonPath('records.0.requiresReview', true);
        $this->assertSame($original, DealPaymentAllocation::first()->getAttributes());
        $this->assertDatabaseCount('deal_proposal_item_responses', 3);
        $this->pay($deal, 'item', 'item-2', 1)->assertUnprocessable();
        $this->assertFalse(app(DealPaymentEligibility::class)->batchEligible($deal, [['id' => 'item-2']]));
    }

    public function test_new_revision_requires_new_client_decisions_and_does_not_reassign_old_payments(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $this->pay($deal, 'item', 'item-1', 100)->assertCreated();
        $proposal->update(['is_current' => false]);
        $revision = DealProposal::create(['deal_id' => $deal->id, 'is_current' => true, 'proposal_version' => 2,
            'status' => 'sent', 'line_items_snapshot' => $proposal->line_items_snapshot]);
        $this->getJson(route('deals.payment-allocations.index', $deal))->assertJsonCount(0, 'items')->assertJsonPath('review_required', true);
        $this->decide($revision, 'item-1', 'accepted');
        $this->getJson(route('deals.payment-allocations.index', $deal))->assertJsonPath('allocated_total', 0)->assertJsonPath('items.0.ready', false);
        $this->assertDatabaseCount('deal_payment_allocations', 1);
    }

    public function test_direct_start_requests_cannot_activate_rejected_pending_or_unpaid_items(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $this->decide($proposal, 'item-2', 'declined');
        foreach (['item-1', 'item-2', 'item-3'] as $id) {
            $this->postJson('/deals/'.$deal->id.'/start/batches', [
                'start_code' => 'ST-1', 'batch_name' => 'Batch', 'activated_items' => [['id' => $id]],
            ])->assertUnprocessable()->assertJsonValidationErrors('activated_items');
        }
        $this->pay($deal, 'item', 'item-1', 10)->assertCreated();
        $this->postJson('/deals/'.$deal->id.'/start/batches', [
            'start_code' => 'ST-1', 'batch_name' => 'Batch',
            'activated_items' => [['id' => 'item-1', 'name' => 'Item 1']],
        ])->assertOk();
        $this->assertFalse(app(DealPaymentEligibility::class)->batchEligible($deal, [['id' => 'item-2', 'name' => 'Item 1']]));
    }

    public function test_general_allocations_preserve_cents_and_do_not_fund_later_approvals(): void
    {
        [$deal, $proposal] = $this->fixture();
        foreach (['item-1', 'item-2', 'item-3'] as $id) {
            $this->decide($proposal, $id, 'accepted');
        }
        $this->pay($deal, 'downpayment', null, 0.05)->assertCreated();
        $this->assertEquals(0.05, array_sum(array_column(DealPaymentAllocation::first()->allocations, 'amount')));
        $this->decide($proposal, 'item-4', 'accepted');
        $state = app(DealPaymentEligibility::class)->state($deal);
        $this->assertEquals(0, collect($state['items'])->firstWhere('id', 'item-4')['allocated']);
    }

    public function test_client_submission_automatically_updates_payment_eligibility(): void
    {
        [$deal, $proposal] = $this->fixture();
        $proposal->update(['client_access_token' => 'payment-client-token', 'client_form_sent_at' => now()]);
        $this->post(route('deals.proposal.client.items', 'payment-client-token'), [
            'client_name' => 'Client', 'responses' => [
                ['line_item_id' => 'item-1', 'decision' => 'accepted'],
                ['line_item_id' => 'item-2', 'decision' => 'declined', 'comment' => 'Not needed'],
            ],
        ])->assertRedirect();
        $this->getJson(route('deals.payment-allocations.index', $deal))->assertOk()
            ->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', 'item-1')->assertJsonPath('approved_total', 100);
    }

    public function test_retrying_the_same_payment_does_not_duplicate_the_transaction(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $data = ['request_id' => (string) Str::uuid(), 'scope' => 'item', 'itemId' => 'item-1', 'amount' => 20, 'method' => 'Cash'];
        $url = route('deals.payment-allocations.store', $deal);
        $this->postJson($url, $data)->assertCreated();
        $this->postJson($url, $data)->assertCreated()->assertJsonPath('allocated_total', 20);
        $this->assertDatabaseCount('deal_payment_allocations', 1);
    }

    public function test_later_rejection_blocks_existing_batch_memo_and_generic_activation_updates(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $this->pay($deal, 'item', 'item-1', 20)->assertCreated();
        DB::table('deals')->where('id', $deal->id)->update(['start_batches' => json_encode([
            ['id' => 42, 'status' => 'Approved', 'activated_items' => [['id' => 'item-1', 'name' => 'Item 1']]],
        ])]);
        $this->decide($proposal, 'item-1', 'declined');
        $this->postJson('/deals/'.$deal->id.'/start/batches/42/issue-memo', [])->assertUnprocessable()->assertJsonValidationErrors('activated_items');
        $this->assertDatabaseCount('deal_payment_allocations', 1);
        $this->expectException(ValidationException::class);
        $deal->fresh()->update(['pipeline_stage' => 'Activation', 'stage' => 'Activation']);
    }

    public function test_payment_uses_issued_quantity_discount_and_tax_and_preserves_zero_prices(): void
    {
        [$deal, $proposal] = $this->fixture();
        $proposal->line_items_snapshot = [
            ['id' => 'item-1', 'name' => 'Priced item', 'qty' => 2, 'unitPrice' => 100, 'discount' => 20, 'tax' => 5],
            ['id' => 'item-2', 'name' => 'Free item', 'line_total' => 0],
        ];
        $proposal->save();
        foreach (['item-1', 'item-2'] as $id) {
            $this->decide($proposal, $id, 'accepted');
        }
        $this->getJson(route('deals.payment-allocations.index', $deal))->assertJsonPath('approved_total', 185)
            ->assertJsonPath('items.0.price', 185)->assertJsonPath('items.1.price', 0);
    }

    public function test_workspace_activation_cannot_bypass_pending_item_decisions(): void
    {
        [$deal, $proposal] = $this->fixture();
        Schema::dropIfExists('projects');
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->string('status');
            $table->string('engagement_type');
            $table->json('metadata')->nullable();
        });
        $id = DB::table('projects')->insertGetId(['deal_id' => $deal->id, 'status' => 'shell', 'engagement_type' => 'Regular']);
        $project = Project::findOrFail($id);
        try {
            app(ProjectProvisioner::class)->activateShellWorkspace($project);
            $this->fail('A pending item cannot activate a linked workspace.');
        } catch (ValidationException $exception) {
            $this->assertSame('shell', $project->fresh()->status);
            $this->assertDatabaseCount('deal_payment_allocations', 0);
        }
    }

    public function test_a_rejected_historical_batch_does_not_block_other_approved_items(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $this->decide($proposal, 'item-2', 'accepted');
        $this->pay($deal, 'entire', null, 150)->assertCreated();
        DB::table('deals')->where('id', $deal->id)->update(['start_batches' => json_encode([
            ['id' => 1, 'status' => 'Activated', 'activated_items' => [['id' => 'item-2', 'name' => 'Item 2']]],
        ])]);
        $this->decide($proposal, 'item-2', 'declined');
        $eligibility = app(DealPaymentEligibility::class);
        $eligibility->assertActivationAllowed($deal->fresh());
        $this->assertTrue($eligibility->batchEligible($deal, [['id' => 'item-1']]));
        $this->assertFalse($eligibility->batchEligible($deal, [['id' => 'item-2']]));
        $this->assertSame('Activated', $deal->fresh()->start_batches[0]['status'], 'Historical status remains recorded.');
        $this->assertDatabaseCount('deal_payment_allocations', 1);
    }

    public function test_payment_api_requires_finance_permission(): void
    {
        [$deal, $proposal] = $this->fixture();
        $this->decide($proposal, 'item-1', 'accepted');
        $permissions = \Mockery::mock(DealFinanceDocuments::class);
        $permissions->shouldReceive('canUpload')->andReturn(false);
        $this->app->instance(DealFinanceDocuments::class, $permissions);
        $this->pay($deal, 'item', 'item-1', 100)->assertForbidden();
    }
}
