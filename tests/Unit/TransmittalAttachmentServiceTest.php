<?php

namespace Tests\Unit;

use App\Services\TransmittalAttachmentAttempt;
use App\Services\TransmittalAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\TransmittalV1Fixture;
use Tests\Support\TransmittalV1Isolation;
use Tests\TestCase;

// Draft test source only. No existing runner discovers this file. This opt-in is NOT
// proof of OS isolation: a separately reviewed attachment launcher must establish it.
if (! defined('TRANSMITTAL_ATTACHMENT_STAGE_A_TESTS') || TRANSMITTAL_ATTACHMENT_STAGE_A_TESTS !== true
    || ! defined('TRANSMITTAL_V1_BOOTSTRAPPED') || function_exists('proc_open')) {
    throw new \RuntimeException('Stage A tests require separate execution approval and an approved isolated attachment bootstrap.');
}
TransmittalV1Isolation::assertEnvironment();
if (realpath((string) (new \ReflectionClass(TestCase::class))->getFileName()) !== realpath(__DIR__.'/../Support/TransmittalV1TestCase.php')) {
    throw new \RuntimeException('The guarded in-memory V1 TestCase mapping is missing.');
}

final class TransmittalAttachmentServiceTest extends TestCase
{
    use TransmittalV1Fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTransmittalV1Fixture();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function requestFor(array $items, array $itemFiles = [], array $attachments = [], ?array $manifest = null): Request
    {
        $expected = ['version' => 1, 'item_files' => [], 'attachments' => []];
        foreach (['item_files' => $itemFiles, 'attachments' => $attachments] as $group => $files) {
            foreach ($files as $index => $file) $expected[$group][] = ['index' => $index, 'size' => $file->getSize()];
        }
        return Request::create('/transmittal', 'POST', ['items' => json_encode($items),
            'upload_manifest' => json_encode($manifest ?? $expected)], [],
            ['item_files' => $itemFiles, 'attachments' => $attachments]);
    }

    public function test_fifty_files_and_exactly_one_hundred_mib_are_accepted(): void
    {
        $files = [];
        for ($i = 0; $i < 50; $i++) $files[] = UploadedFile::fake()->create('synthetic.txt', 2048, 'text/plain');
        $result = (new TransmittalAttachmentService)->validate($this->requestFor([], [], $files));
        $this->assertCount(50, $result['attachments']);
    }

    public function test_combined_fifty_one_files_are_rejected_across_both_groups(): void
    {
        $items = array_fill(0, 26, ['particular' => 'Synthetic item']);
        $itemFiles = $attachments = [];
        for ($i = 0; $i < 26; $i++) $itemFiles[] = UploadedFile::fake()->create('synthetic.pdf', 1, 'application/pdf');
        for ($i = 0; $i < 25; $i++) $attachments[] = UploadedFile::fake()->create('synthetic.txt', 1, 'text/plain');
        $this->expectException(ValidationException::class);
        (new TransmittalAttachmentService)->validate($this->requestFor($items, $itemFiles, $attachments));
    }

    public function test_one_byte_over_combined_limit_is_rejected(): void
    {
        $files = [UploadedFile::fake()->createWithContent('edge.txt', str_repeat('x', 2 * 1024 * 1024 + 1))];
        for ($i = 1; $i < 50; $i++) $files[] = UploadedFile::fake()->create('synthetic.txt', 2048, 'text/plain');
        $this->expectException(ValidationException::class);
        (new TransmittalAttachmentService)->validate($this->requestFor([], [], $files));
    }

    public function test_individual_boundaries_and_valid_sparse_indexes_are_preserved(): void
    {
        $items = [['particular' => 'First'], [], [], ['particular' => 'Fourth', 'qty' => '0']];
        $files = [3 => UploadedFile::fake()->create('synthetic.pdf', 10240, 'application/pdf')];
        $support = [2 => UploadedFile::fake()->create('synthetic.txt', 20480, 'text/plain')];
        $result = (new TransmittalAttachmentService)->validate($this->requestFor($items, $files, $support));
        $this->assertSame([3], array_keys($result['item_files']));
        $this->assertSame([2], array_keys($result['attachments']));
        $this->assertSame('0', $result['items'][3]['qty']);
    }

