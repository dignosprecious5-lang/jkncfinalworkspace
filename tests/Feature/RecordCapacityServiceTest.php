<?php

namespace Tests\Feature;

use App\Enums\IntakeStatus;
use App\Enums\LinkRelationship;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordIntakeItem;
use App\Services\CompanyContext;
use App\Services\Records\RecordCapacityService;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordLinkService;
use LogicException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordCapacityServiceTest extends TestCase
{
    use FakeUploads;

    private RecordCapacityService $capacity;

    private Company $company;

    /** @var array<string, array<string, RecordClassification>> */
    private array $classifications = [];

    /** @var array<string, array<string, RecordIndex>> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->capacity = $this->app->make(RecordCapacityService::class);
        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    public function test_each_filed_record_consumes_exactly_one_record_unit(): void
    {
        $this->assertSame(0, $this->capacity->units()['consumed']);

        $this->fileRecord();
        $this->fileRecord();

        $this->assertSame(2, $this->capacity->units()['consumed']);
    }

    public function test_an_intake_item_consumes_no_record_unit_but_does_use_storage(): void
    {
        $this->fileRecord();

        $before = $this->capacity->storage()['total'];

        $this->createIntake();

        $this->assertSame(1, $this->capacity->units()['consumed']);
        $this->assertGreaterThan($before, $this->capacity->storage()['total']);
    }

    public function test_links_and_supporting_files_add_no_record_units(): void
    {
        $first = $this->fileRecord();
        $second = $this->fileRecord();

        $this->app->make(RecordLinkService::class)
            ->linkRecordToRecord($first, $second, LinkRelationship::Supports);

        $this->assertSame(2, $this->capacity->units()['consumed']);
    }

    public function test_an_archived_record_keeps_the_unit_it_was_filed_with(): void
    {
        $record = $this->fileRecord();
        $record->forceFill(['status' => 'archived'])->save();

        $this->assertSame(1, $this->capacity->units()['consumed']);
        $this->assertSame(1, $this->capacity->counts()['archived']);
    }

    /**
     * The bug this guards: filing copies an intake item's storage_bytes onto the
     * new record but leaves the intake row intact, so summing both columns counts
     * every filed document twice.
     */
    public function test_a_filed_document_is_counted_once_not_twice(): void
    {
        $record = $this->fileRecord();

        $bytes = (int) $record->files()->firstOrFail()->size_bytes;

        $storage = $this->capacity->storage();

        $this->assertSame($bytes, $storage['filed']);
        $this->assertSame(0, $storage['intake']);
        $this->assertSame($bytes, $storage['total']);
        $this->assertSame($bytes, $storage['filed'] + $storage['intake']);

        // The intake row still carries the same byte count after filing, which is
        // exactly why the counters cannot both be summed.
        $intake = RecordIntakeItem::query()->findOrFail($record->record_intake_item_id);

        $this->assertSame($bytes, (int) $record->storage_bytes);
        $this->assertSame($bytes, (int) $intake->storage_bytes);

        $naive = (int) $record->storage_bytes + (int) $intake->storage_bytes;

        $this->assertSame($bytes * 2, $naive);
        $this->assertSame($bytes, $storage['total']);
        $this->assertLessThan($naive, $storage['total']);
    }

    public function test_unfiled_intake_bytes_are_counted_as_intake_storage(): void
    {
        $item = $this->createIntake();

        $bytes = (int) $item->files()->firstOrFail()->size_bytes;

        $storage = $this->capacity->storage();

        $this->assertSame(0, $storage['filed']);
        $this->assertSame($bytes, $storage['intake']);
        $this->assertSame($bytes, $storage['total']);
    }

    public function test_a_discarded_intake_item_keeps_its_bytes_on_disk(): void
    {
        $item = $this->createIntake();
        $bytes = (int) $item->files()->firstOrFail()->size_bytes;

        $this->app->make(RecordIntakeService::class)->discard($item);

        $this->assertSame(IntakeStatus::Discarded, $item->refresh()->status);
        $this->assertSame($bytes, $this->capacity->storage()['total']);
        $this->assertSame(0, $this->capacity->counts()['open_intake']);
    }

    public function test_a_company_with_no_allocation_is_unlimited(): void
    {
        $this->fileRecord();

        $units = $this->capacity->units();

        $this->assertTrue($units['unlimited']);
        $this->assertNull($units['allocation']);
        $this->assertNull($units['remaining']);
        $this->assertNull($units['utilisation']);
    }

    public function test_remaining_and_utilisation_are_reported_against_the_allocation(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 10])->save();

        $this->fileRecord();
        $this->fileRecord();

        $units = $this->capacity->units();

        $this->assertSame(2, $units['consumed']);
        $this->assertSame(10, $units['allocation']);
        $this->assertSame(8, $units['remaining']);
        $this->assertSame(0.2, $units['utilisation']);
    }

    public function test_filing_is_allowed_while_the_allocation_has_room(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 2])->save();

        $this->fileRecord();

        // The second filing lands exactly on the allocation.
        $this->fileRecord();

        $this->assertSame(2, $this->capacity->units()['consumed']);
    }

    public function test_filing_is_refused_once_the_allocation_is_exhausted(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 1])->save();

        $this->fileRecord();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No further records can be filed');

        $this->fileRecord();
    }

    public function test_a_refused_filing_leaves_the_intake_item_open(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 1])->save();

        $this->fileRecord();

        $item = $this->createIntake();

        try {
            $this->fileIntake($item);
            $this->fail('Filing should have been refused.');
        } catch (LogicException) {
            // Expected.
        }

        $item->refresh();

        $this->assertSame(IntakeStatus::Pending, $item->status);
        $this->assertTrue($item->status->isOpen());
        $this->assertSame(1, $this->capacity->units()['consumed']);
        $this->assertSame(1, Record::query()->count());
    }

    public function test_filing_passes_when_no_allocation_is_set(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->fileRecord();
        }

        $this->assertSame(5, $this->capacity->units()['consumed']);
    }

    public function test_verify_filing_capacity_can_reserve_more_than_one_unit(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 3])->save();

        $this->capacity->verifyFilingCapacity(2);

        $this->expectException(LogicException::class);

        $this->capacity->verifyFilingCapacity(4);
    }

    public function test_counts_only_include_the_active_companys_records(): void
    {
        $this->fileRecord();

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->fileRecord();
        $this->fileRecord();

        $this->actingInCompany($this->company);

        $this->assertSame(1, $this->capacity->counts()['records']);
        $this->assertSame(1, $this->capacity->units()['consumed']);
    }

    public function test_storage_never_includes_another_companys_files(): void
    {
        $record = $this->fileRecord();
        $mine = (int) $record->files()->firstOrFail()->size_bytes;

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $foreign = $this->fileRecord();
        $theirs = (int) $foreign->files()->firstOrFail()->size_bytes;

        $this->actingInCompany($this->company);

        $this->assertSame($mine, $this->capacity->storage()['total']);
        $this->assertNotSame($mine + $theirs, $this->capacity->storage()['total']);
    }

    public function test_allocation_is_per_company(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 1])->save();
        $this->fileRecord();

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);

        $this->assertTrue($this->capacity->units()['unlimited']);
        $this->assertSame(0, $this->capacity->units()['consumed']);
    }

    public function test_verifying_capacity_uses_the_active_company_only(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 1])->save();
        $this->fileRecord();

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);

        // The other company's exhaustion must not block this one.
        $this->capacity->verifyFilingCapacity();
        $this->fileRecord();

        $this->assertSame(1, $this->capacity->units()['consumed']);
    }

    public function test_distribution_groups_records_by_classification(): void
    {
        $this->fileRecord('301');
        $this->fileRecord('301');
        $this->fileRecord('901');

        $distribution = collect($this->capacity->classificationDistribution())->keyBy('code');

        $this->assertSame(2, $distribution['301']['records']);
        $this->assertSame(2, $distribution['301']['units']);
        $this->assertSame(1, $distribution['901']['records']);
    }

    public function test_distribution_excludes_another_companys_records(): void
    {
        $this->fileRecord('301');

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->fileRecord('901');

        $this->actingInCompany($this->company);

        $codes = collect($this->capacity->classificationDistribution())->pluck('code')->all();

        $this->assertSame(['301'], $codes);
    }

    public function test_recent_activity_is_scoped_and_newest_first(): void
    {
        $record = $this->fileRecord();

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->fileRecord();

        $this->actingInCompany($this->company);

        $activity = $this->capacity->recentActivity();

        $this->assertNotEmpty($activity);
        $this->assertSame('record.filed', $activity->first()->action);
        $this->assertSame((string) $record->id, (string) $activity->first()->record_id);

        foreach ($activity as $entry) {
            $this->assertSame((string) $this->company->id, (string) $entry->company_id);
        }
    }

    public function test_report_bundles_every_dashboard_figure(): void
    {
        $this->fileRecord();

        $report = $this->capacity->report();

        $this->assertArrayHasKey('units', $report);
        $this->assertArrayHasKey('storage', $report);
        $this->assertArrayHasKey('counts', $report);
        $this->assertArrayHasKey('distribution', $report);
        $this->assertArrayHasKey('activity', $report);

        $this->assertSame(1, $report['units']['consumed']);
        $this->assertSame(1, $report['counts']['records']);
    }

    /**
     * File a record in whichever company is currently active.
     */
    private function fileRecord(?string $classificationCode = null): Record
    {
        $company = $this->current();
        $code = $classificationCode ?? '301';

        $classification = $this->classifications[$company->id][$code] ??= $this->seedClassification($company, $code);
        $index = $this->indexes[$company->id][$code] ??= $this->seedIndex($company, $classification, $code.'-6');

        return $this->fileIntake($this->createIntake(), $classification, $index);
    }

    private function fileIntake(RecordIntakeItem $item, ?RecordClassification $classification = null, ?RecordIndex $index = null): Record
    {
        $company = $this->current();
        $classification ??= $this->classifications[$company->id]['301'];
        $index ??= $this->indexes[$company->id]['301'];

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);
    }

    private function current(): Company
    {
        return $this->app->make(CompanyContext::class)->current();
    }

    private function createIntake(): RecordIntakeItem
    {
        return $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'General Information Sheet'],
        );
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
