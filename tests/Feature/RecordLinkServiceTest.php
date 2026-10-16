<?php

namespace Tests\Feature;

use App\Enums\LinkRelationship;
use App\Enums\RecordStatus;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordFile;
use App\Models\RecordIndex;
use App\Models\RecordLink;
use App\Models\Tenant;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordLinkService;
use Illuminate\Support\Facades\Storage;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordLinkServiceTest extends TestCase
{
    use FakeUploads;

    private RecordLinkService $links;

    private Company $company;

    /** @var array<string, RecordClassification> */
    private array $classifications = [];

    /** @var array<string, RecordIndex> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->links = $this->app->make(RecordLinkService::class);
        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    public function test_linking_two_records_writes_the_relationship_from_both_sides(): void
    {
        [$source, $target] = $this->twoRecords();

        $forward = $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $this->assertSame(LinkRelationship::Supports, $forward->link_type);
        $this->assertSame($source->id, $forward->record_id);
        $this->assertSame($target->id, $forward->linked_id);
        $this->assertTrue($forward->targetsRecord());

        $mirror = $target->links()->firstOrFail();

        $this->assertNotSame($forward->id, $mirror->id);
        $this->assertSame(LinkRelationship::SupportedBy, $mirror->link_type);
        $this->assertSame($target->id, $mirror->record_id);
        $this->assertSame($source->id, $mirror->linked_id);
    }

    /**
     * @return array<string, array{LinkRelationship, LinkRelationship}>
     */
    public static function relationshipPairs(): array
    {
        return [
            'supports' => [LinkRelationship::Supports, LinkRelationship::SupportedBy],
            'supersedes' => [LinkRelationship::Supersedes, LinkRelationship::SupersededBy],
            'evidence' => [LinkRelationship::EvidenceFor, LinkRelationship::HasEvidence],
            'related is its own inverse' => [LinkRelationship::Related, LinkRelationship::Related],
        ];
    }

    #[DataProvider('relationshipPairs')]
    public function test_each_relationship_writes_its_inverse(LinkRelationship $forward, LinkRelationship $inverse): void
    {
        [$source, $target] = $this->twoRecords();

        $link = $this->links->linkRecordToRecord($source, $target, $forward);

        $this->assertSame($forward, $link->link_type);
        $this->assertSame($inverse, $link->reciprocal()->link_type);
        $this->assertSame(2, RecordLink::query()->count());
    }

    public function test_inverse_is_symmetric(): void
    {
        foreach (LinkRelationship::cases() as $relationship) {
            $this->assertSame($relationship, $relationship->inverse()->inverse());
        }
    }

    public function test_unlinking_removes_the_link_from_both_records(): void
    {
        [$source, $target] = $this->twoRecords();

        $link = $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $this->assertSame(1, $source->links()->count());
        $this->assertSame(1, $target->links()->count());

        $this->links->unlink($link);

        $this->assertSame(0, RecordLink::query()->count());
        $this->assertSame(0, $source->links()->count());
        $this->assertSame(0, $target->links()->count());
    }

    public function test_unlinking_from_either_side_removes_both_rows(): void
    {
        [$source, $target] = $this->twoRecords();

        $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supersedes);

        $this->links->unlink($target->links()->firstOrFail());

        $this->assertSame(0, RecordLink::query()->count());
    }

    public function test_linking_copies_no_bytes_and_adds_no_record_units(): void
    {
        [$source, $target] = $this->twoRecords();

        $before = $this->storageFingerprint($source, $target);
        $filesOnDisk = $this->storedFileCount();

        $link = $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $this->assertSame($before, $this->storageFingerprint($source, $target));
        $this->assertSame($filesOnDisk, $this->storedFileCount());
        $this->assertSame(1, $source->recordUnits());
        $this->assertSame(1, $target->recordUnits());
        $this->assertSame(0, $link->recordUnits());
        $this->assertFalse($link->duplicatesStorage());

        // The two records still own their own distinct master file.
        $this->assertNotSame(
            $source->files()->firstOrFail()->path,
            $target->files()->firstOrFail()->path,
        );

        $this->links->unlink($link);

        $this->assertSame($before, $this->storageFingerprint($source, $target));
        $this->assertSame($filesOnDisk, $this->storedFileCount());
    }

    public function test_linking_to_a_module_copies_no_bytes_and_adds_no_record_units(): void
    {
        [$source] = $this->twoRecords();

        $before = $this->storageFingerprint($source);
        $filesOnDisk = $this->storedFileCount();

        $link = $this->links->linkRecordToModule(
            $source,
            'Corporate Governance',
            'BR-2026-0142',
            'Board Resolution 2026-0142',
            LinkRelationship::EvidenceFor,
        );

        $this->assertSame('corporate_governance', $link->linked_type);
        $this->assertSame('Board Resolution 2026-0142', $link->linked_label);
        $this->assertSame('Corporate Governance', $link->moduleLabel());
        $this->assertFalse($link->targetsRecord());
        $this->assertNull($link->reciprocal());

        $this->assertSame($before, $this->storageFingerprint($source));
        $this->assertSame($filesOnDisk, $this->storedFileCount());
        $this->assertSame(1, $source->recordUnits());
        $this->assertSame(0, $link->recordUnits());
    }

    public function test_a_module_link_writes_one_row_and_unlinks_on_its_own(): void
    {
        [$source] = $this->twoRecords();

        $link = $this->links->linkRecordToModule($source, 'Finance', 'INV-0042', 'Invoice 0042', LinkRelationship::Related);

        $this->assertSame(1, RecordLink::query()->count());

        $this->links->unlink($link);

        $this->assertSame(0, RecordLink::query()->count());
    }

    public function test_a_record_cannot_link_to_itself(): void
    {
        [$source] = $this->twoRecords();

        $this->expectException(LogicException::class);

        $this->links->linkRecordToRecord($source, $source, LinkRelationship::Related);
    }

    public function test_a_record_cannot_be_linked_twice(): void
    {
        [$source, $target] = $this->twoRecords();

        $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $this->expectException(LogicException::class);

        $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supersedes);
    }

    public function test_an_archived_record_cannot_be_linked(): void
    {
        [$source, $target] = $this->twoRecords();

        $target->forceFill(['status' => RecordStatus::Archived])->save();

        $this->expectException(LogicException::class);

        $this->links->linkRecordToRecord($source, $target->fresh(), LinkRelationship::Related);
    }

    public function test_a_record_from_another_company_cannot_be_linked(): void
    {
        [$source, $target] = $this->twoRecords();
        $foreign = $this->recordIn($this->seedSecondCompany($source->company));

        $this->expectException(LogicException::class);

        $this->links->linkRecordToRecord($source, $foreign, LinkRelationship::Supports);
    }

    public function test_a_record_cannot_be_linked_from_another_company(): void
    {
        [$source, $target] = $this->twoRecords();
        $foreign = $this->recordIn($this->seedSecondCompany($source->company));

        $this->expectException(LogicException::class);

        $this->links->linkRecordToRecord($foreign, $target, LinkRelationship::Supports);
    }

    public function test_a_link_from_another_company_cannot_be_unlinked(): void
    {
        [$source, $target] = $this->twoRecords();
        $link = $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $this->actingInCompany($this->seedSecondCompany($source->company));

        $this->expectException(LogicException::class);

        $this->links->unlink($link);
    }

    public function test_a_link_cannot_cross_tenants(): void
    {
        [$source, $target] = $this->twoRecords();
        $link = $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $link->forceFill(['tenant_id' => (string) Tenant::factory()->create()->id])->save();

        $this->expectException(LogicException::class);

        $this->links->unlink($link);
    }

    public function test_linking_is_recorded_on_the_activity_log_of_both_records(): void
    {
        [$source, $target] = $this->twoRecords();

        $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supersedes);

        $sourceLink = $source->activityLogs()->latest('id')->firstOrFail();
        $targetLink = $target->activityLogs()->latest('id')->firstOrFail();

        $this->assertSame('record.linked', $sourceLink->action);
        $this->assertSame('record.linked', $targetLink->action);
        $this->assertStringContainsString('Supersedes', $sourceLink->description);
        $this->assertStringContainsString('Superseded By', $targetLink->description);
        $this->assertSame(0, $sourceLink->properties['storage_bytes_added']);
        $this->assertSame(0, $sourceLink->properties['record_units_added']);
        $this->assertNotSame($sourceLink->id, $targetLink->id);
    }

    public function test_unlinking_is_recorded_on_the_activity_log(): void
    {
        [$source, $target] = $this->twoRecords();

        $link = $this->links->linkRecordToRecord($source, $target, LinkRelationship::Supports);

        $this->links->unlink($link);

        $this->assertSame('record.unlinked', $source->activityLogs()->latest('id')->firstOrFail()->action);
    }

    public function test_a_failed_link_leaves_no_partial_rows_behind(): void
    {
        [$source, $target] = $this->twoRecords();
        $foreign = $this->recordIn($this->seedSecondCompany($source->company));

        try {
            $this->links->linkRecordToRecord($source, $foreign, LinkRelationship::Supports);
        } catch (LogicException) {
            // Expected.
        }

        $this->assertSame(0, RecordLink::query()->count());
    }

    /**
     * Storage bytes, record units and attached file rows for the given records.
     *
     * @return array<string, int>
     */
    private function storageFingerprint(Record ...$records): array
    {
        $fingerprint = [];

        foreach ($records as $record) {
            $fresh = $record->fresh();

            $fingerprint["{$record->id}_bytes"] = (int) $fresh->storage_bytes;
            $fingerprint["{$record->id}_units"] = $fresh->recordUnits();
            $fingerprint["{$record->id}_files"] = RecordFile::query()->where('record_id', $fresh->id)->count();
            $fingerprint["{$record->id}_file_bytes"] = (int) RecordFile::query()
                ->where('record_id', $fresh->id)
                ->sum('size_bytes');
        }

        return $fingerprint;
    }

    private function storedFileCount(): int
    {
        return count(Storage::disk((string) config('filesystems.default'))->allFiles());
    }

    /**
     * @return array{Record, Record}
     */
    private function twoRecords(): array
    {
        return [$this->recordIn($this->company), $this->recordIn($this->company)];
    }

    /**
     * A record filed in the given company, leaving the active context on that company.
     *
     * Each company has one classification and index here, so records can be filed
     * as many times as a test needs.
     */
    private function recordIn(Company $company): Record
    {
        $this->actingInCompany($company);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'General Information Sheet'],
        );

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $this->classificationFor($company)->id,
            'record_index_id' => $this->indexFor($company)->id,
        ]);
    }

    private function classificationFor(Company $company): RecordClassification
    {
        return $this->classifications[$company->id] ??= $this->seedClassification($company, '301');
    }

    private function indexFor(Company $company): RecordIndex
    {
        return $this->indexes[$company->id] ??= $this->seedIndex($company, $this->classificationFor($company), '301-6');
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
