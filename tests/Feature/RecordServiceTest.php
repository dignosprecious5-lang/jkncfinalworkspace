<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordService;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordServiceTest extends TestCase
{
    use FakeUploads;

    private RecordService $records;

    private ?RecordClassification $classification = null;

    private ?RecordIndex $index = null;

    private ?Company $company = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->records = $this->app->make(RecordService::class);
    }

    public function test_it_updates_confirmed_metadata_on_a_filed_record(): void
    {
        $record = $this->fileRecord();

        $updated = $this->records->updateMetadata($record, [
            'title' => 'Amended Securities Registration',
            'document_date' => '2026-05-04',
            'source' => 'Securities and Exchange Commission',
            'external_reference_number' => 'SEC-9-1',
            'amount' => '12500.50',
            'currency' => 'PHP',
            'notes' => 'Corrected after review.',
        ]);

        $this->assertSame('Amended Securities Registration', $updated->title);
        $this->assertSame('2026-05-04', $updated->document_date->format('Y-m-d'));
        $this->assertSame('SEC-9-1', $updated->external_reference_number);
        $this->assertSame('12500.50', (string) $updated->amount);
        $this->assertSame('PHP', $updated->currency);
        $this->assertSame('Corrected after review.', $updated->notes);
    }

    public function test_updating_metadata_never_touches_the_document_reference_or_record_unit(): void
    {
        $record = $this->fileRecord();

        $reference = $record->document_reference;
        $units = $record->recordUnits();

        $this->records->updateMetadata($record, ['title' => 'A Different Title']);

        $record->refresh();

        $this->assertSame($reference, $record->document_reference);
        $this->assertSame($units, $record->recordUnits());
        $this->assertSame(1, $record->recordUnits());
        $this->assertSame($this->classification->id, $record->record_classification_id);
        $this->assertSame($this->index->id, $record->record_index_id);
    }

    public function test_it_stores_the_advanced_details_as_lists(): void
    {
        $record = $this->fileRecord();

        $updated = $this->records->updateMetadata($record, [
            'identifiers' => ['TIN 000-123-456', '  ', 'SEC Reg. No. 12345'],
            'signatories' => ['Juan Dela Cruz'],
            'financials' => [],
        ]);

        $this->assertSame(['TIN 000-123-456', 'SEC Reg. No. 12345'], $updated->identifiers);
        $this->assertSame(['Juan Dela Cruz'], $updated->signatories);
        $this->assertNull($updated->financials);
    }

    public function test_it_writes_an_append_only_activity_log_for_a_metadata_update(): void
    {
        $record = $this->fileRecord();

        $this->records->updateMetadata($record, ['title' => 'Renamed Document']);

        $log = RecordActivityLog::query()
            ->where('record_id', $record->id)
            ->where('action', 'record.metadata_updated')
            ->sole();

        $this->assertStringContainsString('title', $log->description);
        $this->assertSame(['title'], $log->properties['fields']);
    }

    public function test_a_metadata_update_that_changes_nothing_writes_no_log(): void
    {
        $record = $this->fileRecord();

        $this->records->updateMetadata($record, ['title' => $record->title]);

        $this->assertFalse(
            RecordActivityLog::query()->where('action', 'record.metadata_updated')->exists(),
        );
    }

    public function test_it_refuses_to_update_a_record_from_another_company(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $record = $this->fileRecordIn($second);

        $this->actingInCompany($first);

        $this->expectException(LogicException::class);

        $this->records->updateMetadata($record, ['title' => 'Hijacked']);
    }

    public function test_archiving_is_a_status_transition_not_a_deletion(): void
    {
        $record = $this->fileRecord();

        $archived = $this->records->archive($record, null, 'Superseded by a later filing');

        $this->assertSame(RecordStatus::Archived, $archived->status);
        $this->assertNull($archived->deleted_at);
        $this->assertTrue($archived->exists());
        $this->assertNotNull($archived->document_reference);
        $this->assertSame(1, $archived->recordUnits());
    }

    public function test_an_archived_record_keeps_its_place_in_the_register(): void
    {
        $record = $this->fileRecord();
        $reference = $record->document_reference;

        $this->records->archive($record);

        $this->get(route('records.index'))->assertOk()->assertSee($reference);
    }

    public function test_archiving_writes_an_append_only_activity_log_with_the_reason(): void
    {
        $record = $this->fileRecord();

        $this->records->archive($record, null, 'Superseded by a later filing');

        $log = RecordActivityLog::query()
            ->where('record_id', $record->id)
            ->where('action', 'record.archived')
            ->sole();

        $this->assertStringContainsString('Superseded', $log->description);
        $this->assertSame(RecordStatus::Filed->value, $log->properties['previous_status']);
    }

    public function test_it_refuses_to_archive_an_already_archived_record(): void
    {
        $record = $this->fileRecord();

        $this->records->archive($record);

        $this->expectException(LogicException::class);

        $this->records->archive($record->fresh());
    }

    public function test_it_refuses_to_edit_an_archived_record(): void
    {
        $record = $this->fileRecord();

        $this->records->archive($record);

        $this->expectException(LogicException::class);

        $this->records->updateMetadata($record->fresh(), ['title' => 'Too late']);
    }

    public function test_it_refuses_caller_supplied_tenant_and_company_scope(): void
    {
        $record = $this->fileRecord();

        $this->expectException(LogicException::class);

        $this->records->updateMetadata($record, ['tenant_id' => 'forged']);
    }

    public function test_the_register_filters_by_classification_index_source_and_date(): void
    {
        $matching = $this->fileRecord(['title' => 'General Information Sheet', 'source' => 'BIR']);

        $this->fileRecord(['title' => 'Board Resolution', 'source' => 'Board of Directors', 'classification' => '401']);

        $this->get(route('records.index', ['classification' => $this->classification->code]))
            ->assertOk()
            ->assertSee('General Information Sheet')
            ->assertDontSee('Board Resolution');

        $this->get(route('records.index', ['source' => 'BIR']))
            ->assertOk()
            ->assertSee($matching->document_reference);

        $this->get(route('records.index', ['from' => '2099-01-01']))
            ->assertOk()
            ->assertSee('No records match the selected filters.');
    }

    public function test_the_csv_export_streams_the_eleven_register_columns(): void
    {
        $record = $this->fileRecord(['title' => 'General Information Sheet']);

        $response = $this->get(route('records.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="ordo-records-register.csv"');

        $body = $response->streamedContent();
        $lines = array_values(array_filter(explode("\r\n", $body)));

        $this->assertSame(
            '"Doc Ref","Record ID","Title","Classification","Index","Source","Document Date","Amount","Currency","Origin","Status"',
            $lines[0],
        );
        $this->assertCount(2, $lines);
        $this->assertStringContainsString($record->document_reference, $lines[1]);
        $this->assertStringContainsString('General Information Sheet', $lines[1]);
        $this->assertStringContainsString('Uploaded', $lines[1]);
        $this->assertStringContainsString('Active', $lines[1]);
    }

    public function test_the_csv_export_includes_archived_records_with_their_archived_status(): void
    {
        $record = $this->fileRecord();

        $this->records->archive($record);

        $body = $this->get(route('records.export'))->streamedContent();

        $this->assertStringContainsString($record->document_reference, $body);
        $this->assertStringContainsString('Archived', $body);
    }

    public function test_the_workspace_payload_exposes_history_files_and_links(): void
    {
        $record = $this->fileRecord();

        $view = $this->get(route('records.index'))->assertOk();

        $workspace = $view->viewData('workspaceRecords');

        $this->assertCount(1, $workspace);
        $this->assertSame($record->document_reference, $workspace[0]['documentReference']);
        $this->assertSame(1, $workspace[0]['recordUnits']);
        $this->assertCount(1, $workspace[0]['files']);
        $this->assertSame(0, $workspace[0]['linkCount']);
        $this->assertNotEmpty($workspace[0]['history']);
        $this->assertContains('record.filed', array_column($workspace[0]['history'], 'action'));
    }

    public function test_the_register_renders_every_column_header(): void
    {
        $this->fileRecord();

        $response = $this->get(route('records.index'));

        $response->assertOk();

        foreach (['Doc Ref', 'Record ID', 'Document Title', 'Classification', 'Index', 'Source', 'Document Date', 'Amount', 'Origin', 'OCR', 'Status'] as $header) {
            $response->assertSee($header);
        }
    }

    public function test_the_controller_updates_and_archives_through_the_register(): void
    {
        $record = $this->fileRecord();

        $this->put(route('records.update', ['record' => $record->id]), [
            'title' => 'Updated Through The Controller',
        ])->assertRedirect(route('records.index'));

        $this->assertSame('Updated Through The Controller', $record->fresh()->title);

        $this->post(route('records.archive', ['record' => $record->id]), [
            'reason' => 'Done',
        ])->assertRedirect(route('records.index'));

        $this->assertSame(RecordStatus::Archived, $record->fresh()->status);
        $this->assertSame(1, DB::table('records')->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fileRecord(array $attributes = [], ?Company $company = null): Record
    {
        $company ??= $this->defaultCompany();

        $this->actingInCompany($company);

        $classification = ($attributes['classification'] ?? null) === '401'
            ? $this->seedClassification($company, '401')
            : ($this->classification ??= $this->seedClassification($company, '301'));

        $index = ($attributes['classification'] ?? null) === '401'
            ? $this->seedIndex($company, $classification, '401-4')
            : ($this->index ??= $this->seedIndex($company, $classification, '301-6'));

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => $attributes['title'] ?? 'General Information Sheet'],
        );

        $record = $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'source' => $attributes['source'] ?? 'Securities and Exchange Commission',
        ]);

        return $record->load(['classification', 'index', 'files', 'links', 'activityLogs.user']);
    }

    /**
     * Seed the tenant and default company once per test, then reuse them.
     */
    private function defaultCompany(): Company
    {
        if ($this->company === null) {
            $this->company = $this->seedTenantAndCompany();
        }

        return $this->company;
    }

    private function fileRecordIn(Company $company): Record
    {
        return $this->fileRecord([], $company);
    }

    private function seedClassification(Company $company, string $code): RecordClassification
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => $code,
            'name' => "Classification {$code}",
            'is_active' => true,
        ])->save();

        return $classification;
    }

    private function seedIndex(Company $company, RecordClassification $classification, string $code): RecordIndex
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_classification_id' => $classification->id,
            'code' => $code,
            'name' => "Index {$code}",
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        return $index;
    }
}
