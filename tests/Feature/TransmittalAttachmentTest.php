<?php

namespace Tests\Feature;

use App\Services\TransmittalAttachmentAttempt;
use App\Services\TransmittalAttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TransmittalV1Fixture;
use Tests\Support\TransmittalV1Isolation;
use Tests\TestCase;

// Prepared, not execution-approved. Existing fixed V1/OCR runners remain unchanged.
if (! defined('TRANSMITTAL_ATTACHMENT_STAGE_A_TESTS') || TRANSMITTAL_ATTACHMENT_STAGE_A_TESTS !== true
    || ! defined('TRANSMITTAL_V1_BOOTSTRAPPED') || function_exists('proc_open')) {
    throw new \RuntimeException('Stage A tests require separate execution approval and an approved isolated attachment bootstrap.');
}
TransmittalV1Isolation::assertEnvironment();
if (realpath((string) (new \ReflectionClass(TestCase::class))->getFileName()) !== realpath(__DIR__.'/../Support/TransmittalV1TestCase.php')) {
    throw new \RuntimeException('The guarded in-memory V1 TestCase mapping is missing.');
}

final class TransmittalAttachmentTest extends TestCase
{
    use TransmittalV1Fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTransmittalV1Fixture();
        $this->actingAs($this->v1User());
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function payload(array $items = [], array $itemFiles = [], array $attachments = []): array
    {
        $manifest = ['version' => 1, 'item_files' => [], 'attachments' => []];
        foreach (['item_files' => $itemFiles, 'attachments' => $attachments] as $group => $files) {
            foreach ($files as $index => $file) $manifest[$group][] = ['index' => $index, 'size' => $file->getSize()];
        }
        return ['mode' => 'SEND', 'party_name' => 'Synthetic Party', 'office_name' => 'Synthetic Office',
            'items' => json_encode($items), 'item_files' => $itemFiles, 'attachments' => $attachments,
            'upload_manifest' => json_encode($manifest)];
    }

    private function submitPayload(array $payload): \Illuminate\Testing\TestResponse
    {
        $files = array_intersect_key($payload, array_flip(['item_files', 'attachments']));
        $parameters = array_diff_key($payload, $files);

        return $this->withHeader('Accept', 'application/json')
            ->call('POST', '/transmittal', $parameters, [], $files);
    }

    public function test_v1_save_keeps_workflow_sparse_associations_and_file_bytes(): void
    {
        $itemFile = UploadedFile::fake()->createWithContent('item.pdf', "%PDF-1.4\nSYNTHETIC ITEM");
        $support = UploadedFile::fake()->createWithContent('support.txt', 'SYNTHETIC SUPPORT');
        $response = $this->submitPayload($this->payload([[], [], ['particular' => 'Third item', 'qty' => '0']], [2 => $itemFile], [$support]));
        $response->assertOk()->assertJsonStructure(['id', 'message']);
        $record = \App\Models\Transmittal::with(['items', 'attachments'])->findOrFail($response->json('id'));
        $this->assertSame('Uploaded', $record->workflow_status);
        $this->assertSame('Pending', $record->approval_status);
        $this->assertCount(1, $record->items);
        $this->assertSame(3, (int) $record->items[0]->item_no);
        $this->assertSame("%PDF-1.4\nSYNTHETIC ITEM", Storage::disk('public')->get($record->items[0]->attachment_path));
        $this->assertSame('SYNTHETIC SUPPORT', Storage::disk('public')->get($record->attachments[0]->file_path));
    }

