<?php

namespace Tests\Feature;

use App\Enums\RecordFieldType;
use App\Enums\ReferenceStatus;
use App\Enums\SourceType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordField;
use App\Models\RecordIndex;
use App\Models\RecordIntakeItem;
use App\Models\RecordSource;
use App\Services\CompanyContext;
use App\Services\Records\RecordLookups;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The Records Management Setup modal: the gear on every page, the tabs it
 * serves, and the two writes those tabs own.
 *
 * The isolation assertions matter more than the rendering ones here. The modal is
 * mounted in the layout, so its tab fragment is reachable from every page, and a
 * tab that read reference data without scoping it to the active company would
 * show one company's scheme inside another's window.
 */
class RecordSetupModalTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    // ------------------------------------------------------------ every page

    /**
     * The gear belongs to the layout rather than to any one page, so the modal is
     * asserted present on each page that offers it rather than on a single screen.
     *
     * @return list<array{string}>
     */
    public static function pagesWithTheGear(): array
    {
        return [
            ['records.dashboard'],
            ['records.index'],
            ['records.unclassified'],
            ['records.classifications'],
            ['records.archive.index'],
            ['records.logs'],
            ['records.reports.index'],
            ['records.intake.upload'],
        ];
    }

    /**
     * @dataProvider pagesWithTheGear
     */
    #[DataProvider('pagesWithTheGear')]
    public function test_every_page_offers_the_setup_gear(string $route): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('Records Management Setup')
            ->assertSee('title="Records setup"', false);
    }

    // ------------------------------------------------------------ the tabs

    public function test_each_tab_renders(): void
    {
        foreach (RecordSetupControllerTabs::ALL as $tab) {
            $this->get(route('records.setup.tab', ['tab' => $tab]))->assertOk();
        }
    }

    public function test_an_unknown_tab_is_not_found(): void
    {
        $this->get('/records/setup/nonsense')->assertNotFound();
    }

    /**
     * The add forms inside the modal must read the same as the ones on the
     * Classifications page. They are the same form reached through two different
     * doors, and a copy change made in only one of them leaves two different
     * forms behind.
     */
    /**
     * The tab renders the same tree component as the Classifications page.
     *
     * They show one scheme through two doors. Sharing the component is what stops
     * a row layout changing on one screen and not the other, which is exactly
     * what had happened: the page used a flex row and bordered action buttons
     * while the tab used something else again.
     */
    public function test_the_tab_renders_the_shared_tree_component(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity &amp; Ownership',
            'description' => 'Legal identity records.',
            'is_active' => true,
        ])->save();

        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $classification->tenant_id,
            'company_id' => $classification->company_id,
            'record_classification_id' => $classification->id,
            'code' => '301-6',
            'name' => 'General Information Sheet',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        $response = $this->get(route('records.setup.tab', ['tab' => 'classifications']));

        $response->assertOk();
        // The reference grid, shared with the page.
        $response->assertSee('grid-cols-[90px_1fr_120px_90px]', false);
        $response->assertSee('301-6', false);
        // Text actions, not bordered buttons.
        $response->assertSee('>View Records</a>', false);
        $response->assertSee('border-0 bg-transparent p-0 text-[11px] text-ordo-blue', false);
    }

    public function test_the_setup_copy_of_the_add_forms_matches_the_page(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $response = $this->get(route('records.setup.tab', ['tab' => 'classifications']));

        $response->assertOk();
        $response->assertSee('Add Classification');
        $response->assertSee('Classification Code');
        $response->assertSee('Classification Name');
        // The asterisk came from hand-written labels that also spelled out the
        // required marker; the field component renders it.
        $response->assertDontSee('Classification Code *');
        $response->assertDontSee('Classification Name *');
        $response->assertSee('>Save<', false);
        // The description box is a bare field, as the reference shows it.
        $response->assertDontSee('What belongs in this classification.');
    }

    public function test_the_sources_tab_lists_the_companys_sources(): void
    {
        $this->source('Bureau of Internal Revenue', SourceType::GovernmentAgency);

        $this->get(route('records.setup.tab', ['tab' => 'sources']))
            ->assertOk()
            // The controller always serves the dispatcher; which tab it lands on
            // is asserted by what that tab renders.
            ->assertViewIs('records.partials.setup.tab')
            ->assertSee('Bureau of Internal Revenue')
            ->assertSee('Government Agency');
    }

    public function test_the_fields_tab_lists_the_registered_fields(): void
    {
        $this->field('Client Code', RecordFieldType::Text);

        $this->get(route('records.setup.tab', ['tab' => 'fields']))
            ->assertOk()
            ->assertSee('Client Code')
            ->assertSee('Text');
    }

    /**
     * The Usage tab reports the company's own figures, so a Record Unit that was
     * never filed can never appear in another company's meter.
     */
    public function test_the_usage_tab_counts_only_this_companys_records(): void
    {
        $this->actingInCompany($this->seedSecondCompany($this->company));

        // One filed record in the other company, contributing 1 Record Unit and
        // some storage. None of it may show up in this company's meter.
        $theirs = $this->classification('301', 'Theirs');
        $this->record($theirs, storageBytes: 2_048);

        $this->actingInCompany($this->company);

        $this->get(route('records.setup.tab', ['tab' => 'usage']))
            ->assertOk()
            ->assertViewHas('recordUnitsUsed', 0)
            ->assertViewHas('storageBytesUsed', 0);
    }

    public function test_the_usage_tab_includes_unclassified_uploads_in_storage_only(): void
    {
        $classification = $this->classification('301', 'Entity &amp; Ownership');
        $this->record($classification, storageBytes: 2_048);
        // An intake item: storage bytes, but no Record Unit.
        $intake = new RecordIntakeItem;
        $intake->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'title' => 'Scanned Receipt',
            'status' => 'pending',
            'capture_source' => 'upload',
            'storage_bytes' => 4_096,
        ])->save();

        $this->get(route('records.setup.tab', ['tab' => 'usage']))
            ->assertOk()
            ->assertViewHas('recordUnitsUsed', 1)
            // 2048 from the record plus 4096 from the upload.
            ->assertViewHas('storageBytesUsed', 6_144);
    }

    /**
     * The Record ID is configurable, because it is a separate identifier from the
     * Document Reference and nothing here can compromise the latter.
     */
    public function test_the_numbering_tab_offers_the_record_id_settings(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'numbering']));

        $response->assertOk();
        $response->assertSee('name="record_prefix"', false);
        $response->assertSee('name="record_sequence_digits"', false);
        $response->assertSee('name="record_next_sequence"', false);
        $response->assertSee('name="record_include_year"', false);
        $response->assertSee('Save Numbering');
        $response->assertSee('Record ID Example');
    }

    /**
     * The half of the guard that still has to hold.
     *
     * This replaces the old read-only test, which kept the whole tab inputless
     * on the grounds that nothing behind it could change. That conflated the two
     * identifiers: the Document Reference is still generated system-side at
     * filing, and there is deliberately still no control that touches it.
     */
    public function test_the_document_reference_stays_system_controlled(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'numbering']));

        $response->assertOk();
        $response->assertSee('Document Reference &mdash; system controlled', false);
        // Nothing in the form posts to the reference.
        $response->assertDontSee('name="document_reference"', false);
    }

    public function test_the_numbering_example_shows_the_saved_settings(): void
    {
        $company = $this->company->forceFill([
            'record_prefix' => 'ACME',
            'record_sequence_digits' => 4,
            'record_next_sequence' => 1285,
            'record_include_year' => true,
        ]);

        $company->save();
        $this->actingInCompany($company->refresh());

        $this->get(route('records.setup.tab', ['tab' => 'numbering']))
            ->assertOk()
            ->assertSee('ACME-'.now()->format('Y').'-1285');
    }

    public function test_the_numbering_example_omits_the_year_when_it_is_switched_off(): void
    {
        $company = $this->company->forceFill([
            'record_prefix' => 'ACME',
            'record_sequence_digits' => 6,
            'record_next_sequence' => 1285,
            'record_include_year' => false,
        ]);

        $company->save();
        $this->actingInCompany($company->refresh());

        $this->get(route('records.setup.tab', ['tab' => 'numbering']))
            ->assertOk()
            ->assertSee('ACME-001285')
            ->assertDontSee('ACME-'.now()->format('Y').'-001285', false);
    }

    /**
     * Opening the modal must not consume a number.
     */
    public function test_opening_the_tab_does_not_advance_the_sequence(): void
    {
        $company = $this->company->forceFill(['record_next_sequence' => 1285]);
        $company->save();
        $this->actingInCompany($company->refresh());

        $this->get(route('records.setup.tab', ['tab' => 'numbering']))->assertOk();
        $this->get(route('records.setup.tab', ['tab' => 'numbering']))->assertOk();

        $this->assertSame(1285, (int) $company->refresh()->record_next_sequence);
    }

    /**
     * The Integration tab documents module connections that are not built yet. It
     * must not offer the mock's simulators, which would write records from a
     * screen whose job is to describe what comes next.
     */
    /**
     * The Integration tab's shape.
     *
     * Replaces the old test that asserted the tab offered no writing actions.
     * Its premise is gone: both actions are now real, so the tab claims less and
     * does more.
     */
    public function test_the_integration_tab_carries_every_section(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'integration']));

        $response->assertOk();

        // Stack and pattern
        $response->assertSee('Current ORDO Stack');
        $response->assertSee('Brevo');
        $response->assertSee('Integration Mode');
        $response->assertSee('Additive shared module');
        $response->assertSee('Core Pattern');

        // The mapping, now with an Index column
        $response->assertSee('Generated Document Mapping');
        $response->assertSee('Board Resolution');
        $response->assertSee('301-13');
        $response->assertSee('>Index<', false);

        // The two actions
        $response->assertSee('Simulate Generated Document');
        $response->assertSee('Test Global Record Picker');

        // Unregistered uploads and the wiring contract
        $response->assertSee('Unregistered Uploads');
        $response->assertSee('Shared Service Wiring');
        $response->assertSee('App\Services\Records\RecordService', false);

        // Live figures and real events
        $response->assertSee('Connected Across ORDO');
        $response->assertSee('Recent Usage / Integration Events');
    }

    /**
     * The claim the reference mock makes and this codebase cannot.
     *
     * Four of its rows read "Ready", meaning those modules can already register
     * documents. No module in ORDO reaches the Records engine — Corporate
     * Governance is explicitly a later phase — so the column is derived from
     * this company's own scheme instead of copied.
     */
    public function test_the_integration_tab_never_claims_a_mapping_is_ready(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'integration']));

        $response->assertOk();
        $response->assertDontSee('>Ready</span>', false);
        $response->assertDontSee('>Ready</button>', false);
    }

    /**
     * The two services the Core Pattern names and this codebase does not have.
     */
    public function test_the_wiring_table_marks_what_is_not_built(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'integration']));

        $response->assertOk();
        $response->assertSee('App\Models\Concerns\Recordable', false);
        $response->assertSee('Laravel Notifications → Brevo', false);
        // Neither is presented as wired: the row itself says so.
        $response->assertSee('>planned</span>', false);
        $response->assertSee('Not implemented in this codebase.', false);
    }

    /**
     * With nothing in the scheme carrying the codes the mapping names, offering
     * the simulate button would only produce a validation failure. The tab says
     * why instead.
     */
    public function test_the_simulate_button_is_withheld_when_no_mapping_can_resolve(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'integration']));

        $response->assertOk();
        // The form is what makes the button work, and it is the form that goes:
        // asserting on the route rather than the label, because the label also
        // appears in the paragraph explaining what the button costs.
        $response->assertDontSee(route('records.integration.simulate'), false);
        $response->assertSee('No mapping can be simulated yet');
    }

    /**
     * With at least one resolvable row the button is offered, and the rows that
     * cannot resolve are disabled rather than hidden.
     */
    public function test_the_simulate_button_appears_once_a_mapping_can_resolve(): void
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity & Ownership',
            'is_active' => true,
        ])->save();

        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_classification_id' => $classification->id,
            'code' => '301-13',
            'name' => 'Board Resolution',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        $response = $this->get(route('records.setup.tab', ['tab' => 'integration']));

        $response->assertOk();
        $response->assertSee(route('records.integration.simulate'), false);
        $response->assertSee('Simulate Generated Document');
        $response->assertDontSee('No mapping can be simulated yet');
        // The unresolvable rows stay visible, just not selectable.
        $response->assertSee('disabled', false);
        $response->assertSee('Transmittal Output');
    }

    /**
     * The simulate button spends money, so the cost is stated where the button
     * is rather than discovered afterwards.
     */
    public function test_the_simulate_action_states_what_it_costs(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'integration']));

        $response->assertOk();
        $response->assertSee('1 Record Unit');
        $response->assertSee('creates a real intake item and files it');
    }

    // ------------------------------------------------------------ isolation

    public function test_the_sources_tab_shows_only_this_companys_sources(): void
    {
        $mine = $this->source('Mine', SourceType::Internal);

        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);
        $theirs = new RecordSource;
        $theirs->forceFill([
            'tenant_id' => $other->tenant_id,
            'company_id' => $other->id,
            'name' => 'Theirs',
            'type' => SourceType::Bank->value,
            'status' => ReferenceStatus::Active->value,
            'sort_order' => 0,
        ])->save();

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.setup.tab', ['tab' => 'sources']));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee('Theirs');
    }

    public function test_the_fields_tab_shows_only_this_companys_fields(): void
    {
        $mine = $this->field('Mine', RecordFieldType::Text);

        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);
        $theirs = new RecordField;
        $theirs->forceFill([
            'tenant_id' => $other->tenant_id,
            'company_id' => $other->id,
            'name' => 'Theirs',
            'type' => RecordFieldType::Text->value,
            'is_filterable' => false,
            'status' => ReferenceStatus::Active->value,
            'sort_order' => 0,
        ])->save();

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.setup.tab', ['tab' => 'fields']));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee('Theirs');
    }

    // ------------------------------------------------------------ writing

    public function test_a_system_admin_can_add_a_source(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $response = $this->from(route('records.dashboard'))->post(route('records.sources.store'), [
            'name' => 'Securities and Exchange Commission',
            'type' => SourceType::GovernmentAgency->value,
        ]);

        $response->assertRedirect(route('records.dashboard'));
        $response->assertSessionHas('setup_tab', 'sources');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('record_sources', [
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'name' => 'Securities and Exchange Commission',
            'type' => SourceType::GovernmentAgency->value,
            'status' => ReferenceStatus::Active->value,
        ]);
    }

    /**
     * A source appears in the filing form's Source dropdown. Without dropping the
     * cached lookups the one added here would be missing from the very next
     * screen the user reaches.
     */
    public function test_adding_a_source_drops_the_cached_lookups(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $this->post(route('records.sources.store'), [
            'name' => 'Land Registry',
            'type' => SourceType::GovernmentAgency->value,
        ])->assertRedirect();

        // A read through the lookup service must see the new source.
        $lookups = app(RecordLookups::class);

        $this->assertTrue(
            $lookups->sources()->contains(fn (RecordSource $source): bool => $source->name === 'Land Registry'),
            'The new source was not visible through the shared lookups.',
        );
    }

    public function test_a_duplicate_source_name_is_refused_for_the_same_company(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);
        $this->source('Bureau of Internal Revenue', SourceType::GovernmentAgency);

        $this->post(route('records.sources.store'), [
            'name' => 'Bureau of Internal Revenue',
            'type' => SourceType::Bank->value,
        ])->assertSessionHasErrors('name');
    }

    /**
     * Uniqueness is per company: two companies may each register their own
     * "Client Code" without colliding.
     */
    public function test_the_same_source_name_is_allowed_in_another_company(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);
        $this->source('Land Registry', SourceType::GovernmentAgency);

        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);

        $this->post(route('records.sources.store'), [
            'name' => 'Land Registry',
            'type' => SourceType::GovernmentAgency->value,
        ])->assertSessionHasNoErrors();
    }

    public function test_a_system_admin_can_add_a_record_field(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $response = $this->from(route('records.dashboard'))->post(route('records.fields.store'), [
            'name' => 'Client Code',
            'type' => RecordFieldType::Text->value,
            'is_filterable' => '1',
        ]);

        $response->assertRedirect(route('records.dashboard'));
        $response->assertSessionHas('setup_tab', 'fields');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('record_fields', [
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'name' => 'Client Code',
            'type' => RecordFieldType::Text->value,
            'is_filterable' => true,
            'status' => ReferenceStatus::Active->value,
        ]);
    }

    /**
     * An unchecked filterable box is simply absent from the payload, so the field
     * must default to not filterable rather than silently becoming filterable.
     */
    public function test_a_field_is_not_filterable_unless_asked_for(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $this->post(route('records.fields.store'), [
            'name' => 'Client Code',
            'type' => RecordFieldType::Text->value,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('record_fields', [
            'name' => 'Client Code',
            'is_filterable' => false,
        ]);
    }

    public function test_an_unsupported_field_type_is_refused(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $this->post(route('records.fields.store'), [
            'name' => 'Client Code',
            'type' => 'Not A Real Type',
        ])->assertSessionHasErrors('type');
    }

    /**
     * A Records Manager files records against the scheme but does not rewrite it,
     * so the setup writes are refused for that role. Only a System Admin manages.
     */
    public function test_a_records_manager_cannot_add_a_source(): void
    {
        config(['ordo.default_role' => UserRole::RecordsManager->value]);

        $this->post(route('records.sources.store'), [
            'name' => 'Land Registry',
            'type' => SourceType::GovernmentAgency->value,
        ])->assertForbidden();

        $this->assertDatabaseMissing('record_sources', ['name' => 'Land Registry']);
    }

    public function test_a_records_manager_cannot_add_a_field(): void
    {
        config(['ordo.default_role' => UserRole::RecordsManager->value]);

        $this->post(route('records.fields.store'), [
            'name' => 'Client Code',
            'type' => RecordFieldType::Text->value,
        ])->assertForbidden();

        $this->assertDatabaseMissing('record_fields', ['name' => 'Client Code']);
    }

    // ------------------------------------------------------- plan limits

    public function test_the_usage_tab_offers_the_plan_limits_editor(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $this->get(route('records.setup.tab', ['tab' => 'usage']))
            ->assertOk()
            ->assertSee('Plan Limits')
            ->assertSee('name="record_unit_allocation"', false)
            ->assertSee('name="storage_limit_gb"', false);
    }

    public function test_a_system_admin_can_set_the_plan_limits(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $response = $this->from(route('records.dashboard'))->put(route('records.plan-limits.update'), [
            'record_unit_allocation' => 250,
            'storage_limit_gb' => 2.5,
        ]);

        $response->assertRedirect(route('records.dashboard'));
        // The write came from the setup modal, so the modal must come back to the
        // tab it was made in rather than closing on save.
        $response->assertSessionHas('setup_tab', 'usage');
        $response->assertSessionHas('status');

        $this->company->refresh();

        $this->assertSame(250, (int) $this->company->record_unit_allocation);
        // 2.5 GB is stored as raw bytes, matching the unit the storage sum uses.
        $this->assertSame((int) (2.5 * 1073741824), (int) $this->company->storage_limit_bytes);
    }

    /**
     * Zero is the explicit "unlimited" choice, and the rest of the application
     * reads a null allowance as unlimited. Storing 0 instead would show up as a
     * company that has consumed its whole allowance of nothing.
     */
    public function test_a_zero_allowance_is_stored_as_unlimited(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $this->put(route('records.plan-limits.update'), [
            'record_unit_allocation' => 0,
            'storage_limit_gb' => 0,
        ])->assertSessionHasNoErrors();

        $this->company->refresh();

        $this->assertNull($this->company->record_unit_allocation);
        $this->assertNull($this->company->storage_limit_bytes);
    }

    public function test_a_negative_allowance_is_refused(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $this->put(route('records.plan-limits.update'), [
            'record_unit_allocation' => -1,
            'storage_limit_gb' => 1,
        ])->assertSessionHasErrors('record_unit_allocation');
    }

    /**
     * Plan limits are billing inputs, so they are reference data rather than
     * day-to-day records work and are reserved for a System Admin.
     */
    public function test_a_records_manager_cannot_change_the_plan_limits(): void
    {
        config(['ordo.default_role' => UserRole::RecordsManager->value]);

        $this->put(route('records.plan-limits.update'), [
            'record_unit_allocation' => 999,
            'storage_limit_gb' => 1,
        ])->assertForbidden();

        $this->company->refresh();

        $this->assertNotSame(999, (int) $this->company->record_unit_allocation);
    }

    /**
     * The limits belong to the active company and are never taken from the
     * request, so a payload cannot retune another company's metering.
     */
    public function test_plan_limits_are_only_changed_for_the_active_company(): void
    {
        config(['ordo.default_role' => UserRole::SystemAdmin->value]);

        $other = $this->seedSecondCompany($this->company);
        $other->forceFill(['record_unit_allocation' => 100])->save();

        $this->actingInCompany($this->company);

        $this->put(route('records.plan-limits.update'), [
            'record_unit_allocation' => 999,
            'storage_limit_gb' => 1,
            // Present in the payload and deliberately ignored.
            'company_id' => $other->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(999, (int) $this->company->refresh()->record_unit_allocation);
        $this->assertSame(100, (int) $other->refresh()->record_unit_allocation);
    }

    // --------------------------------------------------------------- helpers

    /**
     * Helpers below write through the active company rather than through
     * $this->company.
     *
     * The isolation tests switch company mid-test and then write; helpers that
     * pinned the original company would quietly put that data in the wrong tenant
     * scope and make the isolation assertion pass for the wrong reason.
     */
    private function activeCompany(): Company
    {
        return app(CompanyContext::class)->current();
    }

    private function source(string $name, SourceType $type): RecordSource
    {
        $company = $this->activeCompany();

        $source = new RecordSource;
        $source->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'name' => $name,
            'type' => $type->value,
            'status' => ReferenceStatus::Active->value,
            'sort_order' => 0,
        ])->save();

        return $source;
    }

    private function field(string $name, RecordFieldType $type): RecordField
    {
        $company = $this->activeCompany();

        $field = new RecordField;
        $field->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'name' => $name,
            'type' => $type->value,
            'is_filterable' => false,
            'status' => ReferenceStatus::Active->value,
            'sort_order' => 0,
        ])->save();

        return $field;
    }

    private function classification(string $code, string $name): RecordClassification
    {
        $company = $this->activeCompany();

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => $code,
            'name' => $name,
            'description' => 'Test records.',
            'is_active' => true,
        ])->save();

        return $classification;
    }

    private function record(RecordClassification $classification, int $storageBytes = 0): Record
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $classification->tenant_id,
            'company_id' => $classification->company_id,
            'record_classification_id' => $classification->id,
            'code' => '301-1',
            'name' => 'Incorporation',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        $record = new Record;
        $record->forceFill([
            'tenant_id' => $classification->tenant_id,
            'company_id' => $classification->company_id,
            'record_classification_id' => $classification->id,
            // A record always points at an index as well as a classification.
            'record_index_id' => $index->id,
            'document_reference' => 'AB12C',
            'title' => 'Filed Record',
            'status' => 'filed',
            'record_units' => 1,
            'storage_bytes' => $storageBytes,
            'currency' => 'PHP',
        ])->save();

        return $record;
    }
}

/**
 * The tab list, mirrored from the controller.
 *
 * Duplicated rather than imported so the test fails if a tab is added without
 * being reachable here, which is the mistake worth catching.
 */
final class RecordSetupControllerTabs
{
    /** @var list<string> */
    public const ALL = [
        'classifications',
        'sources',
        'fields',
        'numbering',
        'usage',
        'integration',
    ];
}
