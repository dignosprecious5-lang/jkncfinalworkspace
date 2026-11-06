<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealProposal;
use App\Models\DealProposalDelivery;
use App\Models\DealProposalVersion;
use App\Services\DealProposalDeliveryService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\DealsMysqlTestCase;

class DealProposalDeliveryTest extends DealsMysqlTestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
    }

    private const TOKEN = 'delivery-client-token-synthetic';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createVersion(?\DateTimeInterface $expiresAt = null, string $suffix = ''): DealProposalVersion
    {
        $contact = Contact::query()->create([
            'first_name' => 'Delivery',
            'last_name' => 'Test',
            'email' => 'delivery'.($suffix !== '' ? '+'.$suffix : '').'@example.invalid',
        ]);
        $deal = Deal::query()->create([
            'contact_id' => $contact->id,
            'deal_name' => 'Proposal delivery test',
            'stage' => 'Proposal',
        ]);
        $proposal = $deal->proposal()->create(['status' => 'sent']);
        $expiry = $expiresAt ?? now()->addDays(2);
        $proposal->forceFill([
            'client_access_token' => self::TOKEN.$suffix,
            'client_access_expires_at' => $expiry,
        ])->save();

        return DealProposalVersion::query()->create([
            'deal_proposal_id' => $proposal->id,
            'version_number' => 1,
            'snapshot_schema_version' => 1,
            'resolved_proposal_data' => ['client_name' => 'Delivery Test'],
            'commercial_values' => ['total' => '100.00'],
            'source_metadata' => ['captured_from' => 'synthetic_test'],
            'document_identifiers' => ['reference_id' => 'DELIVERY-TEST'.$suffix],
            'rendered_html' => '<html><body>Delivery snapshot</body></html>',
            'artifact_private_path' => 'deal-proposal-versions/'.$proposal->id.'/delivery-test.pdf',
            'artifact_sha256' => str_repeat('d', 64),
            'issued_token_sha256' => hash('sha256', self::TOKEN.$suffix),
            'issued_to_email' => 'delivery'.($suffix !== '' ? '+'.$suffix : '').'@example.invalid',
            'issued_at' => now(),
            'client_access_expires_at' => $expiry,
            'issuance_request_key' => 'delivery-issuance-request-1'.$suffix,
            'issuance_request_fingerprint' => DealProposalVersion::fingerprintIssuanceRequest(
                (int) $proposal->id,
                'delivery'.($suffix !== '' ? '+'.$suffix : '').'@example.invalid',
                ['reference_id' => 'DELIVERY-TEST'.$suffix],
            ),
        ]);
    }

    private function deliveryPayload(DealProposalVersion $version, string $key = 'delivery-key-1'): array
    {
        return [
            'deal_proposal_version_id' => $version->id,
            'idempotency_key' => $key,
            'recipient_email' => $version->issued_to_email,
            'status' => DealProposalDelivery::STATUS_PENDING,
            'attempt_count' => 0,
            'next_attempt_at' => now(),
        ];
    }

    private function createDelivery(DealProposalVersion $version, string $key = 'delivery-key-1'): DealProposalDelivery
    {
        return DealProposalDelivery::query()->create($this->deliveryPayload($version, $key));
    }

    private function expectSuccessfulHtmlMail(string $expectedToken, string $recipient = 'delivery@example.invalid'): void
    {
        Mail::shouldReceive('html')
            ->once()
            ->andReturnUsing(function (string $html, callable $callback) use ($expectedToken, $recipient): void {
                $this->assertStringContainsString(route('deals.proposal.client.show', ['token' => $expectedToken]), $html);
                $this->assertStringContainsString('Delivery Test', $html);
                $message = Mockery::mock();
                $message->shouldReceive('from')->once()->andReturnSelf();
                $message->shouldReceive('to')->once()->with($recipient)->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('Proposal for Delivery Test (Version 1)')->andReturnSelf();
                $callback($message);
            });
    }

    public function test_each_issued_version_has_at_most_one_delivery_record(): void
    {
        $version = $this->createVersion();
        DealProposalDelivery::query()->create($this->deliveryPayload($version));

        $duplicate = $this->deliveryPayload($version, 'delivery-key-2');
        $this->expectException(QueryException::class);
        DealProposalDelivery::query()->create($duplicate);
    }

    public function test_delivery_state_transitions_are_valid_and_error_details_are_sanitized(): void
    {
        $version = $this->createVersion();
        $delivery = DealProposalDelivery::query()->create($this->deliveryPayload($version));

        $delivery->transitionTo(DealProposalDelivery::STATUS_PROCESSING, [
            'attempt_count' => 1,
            'claimed_at' => now(),
            'lease_expires_at' => now()->addMinutes(2),
        ]);
        $delivery->transitionTo(DealProposalDelivery::STATUS_PENDING, [
            'claimed_at' => null,
            'lease_expires_at' => null,
            'next_attempt_at' => now()->addMinute(),
            'last_error_code' => 'smtp_unavailable',
            'last_error_message' => 'Failure https://mail.invalid/debug for client@example.invalid token ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        ]);

        $delivery->refresh();
        $this->assertSame(DealProposalDelivery::STATUS_PENDING, $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertSame('SMTP_UNAVAILABLE', $delivery->last_error_code);
        $this->assertStringNotContainsString('mail.invalid', $delivery->last_error_message);
        $this->assertStringNotContainsString('client@example.invalid', $delivery->last_error_message);
        $this->assertStringNotContainsString('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $delivery->last_error_message);

        $delivery->transitionTo(DealProposalDelivery::STATUS_PROCESSING, [
            'attempt_count' => 2,
            'claimed_at' => now(),
            'lease_expires_at' => now()->addMinutes(2),
            'last_error_code' => null,
            'last_error_message' => null,
        ]);
        $delivery->transitionTo(DealProposalDelivery::STATUS_SENT, [
            'sent_at' => now(),
            'claimed_at' => null,
            'lease_expires_at' => null,
        ]);

        $this->assertSame(DealProposalDelivery::STATUS_SENT, $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
    }

    public function test_terminal_delivery_states_cannot_be_reopened(): void
    {
        $version = $this->createVersion();
        $delivery = DealProposalDelivery::query()->create($this->deliveryPayload($version));
        $delivery->transitionTo(DealProposalDelivery::STATUS_SUPERSEDED);

        $this->expectException(DomainException::class);
        $delivery->transitionTo(DealProposalDelivery::STATUS_PENDING);
    }

    public function test_outbox_references_a_real_version_and_contains_no_raw_client_token_field(): void
    {
        $this->assertNotContains('client_access_token', (new DealProposalDelivery())->getFillable());
        $this->assertNotContains('client_access_token', (new DealProposalVersion())->getFillable());

        $this->expectException(QueryException::class);
        DealProposalDelivery::query()->create([
            'deal_proposal_version_id' => 987654321,
            'idempotency_key' => 'missing-version-delivery',
            'recipient_email' => 'delivery@example.invalid',
            'status' => DealProposalDelivery::STATUS_PENDING,
        ]);
    }

    public function test_existing_foreign_keys_prevent_missing_proposal_or_version_targets(): void
    {
        $version = $this->createVersion();
        $this->createDelivery($version);

        try {
            DB::table('deal_proposals')->where('id', $version->deal_proposal_id)->delete();
            $this->fail('An issued version must prevent its proposal from disappearing.');
        } catch (QueryException) {
            $this->assertTrue(DealProposalVersion::query()->whereKey($version->id)->exists());
            $this->assertTrue(DealProposalDelivery::query()->where('deal_proposal_version_id', $version->id)->exists());
        }
    }

    public function test_successful_delivery_uses_exact_issued_link_and_marks_outbox_sent(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        $this->expectSuccessfulHtmlMail(self::TOKEN);

        $summary = app(DealProposalDeliveryService::class)->processDue(10);

        $delivery->refresh();
        $this->assertSame(['claimed' => 1, 'sent' => 1, 'failed' => 0, 'cancelled' => 0], $summary);
        $this->assertSame(DealProposalDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertNotNull($delivery->sent_at);
        $this->assertSame(self::TOKEN, $version->proposal->fresh()->client_access_token);
        $this->assertSame(1, DealProposalVersion::query()->count());
    }

    public function test_mail_provider_failure_is_sanitized_and_scheduled_for_retry(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        Mail::shouldReceive('html')->once()->andThrow(new RuntimeException('Provider failed for '.self::TOKEN.' at https://mail.invalid/debug'));

        $summary = app(DealProposalDeliveryService::class)->processDue(10);
        $delivery->refresh();

        $this->assertSame(1, $summary['failed']);
        $this->assertSame(DealProposalDelivery::STATUS_PENDING, $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertNotNull($delivery->next_attempt_at);
        $this->assertSame('MAIL_DELIVERY_FAILED', $delivery->last_error_code);
        $this->assertSame('The mail provider did not confirm delivery.', $delivery->last_error_message);
        $this->assertStringNotContainsString(self::TOKEN, $delivery->last_error_message);
        $this->assertStringNotContainsString('mail.invalid', $delivery->last_error_message);
    }

    public function test_retry_reuses_same_version_token_recipient_and_delivery_without_rotation(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        Mail::shouldReceive('html')->once()->andThrow(new RuntimeException('Synthetic provider failure.'));
        app(DealProposalDeliveryService::class)->processDue(1);
        $delivery->refresh();
        $retryAt = $delivery->next_attempt_at->copy()->addSecond();
        Carbon::setTestNow($retryAt);
        $this->expectSuccessfulHtmlMail(self::TOKEN);

        $summary = app(DealProposalDeliveryService::class)->processDue(1);

        $delivery->refresh();
        $this->assertSame(1, $summary['sent']);
        $this->assertSame(DealProposalDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame(2, $delivery->attempt_count);
        $this->assertSame((int) $version->id, (int) $delivery->deal_proposal_version_id);
        $this->assertSame('delivery@example.invalid', $delivery->recipient_email);
        $this->assertSame(self::TOKEN, $version->proposal->fresh()->client_access_token);
    }

    public function test_superseded_token_cancels_delivery_without_sending(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        $version->proposal->forceFill(['client_access_token' => 'new-current-synthetic-token'])->save();
        Mail::shouldReceive('html')->never();

        $summary = app(DealProposalDeliveryService::class)->processDue(1);

        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(DealProposalDelivery::STATUS_SUPERSEDED, $delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->claimed_at);
    }

    public function test_expired_token_cancels_delivery_without_sending(): void
    {
        $expiry = now()->subSecond();
        $version = $this->createVersion($expiry);
        $delivery = $this->createDelivery($version);
        Mail::shouldReceive('html')->never();

        $summary = app(DealProposalDeliveryService::class)->processDue(1);

        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(DealProposalDelivery::STATUS_SUPERSEDED, $delivery->fresh()->status);
    }

    public function test_recipient_mismatch_cancels_delivery_without_sending(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        DB::table('deal_proposal_deliveries')->where('id', $delivery->id)->update([
            'recipient_email' => 'different@example.invalid',
        ]);
        Mail::shouldReceive('html')->never();

        $summary = app(DealProposalDeliveryService::class)->processDue(1);

        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame(DealProposalDelivery::STATUS_SUPERSEDED, $delivery->fresh()->status);
    }

    public function test_active_delivery_claim_prevents_a_second_processor_claim(): void
    {
        $version = $this->createVersion();
        $this->createDelivery($version);
        $service = app(DealProposalDeliveryService::class);

        $firstClaims = $service->claimDueDeliveries(1);
        $secondClaims = $service->claimDueDeliveries(1);

        $this->assertCount(1, $firstClaims);
        $this->assertSame([], $secondClaims);
        $this->assertSame(DealProposalDelivery::STATUS_PROCESSING, DealProposalDelivery::query()->sole()->status);
    }

    public function test_concurrent_worker_skips_locked_delivery_and_claims_next_due_row(): void
    {
        $base = config('database.connections.mysql');
        config(['database.connections.deals_claim_fixture' => $base]);
        DB::purge('deals_claim_fixture');
        $originalDefault = DB::getDefaultConnection();
        DB::setDefaultConnection('deals_claim_fixture');
        try {
            $firstVersion = $this->createVersion(null, '-first');
            $secondVersion = $this->createVersion(null, '-second');
            $first = $this->createDelivery($firstVersion, 'delivery-key-first');
            $second = $this->createDelivery($secondVersion, 'delivery-key-second');
        } finally {
            DB::setDefaultConnection($originalDefault);
        }
        config(['database.connections.deals_claim_worker' => $base]);
        DB::purge('deals_claim_worker');
        config(['database.connections.deals_claim_lock_test' => $base]);
        DB::purge('deals_claim_lock_test');
        $locker = DB::connection('deals_claim_lock_test');
        $previousTimeout = (int) $locker->scalar('SELECT @@innodb_lock_wait_timeout');
        $locker->statement('SET SESSION innodb_lock_wait_timeout = 3');
        $locker->beginTransaction();
        try {
            $locker->table('deal_proposal_deliveries')->where('id', $first->id)->lockForUpdate()->first();
            $originalDefault = DB::getDefaultConnection();
            DB::setDefaultConnection('deals_claim_worker');
            try {
                $claimed = app(DealProposalDeliveryService::class)->claimDueDeliveries(1);
            } finally {
                DB::setDefaultConnection($originalDefault);
            }
            $this->assertCount(1, $claimed);
            $this->assertSame((int) $second->id, (int) $claimed[0]['id']);
            $this->assertSame(DealProposalDelivery::STATUS_PENDING, $first->fresh()->status);
            $this->assertSame(DealProposalDelivery::STATUS_PROCESSING, $second->fresh()->status);
        } finally {
            $locker->rollBack();
            $locker->statement('SET SESSION innodb_lock_wait_timeout = '.max(1, $previousTimeout));
            DB::disconnect('deals_claim_lock_test');
            DB::purge('deals_claim_lock_test');
            $fixture = DB::connection('deals_claim_fixture');
            $proposalIds = [$firstVersion->deal_proposal_id, $secondVersion->deal_proposal_id];
            $dealIds = $fixture->table('deal_proposals')->whereIn('id', $proposalIds)->pluck('deal_id')->all();
            $contactIds = $fixture->table('deals')->whereIn('id', $dealIds)->pluck('contact_id')->all();
            $fixture->table('deal_proposal_deliveries')->whereIn('id', [$first->id, $second->id])->delete();
            $fixture->table('deal_proposal_versions')->whereIn('id', [$firstVersion->id, $secondVersion->id])->delete();
            $fixture->table('deal_proposals')->whereIn('id', $proposalIds)->delete();
            $fixture->table('deals')->whereIn('id', $dealIds)->delete();
            $fixture->table('contacts')->whereIn('id', $contactIds)->delete();
            DB::purge('deals_claim_fixture');
            DB::purge('deals_claim_worker');
        }
    }

    public function test_processor_rejects_unbounded_batch_sizes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(DealProposalDeliveryService::class)->claimDueDeliveries(101);
    }

    public function test_expired_worker_lease_is_recovered_and_reclaimed(): void
    {
        $version = $this->createVersion();
        $this->createDelivery($version);
        $service = app(DealProposalDeliveryService::class);
        $firstClaim = $service->claimDueDeliveries(1)[0];

        Carbon::setTestNow(now()->addMinutes(11));
        $reclaimed = $service->claimDueDeliveries(1);

        $this->assertCount(1, $reclaimed);
        $this->assertSame($firstClaim['id'], $reclaimed[0]['id']);
        $this->assertNotSame($firstClaim['claimed_at'], $reclaimed[0]['claimed_at']);
        $this->assertSame(2, $reclaimed[0]['attempt_count']);
        $this->assertSame(DealProposalDelivery::STATUS_PROCESSING, DealProposalDelivery::query()->sole()->status);
    }

    public function test_retry_backoff_is_bounded_at_six_hours(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        Mail::shouldReceive('html')->times(11)->andThrow(new RuntimeException('Synthetic provider failure.'));
        $service = app(DealProposalDeliveryService::class);

        for ($attempt = 1; $attempt <= 11; $attempt++) {
            if ($attempt > 1) {
                Carbon::setTestNow($delivery->fresh()->next_attempt_at->copy()->addSecond());
            }
            $service->processDue(1);
            $delivery->refresh();
        }

        $delay = $delivery->next_attempt_at->getTimestamp() - now()->getTimestamp();
        $this->assertSame(11, $delivery->attempt_count);
        $this->assertSame(21600, $delay);
        $this->assertSame(DealProposalDelivery::STATUS_PENDING, $delivery->status);
    }

    public function test_sent_delivery_is_not_claimed_or_sent_again(): void
    {
        $version = $this->createVersion();
        $delivery = $this->createDelivery($version);
        $this->expectSuccessfulHtmlMail(self::TOKEN);
        $service = app(DealProposalDeliveryService::class);
        $first = $service->processDue(1);
        $second = $service->processDue(1);

        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $second['claimed']);
        $this->assertSame(DealProposalDelivery::STATUS_SENT, $delivery->fresh()->status);
    }

    public function test_command_contract_is_bounded_and_not_scheduled(): void
    {
        $command = new \App\Console\Commands\ProcessDealProposalDeliveries();
        $this->assertSame('ordo:process-deal-proposal-deliveries', $command->getName());
        $option = $command->getDefinition()->getOption('limit');
        $this->assertSame('25', $option->getDefault());
        $this->assertSame('Maximum deliveries to claim (1-100)', $option->getDescription());
        $this->assertSame('App\\Console\\Commands\\ProcessDealProposalDeliveries', get_class($command));
    }
}
