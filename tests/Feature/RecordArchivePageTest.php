<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\CompanyContext;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordService;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordArchivePageTest extends TestCase
{
    use FakeUploads;

    private Company $company;

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
    }

    // -------------------------------------------------------------- rendering

    public function test_the_page_renders_the_archive(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $response = $this->get(route('records.archive.index'));

        $response->assertOk();
        $response->assertViewIs('records.archive');
        $response->assertSee('Archive');
        $response->assertSee('Historical or inactive records retained as part of the official records history.');
        $response->assertSee('Minutes of the Board');
        $response->assertSee((string) $record->id);
        $response->assertSee((string) $record->document_reference);
    }

    public function test_it_shows_the_classification_and_index_of_each_archived_record(): void
    {
        $this->archivedRecord('Minutes of the Board');

        $this->get(route('records.archive.index'))
            ->assertOk()
            ->assertSee('301 — Entity &amp; Ownership', false)
            ->assertSee('301-6 — GIS', false);
    }

    public function test_it_shows_the_date_the_record_was_archived(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $this->get(route('records.archive.index'))
            ->assertOk()
            ->assertSee($record->archived_at->format('Y-m-d H:i'));
    }

    public function test_it_lists_only_archived_records(): void
    {
        $this->archivedRecord('Archived one');
        $this->filedRecord('Still active');

        $response = $this->get(route('records.archive.index'));

        $response->assertOk();
        $response->assertSee('Archived one');
        $response->assertDontSee('Still active');
    }

    public function test_it_has_an_empty_state_with_nothing_archived(): void
    {
        $this->get(route('records.archive.index'))
            ->assertOk()
            ->assertSee('No archived records.');
    }

    // ------------------------------------------------------------- restoring

    public function test_restoring_returns_the_record_to_the_active_register(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $response = $this->post(route('records.restore', ['record' => $record->id]));

        $response->assertRedirect(route('records.archive.index'));
        $response->assertSessionHas('status');

        $record->refresh();

        $this->assertSame(RecordStatus::Filed, $record->status);
        $this->assertNull($record->archived_at);
        $this->assertSame('Active', $record->status->label());
    }

    /**
     * Restoring is a lifecycle change, not a re-filing: the Document Reference and
     * the Record Unit are untouched, so nothing is renumbered or re-allocated.
     */
    public function test_restoring_preserves_the_document_reference_and_record_unit(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $reference = $record->document_reference;
        $units = $record->record_units;

        $this->post(route('records.restore', ['record' => $record->id]))->assertRedirect();

        $record->refresh();

        $this->assertSame($reference, $record->document_reference);
        $this->assertSame($units, $record->record_units);
    }

    public function test_the_restored_record_reappears_in_the_register(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $this->post(route('records.restore', ['record' => $record->id]))->assertRedirect();

        $this->get(route('records.index'))
            ->assertOk()
            ->assertSee('Minutes of the Board');

        $this->get(route('records.archive.index'))
            ->assertOk()
            ->assertDontSee('Minutes of the Board');
    }

    // --------------------------------------------------------- audit trail

    public function test_restoring_is_recorded_on_the_activity_trail(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $this->post(route('records.restore', ['record' => $record->id]))->assertRedirect();

        $this->assertDatabaseHas('record_activity_logs', [
            'record_id' => $record->id,
            'action' => 'record.restored',
        ]);

        $entry = RecordActivityLog::query()
            ->where('record_id', $record->id)
            ->where('action', 'record.restored')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('archived', $entry->properties['previous_status']);
        $this->assertSame($record->document_reference, $entry->properties['document_reference']);
    }

    /**
     * Both transitions stay on the trail, so an archive/restore round trip is
     * legible afterwards without reading the current status.
     */
    public function test_an_archive_and_restore_round_trip_leaves_both_entries(): void
    {
        $record = $this->filedRecord('Minutes of the Board');

        $this->post(route('records.archive', ['record' => $record->id]))->assertRedirect();
        $this->post(route('records.restore', ['record' => $record->id]))->assertRedirect();

        $actions = RecordActivityLog::query()
            ->where('record_id', $record->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertContains('record.archived', $actions);
        $this->assertContains('record.restored', $actions);
    }

    public function test_the_new_action_appears_in_the_audit_log_filter(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');

        $this->post(route('records.restore', ['record' => $record->id]))->assertRedirect();

        $this->get(route('records.logs'))
            ->assertOk()
            ->assertSee('record.restored');
    }

    // ------------------------------------------------------------ isolation

    public function test_it_does_not_list_another_companys_archived_records(): void
    {
        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $theirs = $this->filedRecord('Theirs');
        $this->post(route('records.archive', ['record' => $theirs->id]))->assertRedirect();

        $this->actingInCompany($this->company);
        $this->archivedRecord('Mine');

        $response = $this->get(route('records.archive.index'));

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
        $this->assertStringNotContainsString((string) $theirs->id, $response->getContent() ?? '');
    }

    public function test_a_record_from_another_company_cannot_be_restored(): void
    {
        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $theirs = $this->filedRecord('Theirs');
        $this->post(route('records.archive', ['record' => $theirs->id]))->assertRedirect();

        $this->actingInCompany($this->company);

        $this->post(route('records.restore', ['record' => $theirs->id]))->assertNotFound();

        $this->assertSame(RecordStatus::Archived, $theirs->refresh()->status);
    }

    // -------------------------------------------------------------- refusals

    public function test_a_record_that_is_not_archived_cannot_be_restored(): void
    {
        $record = $this->filedRecord('Still active');

        $this->expectException(\LogicException::class);

        try {
            $this->app->make(RecordService::class)->restore($record);
        } finally {
            $this->assertSame(RecordStatus::Filed, $record->refresh()->status);
        }
    }

    /**
     * A legal hold freezes the record in whichever state it is in, so an archived
     * record under hold is left alone rather than pulled back into the register.
     */
    public function test_a_record_under_legal_hold_cannot_be_restored(): void
    {
        $record = $this->archivedRecord('Minutes of the Board');
        $record->forceFill(['is_legal_hold' => true])->save();

        $this->expectException(\LogicException::class);

        try {
            $this->app->make(RecordService::class)->restore($record);
        } finally {
            $this->assertSame(RecordStatus::Archived, $record->refresh()->status);
            $this->assertNotNull($record->archived_at);
        }
    }

    // ---------------------------------------------------------------- subnav

    public function test_the_subnav_item_links_here_and_highlights(): void
    {
        $response = $this->get(route('records.archive.index'));

        $response->assertOk();
        $response->assertSee(route('records.archive.index'), false);

        // Every subnav item now has a route.
        $this->assertSame(0, substr_count($response->getContent() ?? '', 'href="#"'), 'A placeholder subnav item remains.');
    }

    public function test_the_archive_listing_route_did_not_take_the_archive_action_name(): void
    {
        // records.archive is still the POST that archives a record; the listing is
        // records.archive.index. They must not collide.
        $record = $this->filedRecord('Minutes of the Board');

        $this->assertStringNotContainsString('{record}', route('records.archive.index'));
        $this->post(route('records.archive', ['record' => $record->id]))->assertRedirect();

        $this->assertSame(RecordStatus::Archived, $record->refresh()->status);
    }

    // --------------------------------------------------------------- helpers

    /**
     * A Classification and Index belonging to whichever company is active.
     *
     * Filing validates the Classification against the active company, so a fixture
     * has to follow the context rather than reuse the first company's codes.
     *
     * @return array{0: RecordClassification, 1: RecordIndex}
     */
    private function schemeFor(Company $company): array
    {
        if (! isset($this->schemes[$company->id])) {
            $classification = $this->classification('301', 'Entity & Ownership', $company);
            $this->schemes[$company->id] = [
                $classification,
                $this->index($classification, '301-6', 'GIS'),
            ];
        }

        return $this->schemes[$company->id];
    }

    private function classification(
        string $code,
        string $name,
        ?Company $company = null,
    ): RecordClassification {
        $company ??= $this->company;

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ])->save();

        return $classification;
    }

    private function index(RecordClassification $classification, string $code, string $name): RecordIndex
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $classification->tenant_id,
            'company_id' => $classification->company_id,
            'record_classification_id' => $classification->id,
            'code' => $code,
            'name' => $name,
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        return $index;
    }

    private function filedRecord(string $title): Record
    {
        [$classification, $index] = $this->schemeFor($this->app->make(CompanyContext::class)->current());

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakePdf($title.'.pdf', 32)],
            ['title' => $title],
        );

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);
    }

    private function archivedRecord(string $title): Record
    {
        $record = $this->filedRecord($title);

        $this->post(route('records.archive', ['record' => $record->id]))->assertRedirect();

        return $record->refresh();
    }
}
