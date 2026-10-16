<?php

namespace Tests\Feature;

use App\Enums\LinkRelationship;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordLink;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The Linked To tab and the record picker, driven through HTTP.
 */
class RecordLinkPageTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    /** @var array<string, RecordClassification> */
    private array $classifications = [];

    /** @var array<string, RecordIndex> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    public function test_the_picker_offers_the_companys_other_records(): void
    {
        $source = $this->fileRecord();
        $other = $this->fileRecord();

        $response = $this->get(route('records.links.picker', ['record' => $source->id]));

        $response->assertOk();
        $response->assertViewIs('records.partials.record-picker-modal');
        $response->assertSee($other->document_reference);
        $response->assertSee($other->title);
    }

    public function test_the_picker_never_offers_the_record_itself(): void
    {
        $source = $this->fileRecord();
        $this->fileRecord();

        $candidates = $this->get(route('records.links.picker', ['record' => $source->id]))
            ->viewData('candidates');

        $this->assertCount(1, $candidates);
        $this->assertNotSame((string) $source->id, (string) $candidates[0]['id']);
    }

    public function test_the_picker_states_that_linking_copies_nothing(): void
    {
        $source = $this->fileRecord();
        $this->fileRecord();

        $response = $this->get(route('records.links.picker', ['record' => $source->id]));

        $response->assertOk();
        $response->assertSee('No file is copied.');
        $response->assertSee('0 Record Units');
        $response->assertSee('0 storage bytes');
    }

    public function test_the_picker_only_offers_the_forward_relationships(): void
    {
        $source = $this->fileRecord();
        $this->fileRecord();

        $relationships = $this->get(route('records.links.picker', ['record' => $source->id]))
            ->viewData('relationships');

        $this->assertSame([
            LinkRelationship::Related->value,
            LinkRelationship::Supports->value,
            LinkRelationship::Supersedes->value,
            LinkRelationship::EvidenceFor->value,
        ], array_keys($relationships));

        $this->assertArrayNotHasKey(LinkRelationship::SupportedBy->value, $relationships);
        $this->assertArrayNotHasKey(LinkRelationship::SupersededBy->value, $relationships);
        $this->assertArrayNotHasKey(LinkRelationship::HasEvidence->value, $relationships);
    }

    public function test_the_picker_does_not_offer_another_companys_records(): void
    {
        $source = $this->fileRecord();
        $second = $this->seedSecondCompany($this->company);
        $foreign = $this->fileRecordIn($second);

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.links.picker', ['record' => $source->id]));

        $response->assertOk();
        $response->assertDontSee($foreign->title);
        $response->assertDontSee($foreign->document_reference);
        $this->assertCount(0, $response->viewData('candidates'));
    }

    public function test_the_picker_404s_for_another_companys_record(): void
    {
        $second = $this->seedSecondCompany($this->company);
        $foreign = $this->fileRecordIn($second);

        $this->actingInCompany($this->company);

        $this->get(route('records.links.picker', ['record' => $foreign->id]))->assertNotFound();
    }

    public function test_it_creates_a_reciprocal_link_from_the_picker(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $response = $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Supports->value,
        ]);

        $response->assertRedirect(route('records.index'));
        $response->assertSessionHas('status');

        $this->assertSame(LinkRelationship::Supports, $source->links()->firstOrFail()->link_type);
        $this->assertSame(LinkRelationship::SupportedBy, $target->links()->firstOrFail()->link_type);
    }

    public function test_it_creates_a_module_link_from_the_picker(): void
    {
        $source = $this->fileRecord();

        $response = $this->post(route('records.links.store', ['record' => $source->id]), [
            'module' => 'Corporate Governance',
            'reference_id' => 'BR-2026-0142',
            'linked_label' => 'Board Resolution 2026-0142',
            'link_type' => LinkRelationship::EvidenceFor->value,
        ]);

        $response->assertRedirect(route('records.index'));

        $link = $source->links()->firstOrFail();

        $this->assertSame('corporate_governance', $link->linked_type);
        $this->assertSame('BR-2026-0142', $link->linked_id);
        $this->assertSame('Board Resolution 2026-0142', $link->linked_label);
        $this->assertSame(1, RecordLink::query()->count());
    }

    public function test_it_stores_notes_on_the_link_only(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Related->value,
            'notes' => 'Cross-checked during the GIS review.',
        ]);

        $this->assertSame('Cross-checked during the GIS review.', $source->links()->firstOrFail()->notes);
        $this->assertNull($source->notes);
        $this->assertNull($target->notes);
    }

    public function test_it_refuses_an_inverse_relationship_submitted_by_hand(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::SupportedBy->value,
        ])->assertSessionHasErrors('link_type');

        $this->assertSame(0, RecordLink::query()->count());
    }

    public function test_it_refuses_to_link_a_record_in_another_company(): void
    {
        $source = $this->fileRecord();
        $second = $this->seedSecondCompany($this->company);
        $foreign = $this->fileRecordIn($second);

        $this->actingInCompany($this->company);

        // A record in another company is simply not found, so the id cannot leak.
        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $foreign->id,
            'link_type' => LinkRelationship::Supports->value,
        ])->assertNotFound();

        $this->assertSame(0, RecordLink::query()->count());
    }

    public function test_a_refused_link_is_reported_rather_than_silently_dropped(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Supports->value,
        ]);

        // Linking the same pair again is refused by the service, not by a 500.
        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Related->value,
        ])
            ->assertRedirect(route('records.index'))
            ->assertSessionHas('error');

        $this->assertSame(2, RecordLink::query()->count());
    }

    public function test_it_404s_when_linking_through_another_companys_record(): void
    {
        $second = $this->seedSecondCompany($this->company);
        $foreign = $this->fileRecordIn($second);

        $this->actingInCompany($this->company);

        $this->post(route('records.links.store', ['record' => $foreign->id]), [
            'module' => 'Finance',
            'reference_id' => 'INV-1',
            'link_type' => LinkRelationship::Related->value,
        ])->assertNotFound();
    }

    public function test_unlinking_removes_both_directions(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Supports->value,
        ]);

        $link = RecordLink::query()->where('record_id', $source->id)->firstOrFail();

        $this->delete(route('records.links.destroy', ['link' => $link->id]))
            ->assertRedirect(route('records.index'))
            ->assertSessionHas('status');

        $this->assertSame(0, RecordLink::query()->count());
        $this->assertSame(0, $source->links()->count());
        $this->assertSame(0, $target->links()->count());
    }

    public function test_it_refuses_to_unlink_another_companys_link(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Supports->value,
        ]);

        $link = RecordLink::query()->where('record_id', $source->id)->firstOrFail();

        $this->actingInCompany($this->seedSecondCompany($this->company));

        $this->delete(route('records.links.destroy', ['link' => $link->id]))->assertNotFound();

        $this->assertSame(2, RecordLink::query()->count());
    }

    public function test_the_register_shows_the_friendly_module_name_not_the_class(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Supports->value,
        ]);

        $payload = $this->get(route('records.index'))->viewData('workspaceRecords');
        $link = collect($payload)->firstWhere('id', (string) $source->id)['links'][0];

        $this->assertSame('Records', $link['module']);
        $this->assertSame('Supports', $link['linkTypeLabel']);
        $this->assertSame((string) $target->document_reference, $link['linkedLabel']);
        $this->assertStringNotContainsString('App\\Models\\Record', json_encode($payload));
    }

    public function test_the_register_shows_a_module_link_by_its_title(): void
    {
        $source = $this->fileRecord();

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'module' => 'Corporate Governance',
            'reference_id' => 'BR-2026-0142',
            'linked_label' => 'Board Resolution 2026-0142',
            'link_type' => LinkRelationship::EvidenceFor->value,
        ]);

        $payload = $this->get(route('records.index'))->viewData('workspaceRecords');
        $link = collect($payload)->firstWhere('id', (string) $source->id)['links'][0];

        $this->assertSame('Corporate Governance', $link['module']);
        $this->assertSame('Board Resolution 2026-0142', $link['linkedLabel']);
        $this->assertSame('Evidence For', $link['linkTypeLabel']);
    }

    public function test_the_linked_to_tab_offers_add_link_and_unlink(): void
    {
        $response = $this->get(route('records.index'));

        $response->assertOk();
        $response->assertSee('+ Add Link');
        $response->assertSee('Unlink');
        $response->assertSee('__record__', false);
    }

    public function test_linking_does_not_change_either_records_storage_or_record_units(): void
    {
        $source = $this->fileRecord();
        $target = $this->fileRecord();

        $before = [$source->storage_bytes, $target->storage_bytes, $source->record_units, $target->record_units];

        $this->post(route('records.links.store', ['record' => $source->id]), [
            'target' => $target->id,
            'link_type' => LinkRelationship::Supports->value,
        ]);

        $this->assertSame($before, [
            $source->refresh()->storage_bytes,
            $target->refresh()->storage_bytes,
            $source->record_units,
            $target->record_units,
        ]);
    }

    private function fileRecord(): Record
    {
        return $this->fileRecordIn($this->company);
    }

    private function fileRecordIn(Company $company): Record
    {
        $this->actingInCompany($company);

        $classification = $this->classifications[$company->id] ??= $this->seedClassification($company);
        $index = $this->indexes[$company->id] ??= $this->seedIndex($company, $classification);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'General Information Sheet'],
        );

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);
    }

    private function seedClassification(Company $company): RecordClassification
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
            'is_active' => true,
        ])->save();

        return $classification;
    }

    private function seedIndex(Company $company, RecordClassification $classification): RecordIndex
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_classification_id' => $classification->id,
            'code' => '301-6',
            'name' => 'General Information Sheet',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        return $index;
    }
}
