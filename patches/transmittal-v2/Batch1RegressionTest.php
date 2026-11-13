<?php

declare(strict_types=1);

namespace Tests\Prepared\TransmittalV2;

use App\Models\Transmittal;
use App\Models\TransmittalDocument;
use App\Models\ProofOfTransmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TransmittalV2Isolation;
use Tests\TestCase;

// Prepared only: outside normal discovery, with no application/autoload entry point.
// The future approved runner must supply an isolated TestCase and synthetic schema.
if (! defined('TRANSMITTAL_V2_BATCH1_TESTS') || TRANSMITTAL_V2_BATCH1_TESTS !== true
    || ! defined('TRANSMITTAL_V2_LAUNCHER') || TRANSMITTAL_V2_LAUNCHER !== true
    || function_exists('proc_open')) {
    throw new \RuntimeException('V2 Batch 1 tests require a separately approved isolated runner.');
}
TransmittalV2Isolation::assertEnvironment();
$expectedTestCase = dirname(__DIR__, 2).'/tests/Support/TransmittalV2TestCase.php';
if (! is_file($expectedTestCase)
    || realpath((string) (new \ReflectionClass(TestCase::class))->getFileName()) !== realpath($expectedTestCase)) {
    throw new \RuntimeException('The future isolated V2 TestCase mapping is required before these tests can load.');
}

final class Batch1RegressionTest extends TestCase
{
    private const OWNER_TOKEN = 'synthetic-record-specific-token-batch-one';

    protected function setUp(): void
    {
        TransmittalV2Isolation::assertNoDatabaseLifecycleTraits(static::class);
        parent::setUp();
        TransmittalV2Isolation::assertBootedGuards($this->app);
        $csrf = str_repeat('b', 40);
        $this->withSession(['_token' => $csrf])->withHeader('X-CSRF-TOKEN', $csrf);
    }

    private function record(?User $owner = null, string $token = self::OWNER_TOKEN): Transmittal
    {
        return Transmittal::query()->create([
            'tracking_number' => 'SYNTHETIC-'.Str::uuid(),
            'subject' => 'Synthetic Batch 1 record',
            'purpose' => 'Isolated authorization regression only.',
            'confidentiality' => 'standard',
            'status' => 'pending_approval',
            'prepared_by' => $owner?->id,
            'edit_token_hash' => hash('sha256', $token),
        ]);
    }

    public static function actions(): array
    {
        return ['recall' => ['recall', 'draft', 'recalled'], 'cancel' => ['cancel', 'cancelled', 'cancelled']];
    }

