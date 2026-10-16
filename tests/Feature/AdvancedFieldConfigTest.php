<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\Records\RecordIntakeService;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Advanced Details renders what the selected Index applies, not everything.
 *
 * AT-07 asks for progressive disclosure: the default form shows the handful of
 * always-useful fields, and the advanced sections appear only for an Index that
 * declares them. Before this the panel opened on demand but then rendered all four
 * sections regardless, so the disclosure was cosmetic.
 *
 * The contract lives in record_indexes.advanced_field_config:
 *
 *   null            no advanced sections apply
 *   true            all four, required — the shape the panel already accepted
 *   { sections: [] } none, but the object is present
 *   { sections: [..], required: [..] }  the real case
 *
 * A bare `true` is kept working deliberately. Configs written before sections
 * existed must not silently lose their fields.
 */
class AdvancedFieldConfigTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordClassification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);

        $this->classification = new RecordClassification;
        $this->classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
            'is_active' => true,
        ])->save();
    }

    /**
     * The config reaches the browser at all. Without this the drawer's sort has
     * nothing to read, which is how the classify drawer ended up rendering every
     * section: the fields were never in the payload.
     */
    public function test_the_index_config_reaches_the_classify_drawer_payload(): void
    {
        $this->index('301-13', ['sections' => ['identifiers', 'signatories']]);

        $payload = html_entity_decode(
            $this->get(route('records.unclassified'))->getContent(),
            ENT_QUOTES,
        );

        // @js() writes every quote as an escape, so the key is searchable but not
        // in plain JSON form.
        $this->assertStringContainsString('\u0022advanced_field_config\u0022', $payload);
    }

    /**
     * The four sections are declared in one place in the drawer markup, keyed by the
     * field name they submit. This pins the set the config is allowed to name, so a
     * renamed field cannot leave a config pointing at nothing.
     */
    public function test_every_declared_section_maps_to_a_field_the_form_submits(): void
    {
        $page = $this->get(route('records.unclassified'))->getContent();

        foreach (['identifiers', 'additional_dates', 'signatories', 'financials'] as $section) {
            $this->assertStringContainsString(
                "name=\"{$section}[]\"",
                $page,
                "The {$section} section has no input on the page, so a config naming it would render nothing.",
            );
        }
    }

    /**
     * Each section is gated on its own key rather than on the panel being open, so
     * an Index that declares one section does not get the other three.
     */
    public function test_each_section_is_gated_on_its_own_key(): void
    {
        $page = $this->get(route('records.unclassified'))->getContent();

        foreach (['identifiers', 'additional_dates', 'signatories', 'financials'] as $section) {
            $this->assertStringContainsString(
                "advancedSectionVisible('{$section}')",
                $page,
                "The {$section} section is not gated on its own config key.",
            );
        }
    }

    /**
     * And the gate itself has to exist in the script, or the attribute above is a
     * call to nothing.
     */
    public function test_the_drawer_exposes_the_section_gate(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('advancedSectionVisible(section)', $js);
        $this->assertStringContainsString('get advancedSections()', $js);
    }

    /**
     * The four keys the config may name, in one place, matching the fields the form
     * submits. Read this to see what an advanced_field_config can ask for.
     */
    public function test_the_allowed_section_keys_are_the_four_submitted_fields(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString(
            "const ADVANCED_SECTIONS = ['identifiers', 'additional_dates', 'signatories', 'financials']",
            $js,
        );
    }

    /**
     * An Index that names an unknown key shows one field too few rather than an
     * unlabelled input. A typo in stored config should fail quietly and visibly.
     */
    public function test_an_unknown_section_key_is_ignored(): void
    {
        $this->index('301-13', ['sections' => ['identifiers', 'not_a_field']]);

        $index = RecordIndex::query()->where('code', '301-13')->firstOrFail();

        $this->assertSame(
            ['identifiers'],
            $this->applicableSections($index->advanced_field_config),
            'An unknown key should be dropped rather than rendered.',
        );
    }

    public function test_a_bare_true_still_means_all_four(): void
    {
        $this->assertSame(
            ['identifiers', 'additional_dates', 'signatories', 'financials'],
            $this->applicableSections(true),
            'Configs written before sections existed must keep working.',
        );
    }

    public function test_no_config_means_no_sections(): void
    {
        $this->assertSame([], $this->applicableSections(null));
        $this->assertSame([], $this->applicableSections(['sections' => []]));
    }

    public function test_only_the_declared_sections_apply(): void
    {
        $this->assertSame(
            ['identifiers', 'signatories'],
            $this->applicableSections(['sections' => ['identifiers', 'signatories']]),
        );
    }

    /**
     * The same filtering the browser does, so the contract is asserted here rather
     * than only through the rendered markup.
     *
     * @param  mixed  $config
     * @return list<string>
     */
    private function applicableSections(mixed $config): array
    {
        $allowed = ['identifiers', 'additional_dates', 'signatories', 'financials'];

        if ($config === true) {
            return $allowed;
        }

        if (! is_array($config) || ! is_array($config['sections'] ?? null)) {
            return [];
        }

        return array_values(array_filter(
            $config['sections'],
            static fn (string $section): bool => in_array($section, $allowed, true),
        ));
    }

    /**
     * @param  array<string, mixed>|bool|null  $config
     */
    private function index(string $code, array|bool|null $config = null): RecordIndex
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_classification_id' => $this->classification->id,
            'code' => $code,
            'name' => 'Board Resolution',
            'sort_order' => 0,
            'is_active' => true,
            'advanced_field_config' => $config,
        ])->save();

        $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'Queued'],
        );

        return $index;
    }
}