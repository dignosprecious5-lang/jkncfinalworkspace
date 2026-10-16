<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\CompanyContext;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordSearchService;
use App\Services\Records\RecordService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordSearchServiceTest extends TestCase
{
    use FakeUploads;

    private RecordSearchService $searches;

    private Company $company;

    /** @var array<string, array<string, RecordClassification>> */
    private array $classifications = [];

    /** @var array<string, array<string, RecordIndex>> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->searches = $this->app->make(RecordSearchService::class);
        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    /**
     * Run a filter set through the service the way a controller would.
     *
     * @param  array<string, string>  $filters
     */
    private function search(array $filters): Builder
    {
        return $this->searches->search($filters);
    }

    public function test_it_matches_a_document_reference(): void
    {
        $wanted = $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        $found = $this->search(['q' => $wanted->document_reference])->get();

        $this->assertCount(1, $found);
        $this->assertSame((string) $wanted->id, (string) $found->first()->id);
    }

    public function test_it_matches_a_partial_document_reference(): void
    {
        $wanted = $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        $found = $this->search(['q' => substr((string) $wanted->document_reference, 0, 3)])->get();

        $this->assertCount(1, $found);
    }

    public function test_it_matches_a_title(): void
    {
        $this->record(['title' => 'Amended Securities Registration']);
        $this->record(['title' => 'General Information Sheet']);

        $found = $this->search(['q' => 'Securities'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Amended Securities Registration', $found->first()->title);
    }

    public function test_it_matches_a_source(): void
    {
        $this->record(['title' => 'Alpha', 'source' => 'SEC']);
        $this->record(['title' => 'Beta', 'source' => 'BIR']);

        $this->assertCount(1, $this->search(['q' => 'BIR'])->get());
    }

    public function test_it_matches_an_external_reference_number(): void
    {
        $this->record(['title' => 'Alpha', 'external_reference_number' => 'SEC-9-1']);
        $this->record(['title' => 'Beta', 'external_reference_number' => 'BIR-3-2']);

        $this->assertCount(1, $this->search(['q' => 'SEC-9'])->get());
    }

    public function test_it_matches_a_record_id(): void
    {
        $wanted = $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        $this->assertCount(1, $this->search(['q' => (string) $wanted->id])->get());
    }

    public function test_it_matches_extracted_document_text(): void
    {
        $this->record(['title' => 'Alpha'], ocrText: 'The total consideration was five hundred thousand pesos.');
        $this->record(['title' => 'Beta'], ocrText: 'Unrelated wording entirely.');

        $found = $this->search(['ocr' => 'five hundred'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Alpha', $found->first()->title);
    }

    public function test_it_matches_document_text_across_supporting_files(): void
    {
        $record = $this->record(['title' => 'Alpha']);

        $record->files()->create([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_id' => $record->id,
            'file_role' => 'supporting',
            'disk' => 'local',
            'path' => 'supporting/scan.jpg',
            'original_name' => 'annex.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 10,
            'ocr_text' => 'Annex bearing the corporate seal.',
        ]);

        $this->record(['title' => 'Beta'], ocrText: 'Nothing of note.');

        $this->assertCount(1, $this->search(['ocr' => 'corporate seal'])->get());
    }

    public function test_it_filters_by_classification(): void
    {
        $this->record(['title' => 'Alpha', 'classification' => '301']);
        $this->record(['title' => 'Beta', 'classification' => '901']);

        $found = $this->search(['classification' => '301'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Alpha', $found->first()->title);
    }

    public function test_it_filters_by_index(): void
    {
        $this->record(['title' => 'Alpha', 'classification' => '301', 'index' => '301-6']);
        $this->record(['title' => 'Beta', 'classification' => '301', 'index' => '301-1']);

        $found = $this->search(['index' => '301-1'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Beta', $found->first()->title);
    }

    public function test_it_filters_by_source(): void
    {
        $this->record(['title' => 'Alpha', 'source' => 'SEC']);
        $this->record(['title' => 'Beta', 'source' => 'BIR']);

        $this->assertCount(1, $this->search(['source' => 'BIR'])->get());
    }

    public function test_it_filters_by_status(): void
    {
        $active = $this->record(['title' => 'Alpha']);
        $archived = $this->record(['title' => 'Beta']);
        $archived->forceFill(['status' => 'archived'])->save();

        $this->assertCount(1, $this->search(['status' => 'archived'])->get());
        $this->assertCount(1, $this->search(['status' => 'filed'])->get());
        $this->assertCount(2, $this->search([])->get());

        $this->assertSame((string) $active->id, (string) $this->search(['status' => 'filed'])->first()->id);
    }

    public function test_it_filters_by_a_date_range(): void
    {
        $this->record(['title' => 'Early', 'document_date' => '2026-01-15']);
        $this->record(['title' => 'Middle', 'document_date' => '2026-06-15']);
        $this->record(['title' => 'Late', 'document_date' => '2026-12-15']);

        $this->assertCount(1, $this->search(['from' => '2026-02-01', 'to' => '2026-10-01'])->get());
        $this->assertCount(2, $this->search(['from' => '2026-02-01'])->get());
        $this->assertCount(2, $this->search(['to' => '2026-10-01'])->get());
    }

    public function test_a_date_range_is_inclusive_on_both_ends(): void
    {
        $this->record(['title' => 'Edge', 'document_date' => '2026-06-15']);

        $this->assertCount(1, $this->search(['from' => '2026-06-15', 'to' => '2026-06-15'])->get());
    }

    public function test_it_filters_by_a_minimum_amount(): void
    {
        $this->record(['title' => 'Small', 'amount' => '100.00']);
        $this->record(['title' => 'Large', 'amount' => '9000.00']);

        $found = $this->search(['amount_min' => '1000'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Large', $found->first()->title);
    }

    public function test_it_filters_by_a_maximum_amount(): void
    {
        $this->record(['title' => 'Small', 'amount' => '100.00']);
        $this->record(['title' => 'Large', 'amount' => '9000.00']);

        $found = $this->search(['amount_max' => '1000'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Small', $found->first()->title);
    }

    public function test_it_filters_by_an_amount_range(): void
    {
        $this->record(['title' => 'Small', 'amount' => '100.00']);
        $this->record(['title' => 'Middle', 'amount' => '5000.00']);
        $this->record(['title' => 'Large', 'amount' => '9000.00']);

        $found = $this->search(['amount_min' => '1000', 'amount_max' => '6000'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Middle', $found->first()->title);
    }

    public function test_it_filters_by_currency(): void
    {
        $this->record(['title' => 'Peso', 'amount' => '100.00', 'currency' => 'PHP']);
        $this->record(['title' => 'Dollar', 'amount' => '100.00', 'currency' => 'USD']);

        $found = $this->search(['currency' => 'USD'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Dollar', $found->first()->title);
    }

    public function test_currency_matching_is_case_insensitive(): void
    {
        $this->record(['title' => 'Peso', 'currency' => 'PHP']);

        $this->assertCount(1, $this->search(['currency' => 'php'])->get());
        $this->assertCount(1, $this->search(['currency' => 'Php'])->get());
    }

    public function test_it_combines_filters(): void
    {
        $wanted = $this->record([
            'title' => 'Board Resolution',
            'classification' => '301',
            'index' => '301-13',
            'source' => 'SEC',
            'document_date' => '2026-06-15',
            'amount' => '5000.00',
            'currency' => 'PHP',
        ], ocrText: 'Unanimously approved by the board.');

        $this->record(['title' => 'Board Resolution', 'classification' => '301', 'index' => '301-6']);
        $this->record(['title' => 'Minutes', 'classification' => '301', 'index' => '301-13']);

        $found = $this->search([
            'q' => 'Board',
            'classification' => '301',
            'index' => '301-13',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'amount_min' => '1000',
            'currency' => 'PHP',
            'ocr' => 'unanimously',
        ])->get();

        $this->assertCount(1, $found);
        $this->assertSame((string) $wanted->id, (string) $found->first()->id);
    }

    public function test_an_empty_filter_set_returns_every_record(): void
    {
        $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        $this->assertCount(2, $this->search(RecordSearchService::defaults())->get());
    }

    public function test_blank_filter_values_are_ignored(): void
    {
        $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        $found = $this->search(RecordSearchService::normalise(['q' => '   ']))->get();

        $this->assertCount(2, $found);
    }

    public function test_a_filter_that_matches_nothing_returns_nothing(): void
    {
        $this->record(['title' => 'Alpha']);

        $this->assertCount(0, $this->search(['q' => 'no such document'])->get());
        $this->assertCount(0, $this->search(['ocr' => 'no such text'])->get());
    }

    public function test_it_never_returns_another_companys_records(): void
    {
        $this->record(['title' => 'Mine', 'classification' => '301']);

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->record(['title' => 'Theirs', 'classification' => '301']);

        $this->actingInCompany($this->company);

        $found = $this->search([])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Mine', $found->first()->title);
    }

    public function test_search_text_cannot_reach_another_company_through_ocr(): void
    {
        $this->record(['title' => 'Mine'], ocrText: 'Private body text.');

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->record(['title' => 'Theirs'], ocrText: 'Private body text.');

        $this->actingInCompany($this->company);

        $this->assertCount(1, $this->search(['ocr' => 'Private body text'])->get());
    }

    public function test_a_wildcard_search_term_cannot_widen_the_query(): void
    {
        $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        // A bare wildcard searches for that literal character instead of
        // matching every row in the company.
        $this->assertCount(0, $this->search(['q' => '%'])->get());
        $this->assertCount(0, $this->search(['q' => '_'])->get());
    }

    public function test_a_wildcard_inside_a_term_does_not_match_every_row(): void
    {
        $this->record(['title' => 'Alpha']);
        $this->record(['title' => 'Beta']);

        // Unescaped this would be the pattern '%%%lph%%%' and match both.
        $this->assertCount(0, $this->search(['q' => '%lph%'])->get());
    }

    public function test_a_real_percent_sign_in_a_title_is_still_searchable(): void
    {
        $this->record(['title' => 'Shareholding 100% Complete']);
        $this->record(['title' => 'Shareholding 50% Complete']);

        // Stripping removes the wildcard, so the rest of the term still matches.
        $found = $this->search(['q' => '100% Complete'])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Shareholding 100% Complete', $found->first()->title);
    }

    public function test_normalise_drops_unknown_filter_keys(): void
    {
        $filters = RecordSearchService::normalise([
            'q' => ' Alpha ',
            'tenant_id' => '01JZZZZZZZZZZZZZZZZZZZZZZ',
            'company_id' => '01JZZZZZZZZZZZZZZZZZZZZZY',
            'record_units' => '99',
        ]);

        $this->assertArrayNotHasKey('tenant_id', $filters);
        $this->assertArrayNotHasKey('company_id', $filters);
        $this->assertArrayNotHasKey('record_units', $filters);
        $this->assertSame('Alpha', $filters['q']);
    }

    public function test_supplied_scope_keys_cannot_override_the_active_company(): void
    {
        $this->record(['title' => 'Mine']);

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->record(['title' => 'Theirs']);

        $this->actingInCompany($this->company);

        // Even if a caller hands the service a hostile filter array, scope wins.
        $found = $this->search([
            'q' => '',
            'tenant_id' => (string) $second->tenant_id,
            'company_id' => (string) $second->id,
        ])->get();

        $this->assertCount(1, $found);
        $this->assertSame('Mine', $found->first()->title);
        $this->assertSame((string) $this->company->id, (string) $found->first()->company_id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = [], string $ocrText = ''): Record
    {
        $company = $this->current();
        $classificationCode = (string) ($attributes['classification'] ?? '301');
        $indexCode = (string) ($attributes['index'] ?? '301-6');

        $classification = $this->classifications[$company->id][$classificationCode]
            ??= $this->seedClassification($company, $classificationCode);

        // An index must belong to its own classification, so fall back to an
        // index under whichever classification is in play. Cache on the resolved
        // code, not the requested one, so two classifications cannot collide.
        $resolved = $this->indexCodeFor($indexCode, $classificationCode);

        $index = $this->indexes[$company->id][$resolved]
            ??= $this->seedIndex($company, $classification, $resolved);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => (string) ($attributes['title'] ?? 'Untitled')],
        );

        $record = $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'source' => $attributes['source'] ?? null,
            'external_reference_number' => $attributes['external_reference_number'] ?? null,
            'document_date' => $attributes['document_date'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);

        // Amount and currency are metadata confirmed after filing, not filing inputs.
        if (isset($attributes['amount'])) {
            $this->app->make(RecordService::class)->updateMetadata($record, [
                'amount' => $attributes['amount'],
                'currency' => $attributes['currency'] ?? 'PHP',
            ]);
        }

        if ($ocrText !== '') {
            $file = $record->files()->firstOrFail();
            $file->forceFill(['ocr_text' => $ocrText])->save();
        }

        return $record->refresh();
    }

    private function current(): Company
    {
        return $this->app->make(CompanyContext::class)->current();
    }

    /**
     * Keep the index code under the classification it was requested for.
     */
    private function indexCodeFor(string $requested, string $classificationCode): string
    {
        return str_starts_with($requested, $classificationCode.'-') ? $requested : $classificationCode.'-6';
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
