<?php

namespace Tests\Feature;

use App\Enums\FileRole;
use App\Enums\IntakeStatus;
use App\Enums\RecordStatus;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordIntakeItem;
use App\Services\CompanyContext;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Support\DocumentReferenceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordFilingServiceTest extends TestCase
{
    use FakeUploads;

    private RecordFilingService $filing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filing = $this->app->make(RecordFilingService::class);
    }

    public function test_it_assigns_a_five_character_document_reference_at_filing(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $record = $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        $this->assertSame(5, strlen((string) $record->document_reference));
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{5}$/', (string) $record->document_reference);
        $this->assertTrue($record->isFiled());
        $this->assertSame(RecordStatus::Filed, $record->status);
    }

    public function test_it_never_repeats_a_document_reference_within_a_company(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');

        $references = [];

        for ($i = 0; $i < 40; $i++) {
            $references[] = $this->filing
                ->file($this->seedIntakeItem($company), [
                    'record_classification_id' => $classification->id,
                    'record_index_id' => $index->id,
                ])
                ->document_reference;
        }

        $this->assertCount(40, array_unique($references));
    }

    public function test_a_document_reference_is_unique_only_within_its_own_company(): void
    {
        $this->actingInCompany($first = $this->seedTenantAndCompany());
        $second = $this->seedSecondCompany($first);

        $firstClassification = $this->seedClassification($first, '301');
        $secondClassification = $this->seedClassification($second, '301');

        $generator = $this->app->make(DocumentReferenceGenerator::class);

        $this->assertSame(
            5,
            strlen($generator->generate((string) $first->tenant_id, (string) $first->id)),
        );

        $this->assertSame(
            5,
            strlen($generator->generate((string) $second->tenant_id, (string) $second->id)),
        );
    }

    public function test_filing_assigns_one_record_unit_and_intake_keeps_none(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $this->assertSame(0, $item->recordUnits());

        $record = $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        $this->assertSame(1, $record->recordUnits());

        foreach ($record->files as $file) {
            $this->assertSame(FileRole::Master, $file->file_role);
            $this->assertSame(0, $file->file_role->recordUnits());
        }
    }

    public function test_it_repoints_files_without_duplicating_a_single_byte(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $file = $item->files->first();
        $path = $file->path;
        $checksum = $file->checksum_sha256;
        $disk = Storage::disk($file->disk);

        $before = $disk->allFiles();

        $record = $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        $file->refresh();

        $this->assertSame($record->id, $file->record_id);
        $this->assertSame($path, $file->path, 'Filing must not move the stored bytes.');
        $this->assertSame($checksum, $file->checksum_sha256);
        $this->assertTrue($disk->exists($path));
        $this->assertSame(
            $before,
            $disk->allFiles(),
            'Filing must not write a second copy of the document.',
        );
        $this->assertSame(1, $record->files()->count());
    }

    public function test_it_marks_the_intake_item_filed_and_stamps_the_time(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        $item->refresh();

        $this->assertSame(IntakeStatus::Filed, $item->status);
        $this->assertFalse($item->status->isOpen());
        $this->assertNotNull($item->filed_at);
    }

    public function test_it_writes_an_append_only_activity_log_for_the_filing(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $record = $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        $log = RecordActivityLog::query()
            ->where('record_id', $record->id)
            ->where('action', 'record.filed')
            ->sole();

        $this->assertSame((string) $record->document_reference, $log->properties['document_reference']);
        $this->assertSame('301', $log->properties['classification_code']);
        $this->assertSame('301-6', $log->properties['index_code']);
        $this->assertSame(1, $log->properties['record_units']);
    }

    public function test_it_rolls_back_completely_when_the_index_does_not_belong_to_the_classification(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $otherClassification = $this->seedClassification($company, '401');
        $index = $this->seedIndex($company, $otherClassification, '401-4');
        $item = $this->seedIntakeItem($company);

        $this->expectException(LogicException::class);

        try {
            $this->filing->file($item, [
                'record_classification_id' => $classification->id,
                'record_index_id' => $index->id,
            ]);
        } finally {
            $this->assertSame(0, DB::table('records')->count());
            $this->assertSame(IntakeStatus::Pending, $item->fresh()->status);
            $this->assertNull($item->fresh()->files->first()->record_id);
            $this->assertFalse(
                RecordActivityLog::query()->where('action', 'record.filed')->exists(),
            );
        }
    }

    public function test_it_refuses_to_file_an_item_that_belongs_to_another_company(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        // Create the item while the second company is active, then switch back.
        $this->actingInCompany($second);
        $item = $this->seedIntakeItem($second);

        $this->actingInCompany($first);
        $classification = $this->seedClassification($first, '301');
        $index = $this->seedIndex($first, $classification, '301-6');

        $this->expectException(LogicException::class);

        $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);
    }

    public function test_it_refuses_to_file_the_same_intake_item_twice(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $attributes = [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ];

        $this->filing->file($item, $attributes);

        $this->expectException(LogicException::class);

        $this->filing->file($item->fresh(), $attributes);
    }

    public function test_it_stores_the_advanced_details_supplied_at_filing(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');
        $item = $this->seedIntakeItem($company);

        $record = $this->filing->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'identifiers' => ['TIN 000-123-456', ''],
            'signatories' => ['Juan Dela Cruz'],
            'financials' => [],
            'additional_dates' => null,
        ]);

        $this->assertSame(['TIN 000-123-456'], $record->identifiers);
        $this->assertSame(['Juan Dela Cruz'], $record->signatories);
        $this->assertNull($record->financials);
        $this->assertNull($record->additional_dates);
    }

    public function test_bulk_filing_gives_every_record_its_own_reference_and_unit(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');

        $items = collect(range(1, 3))
            ->map(fn (): RecordIntakeItem => $this->seedIntakeItem($company))
            ->values();

        $records = $this->filing->fileMany(
            $items->map(fn (RecordIntakeItem $item): string => (string) $item->id)->all(),
            [
                'record_classification_id' => $classification->id,
                'record_index_id' => $index->id,
            ],
        );

        $this->assertCount(3, $records);
        $this->assertCount(3, array_unique(array_map(
            fn (Record $record): ?string => $record->document_reference,
            $records,
        )));

        foreach ($records as $record) {
            $this->assertSame(1, $record->recordUnits());
        }
    }

    public function test_bulk_filing_rolls_back_every_item_when_one_cannot_be_filed(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $classification = $this->seedClassification($company, '301');
        $index = $this->seedIndex($company, $classification, '301-6');

        $first = $this->seedIntakeItem($company);
        $second = $this->seedIntakeItem($company);

        $this->filing->file($first, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        $this->expectException(LogicException::class);

        try {
            $this->filing->fileMany([$first->id, $second->id], [
                'record_classification_id' => $classification->id,
                'record_index_id' => $index->id,
            ]);
        } finally {
            $this->assertSame(1, DB::table('records')->count());
            $this->assertSame(IntakeStatus::Pending, $second->fresh()->status);
        }
    }

    public function test_it_refuses_caller_supplied_tenant_and_company_scope(): void
    {
        $this->actingInCompany($company = $this->seedTenantAndCompany());
        $item = $this->seedIntakeItem($company);

        $this->expectException(LogicException::class);

        $this->filing->file($item, ['tenant_id' => 'forged']);
    }

    public function test_missing_company_context_is_reported_rather_than_guessed(): void
    {
        $this->seedTenantAndCompany();

        $this->expectException(LogicException::class);

        $this->app->make(CompanyContext::class)->current();
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

    private function seedIntakeItem(Company $company): RecordIntakeItem
    {
        return $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'General Information Sheet'],
        );
    }
}
