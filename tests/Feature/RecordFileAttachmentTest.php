<?php

namespace Tests\Feature;

use App\Enums\FileRole;
use App\Jobs\ProcessIntakeOcr;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\CompanyContext;
use App\Services\Records\RecordAttachmentService;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The Files tab's three actions.
 *
 * The distinction under test throughout is cost. Supporting files and versions
 * attach bytes to a Record already on the books and consume no further Record Unit;
 * uploading as a Record files something new and consumes exactly one. Getting that
 * backwards either refuses uploads the plan allows or quietly gives away
 * billable capacity, so it is asserted from several angles rather than once.
 *
 * Every file written here carries a null intake item, which is also the case the
 * reading job's per-file scope exists for.
 */
class RecordFileAttachmentTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordIntakeService $intake;

    private RecordClassification $classification;

    private RecordIndex $index;

    /**
     * Per-company Classification and Index pairs, built on first use.
     *
     * @var array<string, array{0: RecordClassification, 1: RecordIndex}>
     */
    private array $schemes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
        $this->intake = $this->app->make(RecordIntakeService::class);

        // Built through schemeFor so the active company and the scoping tests'
        // second company share one path and cannot collide on the scheme code.
        [$this->classification, $this->index] = $this->schemeFor($this->company);
    }

    // ------------------------------------------------------- supporting files

    public function test_a_supporting_file_attaches_to_the_record(): void
    {
        $record = $this->filedRecord();
        $before = (int) $record->storage_bytes;

        $response = $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fakePdf('annex.pdf', 128),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $file = $record->files()->where('file_role', FileRole::Supporting->value)->firstOrFail();

        $this->assertSame(FileRole::Supporting, $file->file_role);
        $this->assertSame((string) $record->id, (string) $file->record_id);
        $this->assertNull($file->record_intake_item_id, 'A supporting file should carry no intake item.');
        $this->assertSame('annex.pdf', $file->original_name);
        $this->assertSame(128, (int) $file->size_bytes);
        $this->assertNotNull($file->checksum_sha256);
        $this->assertGreaterThan($before, (int) $record->refresh()->storage_bytes);
    }

    /**
     * AGENTS.md: supporting files add storage and no further Record Unit.
     */
    public function test_a_supporting_file_consumes_no_record_unit(): void
    {
        $record = $this->filedRecord();

        $this->assertSame(1, $record->recordUnits());

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fakePdf('annex.pdf', 128),
        ])->assertRedirect();

        $this->assertSame(
            1,
            $record->refresh()->recordUnits(),
            'Attaching a supporting file changed the Record Unit count.',
        );

        $this->assertSame(
            0,
            FileRole::Supporting->recordUnits(),
            'FileRole no longer encodes the zero-unit rule.',
        );
    }

    public function test_a_supporting_file_is_stored_under_the_record(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fakePdf('annex.pdf', 128),
        ])->assertRedirect();

        $file = $record->files()->where('file_role', FileRole::Supporting->value)->firstOrFail();

        $this->assertStringStartsWith(
            sprintf('records/%s/%s/%s/', $record->tenant_id, $record->company_id, $record->id),
            $file->path,
            'A supporting file was stored outside the record\'s own path.',
        );

        $this->assertTrue(Storage::disk($file->disk)->exists($file->path));
    }

    public function test_a_supporting_file_is_queued_for_reading_by_file_id(): void
    {
        Queue::fake();

        $record = $this->filedRecord();

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fakePdf('annex.pdf', 128),
        ])->assertRedirect();

        $file = $record->files()->where('file_role', FileRole::Supporting->value)->firstOrFail();

        Queue::assertPushed(ProcessIntakeOcr::class, function (ProcessIntakeOcr $job) use ($file, $record): bool {
            return $job->recordFileId === (string) $file->id
                && $job->recordIntakeItemId === null
                && $job->tenantId === (string) $record->tenant_id
                && $job->companyId === (string) $record->company_id;
        });
    }

    public function test_a_supporting_file_is_recorded_in_the_trail(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fakePdf('annex.pdf', 128),
        ])->assertRedirect();

        $entry = RecordActivityLog::query()->where('action', 'record.file_added')->firstOrFail();

        $this->assertSame((string) $record->id, (string) $entry->record_id);
        $this->assertSame(0, $entry->properties['record_units']);
    }

    public function test_a_supporting_file_needs_a_file(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.supporting', ['record' => $record->id]))
            ->assertSessionHasErrors('file');
    }

    // -------------------------------------------------------------- versions

    public function test_a_new_version_is_stored_and_reported_as_current(): void
    {
        $record = $this->filedRecord();
        $first = $record->files()->where('file_role', FileRole::Master->value)->firstOrFail();

        $this->post(route('records.files.version', ['record' => $record->id]), [
            'file' => $this->fakePdf('resolution-v2.pdf', 96),
        ])->assertRedirect();

        $second = $record->files()->where('file_role', FileRole::Master->value)
            ->orderByDesc('version')->firstOrFail();

        $this->assertSame(2, $second->version);
        $this->assertGreaterThan($first->version, $second->version);

        // The prior version survives: a history that discards what it superseded
        // is not a history.
        $this->assertTrue($record->files()->whereKey($first->id)->exists());
    }

    public function test_a_new_version_consumes_no_record_unit(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.version', ['record' => $record->id]), [
            'file' => $this->fakePdf('resolution-v2.pdf', 96),
        ])->assertRedirect();

        $this->assertSame(1, $record->refresh()->recordUnits());
    }

    public function test_a_new_version_is_recorded_in_the_trail(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.version', ['record' => $record->id]), [
            'file' => $this->fakePdf('resolution-v2.pdf', 96),
        ])->assertRedirect();

        $this->assertSame(1, RecordActivityLog::query()->where('action', 'record.version_created')->count());
    }

    public function test_versions_are_numbered_in_sequence(): void
    {
        $record = $this->filedRecord();

        foreach (range(1, 3) as $ignored) {
            $this->post(route('records.files.version', ['record' => $record->id]), [
                'file' => $this->fakePdf('version.pdf', 64),
            ])->assertRedirect();
        }

        $versions = $record->files()->where('file_role', FileRole::Master->value)->pluck('version')->sort()->all();

        $this->assertSame([1, 2, 3, 4], $versions);
    }

    // ------------------------------------------------------- upload as record

    public function test_it_files_an_uploaded_file_as_its_own_record(): void
    {
        $before = $this->app->make(Record::class)->query()->count();

        $this->post(route('records.files.upload-as-record'), [
            'file' => $this->fakePdf('standalone.pdf', 128),
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
            'title' => 'Standalone Document',
        ])->assertRedirect();

        $this->assertSame(
            $before + 1,
            Record::query()->count(),
            'The upload did not become a Record of its own.',
        );
    }

    /**
     * The one action in this tab that costs capacity.
     */
    public function test_uploading_as_a_record_consumes_exactly_one_record_unit(): void
    {
        $this->post(route('records.files.upload-as-record'), [
            'file' => $this->fakePdf('standalone.pdf', 128),
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
            'title' => 'Standalone Document',
        ])->assertRedirect();

        $record = Record::query()->where('title', 'Standalone Document')->firstOrFail();

        $this->assertSame(1, $record->recordUnits());
        $this->assertNotNull($record->document_reference, 'No Document Reference was assigned.');
    }

    public function test_uploading_as_a_record_needs_a_classification_and_index(): void
    {
        $this->post(route('records.files.upload-as-record'), [
            'file' => $this->fakePdf('standalone.pdf', 128),
        ])->assertSessionHasErrors(['record_classification_id', 'record_index_id']);
    }

    public function test_it_rejects_a_classification_from_another_scheme(): void
    {
        $other = $this->seedSecondCompany($this->company);

        $this->actingInCompany($other);
        $theirs = new RecordClassification;
        $theirs->forceFill([
            'tenant_id' => $other->tenant_id, 'company_id' => $other->id,
            'code' => '999', 'name' => 'Theirs', 'is_active' => true,
        ])->save();

        $this->actingInCompany($this->company);

        $this->post(route('records.files.upload-as-record'), [
            'file' => $this->fakePdf('standalone.pdf', 128),
            'record_classification_id' => $theirs->id,
            'record_index_id' => $this->index->id,
        ])->assertSessionHasErrors('record_classification_id');
    }

    /**
     * Filing a Record is the one action that bills, so it is the one that has to
     * respect the allocation.
     */
    public function test_it_refuses_to_file_when_no_record_units_remain(): void
    {
        $company = $this->company;
        $company->forceFill(['record_unit_allocation' => 0])->save();
        $this->app->make(CompanyContext::class)->forget();
        $this->actingInCompany($company->refresh());

        $this->post(route('records.files.upload-as-record'), [
            'file' => $this->fakePdf('standalone.pdf', 128),
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
        ])->assertSessionHas('error');
    }

    // --------------------------------------------------------------- archived

    public function test_an_archived_record_refuses_new_files(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.archive', ['record' => $record->id]), ['reason' => 'Closed.'])
            ->assertRedirect();

        $this->app->make(RecordAttachmentService::class);

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fakePdf('late.pdf', 64),
        ])->assertSessionHas('error');
    }

    // ---------------------------------------------------------------- scoping

    public function test_another_companys_record_cannot_receive_a_file(): void
    {
        $other = $this->seedSecondCompany($this->company);

        $this->actingInCompany($other);
        $theirs = $this->filedRecord('Theirs', $other);

        $this->actingInCompany($this->company);

        $before = $theirs->files()->count();

        $this->post(route('records.files.supporting', ['record' => $theirs->id]), [
            'file' => $this->fakePdf('mine.pdf', 64),
        ])->assertNotFound();

        $this->assertSame($before, $theirs->files()->count());
    }

    // -------------------------------------------------------------- rendering

    public function test_the_files_tab_offers_all_three_actions(): void
    {
        $this->filedRecord();

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('+ Supporting File', $content);
        $this->assertStringContainsString('+ New Version', $content);
        $this->assertStringContainsString('+ Upload as Record', $content);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * File a record under whichever company is in context.
     *
     * The scheme is built per company rather than reused from setUp, because the
     * scoping test files into a second company and the filing service refuses a
     * Classification belonging to somebody else.
     */
    private function filedRecord(string $title = 'Minutes', ?Company $company = null): Record
    {
        $company ??= $this->company;

        [$classification, $index] = $this->schemeFor($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf($title.'.pdf', 64)]);

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ])->refresh();
    }

    /**
     * @return array{0: RecordClassification, 1: RecordIndex}
     */
    private function schemeFor(Company $company): array
    {
        if (! isset($this->schemes[$company->id])) {
            $classification = new RecordClassification;
            $classification->forceFill([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
                'code' => '301',
                'name' => 'Entity & Ownership',
                'is_active' => true,
            ])->save();

            $index = new RecordIndex;
            $index->forceFill([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
                'record_classification_id' => $classification->id,
                'code' => '301-6',
                'name' => 'GIS',
                'sort_order' => 0,
                'is_active' => true,
            ])->save();

            $this->schemes[$company->id] = [$classification, $index];
        }

        return $this->schemes[$company->id];
    }
}
