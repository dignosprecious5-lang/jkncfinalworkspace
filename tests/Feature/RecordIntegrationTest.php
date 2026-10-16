<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordLink;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Support\Auth;
use App\Support\GeneratedDocumentMappings;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The Integration tab's two actions, and the mapping they operate on.
 *
 * Both were deliberately absent before, on the grounds that a setup screen
 * should not write records. They are here now, so the tests are mostly about
 * what they cost and what they refuse:
 *
 *  - Simulate Generated Document files a real record. It must bill exactly one
 *    Record Unit, and it must refuse a mapping whose codes this company does not
 *    carry rather than filing it somewhere plausible-looking.
 *  - Test Global Record Picker creates a real link, which must cost nothing at
 *    all — a link is the one thing in ORDO that duplicates no file and consumes
 *    no capacity.
 */
class RecordIntegrationTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
        $this->user = $this->user();
    }

    // ----------------------------------------------------------- the mapping

    public function test_the_mapping_matches_the_reference_mock(): void
    {
        $rows = GeneratedDocumentMappings::rows();

        $this->assertCount(6, $rows);
        $this->assertSame('Board Resolution', $rows[0]['document']);
        $this->assertSame('301-13', $rows[0]['index']);
        $this->assertSame('1001-27', $rows[5]['index']);
    }

    public function test_a_mapping_out_of_range_resolves_to_nothing(): void
    {
        $this->assertNull(GeneratedDocumentMappings::resolve(99));
        $this->assertNull(GeneratedDocumentMappings::resolve(-1));
        $this->assertSame('Board Resolution', GeneratedDocumentMappings::resolve(0)['document']);
    }

    /**
     * The status column is derived, not copied. With an empty scheme nothing
     * resolves, so every row must say so rather than borrowing the mock's "Ready".
     */
    public function test_a_mapping_whose_codes_are_absent_is_marked_not_in_scheme(): void
    {
        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringContainsString('Not in scheme', $content);
    }

    public function test_a_mapping_whose_codes_exist_becomes_mapping_review(): void
    {
        $this->scheme('301', '301-13');

        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        // The 301/301-13 rows now resolve, so they read "Mapping Review" — while
        // the rows still naming codes this company lacks keep the other status.
        // Both appearing is the proof the column is derived rather than constant.
        $this->assertStringContainsString('Mapping Review', $content);
        $this->assertStringContainsString('Not in scheme', $content);
    }

    /**
     * The two states are not equally urgent and must not look identical.
     *
     * A missing code blocks the row outright; a present one only asks for a human
     * to confirm the pairing. Same pill colour would leave an integrator unable
     * to tell which rows they can act on today.
     */
    public function test_the_two_mapping_statuses_are_toned_differently(): void
    {
        $this->scheme('301', '301-13');

        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        // The pill component maps amber to warn and red to the error tone. Both
        // appearing means the column really is derived per row, not per page.
        $this->assertStringContainsString('#fff6e5', $content, 'No amber pill for the resolvable row.');
        $this->assertStringContainsString('#fff0f0', $content, 'No red pill for the unresolvable row.');
    }

    /**
     * The pill explains itself on hover, so the status column can stay short —
     * and it names whichever code is the one that is missing.
     */
    public function test_a_status_pill_explains_which_code_is_missing(): void
    {
        // 301 exists but has no Index under it, so the two rows that name 301
        // fail on the Index while every other row fails on the Classification.
        // Both branches of the hint are therefore exercised at once.
        $this->classificationOnly('301');

        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringContainsString('This scheme has no Index 301-13', $content);
        $this->assertStringContainsString('This scheme has no Classification 701', $content);
    }

    // -------------------------------------------------------------- simulate

    /**
     * The whole point: a module can hand a finished document to the engine and
     * get a Record back, through the ordinary path.
     */
    public function test_simulating_files_a_real_record(): void
    {
        $this->scheme('301', '301-13');

        $response = $this->post(route('records.integration.simulate'), ['mapping' => 0]);

        $response->assertRedirect();
        $response->assertSessionHas('setup_tab', 'integration');

        $record = Record::query()->where('title', 'Board Resolution')->firstOrFail();

        $this->assertSame('Entity & Governance', $record->source);
        $this->assertNotNull($record->document_reference);
        $this->assertNotNull($record->record_number);
        $this->assertSame(1, $record->recordUnits(), 'Filing must bill exactly one Record Unit.');
    }

    public function test_simulating_writes_a_filed_record_to_the_trail(): void
    {
        $this->scheme('301', '301-13');

        $this->post(route('records.integration.simulate'), ['mapping' => 0])->assertRedirect();

        $this->assertDatabaseHas('record_activity_logs', [
            'company_id' => $this->company->id,
            'action' => 'record.filed',
        ]);
    }

    /**
     * The guard that matters most.
     *
     * The mock's mapping names codes a standard ORDO scheme does not carry. Filing
     * "Board Resolution" under some other classification because 301-13 was
     * missing would be worse than refusing: the record would be real, correctly
     * numbered, and filed in the wrong place.
     */
    public function test_simulating_refuses_a_mapping_this_scheme_does_not_carry(): void
    {
        // Only 401 exists, so the 301/301-13 pairing cannot resolve.
        $this->scheme('401', '401-4');

        $before = Record::query()->count();

        $response = $this->post(route('records.integration.simulate'), ['mapping' => 0]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame($before, Record::query()->count(), 'A refused simulation still filed something.');
    }

    public function test_the_refusal_names_the_missing_code(): void
    {
        $this->scheme('401', '401-4');

        $this->post(route('records.integration.simulate'), ['mapping' => 0])
            ->assertSessionHas('error');

        $message = (string) session('error');

        $this->assertStringContainsString('301', $message);
        $this->assertStringContainsString('scheme', $message);
    }

    public function test_a_mapping_outside_the_table_is_refused(): void
    {
        $this->post(route('records.integration.simulate'), ['mapping' => 99])
            ->assertSessionHasErrors('mapping');

        $this->assertSame(0, Record::query()->count());
    }

    public function test_simulating_needs_a_mapping(): void
    {
        $this->post(route('records.integration.simulate'), [])
            ->assertSessionHasErrors('mapping');
    }

    // ----------------------------------------------------------- the picker

    public function test_the_global_picker_is_offered_on_the_tab(): void
    {
        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringContainsString('Global Record Picker', $content);
        $this->assertStringContainsString('Create Link', $content);
        $this->assertStringContainsString('No file is copied', $content);
    }

    /**
     * The picker posts to the endpoint the record drawer already uses, rather
     * than to a second implementation of the same write.
     */
    public function test_the_global_picker_posts_to_the_shared_link_endpoint(): void
    {
        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        // The record drawer fills the same placeholder at runtime, which is the
        // sign both screens post to one endpoint rather than two.
        $this->assertStringContainsString('__record__', $content);
        $this->assertStringContainsString('/links', $content);
    }

    public function test_the_picker_links_a_record_to_a_module_object(): void
    {
        $record = $this->filedRecord();
        $before = $record->recordUnits();
        $bytes = (int) $record->storage_bytes;

        $this->post(route('records.links.store', ['record' => $record->id]), [
            'module' => 'Transmittal',
            'reference_id' => 'TRN-00086',
            'linked_label' => 'RCBC Account Opening',
            'link_type' => 'supports',
        ])->assertRedirect();

        // A module link is a polymorphic row: the module is the type, slugged by
        // the link service, and the module's own identifier is the id.
        $this->assertDatabaseHas('record_links', [
            'record_id' => $record->id,
            'linked_type' => 'transmittal',
            'linked_id' => 'TRN-00086',
            'linked_label' => 'RCBC Account Opening',
            'link_type' => 'supports',
        ]);

        // The claim the Integration tab makes: a link duplicates no file and
        // consumes no capacity on either side.
        $this->assertSame($before, $record->refresh()->recordUnits());
        $this->assertSame($bytes, (int) $record->storage_bytes);
    }

    public function test_the_picker_will_not_link_another_companys_record(): void
    {
        $theirs = $this->seedSecondCompany($this->company);
        $this->actingInCompany($theirs);
        $theirRecord = $this->filedRecord('Theirs');

        $this->actingInCompany($this->company);

        $this->post(route('records.links.store', ['record' => $theirRecord->id]), [
            'module' => 'Transmittal',
            'reference_id' => 'TRN-1',
            'link_type' => 'supports',
        ])->assertNotFound();

        $this->assertSame(0, RecordLink::query()->where('record_id', $theirRecord->id)->count());
    }

    // ------------------------------------------------- unregistered uploads

    /**
     * The Integration tab's "Register" sends the reader to the queue with the
     * item named, so a chosen upload can be filed without hunting for it.
     */
    public function test_register_deep_links_to_the_queue_with_the_item(): void
    {
        $item = $this->app->make(RecordIntakeService::class)
            ->createIntakeItem([$this->fakeImage('meeting_supporting_document.jpg')]);

        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringContainsString('meeting_supporting_document.jpg', $content);
        $this->assertStringContainsString('Register', $content);
        $this->assertStringContainsString(route('records.unclassified').'?open='.$item->id, $content);
    }

    public function test_the_queue_opens_the_requested_item(): void
    {
        $item = $this->app->make(RecordIntakeService::class)
            ->createIntakeItem([$this->fakeImage('queued.jpg')]);

        $this->get(route('records.unclassified', ['open' => $item->id]))
            ->assertOk()
            ->assertSee((string) $item->id, false);
    }

    /**
     * A crafted ?open= must not point the drawer at somebody else's document.
     */
    public function test_the_queue_ignores_an_item_belonging_to_another_company(): void
    {
        $theirs = $this->seedSecondCompany($this->company);
        $this->actingInCompany($theirs);
        $theirItem = $this->app->make(RecordIntakeService::class)
            ->createIntakeItem([$this->fakeImage('theirs.jpg')]);

        $this->actingInCompany($this->company);

        $this->get(route('records.unclassified', ['open' => $theirItem->id]))
            ->assertOk()
            ->assertViewHas('openItemId', null);
    }

    // ------------------------------------------------------------ usage events

    public function test_usage_events_show_real_record_numbers(): void
    {
        $record = $this->filedRecord('Minutes');

        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringContainsString('record.filed', $content);
        $this->assertStringContainsString((string) $record->record_number, $content);
    }

    public function test_usage_events_never_show_another_companys(): void
    {
        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);
        $this->filedRecord('Theirs');

        $this->actingInCompany($this->company);

        $content = $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringNotContainsString('Theirs', $content);
    }

    // ---------------------------------------------------------- confidentiality

    /**
     * The Setup modal is open to anyone who can view Records, so a tab inside it
     * cannot list confidential Records to a role that cannot open them. Both
     * surfaces below — the picker's list and the events card — used to read off
     * the model directly and would have handed out titles and references.
     */
    public function test_the_integration_tab_hides_confidential_records_from_a_standard_user(): void
    {
        $this->filedRecord('Ordinary Record');
        $secret = $this->filedRecord('Salary Benchmark 2026');
        $secret->forceFill(['is_confidential' => true])->save();

        $content = Auth::as(
            User::factory()->create(['role' => UserRole::StandardUser]),
            fn () => $this->get(route('records.setup.tab', ['tab' => 'integration']))
                ->assertOk()
                ->getContent() ?? '',
        );

        $this->assertStringNotContainsString('Salary Benchmark 2026', $content);
        $this->assertStringContainsString('Ordinary Record', $content);
    }

    /**
     * The events card quotes a Record's own description text, so withholding an
     * entry has to be whole rather than a blanked reference with the sentence
     * still standing.
     */
    public function test_the_events_card_withholds_entries_about_confidential_records(): void
    {
        $secret = $this->filedRecord('Director Succession Plan');
        $secret->forceFill(['is_confidential' => true])->save();

        $standard = fn () => $this->get(route('records.setup.tab', ['tab' => 'integration']))
            ->assertOk()
            ->getContent() ?? '';

        $this->assertStringNotContainsString((string) $secret->record_number, Auth::as(
            User::factory()->create(['role' => UserRole::StandardUser]),
            $standard,
        ));

        // An Auditor may see confidential Records, and is entitled to the entry.
        $this->assertStringContainsString((string) $secret->record_number, Auth::as(
            User::factory()->create(['role' => UserRole::Auditor]),
            $standard,
        ));
    }

    /**
     * The drawer's picker had the same exposure and the same fix. The record is
     * not in the fragment at all, rather than hidden by the browser.
     */
    public function test_the_drawer_picker_hides_confidential_records_from_a_standard_user(): void
    {
        $source = $this->filedRecord('The Source Record');
        $this->filedRecord('Ordinary Record');
        $secret = $this->filedRecord('Salary Benchmark 2026');
        $secret->forceFill(['is_confidential' => true])->save();

        $content = Auth::as(
            User::factory()->create(['role' => UserRole::StandardUser]),
            fn () => $this->get(route('records.links.picker', ['record' => $source->id]))
                ->assertOk()
                ->getContent() ?? '',
        );

        // The other record is offered; the confidential one is not in the
        // fragment at all, rather than hidden once it reaches the browser.
        $this->assertStringContainsString('Ordinary Record', $content);
        $this->assertStringNotContainsString('Salary Benchmark 2026', $content);
    }

    // ---------------------------------------------------------------- helpers

    private function user(): User
    {
        return User::query()->first() ?? User::factory()->create(['name' => 'Integration Fixture']);
    }

    private function scheme(string $classificationCode, string $indexCode): void
    {
        $this->schemeFor($this->company->id, $classificationCode, $indexCode);
    }

    /**
     * A Classification with nothing filed under it.
     *
     * The distinction matters when checking the mapping's status: a pairing
     * naming 301/301-13 then fails on the Index alone, which is a different
     * state from one failing on the Classification and worth telling apart.
     */
    private function classificationOnly(string $classificationCode): void
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => $classificationCode,
            'name' => 'Entity & Ownership',
            'is_active' => true,
        ])->save();
    }

    private function schemeFor(string $companyId, string $classificationCode = '301', string $indexCode = '301-6'): void
    {
        $company = Company::query()->findOrFail($companyId);

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => $classificationCode,
            'name' => 'Entity & Ownership',
            'is_active' => true,
        ])->save();

        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_classification_id' => $classification->id,
            'code' => $indexCode,
            'name' => 'Board Resolution',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();
    }

    private function filedRecord(string $title = 'Minutes'): Record
    {
        // The scheme is built on demand rather than required of every caller,
        // because filing is what most of these tests are setting up.
        if (! RecordClassification::query()->where('company_id', $this->company->id)->exists()) {
            $this->scheme('301', '301-6');
        }

        // Read from the acting company rather than $this->company: the scoping
        // tests act in a second company, and the filing service refuses a
        // Classification belonging to anybody else.
        $companyId = $this->app->make(CompanyContext::class)->id();

        if (! RecordClassification::query()->where('company_id', $companyId)->exists()) {
            $this->schemeFor($companyId);
        }

        $classification = RecordClassification::query()->where('company_id', $companyId)->firstOrFail();
        $index = RecordIndex::query()->where('company_id', $companyId)->firstOrFail();

        $item = $this->app->make(RecordIntakeService::class)
            ->createIntakeItem([$this->fakePdf($title.'.pdf', 16)]);

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'title' => $title,
        ], $this->user)->refresh();
    }
}
