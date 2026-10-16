<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\CompanyContext;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordSearchService;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordDashboardPageTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    /** @var array<string, array<string, RecordClassification>> */
    private array $classifications = [];

    /** @var array<string, array<string, RecordIndex>> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    public function test_the_dashboard_renders(): void
    {
        $this->record();

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $response->assertViewIs('records.dashboard');
        $response->assertSee('Records Management');
    }

    // ------------------------------------------------------- link appearance

    /**
     * Nothing on the dashboard underlines itself on hover.
     *
     * A guard rather than a style preference: the reference mock's .link rule
     * draws a line in no state at all, so an underline creeping back into one of
     * the card components would pass every functional test and still be wrong.
     */
    public function test_no_dashboard_link_underlines_on_hover(): void
    {
        $this->record();

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $this->assertStringNotContainsString(
            'hover:underline',
            $response->getContent(),
            'A dashboard link still underlines itself on hover.',
        );
    }

    /**
     * Links keep a hover signal — they just do not draw a line under themselves.
     */
    public function test_dashboard_links_still_react_to_hover(): void
    {
        $this->record();

        $this->get(route('records.dashboard'))
            ->assertOk()
            ->assertSee('hover:opacity-75', false);
    }

    // ----------------------------------------------- recently added records

    /**
     * The Recently Added table reads its columns through joins.
     *
     * A bare addSelect() alongside those joins would leave the query selecting
     * only the joined aliases, so the title, source and date of every row would
     * come back empty while the table still rendered four plausible-looking rows.
     * Asserting the record's own values is what catches that.
     */
    public function test_the_recently_added_table_shows_the_record_own_values(): void
    {
        $this->record([
            'title' => 'Board Resolution 2026',
            'source' => 'Corporate Secretary',
        ]);

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $response->assertSee('Recently Added Records');
        // The title and the source both come from records.*, which is the part
        // that goes missing when the join aliases are selected on their own.
        $response->assertSee('Board Resolution 2026');
        $response->assertSee('Corporate Secretary');
    }

    public function test_the_recently_added_table_links_to_the_register_by_reference(): void
    {
        $record = $this->record(['title' => 'Linked Record']);

        $this->get(route('records.dashboard'))
            ->assertOk()
            ->assertSee(route('records.index', ['q' => $record->document_reference]), false);
    }

    public function test_the_recently_added_table_excludes_records_that_were_never_filed(): void
    {
        // An intake item that has not been filed is not a record, so it has no
        // place in a table of recently added records.
        $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'Still Unfiled'],
        );

        $this->get(route('records.dashboard'))
            ->assertOk()
            ->assertDontSee('Still Unfiled');
    }

    // -------------------------------------------------------------- quality

    /**
     * Completeness rides along in the count the dashboard already runs, so these
     * three figures cost no query of their own.
     */
    public function test_records_quality_counts_what_each_record_is_missing(): void
    {
        // One complete record and one missing its source.
        $this->record(['title' => 'Complete', 'source' => 'Land Registry']);
        $this->record(['title' => 'Incomplete', 'source' => null]);

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $response->assertSee('Records Quality');
        $response->assertViewHas('counts', fn (array $counts): bool => $counts['missing_source'] === 1
            && $counts['missing_title'] === 0);
    }

    public function test_records_by_source_reports_a_record_with_no_source(): void
    {
        $this->record(['title' => 'Has Source', 'source' => 'Land Registry']);
        $this->record(['title' => 'Has No Source', 'source' => null]);

        $this->get(route('records.dashboard'))
            ->assertOk()
            ->assertSee('Records by Source')
            // A record with no source is reported rather than dropped, so the gap
            // is visible here as well as in the quality panel.
            ->assertSee('Land Registry')
            ->assertSee('(No source)');
    }

    /**
     * The completeness figures count only what the current user may see. A
     * confidential record they cannot open must not be reported to them as one
     * they could go and fix.
     */
    public function test_records_quality_ignores_records_the_user_may_not_see(): void
    {
        config(['ordo.default_role' => UserRole::StandardUser->value]);

        $this->record(['title' => 'Visible', 'source' => 'Land Registry']);
        $this->record(['title' => 'Hidden', 'source' => null]);

        $record = Record::query()->where('title', 'Hidden')->firstOrFail();
        $record->forceFill(['is_confidential' => true])->save();

        $this->get(route('records.dashboard'))
            ->assertOk()
            ->assertViewHas('counts', fn (array $counts): bool => $counts['missing_source'] === 0);
    }

    /**
     * Record Units and storage are billed differently, so the dashboard states
     * them as two separate figures rather than one blended number.
     *
     * They now sit in the Repository Usage card rather than in the headline row,
     * but the separation AGENTS.md requires is the same assertion.
     */
    public function test_the_dashboard_shows_the_record_unit_and_storage_figures_separately(): void
    {
        $this->record();

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $response->assertSee('Record Capacity');
        $response->assertSee('Storage');
        $response->assertSee('Files Stored');

        $usage = $response->viewData('usage');

        // A filed record spends one Record Unit, and its bytes are counted
        // separately against the storage ceiling.
        $this->assertSame(1, $usage['records']['used']);
        $this->assertGreaterThan(0, $usage['storage']['used']);
    }

    /**
     * With no ceiling on file the card reports the figure without a denominator,
     * rather than drawing a meter against a limit of zero.
     */
    public function test_the_dashboard_omits_the_denominator_when_there_is_no_limit(): void
    {
        $this->company->forceFill([
            'record_unit_allocation' => null,
            'storage_limit_bytes' => null,
        ])->save();

        $this->record();

        $this->get(route('records.dashboard'))
            ->assertOk()
            ->assertSee('No limit set');
    }

    public function test_the_dashboard_reports_the_allocation_when_one_is_set(): void
    {
        $this->company->forceFill(['record_unit_allocation' => 50])->save();

        $this->record();
        $this->record();

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        // The Repository Usage card states the ceiling as "used / allowed" rather
        // than the older "of N allocated" wording, and the remaining figure moved
        // into the usage read the card renders from.
        $usage = $response->viewData('usage');

        $this->assertSame(2, $usage['records']['used']);
        $this->assertSame(50, $usage['records']['limit']);
        $this->assertSame(4.0, $usage['records']['percent']);
    }

    /**
     * A company with no allocation on file is unlimited, not empty.
     *
     * The card must say so rather than drawing a meter against a limit of zero,
     * which would read as a company that has exhausted an allowance it never had.
     */
    public function test_the_dashboard_reports_an_unlimited_company_as_unlimited(): void
    {
        $this->company->forceFill([
            'record_unit_allocation' => null,
            'storage_limit_bytes' => null,
        ])->save();

        $this->record();

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $usage = $response->viewData('usage');

        $this->assertNull($usage['records']['limit']);
        $this->assertNull($usage['records']['percent']);
        $this->assertNull($usage['storage']['limit']);
        $response->assertDontSee('/ 50', false);
    }

    public function test_the_dashboard_shows_the_distribution(): void
    {
        $this->record(['classification' => '301']);
        $this->record(['classification' => '901']);

        $distribution = $this->get(route('records.dashboard'))->viewData('distribution');

        $this->assertCount(2, $distribution);
        $this->assertSame('301', $distribution[0]['code']);
        $this->assertSame('901', $distribution[1]['code']);
    }

    /**
     * The Top Record Indexes card groups the same register by index code.
     */
    public function test_the_dashboard_ranks_records_by_index(): void
    {
        $this->record(['classification' => '301']);
        $this->record(['classification' => '301']);
        $this->record(['classification' => '901']);

        $indexes = $this->get(route('records.dashboard'))->viewData('indexes');

        $this->assertNotEmpty($indexes);
        $this->assertSame(2, $indexes[0]['records']);
        // Every row is one index with its code and name, ready for the bar list.
        $this->assertArrayHasKey('code', $indexes[0]);
        $this->assertArrayHasKey('name', $indexes[0]);
    }

    /**
     * The trend chart plots real filings, and it always covers a full twelve
     * months so a quiet period reads as a flat stretch rather than being
     * squeezed out of the axis.
     */
    public function test_the_dashboard_charts_real_filings_per_month(): void
    {
        $this->record();
        $this->record();

        $activity = $this->get(route('records.dashboard'))->viewData('activity');

        $this->assertCount(12, $activity);

        // The two records filed now land in the current month, which is the last
        // entry because the series runs oldest first.
        $this->assertSame(2, $activity[11]['filed']);
        $this->assertSame(now()->format('Y-m'), $activity[11]['month']);
        $this->assertSame(now()->format('M'), $activity[11]['label']);
        $this->assertSame(0, $activity[0]['filed']);
    }

    public function test_the_dashboard_never_counts_another_companys_records(): void
    {
        $this->record();

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->record();

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('counts')['records']);
        $this->assertSame(1, $response->viewData('units')['consumed']);
        $this->assertCount(1, $response->viewData('distribution'));
        $this->assertCount(1, $response->viewData('indexes'));
        $this->assertSame(0, $response->viewData('links')['total']);
    }

    public function test_the_dashboard_has_an_empty_state_with_no_records(): void
    {
        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $response->assertSee('No records have been filed yet');
        // The trend card says why it is blank rather than drawing a flat line.
        $response->assertSee('No records have been filed yet, so there is no trend to draw.');
        $response->assertSee('No cross-module links yet.');
    }

    public function test_the_dashboard_subnav_item_links_to_the_dashboard(): void
    {
        $response = $this->get(route('records.dashboard'));

        $response->assertOk();
        $response->assertSee(route('records.dashboard'), false);
    }

    public function test_the_register_toolbar_exposes_the_new_filters(): void
    {
        $this->record();

        $response = $this->get(route('records.index'));

        $response->assertOk();
        $response->assertSee('name="ocr"', false);
        $response->assertSee('name="amount_min"', false);
        $response->assertSee('name="amount_max"', false);
        $response->assertSee('name="currency"', false);
    }

    /**
     * Asserts the rendered dropdown, not the lookup service behind it. A lookup
     * that silently returned nothing still renders a perfectly valid page — just
     * one whose filters cannot narrow anything — so checking the service left the
     * failure invisible until it was found by hand in the browser.
     */
    public function test_the_register_filters_offer_the_company_classifications(): void
    {
        $this->record();

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $start = strpos($content, '<select name="classification"');
        $this->assertNotFalse($start, 'The register toolbar has no classification filter.');

        $end = strpos($content, '</select>', $start);
        $this->assertNotFalse($end, 'The classification filter is not a closed select.');

        // One option is the blank prompt; a populated filter needs at least one more.
        $this->assertGreaterThan(
            1,
            substr_count(substr($content, $start, $end - $start), '<option'),
            'The classification filter is empty, so the register cannot be filtered.',
        );
    }

    public function test_the_register_filters_by_ocr_text(): void
    {
        $this->record(['title' => 'Alpha'], ocrText: 'Consideration of five hundred thousand.');
        $this->record(['title' => 'Beta'], ocrText: 'Nothing relevant.');

        $response = $this->get(route('records.index', ['ocr' => 'five hundred']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('records'));
        $this->assertSame('Alpha', $response->viewData('records')->first()->title);
    }

    public function test_the_register_filters_by_amount_and_currency(): void
    {
        $this->record(['title' => 'Small', 'amount' => '100.00', 'currency' => 'PHP']);
        $this->record(['title' => 'Large', 'amount' => '9000.00', 'currency' => 'USD']);

        $response = $this->get(route('records.index', [
            'amount_min' => '1000',
            'currency' => 'usd',
        ]));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('records'));
        $this->assertSame('Large', $response->viewData('records')->first()->title);
    }

    public function test_the_register_still_filters_by_the_original_parameters(): void
    {
        $this->record(['title' => 'Alpha', 'classification' => '301', 'source' => 'SEC']);
        $this->record(['title' => 'Beta', 'classification' => '901', 'source' => 'BIR']);

        $response = $this->get(route('records.index', ['classification' => '301']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('records'));
        $this->assertSame('Alpha', $response->viewData('records')->first()->title);
    }

    public function test_the_register_does_not_leak_another_companys_records(): void
    {
        $this->record(['title' => 'Mine']);

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->record(['title' => 'Theirs'], ocrText: 'Confidential wording.');

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.index', ['ocr' => 'Confidential wording']));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('records'));
        $response->assertDontSee('Theirs');
    }

    public function test_the_register_keeps_filter_values_in_the_form(): void
    {
        $this->record();

        $response = $this->get(route('records.index', ['ocr' => 'needle', 'currency' => 'php']));

        $response->assertOk();
        $response->assertSee('value="needle"', false);
        $response->assertSee('name="currency"', false);
    }

    public function test_the_csv_export_still_works_with_the_new_filters(): void
    {
        $this->record(['title' => 'Alpha', 'amount' => '5000.00']);
        $this->record(['title' => 'Beta', 'amount' => '100.00']);

        $response = $this->get(route('records.export', ['amount_min' => '1000']));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Alpha', $csv);
        $this->assertStringNotContainsString('Beta', $csv);
    }

    public function test_normalise_keeps_every_key_the_toolbar_sends(): void
    {
        $filters = RecordSearchService::normalise([
            'q' => 'Alpha',
            'ocr' => 'needle',
            'classification' => '301',
            'index' => '301-6',
            'source' => 'SEC',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'currency' => 'PHP',
            'amount_min' => '1',
            'amount_max' => '9999',
        ]);

        $this->assertSame('needle', $filters['ocr']);
        $this->assertSame('1', $filters['amount_min']);
        $this->assertSame('9999', $filters['amount_max']);
        $this->assertSame('PHP', $filters['currency']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = [], string $ocrText = ''): Record
    {
        $company = $this->current();
        $code = (string) ($attributes['classification'] ?? '301');

        $classification = $this->classifications[$company->id][$code] ??= $this->seedClassification($company, $code);
        $index = $this->indexes[$company->id][$code] ??= $this->seedIndex($company, $classification);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => (string) ($attributes['title'] ?? 'Untitled')],
        );

        $record = $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'source' => $attributes['source'] ?? null,
        ]);

        if ($ocrText !== '') {
            $record->files()->firstOrFail()->forceFill(['ocr_text' => $ocrText])->save();
        }

        if (isset($attributes['amount'])) {
            $record->forceFill([
                'amount' => $attributes['amount'],
                'currency' => $attributes['currency'] ?? 'PHP',
            ])->save();
        }

        return $record->refresh();
    }

    private function current(): Company
    {
        return $this->app->make(CompanyContext::class)->current();
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

    private function seedIndex(Company $company, RecordClassification $classification): RecordIndex
    {
        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'record_classification_id' => $classification->id,
            'code' => $classification->code.'-6',
            'name' => 'General Information Sheet',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        return $index;
    }
}