    public static function invalidGuestAccess(): array
    {
        $cases = [];
        foreach (['recall', 'cancel'] as $action) {
            foreach (['missing', 'wrong', 'other-record', 'other-record-remapped', 'stale'] as $tokenCase) {
                $cases[$action.'-'.$tokenCase] = [$action, $tokenCase];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidGuestAccess')]
    public function test_guest_null_id_never_grants_recall_or_cancel(string $action, string $tokenCase): void
    {
        $record = $this->record();
        $this->assertNull($record->prepared_by);
        $historyCount = $record->histories()->count();
        $other = null;
        $otherToken = 'synthetic-distinct-other-record-token';
        if (in_array($tokenCase, ['other-record', 'other-record-remapped'], true)) {
            $other = $this->record(null, $otherToken);
            $this->assertNotSame($record->id, $other->id);
            $this->assertNotSame($record->edit_token_hash, $other->edit_token_hash);
        }
        if ($tokenCase === 'stale') {
            $oldHash = $record->edit_token_hash;
            $record->forceFill(['edit_token_hash' => hash('sha256', 'synthetic-rotated-current-token')])->save();
            $this->assertNotSame($oldHash, $record->fresh()->edit_token_hash);
        }
        $tokens = match ($tokenCase) {
            'wrong' => [$record->id => 'synthetic-wrong-token'],
            'other-record' => [$other->id => $otherToken],
            'other-record-remapped' => [$record->id => $otherToken],
            'stale' => [$record->id => self::OWNER_TOKEN],
            default => [],
        };

        $this->withSession(['transmittal_draft_tokens' => $tokens])
            ->post(route('transmittals.'.$action, $record), ['reason' => 'Synthetic unauthorized attempt'])
            ->assertNotFound();

        $this->assertSame('pending_approval', $record->fresh()->status);
        $this->assertSame($historyCount, $record->histories()->count());
        if ($other) {
            $this->assertSame('pending_approval', $other->fresh()->status);
            $this->assertSame(0, $other->histories()->count());
        }
    }

    #[DataProvider('actions')]
    public function test_authenticated_owner_needs_no_guest_token(string $action, string $status, string $event): void
    {
        $owner = User::factory()->create(['transmittal_role' => 'preparer']);
        $record = $this->record($owner);
        $this->actingAs($owner)->withSession(['transmittal_draft_tokens' => []])
            ->post(route('transmittals.'.$action, $record), ['reason' => 'Synthetic owner decision'])
            ->assertRedirect();

        $this->assertSame($status, $record->fresh()->status);
        $this->assertDatabaseHas('transmittal_histories', ['transmittal_id' => $record->id, 'action' => $event]);
    }

    #[DataProvider('actions')]
    public function test_guest_with_this_records_valid_token_retains_access(string $action, string $status, string $event): void
    {
        $record = $this->record();
        $this->withSession(['transmittal_draft_tokens' => [$record->id => self::OWNER_TOKEN]])
            ->post(route('transmittals.'.$action, $record), ['reason' => 'Synthetic session-owner decision'])
            ->assertRedirect();

        $this->assertSame($status, $record->fresh()->status);
        $this->assertDatabaseHas('transmittal_histories', ['transmittal_id' => $record->id, 'action' => $event]);
    }

    #[DataProvider('actions')]
    public function test_authenticated_nonowner_without_token_is_denied(string $action, string $status, string $event): void
    {
        $owner = User::factory()->create(['transmittal_role' => 'preparer']);
        $other = User::factory()->create(['transmittal_role' => 'preparer']);
        $record = $this->record($owner);
        $historyCount = $record->histories()->count();
        $this->actingAs($other)->withSession(['transmittal_draft_tokens' => []])
            ->post(route('transmittals.'.$action, $record), ['reason' => 'Synthetic nonowner attempt'])
            ->assertNotFound();

        $this->assertSame('pending_approval', $record->fresh()->status);
        $this->assertSame($historyCount, $record->histories()->count());
    }

    #[DataProvider('actions')]
    public function test_existing_administrator_access_is_preserved(string $action, string $status, string $event): void
    {
        $admin = User::factory()->create(['transmittal_role' => 'administrator']);
        $record = $this->record();
        $this->actingAs($admin)->withSession(['transmittal_draft_tokens' => []])
            ->post(route('transmittals.'.$action, $record), ['reason' => 'Synthetic administrator decision'])
            ->assertRedirect();

        $this->assertSame($status, $record->fresh()->status);
        $this->assertDatabaseHas('transmittal_histories', ['transmittal_id' => $record->id, 'action' => $event]);
    }

    #[DataProvider('actions')]
    public function test_rotated_current_guest_token_is_accepted(string $action, string $status, string $event): void
    {
        $record = $this->record();
        $token = 'synthetic-rotated-current-token';
        $record->forceFill(['edit_token_hash' => hash('sha256', $token)])->save();
        $this->withSession(['transmittal_draft_tokens' => [$record->id => $token]])
            ->post(route('transmittals.'.$action, $record), ['reason' => 'Synthetic rotated-token owner'])
            ->assertRedirect();
        $this->assertSame($status, $record->fresh()->status);
        $this->assertDatabaseHas('transmittal_histories', ['transmittal_id' => $record->id, 'action' => $event]);
    }

    private function existingProof(Transmittal $record, User $user): ProofOfTransmission
    {
        $bytes = "%PDF-1.4\n% Existing synthetic proof\n%%EOF\n";
        $path = 'transmittals/'.$record->id.'/synthetic-existing.pdf';
        Storage::disk('local')->put($path, $bytes);
        $document = $record->documents()->create([
            'uploaded_by' => $user->id, 'category' => 'proof', 'original_name' => 'synthetic-existing.pdf',
            'storage_path' => $path, 'mime_type' => 'application/pdf',
            'size_bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes),
        ]);
        $recipient = $record->recipients()->create(['name' => 'Synthetic Recipient', 'email' => 'recipient@example.test']);

        return $record->proofs()->create([
            'recipient_id' => $recipient->id, 'document_id' => $document->id,
            'channel' => 'courier', 'status' => 'uploaded', 'transmitted_at' => now(),
            'transaction_reference' => 'TXN-'.strtoupper(Str::random(18)),
            'provider_metadata' => ['sender_name' => 'Original synthetic sender'],
        ]);
    }

    private function proofPayload(ProofOfTransmission $proof): array
    {
        return [
            'recipient_id' => $proof->recipient_id, 'channel' => 'courier', 'proof_type' => 'delivery_receipt',
            'sender_name' => 'Synthetic Sender', 'receiver_name' => 'Synthetic Receiver',
            'device_category' => 'desktop', 'transmission_at' => '2026-11-09T10:00:00',
            'edit_reason' => 'Synthetic exception regression', 'return_to' => 'workspace',
            'proof' => UploadedFile::fake()->createWithContent('synthetic-proof.pdf', "%PDF-1.4\n% New synthetic proof\n%%EOF\n"),
        ];
    }

    private function files(object $disk): array
    {
        $snapshot = [];
        foreach ($disk->allFiles() as $path) {
            $snapshot[$path] = hash('sha256', $disk->get($path));
        }
        ksort($snapshot);

        return $snapshot;
    }

    public static function deniedProofEditors(): array
    {
        return [['guest'], ['nonowner'], ['recipient'], ['owner-in-disallowed-state']];
    }

    #[DataProvider('deniedProofEditors')]
    public function test_denied_proof_update_cannot_change_records_history_or_files(string $actor): void
    {
        $disk = Storage::fake('local');
        $owner = User::factory()->create(['transmittal_role' => 'preparer']);
        $record = $this->record($owner);
        $proof = $this->existingProof($record, $owner);
        if ($actor === 'owner-in-disallowed-state') {
            $record->forceFill(['status' => 'sent'])->save();
            $this->actingAs($owner);
        } elseif ($actor !== 'guest') {
            $editor = User::factory()->create(['transmittal_role' => 'preparer']);
            if ($actor === 'recipient') {
                $proof->recipient->forceFill(['email' => $editor->email])->save();
            }
            $this->actingAs($editor);
        }
        $before = [$record->fresh()->getAttributes(), $proof->fresh()->getAttributes(),
            $record->documents()->get()->toArray(), $record->histories()->get()->toArray(), $this->files($disk)];
        $response = $this->withHeader('Accept', 'application/json')
            ->withSession(['transmittal_draft_tokens' => [$record->id => self::OWNER_TOKEN]])
            ->put(route('transmittals.proofs.update', [$record, $proof]), $this->proofPayload($proof));
        if ($actor === 'guest') {
            $response->assertUnauthorized();
        } else {
            $response->assertForbidden();
        }
        $this->assertSame($before, [$record->fresh()->getAttributes(), $proof->fresh()->getAttributes(),
            $record->documents()->get()->toArray(), $record->histories()->get()->toArray(), $this->files($disk)]);
    }

    public function test_authorized_editor_cannot_update_another_records_proof(): void
    {
        $disk = Storage::fake('local');
        $owner = User::factory()->create(['transmittal_role' => 'preparer']);
        $a = $this->record($owner);
        $b = $this->record(null, 'synthetic-other-proof-record-token');
        $proof = $this->existingProof($b, $owner);
        $before = [$a->fresh()->getAttributes(), $b->fresh()->getAttributes(), $proof->fresh()->getAttributes(),
            $a->histories()->get()->toArray(), $b->histories()->get()->toArray(), $this->files($disk)];
        $this->actingAs($owner)->put(route('transmittals.proofs.update', [$a, $proof]), $this->proofPayload($proof))
            ->assertNotFound();
        $this->assertSame($before, [$a->fresh()->getAttributes(), $b->fresh()->getAttributes(), $proof->fresh()->getAttributes(),
            $a->histories()->get()->toArray(), $b->histories()->get()->toArray(), $this->files($disk)]);
    }

    public function test_authenticated_owner_can_update_proof_in_allowed_state(): void
    {
        $disk = Storage::fake('local');
        $owner = User::factory()->create(['transmittal_role' => 'preparer']);
        $record = $this->record($owner);
        $proof = $this->existingProof($record, $owner);
        $oldPath = $proof->document->storage_path;
        $oldHash = hash('sha256', $disk->get($oldPath));
        $this->actingAs($owner)->put(route('transmittals.proofs.update', [$record, $proof]), $this->proofPayload($proof))
            ->assertRedirect();
        $this->assertNotSame($oldPath, $proof->fresh()->document->storage_path);
        $this->assertSame($oldHash, hash('sha256', $disk->get($oldPath)));
        $this->assertDatabaseHas('transmittal_histories', ['transmittal_id' => $record->id, 'action' => 'proof_updated']);
    }

    public static function proofFailures(): array
    {
        $cases = [];
        foreach ([false, true] as $update) {
            foreach ([false, true] as $error) {
                foreach (['rollback', 'commit-then-error'] as $outcome) {
                    $cases[($update ? 'update' : 'upload').'-'.($error ? 'error' : 'exception').'-'.$outcome]
                        = [$update, $error, $outcome, false];
                }
            }
        }
        $cases['update-error-after-commit-and-logging-failure'] = [true, true, 'commit-then-error', true];

        return $cases;
    }

    #[DataProvider('proofFailures')]
    public function test_proof_failure_retains_created_replacement_and_reconciliation_evidence(
        bool $update, bool $error, string $outcome, bool $loggingFails
    ): void {
        $this->withoutExceptionHandling();
        $disk = Storage::fake('local');
        $admin = User::factory()->create(['transmittal_role' => 'administrator']);
        $record = $this->record();
        $proof = $this->existingProof($record, $admin);
        $payload = $this->proofPayload($proof);
        $beforeIds = $record->documents()->pluck('id')->all();
        $beforeProof = $proof->fresh()->getAttributes();
        $beforeHistory = $record->histories()->get()->toArray();
        $oldPath = $proof->document->storage_path;
        $oldHash = hash('sha256', $disk->get($oldPath));
        $newPath = null;
        $newId = null;
        $journalPath = null;
        $failure = $error ? new \Error('Synthetic proof error') : new \RuntimeException('Synthetic proof exception');
        if ($loggingFails) {
            \Illuminate\Support\Facades\Log::shouldReceive('error')->once()->andThrow(new \Error('Synthetic logging error'));
        } else {
            \Illuminate\Support\Facades\Log::spy();
        }
        $connection = DB::connection();
        DB::partialMock()->shouldReceive('transaction')->once()->andReturnUsing(
            function (callable $callback) use ($connection, $record, $beforeIds, $disk, $failure, $outcome,
                &$newPath, &$newId, &$journalPath): never {
                $connection->transaction(function () use ($callback, $record, $beforeIds, $disk, $failure, $outcome,
                    &$newPath, &$newId, &$journalPath): void {
                    // Execute the ACTUAL handler closure first: updateProof creates its replacement here.
                    $callback();
                    $replacement = $record->documents()->whereNotIn('id', $beforeIds)->firstOrFail();
                    $newPath = $replacement->storage_path;
                    $newId = $replacement->id;
                    $this->assertTrue($disk->exists($newPath));
                    $this->assertSame($replacement->sha256, hash('sha256', $disk->get($newPath)));
                    $journals = $disk->files('transmittal-attempts');
                    $this->assertCount(1, $journals);
                    $journalPath = $journals[0];
                    $journal = json_decode($disk->get($journalPath), true, 512, JSON_THROW_ON_ERROR);
                    $this->assertSame($newPath, $journal['storage_path']);
                    $this->assertSame($replacement->sha256, $journal['expected_sha256']);
                    $this->assertSame('write_intended_outcome_unverified', $journal['state']);
                    if ($outcome === 'rollback') {
                        throw $failure;
                    }
                });
                // In this scenario the isolated DB committed, but the handler sees a failure.
                throw $failure;
            }
        );
        $this->actingAs($admin);
        $caught = null;
        try {
            if ($update) {
                $this->put(route('transmittals.proofs.update', [$record, $proof]), $payload);
            } else {
                $this->post(route('transmittals.proofs.store', $record), $payload);
            }
        } catch (\Throwable $exception) {
            $caught = $exception;
        }
        $this->assertSame($failure, $caught);
        $this->assertNotNull($newPath, 'Replacement creation must precede the injected failure.');
        $this->assertTrue($disk->exists($newPath));
        $this->assertTrue($disk->exists($journalPath));
        $this->assertSame($oldHash, hash('sha256', $disk->get($oldPath)));
        if ($outcome === 'commit-then-error') {
            $this->assertTrue($record->documents()->whereKey($newId)->exists());
            $this->assertTrue($record->proofs()->where('document_id', $newId)->exists());
        } else {
            $this->assertSame($beforeProof, $proof->fresh()->getAttributes());
            $this->assertSame($beforeHistory, $record->histories()->get()->toArray());
        }
        if (! $loggingFails) {
            \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->once()
                ->with('V2 document attempt retained after failure.', \Mockery::on(
                    fn (array $context): bool => $context['phase'] === ($update ? 'proof_update' : 'proof_upload')
                        && $context['exception_class'] === $failure::class
                ));
        }
    }

    public static function documentFailures(): array
    {
        return [['exception'], ['error']];
    }

    public function test_failed_intent_journal_stops_before_any_document_write(): void
    {
        $this->withoutExceptionHandling();
        $disk = Storage::fake('local');
        $admin = User::factory()->create(['transmittal_role' => 'administrator']);
        $record = $this->record();
        $proof = $this->existingProof($record, $admin);
        $beforeFiles = $this->files($disk);
        $beforeRows = [$record->documents()->get()->toArray(), $record->histories()->get()->toArray(), $proof->getAttributes()];
        $guardDisk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $guardDisk->shouldReceive('exists')->twice()->andReturn(false);
        $guardDisk->shouldReceive('put')->once()
            ->with(\Mockery::on(fn (string $path): bool => str_starts_with($path, 'transmittal-attempts/')), \Mockery::type('string'))
            ->andReturn(false);
        $guardDisk->shouldNotReceive('putFileAs');
        $guardDisk->shouldNotReceive('delete');
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($guardDisk);
        $caught = null;
        $this->actingAs($admin);
        try {
            $this->post(route('transmittals.proofs.store', $record), $this->proofPayload($proof));
        } catch (\Throwable $exception) {
            $caught = $exception;
        }
        $this->assertInstanceOf(\RuntimeException::class, $caught);
        $this->assertSame('Document reconciliation evidence could not be recorded.', $caught->getMessage());
        $this->assertSame($beforeFiles, $this->files($disk));
        $this->assertSame($beforeRows, [$record->documents()->get()->toArray(), $record->histories()->get()->toArray(), $proof->fresh()->getAttributes()]);
    }

    #[DataProvider('documentFailures')]
    public function test_document_row_failure_retains_file_and_prewrite_journal(string $kind): void
    {
        $this->withoutExceptionHandling();
        $disk = Storage::fake('local');
        $admin = User::factory()->create(['transmittal_role' => 'administrator']);
        $record = $this->record();
        $proof = $this->existingProof($record, $admin);
        $before = $record->documents()->count();
        $oldFiles = $this->files($disk);
        $failure = $kind === 'error' ? new \Error('Synthetic document error') : new \RuntimeException('Synthetic document exception');
        // Registered only in this disposable test application, after the existing fixture.
        TransmittalDocument::creating(function () use ($failure, $disk): never {
            $journals = $disk->files('transmittal-attempts');
            $this->assertCount(1, $journals);
            $journal = json_decode($disk->get($journals[0]), true, 512, JSON_THROW_ON_ERROR);
            $this->assertTrue($disk->exists($journal['storage_path']));
            throw $failure;
        });
        $caught = null;
        $this->actingAs($admin);
        try {
            $this->post(route('transmittals.proofs.store', $record), $this->proofPayload($proof));
        } catch (\Throwable $exception) {
            $caught = $exception;
        }
        $this->assertSame($failure, $caught);
        $this->assertSame($before, $record->documents()->count());
        $journals = $disk->files('transmittal-attempts');
        $this->assertCount(1, $journals);
        $journal = json_decode($disk->get($journals[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($disk->exists($journal['storage_path']));
        $this->assertSame($journal['expected_sha256'], hash('sha256', $disk->get($journal['storage_path'])));
        foreach ($oldFiles as $path => $hash) {
            $this->assertSame($hash, hash('sha256', $disk->get($path)));
        }
    }
}
