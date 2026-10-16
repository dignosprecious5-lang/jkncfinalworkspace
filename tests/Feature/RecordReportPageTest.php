<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\ReportTrigger;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordReportRun;
use App\Models\User;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordLookups;
use App\Services\Records\RecordService;
use Illuminate\Support\Collection;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordReportPageTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordClassification $classification;

    private RecordIndex $index;

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

        [$this->classification, $this->index] = $this->schemeFor($this->company);
    }

    // -------------------------------------------------------------- landing

    public function test_the_reports_page_renders(): void
    {
        $response = $this->get(route('records.reports.index'));

        $response->assertOk();
        $response->assertViewIs('records.reports');
        $response->assertSee('Reports');
        $response->assertSee('Formal Records Management outputs for management, consultants and executives.');
    }

    public function test_it_offers_all_six_report_types(): void
    {
        $response = $this->get(route('records.reports.index'));

        $response->assertOk();

        foreach (ReportType::cases() as $type) {
            $response->assertSee($type->label());
            $response->assertSee($type->description());
        }
    }

    public function test_the_report_history_button_is_enabled(): void
    {
        $content = $this->get(route('records.reports.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('Report History', $content);

        // Matched on the attribute, not the substring: every button's class list
        // carries `disabled:`, so a bare `disabled` also matches the styling for
        // a button that is perfectly usable.
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*\sdisabled(?:\s|=|>)/s',
            $content,
            'The Report History control is still disabled.',
        );
    }

    public function test_the_builder_is_hidden_until_a_report_is_chosen(): void
    {
        $this->get(route('records.reports.index'))
            ->assertOk()
            ->assertSee('Choose a report above to configure its filters and preview the output.');
    }

    /**
     * The builder posts its preview fragment with fetch, not a form post, so it
     * reads a token out of the DOM. This page carries no form of its own, so
     * without a meta tag there is no token at all and every preview request comes
     * back expired — which PHP tests do not catch, because the test client does
     * not enforce CSRF the way a real request does.
     */
    public function test_the_page_exposes_a_csrf_token_for_its_preview_request(): void
    {
        $content = $this->get(route('records.reports.index'))->assertOk()->getContent() ?? '';

        $matched = preg_match('/name="csrf-token"\s+content="([^"]+)"/', $content, $m);

        $this->assertSame(1, $matched, 'The page exposes no CSRF token for its preview request.');
        $this->assertNotSame('', $m[1], 'The CSRF token is rendered empty.');
    }

    // ------------------------------------------------------------- preview

    public function test_generating_a_preview_returns_the_report_fragment(): void
    {
        $this->record('Board Minutes');

        $response = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ]);

        $response->assertOk();
        $response->assertViewIs('records.partials.report-preview');
        $response->assertSee('Records Included');
        $response->assertSee('Records by Classification');
        $response->assertSee('Management Summary');
        $response->assertSee('Download CSV');
    }

    /**
     * The builder already names the report on its cover directly above the
     * fragment, so the fragment names it once as the muted card label and no
     * more. This used to fail open: the fragment carried its own heading, the
     * label was also present, and assertSee passed on the label while the
     * duplicate heading went unnoticed. Counting pins the actual behaviour.
     */
    public function test_the_preview_names_the_report_once_and_does_not_repeat_the_cover_heading(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        $this->assertSame(
            1,
            substr_count($content, 'Records Inventory Report'),
            'The report title is rendered more than once, or not at all, in the preview fragment.',
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<h[23][^>]*>\s*Records Inventory Report\s*</',
            $content,
            'The preview fragment still carries its own heading, duplicating the cover above it.',
        );

        // The card label that remains is the muted one on "Records by
        // Classification"; a pill beside it is what the reference shows.
        $this->assertMatchesRegularExpression(
            '/<span[^>]*>\s*Records Inventory Report\s*</',
            $content,
            'The remaining title is not the muted card label.',
        );
    }

    public function test_the_preview_counts_the_records_it_covers(): void
    {
        $this->record('One');
        $this->record('Two');

        $preview = $this->preview();

        $this->assertSame(2, $preview['records']);
        $this->assertSame(1, $preview['classifications']);
        $this->assertSame('All', $preview['year']);
    }

    public function test_it_narrows_by_classification(): void
    {
        $this->record('Mine');

        $other = $this->otherClassification('401', 'BIR & Tax');
        $otherIndex = $this->index($other, '401-30', 'Form 2307');
        $this->recordUnder($other, $otherIndex, 'Theirs');

        $this->assertSame(1, $this->preview(['classification' => '301'])['records']);
        $this->assertSame(1, $this->preview(['classification' => '401'])['records']);
        $this->assertSame(2, $this->preview()['records']);
    }

    public function test_it_narrows_by_index(): void
    {
        $this->record('Mine');

        $other = $this->otherClassification('401', 'BIR & Tax');
        $otherIndex = $this->index($other, '401-30', 'Form 2307');
        $this->recordUnder($other, $otherIndex, 'Theirs');

        $this->assertSame(1, $this->preview(['index' => '301-6'])['records']);
        $this->assertSame(1, $this->preview(['index' => '401-30'])['records']);
    }

    public function test_it_narrows_by_source(): void
    {
        $this->record('Mine', 'BIR');
        $this->record('Theirs', 'SEC');

        $this->assertSame(1, $this->preview(['source' => 'BIR'])['records']);
        $this->assertSame(2, $this->preview()['records']);
    }

    /**
     * There is no reporting_year column, so the filter reads the year of the
     * document date.
     */
    public function test_it_narrows_by_document_year(): void
    {
        $this->record('Old', documentDate: '2024-03-01');
        $this->record('New', documentDate: '2026-03-01');

        $this->assertSame(1, $this->preview(['year' => '2024'])['records']);
        $this->assertSame(1, $this->preview(['year' => '2026'])['records']);
        $this->assertSame('2024', $this->preview(['year' => '2024'])['year']);
    }

    /**
     * A blank filter means "all", not "match nothing".
     */
    public function test_blank_filters_do_not_empty_the_report(): void
    {
        $this->record('One');

        $this->assertSame(1, $this->preview([
            'classification' => '',
            'index' => '',
            'source' => '',
            'year' => '',
        ])['records']);
    }

    /**
     * Archived records stay in the register with their own status, so a
     * management report reads the active set.
     */
    public function test_archived_records_are_excluded(): void
    {
        $active = $this->record('Active');
        $archived = $this->record('Archived');
        $this->app->make(RecordService::class)->archive($archived, null, 'Superseded');

        $this->assertSame(RecordStatus::Archived, $archived->refresh()->status);
        $this->assertSame(1, $this->preview()['records']);

        $csv = $this->csv();
        $this->assertStringContainsString('Active', $csv);
        $this->assertStringNotContainsString('Archived', $csv);
    }

    /**
     * The mock counts distinct sources with a Set that treats a blank as one, so
     * a record with no source would report a phantom source.
     */
    public function test_a_record_without_a_source_is_not_counted_as_a_source(): void
    {
        $this->record('WithSource', 'BIR');
        $this->record('NoSource');
        $this->record('AlsoNoSource');

        $this->assertSame(3, $this->preview()['records']);
        $this->assertSame(1, $this->preview()['sources']);
    }

    public function test_the_distribution_is_grouped_and_ordered_by_size(): void
    {
        $this->record('Mine one');
        $this->record('Mine two');

        $other = $this->otherClassification('401', 'BIR & Tax');
        $otherIndex = $this->index($other, '401-30', 'Form 2307');
        $this->recordUnder($other, $otherIndex, 'Theirs');

        $distribution = $this->preview()['distribution'];

        $this->assertCount(2, $distribution);
        $this->assertSame('301', $distribution->first()['code']);
        $this->assertSame(2, $distribution->first()['count']);
        $this->assertSame('401', $distribution->last()['code']);
        $this->assertSame(1, $distribution->last()['count']);
    }

    public function test_an_empty_report_says_so(): void
    {
        $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
            'source' => 'No Such Source',
        ])->assertOk()->assertSee('No records match the selected filters.');
    }

    // -------------------------------------------------------- reference form

    /**
     * The cover is tinted rather than flat, because it is the only thing naming
     * which of the six reports is being configured, and it sits directly above
     * filters that apply to all six.
     */
    public function test_the_builder_cover_is_tinted_and_carries_a_full_size_title(): void
    {
        $content = $this->get(route('records.reports.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('Configure filters, preview, then present or download.', $content);

        $this->assertMatchesRegularExpression(
            '/<div[^>]*bg-gradient-to-br[^>]*from-\[#eef4ff\][^>]*>\s*<h2[^>]*text-\[20px\][^>]*>/',
            $content,
            'The builder cover is not the tinted, full-size heading of the reference.',
        );
    }

    /**
     * The reference lays its filters out two-up. A single row of four squeezes
     * long Classification codes and makes the four filters read as one control.
     */
    public function test_the_filters_are_laid_out_two_up(): void
    {
        $content = $this->get(route('records.reports.index'))->assertOk()->getContent() ?? '';

        $this->assertMatchesRegularExpression(
            '/<div[^>]*class="grid grid-cols-2[^"]*"[^>]*>\s*<div[^>]*>\s*<label[^>]*>\s*Classification/s',
            $content,
            'The filter grid is not two columns.',
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]*class="grid grid-cols-4[^"]*"[^>]*>\s*<div[^>]*>\s*<label[^>]*>\s*Classification/s',
            $content,
            'The filters are still laid out four-up.',
        );
    }

    public function test_the_generate_button_is_left_aligned(): void
    {
        $content = $this->get(route('records.reports.index'))->assertOk()->getContent() ?? '';

        $this->assertMatchesRegularExpression(
            '/<div[^>]*>\s*<button[^>]*>\s*<span[^>]*>\s*Generate Preview/s',
            $content,
            'The Generate Preview row is not laid out with the button first.',
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]*justify-end[^>]*>\s*<button[^>]*>\s*<span[^>]*>\s*Generate Preview/s',
            $content,
            'Generate Preview is still pushed to the far edge.',
        );
    }

    public function test_the_headline_figures_use_the_large_kpi_tile(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        // Not .integration-card: that is the small dashboard tile, where the
        // label is uppercase and the figure reads as a line of text.
        $this->assertStringNotContainsString('integration-card', $content);

        foreach (['Records Included', 'Classifications', 'Sources', 'Reporting Year'] as $label) {
            $this->assertMatchesRegularExpression(
                '/<div class="kpi">\s*<div class="label">'.preg_quote($label, '/').'<\/div>\s*<div class="value">/s',
                $content,
                "The [{$label}] figure is not rendered as a kpi tile.",
            );
        }
    }

    /**
     * The three output controls are three ways to take the same report onward,
     * not a primary action with two lesser ones beside it, so none of them is
     * privileged over the others.
     */
    public function test_the_three_output_controls_carry_equal_weight(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        $this->assertSame(
            3,
            preg_match_all('/<button(?![^>]*type="submit")[^>]*>\s*(?:Present|Print \/ Save PDF|Download CSV)/s', $content),
            'The preview does not render exactly three output controls.',
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*bg-ordo-blue[^>]*>\s*(?:Present|Print \/ Save PDF|Download CSV)/s',
            $content,
            'One of the output controls is styled as the primary action.',
        );
    }

    /**
     * This fragment is injected with x-html, so its bindings are only structural
     * until a browser runs them. PHP cannot prove they work; it can at least
     * prove they are still there.
     *
     * Download is a handler rather than an href because it has to record the
     * report before the browser starts fetching it: an anchor would begin the
     * download and the write would race it.
     */
    public function test_the_injected_fragment_keeps_its_bindings(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('x-model="comments"', $content, 'The observations box lost its binding.');
        $this->assertStringContainsString('x-on:click="present()"', $content, 'Present lost its handler.');
        $this->assertStringContainsString('x-on:click="download()"', $content, 'Download CSV lost its handler.');
        $this->assertStringContainsString('min-h-[100px]', $content, 'The observations box lost its reference height.');
    }

    /**
     * A soft-blue bar on a light track is all but invisible, and it is still
     * perfectly valid markup: the chart passed every structural test here while
     * painting nothing at all. This pins the fill to the solid blue.
     */
    public function test_the_bars_are_painted_in_the_solid_blue_not_the_pale_tint(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        $this->assertStringNotContainsString(
            'bg-ordo-blue-soft',
            $content,
            'The chart is back to the near-white tint, which is invisible against its track.',
        );

        $this->assertMatchesRegularExpression(
            '/class="block h-full rounded-full bg-ordo-blue"/',
            $content,
            'The bar fill is no longer the solid blue.',
        );
    }

    /**
     * The code stays on the record and in the CSV; prefixing every chart label
     * with it only made the labels longer.
     */
    public function test_the_chart_labels_are_the_classification_name_without_the_code(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        $this->assertStringContainsString(
            'Entity &amp; Ownership',
            $content,
            'The chart label is not the classification name.',
        );

        $this->assertStringNotContainsString(
            "{$this->classification->code} \xE2\x80\x94",
            $content,
            'The chart label is still prefixed with the classification code.',
        );
    }

    public function test_the_summary_states_what_the_reference_states_and_no_more(): void
    {
        $this->record('Board Minutes');

        $content = $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
        ])->assertOk()->getContent() ?? '';

        $this->assertStringContainsString(
            'Use the output controls below to present or export the current report.',
            $content,
        );

        $this->assertStringNotContainsString(
            'Archived records are excluded',
            $content,
            'The archived-records note is back.',
        );
    }

    public function test_it_refuses_a_classification_from_another_scheme(): void
    {
        $this->post(route('records.reports.generate'), [
            'type' => ReportType::Inventory->value,
            'classification' => 'does-not-exist',
        ])->assertSessionHasErrors('classification');
    }

    public function test_it_refuses_an_unknown_report_type(): void
    {
        $this->post(route('records.reports.generate'), ['type' => 'nonsense'])
            ->assertSessionHasErrors('type');
    }

    // -------------------------------------------------------------- exports

    public function test_the_csv_export_streams_the_filtered_records(): void
    {
        $this->record('Mine', 'BIR');
        $this->record('Theirs', 'SEC');

        $response = $this->get(route('records.reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'ordo-records-inventory-report.csv',
            (string) $response->headers->get('Content-Disposition'),
        );

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Doc Ref', $csv);
        $this->assertStringContainsString('Mine', $csv);
        $this->assertStringContainsString('Theirs', $csv);
    }

    /**
     * The CSV and the preview come from one filter builder, so narrowing one
     * narrows the other.
     */
    public function test_the_csv_export_honours_the_same_filters_as_the_preview(): void
    {
        $this->record('Mine', 'BIR');
        $this->record('Theirs', 'SEC');

        $csv = $this->get(route('records.reports.export', ['source' => 'BIR']))->streamedContent();

        $this->assertStringContainsString('Mine', $csv);
        $this->assertStringNotContainsString('Theirs', $csv);
    }

    public function test_the_csv_export_is_named_for_the_chosen_report(): void
    {
        $response = $this->get(route('records.reports.export', [
            'type' => ReportType::Quality->value,
        ]));

        $this->assertStringContainsString(
            'ordo-records-quality-report.csv',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    // ------------------------------------------------------------ isolation

    public function test_a_report_does_not_cover_another_companys_records(): void
    {
        $this->record('Mine');

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        [$classification, $index] = $this->schemeFor($second);
        $this->recordUnder($classification, $index, 'Theirs');

        $this->actingInCompany($this->company);

        $this->assertSame(1, $this->preview()['records']);
        $this->assertStringNotContainsString('Theirs', $this->csv());
    }

    /**
     * A report is a formal output, so it must respect exactly the confidentiality
     * rule the register does. A Standard User who cannot open a record in the
     * register must not have it counted in a report either.
     */
    public function test_a_report_hides_confidential_records_from_a_standard_user(): void
    {
        $this->record('Ordinary');
        $this->record('Secret', confidential: true);

        $this->assertSame(2, $this->preview()['records'], 'The default role should see both.');

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $this->actingAs($standard);

        $this->assertSame(1, $this->preview()['records'], 'A confidential record leaked into a report.');
        $this->assertStringNotContainsString('Secret', $this->csv());
    }

    public function test_an_auditor_still_sees_confidential_records_in_a_report(): void
    {
        $this->record('Ordinary');
        $this->record('Secret', confidential: true);

        $this->actingAs(User::factory()->create(['role' => UserRole::Auditor]));

        $this->assertSame(2, $this->preview()['records']);
    }

    // -------------------------------------------------------------- history

    public function test_presenting_a_report_records_it_in_the_history(): void
    {
        $this->record('Board Minutes');

        $this->storeRun(['trigger' => ReportTrigger::Present->value]);

        $run = $this->onlyRun();

        $this->assertSame(ReportType::Inventory->value, $run->type);
        $this->assertSame(ReportTrigger::Present->value, $run->trigger);
        $this->assertSame(ReportTrigger::Present, $run->triggerType());
        $this->assertSame(1, $run->figures()['records']);
    }

    public function test_downloading_a_report_records_it_in_the_history(): void
    {
        $this->storeRun(['trigger' => ReportTrigger::Download->value]);

        $this->assertSame(ReportTrigger::Download, $this->onlyRun()->triggerType());
    }

    /**
     * The point of the history is that a record filed afterwards does not
     * retroactively change what was reported, so the figures are frozen onto the
     * entry rather than recounted when it is read back.
     */
    public function test_the_recorded_figures_do_not_move_when_the_register_changes(): void
    {
        $this->record('Board Minutes');
        $this->storeRun(['trigger' => ReportTrigger::Present->value]);

        $this->assertSame(1, $this->onlyRun()->figures()['records']);

        $this->record('A Later Resolution');

        $this->assertSame(
            1,
            $this->onlyRun()->figures()['records'],
            'The recorded figures were recounted instead of being read back as stored.',
        );
    }

    public function test_a_run_stores_the_filters_it_was_built_from(): void
    {
        $this->record('Board Minutes', source: 'SEC', documentDate: '2026-03-01');

        $this->storeRun([
            'trigger' => ReportTrigger::Present->value,
            'classification' => '301',
            'index' => '301-6',
            'source' => 'SEC',
            'year' => '2026',
        ]);

        $run = $this->onlyRun();

        $this->assertSame([
            'classification' => '301',
            'index' => '301-6',
            'source' => 'SEC',
            'year' => '2026',
        ], $run->filterSet());
    }

    /**
     * An unused filter reads as a deliberate "all" rather than as a gap, so the
     * history line never looks like it is hiding something.
     */
    public function test_the_filter_summary_names_filters_left_blank(): void
    {
        $this->storeRun([
            'trigger' => ReportTrigger::Present->value,
            'classification' => '301',
        ]);

        $summary = $this->onlyRun()->filterSummary();

        $this->assertStringContainsString('Classification 301', $summary);
        $this->assertStringContainsString('All index', $summary);
        $this->assertStringContainsString('All source', $summary);
        $this->assertStringContainsString('All year', $summary);
    }

    public function test_management_comments_are_saved_with_the_output(): void
    {
        $this->storeRun([
            'trigger' => ReportTrigger::Download->value,
            'comments' => "  Queried the 401 series with the auditor.\nFollow up on Q4.  ",
        ]);

        $this->assertSame(
            "Queried the 401 series with the auditor.\nFollow up on Q4.",
            $this->onlyRun()->comments,
            'The comments were not trimmed, or the internal newlines were lost.',
        );
    }

    /**
     * Blank is stored as absent rather than as an empty string, so an output
     * saved without comments is distinguishable from one saved with them.
     */
    public function test_blank_comments_are_stored_as_null(): void
    {
        $this->storeRun(['trigger' => ReportTrigger::Present->value, 'comments' => '   ']);

        $this->assertNull($this->onlyRun()->comments);
    }

    /**
     * A preview is not an output. Logging it would make the history a record of
     * clicks rather than a list of reports that were actually taken onward.
     */
    public function test_generating_a_preview_does_not_record_a_run(): void
    {
        $this->record('Board Minutes');

        $this->preview();

        $this->assertSame(0, RecordReportRun::query()->count());
    }

    public function test_each_output_is_recorded_separately(): void
    {
        $this->storeRun(['trigger' => ReportTrigger::Present->value]);
        $this->storeRun(['trigger' => ReportTrigger::Download->value]);

        $this->assertSame(2, RecordReportRun::query()->count());
    }

    // ----------------------------------------------------------- history read

    public function test_the_history_fragment_lists_this_companys_runs(): void
    {
        $this->record('Board Minutes');
        $this->storeRun(['trigger' => ReportTrigger::Present->value, 'comments' => 'For the directors.']);

        $response = $this->get(route('records.reports.history'));

        $response->assertOk();
        $response->assertViewIs('records.partials.report-history');
        $response->assertSee('Records Inventory Report');
        $response->assertSee('Presented');
        $response->assertSee('For the directors.');
        $response->assertSee('Re-download CSV');
    }

    public function test_the_history_reads_newest_first(): void
    {
        // Distinguished by Source, which is free text and so needs no second
        // Classification in the scheme to tell two runs apart.
        $this->storeRun(['trigger' => ReportTrigger::Present->value, 'source' => 'First']);
        $this->storeRun(['trigger' => ReportTrigger::Download->value, 'source' => 'Second']);

        $runs = $this->get(route('records.reports.history'))->viewData('runs');

        $this->assertSame('Second', $runs->first()->filterSet()['source']);
        $this->assertSame('First', $runs->last()->filterSet()['source']);
    }

    public function test_an_empty_history_says_so_rather_than_rendering_nothing(): void
    {
        $this->get(route('records.reports.history'))
            ->assertOk()
            ->assertSee('No reports have been presented or downloaded yet.');
    }

    /**
     * The entry's own re-download link has to carry the filters the run was
     * recorded with, or the reader downloads a different report from the one the
     * history describes.
     */
    public function test_the_history_entry_re_downloads_with_the_recorded_filters(): void
    {
        $this->record('Board Minutes', source: 'SEC');

        $run = $this->storeRun([
            'trigger' => ReportTrigger::Download->value,
            'classification' => '301',
            'index' => '301-6',
            'source' => 'SEC',
            'year' => '2026',
        ])->first();

        $response = $this->get(route('records.reports.history'));

        $expected = route('records.reports.export', [
            'type' => ReportType::Inventory->value,
            'classification' => '301',
            'index' => '301-6',
            'source' => 'SEC',
            'year' => '2026',
        ]);

        $this->assertStringContainsString(
            htmlspecialchars($expected, ENT_QUOTES),
            $response->getContent() ?? '',
            'The re-download link does not carry the recorded filters.',
        );
        $this->assertNotNull($run);
    }

    // ------------------------------------------------------- history scoping

    /**
     * The company context, not the payload, decides whose history this is.
     */
    public function test_the_history_never_shows_another_companys_runs(): void
    {
        $this->record('Mine');

        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);
        [$otherClassification, $otherIndex] = $this->schemeFor($other);
        $this->recordUnder($otherClassification, $otherIndex, 'Theirs');
        $this->storeRun(['trigger' => ReportTrigger::Present->value]);

        $this->assertSame(1, RecordReportRun::query()->count());

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.reports.history'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('runs'));
        $response->assertSee('No reports have been presented or downloaded yet.');
    }

    public function test_a_run_is_recorded_against_the_active_company_not_a_posted_one(): void
    {
        $other = $this->seedSecondCompany($this->company);

        $this->post(route('records.reports.runs.store'), [
            'type' => ReportType::Inventory->value,
            'trigger' => ReportTrigger::Present->value,
            // Belongs to nobody the server will accept: the scope is resolved,
            // not read from the request.
            'tenant_id' => $other->tenant_id,
            'company_id' => $other->id,
        ])->assertOk();

        $run = $this->onlyRun();

        $this->assertSame($this->company->id, $run->company_id);
        $this->assertNotSame($other->id, $run->company_id);
    }

    public function test_a_run_cannot_be_recorded_with_an_unknown_report_type(): void
    {
        $this->postJson(route('records.reports.runs.store'), [
            'type' => 'not-a-report',
            'trigger' => ReportTrigger::Present->value,
        ])->assertStatus(422);

        $this->assertSame(0, RecordReportRun::query()->count());
    }

    public function test_a_run_cannot_be_recorded_with_an_unknown_trigger(): void
    {
        $this->postJson(route('records.reports.runs.store'), [
            'type' => ReportType::Inventory->value,
            'trigger' => 'shredded',
        ])->assertStatus(422);

        $this->assertSame(0, RecordReportRun::query()->count());
    }

    /**
     * The filters decide the record set a history entry describes, so they are
     * validated against this company's scheme here exactly as they are for a
     * preview. An entry describing another company's Classification would be a
     * figure the reader could not have arrived at.
     */
    public function test_a_run_rejects_a_classification_from_another_scheme(): void
    {
        $this->postJson(route('records.reports.runs.store'), [
            'type' => ReportType::Inventory->value,
            'trigger' => ReportTrigger::Present->value,
            'classification' => '999',
        ])->assertStatus(422);

        $this->assertSame(0, RecordReportRun::query()->count());
    }

    /**
     * The history is a list of recent outputs, not an archive to be paged
     * through, so it stops at 50 rather than growing without bound.
     *
     * The rows are written straight to the model rather than through the
     * recording route: this is about what the read returns, and driving 55 posts
     * through a route rate limited to 30 a minute would be testing the limiter.
     */
    public function test_the_history_is_capped(): void
    {
        foreach (range(1, 55) as $ignored) {
            $this->makeRun();
        }

        $this->assertCount(50, $this->get(route('records.reports.history'))->viewData('runs'));
    }

    // --------------------------------------------------------------- subnav

    /**
     * The report builder's filters are validated against the company's own scheme, so
     * these also pin down that the cached lookups actually return rows. An empty
     * lookup would not fail any page: the dropdowns would simply render empty.
     */
    public function test_the_scheme_lookups_the_filters_are_validated_against_are_populated(): void
    {
        $other = $this->otherClassification('401', 'BIR & Tax');
        $this->index($other, '401-30', 'Form 2307');

        $lookups = $this->app->make(RecordLookups::class);

        $this->assertSame(['301', '401'], $lookups->classifications()->pluck('code')->all());
        $this->assertSame(['301-6', '401-30'], $lookups->indexes()->pluck('code')->all());
    }

    public function test_the_cached_lookups_survive_a_second_read(): void
    {
        $this->otherClassification('401', 'BIR & Tax');

        $lookups = $this->app->make(RecordLookups::class);

        // First read populates the cache, second is served from it.
        $this->assertCount(2, $lookups->classifications());
        $this->assertCount(2, $lookups->classifications());
    }

    /**
     * Every subnav item now resolves to a real route.
     */
    public function test_no_subnav_item_is_left_as_a_placeholder(): void
    {
        $content = $this->get(route('records.reports.index'))->assertOk()->getContent() ?? '';

        $this->assertSame(0, substr_count($content, 'href="#"'), 'A placeholder subnav item remains.');
    }

    public function test_the_reports_subnav_item_links_here(): void
    {
        $this->get(route('records.reports.index'))
            ->assertOk()
            ->assertSee(route('records.reports.index'), false);
    }

    // --------------------------------------------------------------- helpers

    /**
     * Run a preview and read back the metrics the controller computed.
     *
     * Driven through the route so the request validation and the view are the same
     * ones the builder hits, rather than calling the controller directly.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function preview(array $filters = []): array
    {
        $response = $this->post(route('records.reports.generate'), array_merge(
            ['type' => ReportType::Inventory->value],
            $filters,
        ));

        $response->assertOk();

        return $response->viewData('metrics');
    }

    private function csv(): string
    {
        return $this->get(route('records.reports.export'))->streamedContent();
    }

    /**
     * Record a report the way the builder does when the reader takes it onward.
     *
     * Driven through the route so the validation, the resolved company scope and
     * the recomputed figures are the ones the button produces, rather than a row
     * written directly with the answer already filled in.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function storeRun(array $overrides = []): Collection
    {
        $this->postJson(route('records.reports.runs.store'), array_merge([
            'type' => ReportType::Inventory->value,
            'trigger' => ReportTrigger::Present->value,
        ], $overrides))->assertOk();

        return RecordReportRun::query()->orderBy('id')->get();
    }

    /**
     * The single recorded run, for the tests that expect exactly one.
     */
    private function onlyRun(): RecordReportRun
    {
        $runs = RecordReportRun::query()->get();

        $this->assertCount(1, $runs, 'Expected exactly one recorded report run.');

        return $runs->first();
    }

    /**
     * A run written straight to the model, for the tests about reading a history
     * that would otherwise need dozens of rate-limited posts to build.
     */
    private function makeRun(array $overrides = []): RecordReportRun
    {
        return RecordReportRun::query()->create(array_merge([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'user_id' => null,
            'type' => ReportType::Inventory->value,
            'trigger' => ReportTrigger::Present->value,
            'filters' => ['classification' => '', 'index' => '', 'source' => '', 'year' => ''],
            'metrics' => ['records' => 0, 'classifications' => 0, 'sources' => 0, 'year' => 'All'],
            'comments' => null,
            'ip_address' => null,
        ], $overrides));
    }

    private function record(
        string $title,
        ?string $source = null,
        ?string $documentDate = null,
        bool $confidential = false,
    ): Record {
        return $this->recordUnder(
            $this->classification,
            $this->index,
            $title,
            $source,
            $documentDate,
            $confidential,
        );
    }

    private function recordUnder(
        RecordClassification $classification,
        RecordIndex $index,
        string $title,
        ?string $source = null,
        ?string $documentDate = null,
        bool $confidential = false,
    ): Record {
        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakePdf($title.'.pdf', 32)],
            array_filter(['title' => $title, 'source' => $source, 'document_date' => $documentDate]),
        );

        $record = $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'is_confidential' => $confidential,
        ]);

        return $record->refresh();
    }

    /**
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

    /**
     * A second Classification on the active company, for the distribution tests.
     */
    private function otherClassification(string $code, string $name): RecordClassification
    {
        return $this->classification($code, $name);
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
}
