<?php

namespace Tests\Feature;

use App\Enums\OcrStatus;
use App\Jobs\ProcessIntakeOcr;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordFile;
use App\Models\RecordIndex;
use App\Models\RecordIntakeItem;
use App\Services\Records\RecordAttachmentService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class ProcessIntakeOcrSafetyTest extends TestCase
{
    use FakeUploads;

    private RecordIntakeService $intake;
    private RecordAttachmentService $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intake = $this->app->make(RecordIntakeService::class);
        $this->attachment = $this->app->make(RecordAttachmentService::class);
    }

    public function test_job_implements_should_be_unique_and_routes_to_dedicated_queue(): void
    {
        $job = new ProcessIntakeOcr('item-123', 'tenant-456', 'company-789', 'file-001');

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame('records_ocr', $job->queue);
        $this->assertSame(1800, $job->uniqueFor);
        $this->assertSame('tenant-456:company-789:file-001', $job->uniqueId());
    }

    public function test_unique_id_falls_back_to_intake_item_id_when_file_id_is_null(): void
    {
        $job = new ProcessIntakeOcr('item-123', 'tenant-456', 'company-789');

        $this->assertSame('tenant-456:company-789:item-123', $job->uniqueId());
    }

    public function test_intake_dispatch_routes_explicitly_to_records_ocr_queue(): void
    {
        Queue::fake();

        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('contract.pdf', 64)]);

        Queue::assertPushedOn('records_ocr', ProcessIntakeOcr::class, function (ProcessIntakeOcr $job) use ($item, $company): bool {
            return $job->recordIntakeItemId === (string) $item->id
                && $job->tenantId === (string) $company->tenant_id
                && $job->companyId === (string) $company->id
                && $job->queue === 'records_ocr';
        });
    }

    public function test_attachment_dispatch_routes_explicitly_to_records_ocr_queue(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
            'is_active' => true,
        ])->save();

        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_classification_id' => $classification->id,
            'code' => '301-1',
            'name' => 'Articles of Incorporation',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        $item = $this->intake->createIntakeItem([$this->fakePdf('initial.pdf', 64)]);

        $record = app(\App\Services\Records\RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'title' => 'Initial Document',
        ]);

        Queue::fake();

        $this->attachment->addSupportingFile(
            $record,
            $this->fakePdf('annex_a.pdf', 64),
            null,
        );

        Queue::assertPushedOn('records_ocr', ProcessIntakeOcr::class, function (ProcessIntakeOcr $job) use ($company): bool {
            return $job->recordIntakeItemId === null
                && $job->tenantId === (string) $company->tenant_id
                && $job->companyId === (string) $company->id
                && $job->recordFileId !== null
                && $job->queue === 'records_ocr';
        });
    }

    public function test_atomic_claim_prevents_duplicate_processing_by_concurrent_worker(): void
    {
        Queue::fake();

        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('doc.pdf', 64)]);
        $file = $item->files->first()->refresh();

        // Simulate Worker 1 claiming the file
        $claimed = RecordFile::query()
            ->where('id', $file->id)
            ->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)
            ->whereIn('ocr_status', [OcrStatus::Queued->value, OcrStatus::NeedsReview->value, OcrStatus::NotRead->value])
            ->update([
                'ocr_status' => OcrStatus::Reading->value,
                'ocr_error' => null,
            ]);

        $this->assertSame(1, $claimed);

        // Simulate Worker 2 attempting to claim the same file while Worker 1 is reading
        $secondClaim = RecordFile::query()
            ->where('id', $file->id)
            ->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)
            ->whereIn('ocr_status', [OcrStatus::Queued->value, OcrStatus::NeedsReview->value, OcrStatus::NotRead->value])
            ->update([
                'ocr_status' => OcrStatus::Reading->value,
                'ocr_error' => null,
            ]);

        $this->assertSame(0, $secondClaim, 'Concurrent worker should not be able to claim a file already in Reading status');
    }

    public function test_atomic_settle_protects_against_stale_overwrites(): void
    {
        Queue::fake();

        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('doc.pdf', 64)]);
        $file = $item->files->first()->refresh();

        // File is in Queued state initially, suppose worker claims it:
        $file->forceFill(['ocr_status' => OcrStatus::Reading])->save();

        // Suppose user cancels or settles file before extraction completes:
        $file->forceFill(['ocr_status' => OcrStatus::NeedsReview, 'ocr_error' => 'Interrupted'])->save();

        // Worker finishes extraction and tries to settle to Ready:
        $settled = RecordFile::query()
            ->where('id', $file->id)
            ->where('tenant_id', $company->tenant_id)
            ->where('company_id', $company->id)
            ->where('ocr_status', OcrStatus::Reading->value)
            ->update([
                'ocr_text' => 'Extracted text',
                'ocr_status' => OcrStatus::Ready->value,
                'ocr_error' => null,
                'ocr_completed_at' => now(),
            ]);

        $this->assertSame(0, $settled, 'Worker must not overwrite file that is no longer in Reading state');
        $this->assertSame(OcrStatus::NeedsReview, $file->refresh()->ocr_status);
        $this->assertSame('Interrupted', $file->refresh()->ocr_error);
    }

    public function test_failed_hook_settles_reading_files_to_needs_review(): void
    {
        Queue::fake();

        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf('doc.pdf', 64)]);
        $file = $item->files->first()->refresh();
        $file->forceFill(['ocr_status' => OcrStatus::Reading])->save();

        $job = new ProcessIntakeOcr(
            (string) $item->id,
            (string) $company->tenant_id,
            (string) $company->id,
        );

        $job->failed(new RuntimeException('OCR worker timeout after 900s'));

        $refreshed = $file->refresh();
        $this->assertSame(OcrStatus::NeedsReview, $refreshed->ocr_status);
        $this->assertSame('OCR worker timeout after 900s', $refreshed->ocr_error);
        $this->assertNotNull($refreshed->ocr_completed_at);
    }
}
