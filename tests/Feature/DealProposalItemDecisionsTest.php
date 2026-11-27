<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\PreventBackHistory;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\User;
use App\Services\DealProposalDecisions;
use App\Services\EmailNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DealProposalItemDecisionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->string('role')->nullable();
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
            $table->string('company_name')->nullable();
            $table->string('deal_code')->nullable();
            $table->timestamp('proposal_sent_at')->nullable();
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
            $table->string('prepared_by_name')->nullable();
            $table->string('subject')->nullable();
            $table->string('reference_id')->nullable();
            $table->decimal('price_total', 15, 2)->nullable();
            $table->string('client_form_sent_to_email')->nullable();
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
        (require base_path('database/migrations/2026_10_19_000002_add_proposal_decision_submission_fields.php'))->up();
    }

    public function test_client_item_decisions_and_comments_are_saved_per_proposal_item(): void
    {
        [$deal,$proposal] = $this->createIssuedProposal();
        $this->get(route('deals.proposal.client.show', 'client-token'))->assertOk()->assertSee('Submit Final Decision');
        $this->post(route('deals.proposal.client.items', 'client-token'), [
            'client_name' => 'Victor Sterling', 'responses' => [
                ['line_item_id' => 'service-1', 'decision' => 'declined', 'comment' => 'Beyond our budget.'],
                ['line_item_id' => 'product-2', 'decision' => '', 'comment' => ''],
            ],
        ])->assertRedirect();
        $this->assertDatabaseCount('deal_proposal_item_responses', 1);
        $this->assertSame('partially_approved', $proposal->fresh()->status);
        $this->assertSame('Partially Approved', $deal->fresh()->proposal_decision);
        $this->assertNotNull($proposal->fresh()->client_submitted_at);
        $this->get(route('deals.proposal.client.show', 'client-token'))->assertOk()->assertSee('Partially Approved')->assertSee('Pending')->assertSee('Beyond our budget.')->assertDontSee('Submit Final Decision');
    }

    public function test_final_submission_preserves_the_original_comment_on_replay(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $payload = ['client_name' => 'Client', 'responses' => [['line_item_id' => 'service-1', 'decision' => 'declined', 'comment' => 'Original reason']]];
        $this->post(route('deals.proposal.client.items', 'client-token'), $payload)->assertRedirect();
        $payload['responses'][0]['comment'] = 'Unauthorized change';
        $this->post(route('deals.proposal.client.items', 'client-token'), $payload)->assertStatus(409);
        $this->assertDatabaseCount('deal_proposal_item_responses', 1);
        $this->assertSame('Original reason', $proposal->itemResponses()->first()->comment);
    }

    public function test_submitted_representative_cannot_be_changed_by_replay(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $payload = ['client_name' => 'Original Client', 'responses' => [['line_item_id' => 'service-1', 'decision' => 'accepted']]];
        $this->post(route('deals.proposal.client.items', 'client-token'), $payload)->assertRedirect();
        $payload['client_name'] = 'Different Client';
        $this->post(route('deals.proposal.client.items', 'client-token'), $payload)->assertStatus(409);
        $this->assertSame('Original Client', $proposal->fresh()->client_approved_by_name);
        $this->assertDatabaseCount('deal_proposal_item_responses', 1);
    }

    public function test_changed_item_decisions_email_the_proposal_creator_but_no_change_does_not(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $creatorId = DB::table('users')->insertGetId([
            'name' => 'Proposal Author',
            'email' => 'author@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $proposal->update(['prepared_by_id' => (string) $creatorId]);

        $notificationService = Mockery::mock(EmailNotificationService::class);
        $notificationService->shouldReceive('sendHtml')
            ->once()
            ->withArgs(function (string $recipient, string $subject, string $html): bool {
                return $recipient === 'author@example.test'
                    && str_starts_with($subject, 'Proposal Decision Received')
                    && str_contains($html, 'Casey Client')
                    && str_contains($html, 'AFS Preparation')
                    && str_contains($html, 'The proposed price is beyond our budget.');
            })
            ->andReturn(['success' => true, 'recipient' => 'author@example.test', 'error' => null]);
        $this->app->instance(EmailNotificationService::class, $notificationService);

        $response = [
            'client_name' => 'Casey Client',
            'responses' => [
                ['line_item_id' => 'service-1', 'decision' => 'declined', 'comment' => 'The proposed price is beyond our budget.'],
            ],
        ];
        $this->post(route('deals.proposal.client.items', 'client-token'), $response)
            ->assertRedirect(route('deals.proposal.client.show', 'client-token'));
        $this->post(route('deals.proposal.client.items', 'client-token'), $response)
            ->assertStatus(409);
    }

    public function test_rejection_reason_is_optional(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $this->post(route('deals.proposal.client.items', 'client-token'), ['client_name' => 'Client',
            'responses' => [['line_item_id' => 'service-1', 'decision' => 'declined']],
        ])->assertRedirect();
        $this->assertDatabaseHas('deal_proposal_item_responses', ['deal_proposal_id' => $proposal->id, 'decision' => 'declined', 'comment' => null]);
    }

    public function test_proposal_wide_decline_records_an_item_response_for_each_issued_item(): void
    {
        [$deal, $proposal] = $this->createIssuedProposal();

        $this->post(route('deals.proposal.client.decline', 'client-token'), [
            'client_name' => 'Casey Client',
            'client_feedback' => 'We are not proceeding at this time.',
        ])->assertRedirect(route('deals.proposal.client.show', 'client-token'));

        $this->assertSame('rejected', $proposal->fresh()->status);
        $this->assertSame('Declined', $deal->fresh()->proposal_decision);
        $this->assertDatabaseCount('deal_proposal_item_responses', 2);
        $this->assertDatabaseHas('deal_proposal_item_responses', [
            'deal_proposal_id' => $proposal->id,
            'line_item_id' => 'service-1',
            'decision' => 'declined',
            'comment' => 'We are not proceeding at this time.',
        ]);
        $this->assertDatabaseHas('deal_proposal_item_responses', [
            'deal_proposal_id' => $proposal->id,
            'line_item_id' => 'product-2',
            'decision' => 'declined',
            'comment' => 'We are not proceeding at this time.',
        ]);
    }

    public function test_accepting_every_item_approves_the_overall_proposal(): void
    {
        [$deal,$proposal] = $this->createIssuedProposal();
        $this->post(route('deals.proposal.client.items', 'client-token'), ['client_name' => 'Client', 'responses' => [
            ['line_item_id' => 'service-1', 'decision' => 'accepted'], ['line_item_id' => 'product-2', 'decision' => 'accepted'],
        ]])->assertRedirect();
        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertSame('Approved', $deal->fresh()->proposal_decision);
        $this->assertSame(3300.0, $proposal->fresh()->decision_summary_snapshot['approved_total'] * 1.0);
    }

    public function test_proposal_wide_approval_still_approves_every_issued_item(): void
    {
        [$deal, $proposal] = $this->createIssuedProposal();
        $creatorId = DB::table('users')->insertGetId([
            'name' => 'Proposal Author',
            'email' => 'author@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $proposal->update(['prepared_by_id' => (string) $creatorId]);

        $notificationService = Mockery::mock(EmailNotificationService::class);
        $notificationService->shouldReceive('sendHtml')
            ->once()
            ->withArgs(fn (string $recipient, string $subject, string $html): bool => $recipient === 'author@example.test'
                && str_starts_with($subject, 'Proposal Decision Received')
                && str_contains($html, 'Approved Items')
                && str_contains($html, 'Compliance Kit')
            )
            ->andReturn(['success' => true, 'recipient' => 'author@example.test', 'error' => null]);
        $this->app->instance(EmailNotificationService::class, $notificationService);

        $this->post(route('deals.proposal.client.approve', 'client-token'), [
            'client_name' => 'Casey Client',
        ])->assertRedirect(route('deals.proposal.client.show', 'client-token'));

        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertSame('Approved', $deal->fresh()->proposal_decision);
        $this->assertDatabaseCount('deal_proposal_item_responses', 2);
        $this->assertDatabaseHas('deal_proposal_item_responses', [
            'deal_proposal_id' => $proposal->id,
            'line_item_id' => 'service-1',
            'decision' => 'accepted',
        ]);
        $this->assertDatabaseHas('deal_proposal_item_responses', [
            'deal_proposal_id' => $proposal->id,
            'line_item_id' => 'product-2',
            'decision' => 'accepted',
        ]);
    }

    public function test_item_response_requires_a_valid_issued_item_and_client_token(): void
    {
        [, $proposal] = $this->createIssuedProposal();

        $this->post(route('deals.proposal.client.items', 'client-token'), [
            'client_name' => 'Client',
            'responses' => [
                ['line_item_id' => 'not-in-this-version', 'decision' => 'accepted', 'comment' => ''],
            ],
        ])->assertUnprocessable();

        $this->post(route('deals.proposal.client.items', 'unknown-token'), [
            'client_name' => 'Client',
            'responses' => [
                ['line_item_id' => 'service-1', 'decision' => 'accepted', 'comment' => ''],
            ],
        ])->assertNotFound();

        $this->assertDatabaseCount('deal_proposal_item_responses', 0);
        $this->assertSame('sent', $proposal->fresh()->status);
    }

    public function test_handler_email_contains_all_groups_totals_and_optional_reasons(): void
    {
        [$deal,$proposal] = $this->createIssuedProposal();
        $proposal->update(['handler_user_id' => 99, 'handler_name' => 'Actual Sender', 'handler_email' => 'handler@example.test', 'prepared_by_name' => 'Different Author']);
        $mail = Mockery::mock(EmailNotificationService::class);
        $mail->shouldReceive('sendHtml')->once()->withArgs(function ($to, $subject, $html) {
            return $to === 'handler@example.test' && str_starts_with($subject, 'Proposal Decision Received')
                && str_contains($html, 'Approved Items') && str_contains($html, 'Rejected Items')
                && str_contains($html, 'Pending Items') && str_contains($html, '2,500.00') && str_contains($html, '800.00')
                && str_contains($html, 'Actual Sender') && str_contains($html, 'Asia/Manila');
        })->andReturn(['success' => true]);
        $this->app->instance(EmailNotificationService::class, $mail);
        $this->post(route('deals.proposal.client.items', 'client-token'), ['client_name' => 'Client', 'responses' => [
            ['line_item_id' => 'service-1', 'decision' => 'accepted'], ['line_item_id' => 'product-2', 'decision' => 'declined'],
        ]])->assertRedirect();
        $this->assertNotNull($proposal->fresh()->decision_notified_at);
        $this->assertSame('partially_approved', $proposal->fresh()->status);
    }

    public function test_email_failure_preserves_decisions_and_retries_only_the_recorded_handler(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $proposal->update(['handler_name' => 'Handler', 'handler_email' => 'handler@example.test']);
        $mail = Mockery::mock(EmailNotificationService::class);
        $mail->shouldReceive('sendHtml')->once()->andReturn(['success' => false, 'error' => 'Temporary SMTP error']);
        $this->app->instance(EmailNotificationService::class, $mail);
        $this->post(route('deals.proposal.client.items', 'client-token'), ['client_name' => 'Client', 'responses' => [
            ['line_item_id' => 'service-1', 'decision' => 'accepted'],
        ]])->assertRedirect();
        $this->assertDatabaseCount('deal_proposal_item_responses', 1);
        $this->assertSame('partially_approved', $proposal->fresh()->status);
        $this->assertSame('Temporary SMTP error', $proposal->fresh()->decision_notification_error);
        $retry = Mockery::mock(EmailNotificationService::class);
        $retry->shouldReceive('sendHtml')->once()->andReturn(['success' => true]);
        $this->app->instance(EmailNotificationService::class, $retry);
        $service = app(DealProposalDecisions::class);
        $this->assertTrue($service->notify($proposal));
        $this->assertTrue($service->notify($proposal));
        $this->assertNotNull($proposal->fresh()->decision_notified_at);
        $this->assertNull($proposal->fresh()->decision_notification_error);
    }

    public function test_revised_and_expired_links_cannot_submit_new_decisions(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $proposal->update(['is_current' => false]);
        $payload = ['client_name' => 'Client', 'responses' => [['line_item_id' => 'service-1', 'decision' => 'accepted']]];
        $this->post(route('deals.proposal.client.items', 'client-token'), $payload)->assertStatus(409);
        $proposal->update(['is_current' => true, 'client_access_expires_at' => now()->subMinute()]);
        $this->post(route('deals.proposal.client.items', 'client-token'), $payload)->assertForbidden();
        $this->assertDatabaseCount('deal_proposal_item_responses', 0);
    }

    public function test_revised_proposal_does_not_inherit_submitted_decisions_or_handler_metadata(): void
    {
        [, $proposal] = $this->createIssuedProposal();
        $this->post(route('deals.proposal.client.items', 'client-token'), ['client_name' => 'Client', 'responses' => [
            ['line_item_id' => 'service-1', 'decision' => 'accepted'],
        ]])->assertRedirect();
        $proposal = $proposal->fresh();
        $revision = $proposal->replicate(['client_access_token', 'client_form_sent_at']);
        $revision->forceFill(['proposal_version' => 2, 'status' => 'Draft'])->save();
        $this->assertNull($revision->client_submitted_at);
        $this->assertNull($revision->decision_summary_snapshot);
        $this->assertNull($revision->handler_email);
        $this->assertSame(0, $revision->itemResponses()->count());
        $this->assertSame(1, $proposal->itemResponses()->count());
    }

    public function test_staff_send_client_review_final_submission_and_handler_notification_work_end_to_end(): void
    {
        [$deal,$proposal] = $this->createIssuedProposal();
        $senderId = DB::table('users')->insertGetId(['name' => 'Actual Sender', 'email' => 'sender@example.test', 'role' => 'Employee', 'created_at' => now(), 'updated_at' => now()]);
        $authorId = DB::table('users')->insertGetId(['name' => 'Author', 'email' => 'author@example.test', 'role' => 'Employee', 'created_at' => now(), 'updated_at' => now()]);
        $proposal->update(['prepared_by_id' => (string) $authorId, 'status' => 'Draft', 'client_form_sent_at' => null, 'client_access_token' => null, 'price_total' => 3300]);
        $mail = Mockery::mock(EmailNotificationService::class);
        $mail->shouldReceive('sendHtml')->once()->withArgs(fn ($to, $subject, $html) => $to === 'client@example.test' && str_contains($html, '/proposal/respond/') && str_contains($html, 'secure'))->andReturn(['success' => true]);
        $mail->shouldReceive('sendHtml')->once()->withArgs(fn ($to, $subject, $html) => $to === 'sender@example.test' && str_starts_with($subject, 'Proposal Decision Received') && str_contains($html, 'Partially Approved') && str_contains($html, 'Approved Items') && str_contains($html, 'Pending Items'))->andReturn(['success' => true]);
        $this->app->instance(EmailNotificationService::class, $mail);
        $this->actingAs(User::findOrFail($senderId))->withoutMiddleware([EnsurePasswordChanged::class, PreventBackHistory::class]);
        $this->post(route('deals.proposal.send', $deal), ['recipient_email' => 'client@example.test'])->assertRedirect();
        $sent = $proposal->fresh();
        $this->assertSame($senderId, $sent->handler_user_id);
        $this->assertSame('pending_client_review', $sent->client_decision_status);
        $this->assertSame(64, strlen($sent->client_access_token));
        $this->assertTrue($sent->client_access_expires_at->isFuture());
        $this->get(route('deals.proposal.client.show', $sent->client_access_token))->assertOk()->assertSee('AFS Preparation')->assertSee('Compliance Kit')->assertSee('Submit Final Decision');
        $payload = ['client_name' => 'Casey Client', 'responses' => [['line_item_id' => 'service-1', 'decision' => 'accepted'], ['line_item_id' => 'product-2', 'decision' => 'declined', 'comment' => 'Not needed']]];
        $this->post(route('deals.proposal.client.items', $sent->client_access_token), $payload)->assertRedirect();
        $this->assertSame('partially_approved', $sent->fresh()->status);
        $this->assertSame(2500.0, (float) $sent->fresh()->decision_summary_snapshot['approved_total']);
        $this->assertNotNull($sent->fresh()->decision_notified_at);
        $this->post(route('deals.proposal.client.items', $sent->client_access_token), $payload)->assertStatus(409);
    }

    public function test_all_rejected_items_set_the_proposal_status_to_rejected(): void
    {
        [$deal,$proposal] = $this->createIssuedProposal();
        $this->post(route('deals.proposal.client.items', 'client-token'), ['client_name' => 'Client', 'responses' => [
            ['line_item_id' => 'service-1', 'decision' => 'declined'], ['line_item_id' => 'product-2', 'decision' => 'declined'],
        ]])->assertRedirect();
        $this->assertSame('rejected', $proposal->fresh()->status);
        $this->assertSame(0.0, (float) $proposal->fresh()->decision_summary_snapshot['approved_total']);
    }

    public function test_a_client_account_cannot_send_a_proposal_as_staff(): void
    {
        [$deal,$proposal] = $this->createIssuedProposal();
        $proposal->update(['status' => 'Draft', 'client_form_sent_at' => null, 'client_access_token' => null]);
        $clientId = DB::table('users')->insertGetId(['name' => 'Client', 'email' => 'client-account@example.test', 'role' => 'Client', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(User::findOrFail($clientId))->withoutMiddleware([EnsurePasswordChanged::class, PreventBackHistory::class]);
        $this->post(route('deals.proposal.send', $deal), ['recipient_email' => 'client@example.test'])->assertForbidden();
        $this->assertNull($proposal->fresh()->client_access_token);
        $this->assertSame('Draft', $proposal->fresh()->status);
    }

    private function createIssuedProposal(): array
    {
        $contact = Contact::query()->create([
            'first_name' => 'Casey',
            'last_name' => 'Client',
            'email' => 'casey@example.test',
        ]);
        $deal = Deal::withoutEvents(fn (): Deal => Deal::query()->create([
            'contact_id' => $contact->id,
            'deal_name' => 'Client proposal test',
            'proposal_decision' => 'Pending',
            'services_products' => [
                ['id' => 'service-1', 'type' => 'SERVICE', 'name' => 'AFS Preparation'],
                ['id' => 'product-2', 'type' => 'PRODUCT', 'name' => 'Compliance Kit'],
            ],
        ]));
        $proposal = DealProposal::query()->create([
            'deal_id' => $deal->id,
            'proposal_version' => 1,
            'is_current' => true,
            'status' => 'sent',
            'client_access_token' => 'client-token',
            'client_form_sent_at' => now(),
            'prepared_by_id' => null,
            'line_items_snapshot' => [
                ['id' => 'service-1', 'type' => 'SERVICE', 'name' => 'AFS Preparation', 'qty' => 1, 'unit' => 'lot', 'unitPrice' => 2500, 'line_total' => 2500],
                ['id' => 'product-2', 'type' => 'PRODUCT', 'name' => 'Compliance Kit', 'qty' => 2, 'unit' => 'pcs', 'unitPrice' => 400, 'line_total' => 800],
            ],
        ]);

        return [$deal, $proposal];
    }
}
