<?php

namespace Tests\Feature;

use App\Enums\CaptureSource;
use App\Enums\FileRole;
use App\Enums\IntakeStatus;
use App\Enums\OcrStatus;
use App\Jobs\ProcessIntakeOcr;
use App\Models\RecordActivityLog;
use App\Models\RecordFile;
use App\Models\RecordIntakeItem;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\Records\FileMetadata;
use App\Services\Records\RecordIntakeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use LogicException;
use RuntimeException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordIntakeServiceTest extends TestCase
{
    use FakeUploads;

    private RecordIntakeService $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intake = $this->app->make(RecordIntakeService::class);
    }

    /**
     * A real 1x1 JPEG so mime sniffing sees an image rather than plain text.
     */
    private function swapMetadataToFailOnSecondFile(): void
    {
        $inner = $this->app->make(FileMetadata::class);
        $calls = 0;

        $this->app->instance(FileMetadata::class, new class($inner, $calls) extends FileMetadata
        {
            public function __construct(private readonly FileMetadata $inner, private int &$calls) {}

            public function extract(UploadedFile $file, string $disk, string $path): array
            {
                if (++$this->calls > 1) {
                    throw new RuntimeException('Metadata extraction failed on the second file.');
                }

                return $this->inner->extract($file, $disk, $path);
            }
        });
    }

    public function test_it_creates_an_intake_item_from_a_single_file(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakeImage('gis-2026.png'),
        ]);

        $this->assertSame(IntakeStatus::Pending, $item->status);
        $this->assertSame(CaptureSource::Upload, $item->capture_source);
        $this->assertSame($company->id, $item->company_id);
        $this->assertCount(1, $item->files);
        $this->assertSame(FileRole::Master, $item->files->first()->file_role);
        $this->assertSame(OcrStatus::Queued, $item->files->first()->ocr_status);
    }

    public function test_it_stores_file_bytes_under_the_tenant_and_company_path(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakeImage('gis-2026.png'),
        ]);

        $file = $item->files->first();

        $this->assertStringStartsWith("intake/{$company->tenant_id}/{$company->id}/{$item->id}/", $file->path);
        Storage::disk($file->disk)->assertExists($file->path);
        $this->assertSame(1, $file->page_count, 'An image is a single page.');
    }

    public function test_it_records_a_sha256_checksum_matching_the_stored_bytes(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakePdf('contract.pdf', 64),
        ]);

        $file = $item->files->first();
        $absolute = Storage::disk($file->disk)->path($file->path);

        $this->assertSame(64, strlen($file->checksum_sha256));
        $this->assertSame(hash_file('sha256', $absolute), $file->checksum_sha256);
    }

    public function test_it_accumulates_storage_bytes_across_multiple_files(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakePdf('one.pdf', 30),
            $this->fakePdf('two.pdf', 45),
        ]);

        $expected = $item->files->sum('size_bytes');

        $this->assertCount(2, $item->files);
        $this->assertGreaterThan(0, $expected);
        $this->assertSame($expected, $item->storage_bytes);
        $this->assertSame((int) DB::table('record_intake_items')->where('id', $item->id)->value('storage_bytes'), $item->storage_bytes);
        $this->assertSame(CaptureSource::MultiUpload, $item->capture_source);
    }

    public function test_intake_assigns_zero_record_units_and_creates_no_record(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakePdf('gis.pdf', 20),
        ]);

        $this->assertSame(0, $item->recordUnits());
        $this->assertSame(0, $item->files->sum(fn (RecordFile $file): int => $file->file_role->recordUnits()));
        $this->assertSame(0, DB::table('records')->count());
        $this->assertNull(DB::table('record_intake_items')->where('id', $item->id)->value('filed_at'));
    }

    public function test_it_writes_append_only_activity_logs_for_the_item_and_each_file(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakePdf('one.pdf', 10),
            $this->fakePdf('two.pdf', 10),
        ]);

        $logs = RecordActivityLog::query()->where('record_intake_item_id', $item->id)->get();

        $this->assertCount(3, $logs);
        $this->assertSame(1, $logs->where('action', 'intake.created')->count());
        $this->assertSame(2, $logs->where('action', 'intake.file_uploaded')->count());
        $this->assertSame(2, $logs->whereNotNull('record_file_id')->count());
    }

    public function test_it_rejects_caller_supplied_tenant_and_company_scope(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $this->expectException(LogicException::class);

        $this->intake->createIntakeItem(
            [$this->fakePdf('gis.pdf', 10)],
            ['company_id' => 'some-other-company'],
        );
    }

    public function test_it_rolls_back_the_database_and_removes_stored_bytes_on_failure(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $this->swapMetadataToFailOnSecondFile();

        try {
            $this->app->make(RecordIntakeService::class)->createIntakeItem([
                $this->fakePdf('one.pdf', 10),
                $this->fakePdf('two.pdf', 10),
            ], ['title' => 'Intake that must be rolled back']);

            $this->fail('Expected the second file to fail metadata extraction.');
        } catch (RuntimeException $e) {
            $this->assertSame('Metadata extraction failed on the second file.', $e->getMessage());
        }

        $this->assertNull(RecordIntakeItem::query()->where('title', 'Intake that must be rolled back')->first());
        $this->assertSame(0, RecordFile::query()->count());
        $this->assertSame([], Storage::disk((string) config('filesystems.default'))->allFiles('intake'));
    }

    public function test_it_queues_document_reading_after_the_transaction_commits(): void
    {
        Queue::fake();

        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $this->intake->createIntakeItem([
            $this->fakePdf('gis.pdf', 10),
        ]);

        Queue::assertPushed(ProcessIntakeOcr::class);
    }

    public function test_camera_capture_is_recorded_as_its_own_capture_source(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem(
            [$this->fakeImage('capture.jpg')],
            [],
            CaptureSource::Camera,
        );

        $this->assertSame(CaptureSource::Camera, $item->capture_source);
        $this->assertSame(1, $item->files->first()->page_count);
    }

    public function test_it_throws_when_no_files_are_supplied(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $this->expectException(LogicException::class);

        $this->intake->createIntakeItem([]);
    }

    public function test_missing_company_context_is_reported_rather_than_guessed(): void
    {
        $this->app->make(CompanyContext::class)->forget();

        $this->expectException(LogicException::class);

        $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);
    }

    public function test_discarding_an_intake_item_takes_it_out_of_the_open_queue(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);

        $this->assertTrue($item->status->isOpen());

        $discarded = $this->intake->discard($item);

        $this->assertSame(IntakeStatus::Discarded, $discarded->status);
        $this->assertFalse($discarded->status->isOpen());
        $this->assertSame(IntakeStatus::Discarded, $item->fresh()->status);

        $open = RecordIntakeItem::query()
            ->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)
            ->whereNotIn('status', [IntakeStatus::Filed, IntakeStatus::Discarded])
            ->pluck('id')
            ->all();

        $this->assertNotContains($item->id, $open);
    }

    public function test_discarding_preserves_the_stored_bytes_and_the_item_row(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);
        $path = $item->files->first()->path;
        $disk = Storage::disk((string) config('filesystems.default'));

        $before = $disk->allFiles();

        $this->intake->discard($item);

        $this->assertSame(1, DB::table('record_intake_items')->count());
        $this->assertTrue($disk->exists($path), 'A discard must not delete the stored bytes.');
        $this->assertSame($before, $disk->allFiles());
    }

    public function test_discarding_writes_an_append_only_activity_log(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);

        $this->intake->discard($item);

        $log = RecordActivityLog::query()
            ->where('record_intake_item_id', $item->id)
            ->where('action', 'intake.discarded')
            ->sole();

        $this->assertSame(IntakeStatus::Pending->value, $log->properties['previous_status']);
        $this->assertSame(1, $log->properties['files']);
        $this->assertNotNull($log->created_at);
    }

    public function test_it_refuses_to_discard_an_item_from_another_company(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($second);
        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);

        $this->actingInCompany($first);

        $this->expectException(LogicException::class);

        $this->intake->discard($item);
    }

    public function test_a_filed_intake_item_cannot_be_discarded(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);

        $item->update(['status' => IntakeStatus::Filed]);

        $this->expectException(LogicException::class);

        try {
            $this->intake->discard($item->fresh());
        } finally {
            $this->assertSame(IntakeStatus::Filed, $item->fresh()->status);
        }
    }

    public function test_an_intake_item_cannot_be_discarded_twice(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);

        $this->intake->discard($item);

        $this->expectException(LogicException::class);

        $this->intake->discard($item->fresh());
    }

    public function test_discarding_records_the_acting_user_on_the_activity_log(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $user = User::factory()->create();
        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 10)]);

        $this->intake->discard($item, $user);

        $log = RecordActivityLog::query()
            ->where('record_intake_item_id', $item->id)
            ->where('action', 'intake.discarded')
            ->sole();

        $this->assertSame($user->id, $log->user_id);
    }
}