    public function test_missing_manifest_or_file_never_creates_partial_record(): void
    {
        $payload = $this->payload();
        unset($payload['upload_manifest']);
        $this->submitPayload($payload)->assertUnprocessable()->assertJsonStructure(['reference', 'errors']);
        $file = UploadedFile::fake()->createWithContent('support.txt', 'EXPECTED');
        $payload = $this->payload([], [], [$file]);
        $payload['attachments'] = [];
        $this->submitPayload($payload)->assertUnprocessable();
        $this->assertDatabaseCount('transmittals', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function failAfterFirstUpload(): void
    {
        $this->app->instance(TransmittalAttachmentService::class, new class extends TransmittalAttachmentService {
            public function attempt(string $reference): TransmittalAttachmentAttempt
            {
                return new class($reference) extends TransmittalAttachmentAttempt {
                    private int $writes = 0;
                    public function store(UploadedFile $file, string $category): string
                    {
                        if (++$this->writes === 2) throw new \RuntimeException('SENSITIVE-SQL-AND-PATH-SENTINEL');
                        return parent::store($file, $category);
                    }
                };
            }
        });
    }

    public function test_later_write_failure_retains_files_even_after_acknowledged_rollback(): void
    {
        Storage::disk('public')->put('transmittal/attachments/legacy.txt', 'LEGACY-SENTINEL');
        $this->failAfterFirstUpload();
        Log::shouldReceive('error')->once()->with('Transmittal attachment save failed.', \Mockery::on(function ($context) {
            return $context['rollback_acknowledged'] === true && $context['file_retention']['deleted'] === 0
                && $context['file_retention']['retained'] === 1
                && $context['transaction_guarantees'] === 'not_independently_verified'
                && array_intersect(['exception', 'trace', 'request'], array_keys($context)) === [];
        }));
        $files = [UploadedFile::fake()->createWithContent('one.txt', 'ONE'), UploadedFile::fake()->createWithContent('two.txt', 'TWO')];
        $response = $this->submitPayload($this->payload([], [], $files));
        $response->assertStatus(500)->assertJsonPath('save_status', 'unknown')->assertJsonStructure(['reference']);
        $this->assertStringNotContainsString('SENSITIVE', $response->getContent());
        $this->assertDatabaseCount('transmittals', 0);
        $this->assertDatabaseCount('transmittal_attachments', 0);
        $allFiles = Storage::disk('public')->allFiles();
        $this->assertCount(2, $allFiles);
        $retained = array_values(array_diff($allFiles, ['transmittal/attachments/legacy.txt']));
        $this->assertCount(1, $retained);
        $this->assertSame('ONE', Storage::disk('public')->get($retained[0]));
        $this->assertSame('LEGACY-SENTINEL', Storage::disk('public')->get('transmittal/attachments/legacy.txt'));
    }

    public static function uncertainRollbackScenarios(): array
    {
        return [
            'connection lost before rollback' => ['connection_loss'],
            'connection replaced during rollback acknowledgement' => ['reconnection'],
            'rollback failure' => ['rollback_failure'],
            'unexpected transaction depth' => ['unexpected_state'],
            'unknown table/model guarantees' => ['unknown_guarantees'],
            'verification query would reconnect' => ['verification_reconnection'],
            'verification query would fail' => ['verification_failure'],
        ];
    }

    #[DataProvider('uncertainRollbackScenarios')]
    public function test_uncertain_safety_prerequisites_never_authorize_file_deletion(string $scenario): void
    {
        Storage::disk('public')->put('transmittal/attachments/legacy.txt', 'LEGACY-SENTINEL');
        $this->failAfterFirstUpload();
        $manager = DB::getFacadeRoot();
        $connection = DB::connection();
        $originalPdo = $connection->getPdo();
        $proxy = \Mockery::mock($connection);
        $rawReads = $levelReads = $verificationAttempts = 0;
        $proxy->shouldReceive('transactionLevel')->andReturnUsing(function () use ($connection, $scenario, &$levelReads) {
            $levelReads++;
            if ($scenario === 'unexpected_state' && $levelReads >= 3) return 2;
            return $connection->transactionLevel();
        });
        $proxy->shouldReceive('getRawPdo')->andReturnUsing(function () use ($connection, $originalPdo, $scenario, &$rawReads) {
            $rawReads++;
            if ($scenario === 'connection_loss' && $rawReads >= 2) throw new \RuntimeException('Synthetic connection loss.');
            if ($scenario === 'reconnection' && $rawReads >= 3) {
                // A different native connection is still SQLite :memory:, never a host database.
                $connection->setPdo(new \PDO('sqlite::memory:'));
                return $connection->getRawPdo();
            }
            return $originalPdo;
        });
        if ($scenario === 'rollback_failure') {
            $proxy->shouldReceive('rollBack')->once()->andThrow(new \RuntimeException('Synthetic rollback failure.'));
        }
        // Regression trap: these queries must be eliminated, not trusted when successful.
        // If reintroduced, simulate a query failure or a reconnection and fail the count assertion.
        $proxy->shouldReceive('table')->andReturnUsing(function () use ($connection, $scenario, &$verificationAttempts) {
            $verificationAttempts++;
            if ($scenario === 'verification_reconnection') $connection->setPdo(new \PDO('sqlite::memory:'));
            throw new \RuntimeException('Synthetic verification query failure.');
        });
        DB::shouldReceive('connection')->once()->andReturn($proxy);
        try {
            $response = $this->submitPayload($this->payload([], [], [
                UploadedFile::fake()->createWithContent('one.txt', 'RETAINED-ONE'),
                UploadedFile::fake()->createWithContent('two.txt', 'NEVER-WRITTEN-TWO'),
            ]));
        } finally {
            DB::swap($manager);
            // Dispose only the synthetic test transaction, never attachment files.
            if ($originalPdo->inTransaction()) $originalPdo->rollBack();
            $connection->setPdo($originalPdo);
        }
        $response->assertStatus(500)->assertJsonPath('save_status', 'unknown')->assertJsonStructure(['reference']);
        $this->assertSame(0, $verificationAttempts, 'Failed-save verification must not run queries that can reconnect.');
        $files = Storage::disk('public')->allFiles();
        $this->assertCount(2, $files);
        $retained = array_values(array_diff($files, ['transmittal/attachments/legacy.txt']));
        $this->assertCount(1, $retained);
        $this->assertSame('RETAINED-ONE', Storage::disk('public')->get($retained[0]));
        $this->assertSame('LEGACY-SENTINEL', Storage::disk('public')->get('transmittal/attachments/legacy.txt'));
    }

    public function test_commit_acknowledgement_failure_retains_files_and_reports_unknown(): void
    {
        $manager = DB::getFacadeRoot();
        $connection = DB::connection();
        $proxy = \Mockery::mock($connection);
        $proxy->shouldReceive('commit')->once()->andReturnUsing(function () use ($connection): void {
            $connection->commit();
            throw new \RuntimeException('SENSITIVE-COMMIT-ACKNOWLEDGEMENT');
        });
        DB::shouldReceive('connection')->once()->andReturn($proxy);
        try {
            $response = $this->submitPayload($this->payload([], [], [UploadedFile::fake()->createWithContent('support.txt', 'KEEP-AFTER-COMMIT')]));
        } finally { DB::swap($manager); }
        $response->assertStatus(500)->assertJsonPath('save_status', 'unknown');
        $this->assertStringNotContainsString('SENSITIVE', $response->getContent());
        $this->assertDatabaseCount('transmittals', 1);
        $this->assertDatabaseCount('transmittal_attachments', 1);
        $path = DB::table('transmittal_attachments')->value('file_path');
        $this->assertSame('KEEP-AFTER-COMMIT', Storage::disk('public')->get($path));
    }

    public function test_commit_failure_before_acknowledgement_retains_files_even_after_rollback(): void
    {
        $manager = DB::getFacadeRoot();
        $connection = DB::connection();
        $proxy = \Mockery::mock($connection);
        $proxy->shouldReceive('commit')->once()->andThrow(new \RuntimeException('SENSITIVE-COMMIT-FAILURE'));
        DB::shouldReceive('connection')->once()->andReturn($proxy);
        try {
            $response = $this->submitPayload($this->payload([], [], [UploadedFile::fake()->createWithContent('support.txt', 'KEEP-UNTIL-RECONCILED')]));
        } finally { DB::swap($manager); }
        $response->assertStatus(500)->assertJsonPath('save_status', 'unknown');
        $this->assertDatabaseCount('transmittals', 0);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }
}
