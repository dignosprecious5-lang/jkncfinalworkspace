<?php

namespace Tests\Feature;

use App\Enums\OcrStatus;
use App\Jobs\ProcessIntakeOcr;
use App\Models\Company;
use App\Models\RecordIntakeItem;
use App\Services\Records\RecordIntakeService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Document reading is best-effort by design.
 *
 * AGENTS.md requires that a failed read never costs us the original file and
 * leaves the item in a reviewable state rather than a stuck one. These cover the
 * job-level failure path, which is the one that cannot be handled inside handle().
 */
class ProcessIntakeOcrTest extends TestCase
{
    use FakeUploads;

    private RecordIntakeService $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intake = $this->app->make(RecordIntakeService::class);
    }

    public function test_the_job_is_queued_with_the_tenant_and_company_it_may_touch(): void
    {
        Queue::fake();

        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 64)]);

        Queue::assertPushed(ProcessIntakeOcr::class, function (ProcessIntakeOcr $job) use ($item, $company): bool {
            return $job->recordIntakeItemId === (string) $item->id
                && $job->tenantId === (string) $company->tenant_id
                && $job->companyId === (string) $company->id;
        });
    }

    public function test_a_successful_run_settles_the_file(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 64)]);

        $this->jobFor($item, $company)->handle();

        $file = $item->files->first()->refresh();

        $this->assertTrue($file->ocr_status->isTerminal());
        $this->assertNotNull($file->ocr_completed_at);
    }

    /**
     * The whole point of the failed() hook: a file must never be stranded on
     * Reading with no indication of what became of it.
     */
    public function test_a_failed_run_settles_every_file_into_needs_review(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            $this->fakePdf('first.pdf', 64),
            $this->fakePdf('second.pdf', 64),
        ]);

        $job = $this->jobFor($item, $company);

        $job->failed(new RuntimeException('The reading service did not respond.'));

        $this->assertCount(2, $item->files);

        foreach ($item->files as $file) {
            $refreshed = $file->refresh();

            $this->assertSame(OcrStatus::NeedsReview, $refreshed->ocr_status);
            $this->assertSame(
                'The reading service did not respond.',
                $refreshed->ocr_error,
            );
            $this->assertNotNull($refreshed->ocr_completed_at);
        }
    }

    /**
     * A job that fails with no exception recorded still has to say so, rather
     * than writing a null reason into the trail.
     */
    public function test_a_failure_without_an_exception_still_records_a_reason(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 64)]);

        $this->jobFor($item, $company)->failed(null);

        $file = $item->files->first()->refresh();

        $this->assertSame(OcrStatus::NeedsReview, $file->ocr_status);
        $this->assertNotEmpty((string) $file->ocr_error);
    }

    /**
     * AGENTS.md: OCR failure must not prevent the original file being preserved.
     */
    public function test_a_failed_run_leaves_the_stored_file_on_disk(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('gis.pdf', 64)]);
        $file = $item->files->first();
        $disk = Storage::disk($file->disk);

        $this->assertTrue($disk->exists($file->path));

        $this->jobFor($item, $company)->failed(new RuntimeException('Reading gave up.'));

        $this->assertTrue(
            $disk->exists($file->path),
            'The stored document was removed by a failed read.',
        );
        $this->assertSame(
            $file->path,
            $file->refresh()->path,
            'A failed read changed where the document is stored.',
        );
    }

    /**
     * The job runs on a worker with no session, so its scope travels with it.
     * A job must never reach into another company's files.
     */
    public function test_a_failed_run_leaves_another_companys_files_untouched(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($first);
        $theirs = $this->intake->createIntakeItem([$this->fakePdf('theirs.pdf', 64)]);

        $this->actingInCompany($second);
        $mine = $this->intake->createIntakeItem([$this->fakePdf('mine.pdf', 64)]);

        $theirsBefore = $theirs->files->first()->refresh()->ocr_status;

        $this->jobFor($mine, $second)->failed(new RuntimeException('Reading gave up.'));

        $this->assertSame(
            OcrStatus::NeedsReview,
            $mine->files->first()->refresh()->ocr_status,
        );
        $this->assertSame(
            $theirsBefore,
            $theirs->files->first()->refresh()->ocr_status,
            'A job reached outside its own company.',
        );
    }

    /**
     * The retry ceiling has to be short. Retrying is about settling the file into
     * a correct terminal state, not about rescuing the read, and a file sitting on
     * Reading is invisible to whoever needs to review it.
     */
    public function test_it_retries_a_bounded_number_of_times_before_giving_up(): void
    {
        $job = new ProcessIntakeOcr('item', 'tenant', 'company');

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60, 300], $job->backoff);
        $this->assertGreaterThan(0, $job->timeout);
    }

    private function jobFor(RecordIntakeItem $item, Company $company): ProcessIntakeOcr
    {
        return new ProcessIntakeOcr(
            (string) $item->id,
            (string) $company->tenant_id,
            (string) $company->id,
        );
    }
}
