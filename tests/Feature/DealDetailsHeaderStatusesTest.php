<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealProposal;
use Tests\TestCase;

class DealDetailsHeaderStatusesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Schema::dropIfExists('deal_proposals');
        \Illuminate\Support\Facades\Schema::dropIfExists('deal_histories');
        \Illuminate\Support\Facades\Schema::dropIfExists('deal_stage_histories');
        \Illuminate\Support\Facades\Schema::dropIfExists('deals');
        \Illuminate\Support\Facades\Schema::dropIfExists('users');

        \Illuminate\Support\Facades\Schema::create('deal_histories', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('user_name')->nullable();
            $table->string('activity_type', 50)->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('old_stage')->nullable();
            $table->text('new_stage')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('deal_stage_histories', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->string('stage')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('duration_in_days', 8, 2)->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('deals', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('deal_code')->nullable();
            $table->string('deal_title')->nullable();
            $table->string('deal_name')->nullable();
            $table->string('client_name')->nullable();
            $table->string('pipeline_stage')->nullable();
            $table->string('stage')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->string('proposal_status')->nullable();
            $table->string('proposal_decision')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('commercial_stage')->nullable();
            $table->string('casa_stage')->nullable();
            $table->json('start_batches')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::create('deal_proposals', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->unsignedInteger('proposal_version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('status')->default('Draft');
            $table->string('subject')->nullable();
            $table->text('introduction')->nullable();
            $table->text('scope')->nullable();
            $table->text('terms')->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('tax', 12, 2)->nullable();
            $table->json('line_items_snapshot')->nullable();
            $table->string('revision_request_key')->nullable();
            $table->string('client_access_token')->nullable();
            $table->timestamp('client_access_expires_at')->nullable();
            $table->string('client_form_sent_to_email')->nullable();
            $table->timestamp('client_form_sent_at')->nullable();
            $table->timestamp('client_approved_at')->nullable();
            $table->string('client_approved_by_name')->nullable();
            $table->text('client_approval_note')->nullable();
            $table->string('quotation_status')->nullable();
            $table->string('quotation_finance_file_path')->nullable();
            $table->string('quotation_client_file_path')->nullable();
            $table->timestamp('quotation_finance_started_at')->nullable();
            $table->timestamp('quotation_approved_at')->nullable();
            $table->string('quotation_approved_by_name')->nullable();
            $table->string('invoice_status')->nullable();
            $table->string('invoice_file_path')->nullable();
            $table->timestamp('invoice_generated_at')->nullable();
            $table->timestamp('invoice_uploaded_at')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->string('payment_confirmed_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function test_proposal_approved_status_when_start_initiated_or_proposal_approved(): void
    {
        // 1. When proposal_status is Approved on deal
        $deal1 = new Deal([
            'proposal_status' => 'Approved',
            'proposal_decision' => null,
            'start_batches' => [],
        ]);
        $this->assertSame('Approved', $deal1->getResolvedProposalStatus());

        // 2. When proposal_decision is Approved
        $deal2 = new Deal([
            'proposal_status' => null,
            'proposal_decision' => 'Approved',
            'start_batches' => [],
        ]);
        $this->assertSame('Approved', $deal2->getResolvedProposalStatus());

        // 3. When START workflow is initiated, proposal must show Approved
        $deal3 = new Deal([
            'proposal_status' => null,
            'proposal_decision' => null,
            'start_batches' => [
                ['id' => 1, 'status' => 'Structuring']
            ],
        ]);
        $this->assertSame('Approved', $deal3->getResolvedProposalStatus());

        // 4. When proposal is under review and START not initiated
        $deal4 = new Deal([
            'proposal_status' => 'sent',
            'proposal_decision' => null,
            'start_batches' => [],
        ]);
        $this->assertSame('Under Client Review', $deal4->getResolvedProposalStatus());
    }

    public function test_start_status_reflects_actual_workflow_state(): void
    {
        // 1. Ongoing when START has been initiated with batches
        $ongoingDeal = new Deal([
            'pipeline_stage' => 'Activation',
            'proposal_status' => 'Approved',
            'proposal_decision' => 'Approved',
            'start_batches' => [
                [
                    'id' => 12345,
                    'start_code' => 'ST-2026-002',
                    'batch_name' => 'START Batch 002',
                    'status' => 'Structuring',
                ]
            ],
        ]);
        $this->assertSame('Ongoing', $ongoingDeal->getResolvedStartStatus());

        // 2. Not Ready before the START workflow has been initiated, even when proposal prerequisites are met
        $readyDeal = new Deal([
            'pipeline_stage' => 'Activation',
            'proposal_status' => 'Approved',
            'proposal_decision' => 'Approved',
            'start_batches' => [],
        ]);
        $this->assertSame('Not Ready', $readyDeal->getResolvedStartStatus());

        // 3. Not Ready when readiness requirements are NOT satisfied (proposal not approved)
        $notReadyDeal = new Deal([
            'pipeline_stage' => 'Proposal',
            'proposal_status' => 'Draft',
            'proposal_decision' => null,
            'start_batches' => [],
        ]);
        $this->assertSame('Not Ready', $notReadyDeal->getResolvedStartStatus());

        $historicClosedWonDeal = new Deal([
            'stage' => 'Closed Won',
            'pipeline_stage' => 'Closed Won',
            'start_batches' => [],
        ]);
        $this->assertFalse($historicClosedWonDeal->isStartCompleted());
        $this->assertSame('Not Ready', $historicClosedWonDeal->getResolvedStartStatus());
    }

    public function test_payment_status_reflects_actual_payment_records(): void
    {
        // 1. Recorded payment Completed
        $paidDeal = new Deal([
            'pipeline_stage' => 'Activation',
            'payment_status' => 'Completed',
        ]);
        $this->assertSame('Completed', $paidDeal->getResolvedPaymentStatus());

        // 2. Pending when in Payment stage and not recorded
        $pendingPayDeal = new Deal([
            'pipeline_stage' => 'Payment',
            'payment_status' => null,
        ]);
        $this->assertSame('Pending', $pendingPayDeal->getResolvedPaymentStatus());

        // 3. Not Started before payment stage without payment records
        $notStartedDeal = new Deal([
            'pipeline_stage' => 'Proposal',
            'payment_status' => null,
        ]);
        $this->assertSame('Not Started', $notStartedDeal->getResolvedPaymentStatus());
    }

    public function test_commercial_status_reflects_stage(): void
    {
        $activationDeal = new Deal([
            'pipeline_stage' => 'Activation',
            'commercial_stage' => null,
        ]);
        $this->assertSame('Activation', $activationDeal->getResolvedCommercialStatus());
    }

    public function test_casa_status_differentiates_activation_from_activated_and_pending(): void
    {
        // 1. Pending before activation stage
        $pendingDeal = new Deal([
            'pipeline_stage' => 'Proposal',
            'casa_stage' => null,
            'start_batches' => [],
        ]);
        $this->assertSame('Pending', $pendingDeal->getResolvedCasaStatus());

        // 2. Activation when in activation stage and undergoing activation (not yet completed)
        $activationDeal = new Deal([
            'pipeline_stage' => 'Activation',
            'casa_stage' => 'Activation',
            'start_batches' => [
                ['id' => 1, 'status' => 'Structuring']
            ],
        ]);
        $this->assertSame('Activation', $activationDeal->getResolvedCasaStatus());

        // 3. Activated when activation is actually completed
        $activatedDeal = new Deal([
            'pipeline_stage' => 'Activation',
            'casa_stage' => 'Activated',
            'start_batches' => [
                ['id' => 1, 'status' => 'Activated', 'service_memo_ref' => 'MEMO-001']
            ],
        ]);
        $this->assertSame('Activated', $activatedDeal->getResolvedCasaStatus());
    }

    public function test_store_proposal_creates_new_revision_when_already_sent_or_approved(): void
    {
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $deal = Deal::create([
            'deal_name' => 'Test Deal Revision',
            'client_name' => 'Test Client',
            'pipeline_stage' => 'Activation',
            'proposal_status' => 'Approved',
        ]);

        $approvedProposal = $deal->proposals()->create([
            'proposal_version' => 1,
            'is_current' => true,
            'status' => 'approved',
            'subject' => 'Initial Proposal',
            'scope' => 'Initial Scope',
            'terms' => 'Standard',
        ]);

        $response = $this->actingAs($user)->post("/deals/{$deal->id}/proposal", [
            'subject' => 'Revised Proposal Subject',
            'scope' => 'Revised Scope',
            'terms' => 'Monthly Retainer',
            'discount' => 0,
            'tax' => 0,
        ]);

        $response->assertStatus(302);

        // Verify old proposal is no longer current and remains approved
        $approvedProposal->refresh();
        $this->assertFalse((bool) $approvedProposal->is_current);
        $this->assertSame('approved', $approvedProposal->status);

        // Verify new proposal revision was created
        $newProposal = $deal->proposals()->where('is_current', true)->first();
        $this->assertNotNull($newProposal);
        $this->assertSame(2, $newProposal->proposal_version);
        $this->assertSame('Draft', $newProposal->status);
        $this->assertSame('Revised Proposal Subject', $newProposal->subject);
    }
}