    public function test_individual_file_limits_remain_unchanged(): void
    {
        foreach (['item_files' => 10241, 'attachments' => 20481] as $group => $kib) {
            $file = UploadedFile::fake()->create('synthetic.pdf', $kib, 'application/pdf');
            try {
                (new TransmittalAttachmentService)->validate($this->requestFor([['particular' => 'Synthetic']],
                    $group === 'item_files' ? [$file] : [], $group === 'attachments' ? [$file] : []));
                $this->fail('An oversized individual upload was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($group.'.0', $exception->errors());
            }
        }
    }

    public function test_missing_upload_is_not_silently_accepted(): void
    {
        $file = UploadedFile::fake()->create('synthetic.txt', 1, 'text/plain');
        $manifest = ['version' => 1, 'item_files' => [], 'attachments' => [['index' => 0, 'size' => $file->getSize()]]];
        $this->expectException(ValidationException::class);
        (new TransmittalAttachmentService)->validate($this->requestFor([], [], [], $manifest));
    }

    public function test_size_mismatch_duplicate_manifest_and_orphan_indexes_are_rejected(): void
    {
        $file = UploadedFile::fake()->create('synthetic.pdf', 1, 'application/pdf');
        $manifest = ['version' => 1, 'item_files' => [['index' => 0, 'size' => 1025]], 'attachments' => []];
        $requests = [
            $this->requestFor([[]], [$file], [], $manifest),
            $this->requestFor([[]], [$file], [], ['version' => 1, 'item_files' => [
                ['index' => 0, 'size' => 1024], ['index' => 0, 'size' => 1024]], 'attachments' => []]),
            $this->requestFor([[]], [4 => $file]),
            $this->requestFor([[]], ['invalid' => $file]),
        ];
        foreach ($requests as $request) {
            try { (new TransmittalAttachmentService)->validate($request); $this->fail('Invalid completeness information was accepted.'); }
            catch (ValidationException $exception) { $this->assertNotEmpty($exception->errors()); }
        }
    }

    public function test_missing_manifest_even_with_zero_received_files_requires_refresh(): void
    {
        $this->expectException(ValidationException::class);
        (new TransmittalAttachmentService)->validate(Request::create('/transmittal', 'POST', ['items' => '[]']));
    }

    public function test_malformed_items_and_negative_or_fractional_quantities_are_rejected(): void
    {
        $requests = [$this->requestFor([['qty' => -1]]), $this->requestFor([['qty' => '1.5']]),
            $this->requestFor([['attachment_path' => 'legacy/do-not-adopt.pdf']])];
        $malformed = $this->requestFor([]);
        $malformed->merge(['items' => '{invalid']);
        $requests[] = $malformed;
        foreach ($requests as $request) {
            try { (new TransmittalAttachmentService)->validate($request); $this->fail('Invalid nested item data was accepted.'); }
            catch (ValidationException $exception) { $this->assertNotEmpty($exception->errors()); }
        }
    }

    public function test_failed_attempt_retains_new_and_legacy_file_bytes_for_reconciliation(): void
    {
        Storage::disk('public')->put('transmittal/attachments/legacy.txt', 'LEGACY-SYNTHETIC-BYTES');
        $file = UploadedFile::fake()->createWithContent('support.txt', 'NEW-SYNTHETIC-BYTES');
        $attempt = new TransmittalAttachmentAttempt((string) \Illuminate\Support\Str::uuid());
        $path = $attempt->store($file, 'attachments');
        $this->assertSame('NEW-SYNTHETIC-BYTES', Storage::disk('public')->get($path));
        $this->assertSame(['deleted' => 0, 'failed' => 0, 'retained' => 1, 'policy' => 'retain_for_reconciliation'],
            $attempt->retainForReconciliation());
        $this->assertSame('NEW-SYNTHETIC-BYTES', Storage::disk('public')->get($path));
        $this->assertSame(0, $attempt->cleanupAfterRollback(true)['deleted'], 'A boolean rollback claim must never unlock deletion.');
        $this->assertSame('LEGACY-SYNTHETIC-BYTES', Storage::disk('public')->get('transmittal/attachments/legacy.txt'));
    }

    public function test_uncertain_or_committed_attempt_cannot_be_cleaned_later(): void
    {
        foreach ([false, true] as $commitAttempted) {
            $attempt = new TransmittalAttachmentAttempt((string) \Illuminate\Support\Str::uuid());
            $path = $attempt->store(UploadedFile::fake()->createWithContent('support.txt', 'PRESERVE'), 'attachments');
            if ($commitAttempted) $attempt->retain();
            $this->assertSame(0, $attempt->cleanupAfterRollback(false)['deleted']);
            $this->assertSame(0, $attempt->cleanupAfterRollback(true)['deleted']);
            $this->assertSame('PRESERVE', Storage::disk('public')->get($path));
        }
    }

    public function test_retention_does_not_enumerate_or_delete_owned_or_foreign_files(): void
    {
        $attempt = new TransmittalAttachmentAttempt((string) \Illuminate\Support\Str::uuid());
        $path = $attempt->store(UploadedFile::fake()->createWithContent('support.txt', 'OWNED'), 'attachments');
        $foreign = dirname($path).'/foreign.txt';
        Storage::disk('public')->put($foreign, 'FOREIGN-SENTINEL');
        $result = $attempt->cleanupAfterRollback(true);
        $this->assertSame(0, $result['deleted']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('OWNED', Storage::disk('public')->get($path));
        $this->assertSame('FOREIGN-SENTINEL', Storage::disk('public')->get($foreign));
    }

    public function test_partial_write_is_retained_even_when_rollback_is_claimed_confirmed(): void
    {
        $attempt = new TransmittalAttachmentAttempt((string) \Illuminate\Support\Str::uuid());
        // FileFactory reports 1 KiB but provides no content: force a short stream copy.
        $file = UploadedFile::fake()->create('partial.txt', 1, 'text/plain');
        try {
            $attempt->store($file, 'attachments');
            $this->fail('A short write was accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Attachment write was incomplete.', $exception->getMessage());
        }
        $this->assertSame(0, $attempt->cleanupAfterRollback(true)['deleted']);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_existing_attempt_directory_is_never_adopted_or_deleted(): void
    {
        $reference = (string) \Illuminate\Support\Str::uuid();
        $sentinel = 'transmittal/attachments/'.$reference.'/existing.txt';
        Storage::disk('public')->put($sentinel, 'DO-NOT-DELETE');
        $attempt = new TransmittalAttachmentAttempt($reference);
        try {
            $attempt->store(UploadedFile::fake()->createWithContent('new.txt', 'NEW'), 'attachments');
            $this->fail('A pre-existing directory was adopted.');
        } catch (\RuntimeException) { /* Expected refusal, with an empty owned-file ledger. */ }
        $this->assertSame(0, $attempt->cleanupAfterRollback(true)['deleted']);
        $this->assertSame('DO-NOT-DELETE', Storage::disk('public')->get($sentinel));
    }
}
