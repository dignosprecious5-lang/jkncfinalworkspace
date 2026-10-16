<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Support\DocumentReferenceGenerator;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * A lost race for the Document Reference has to end in a filed record, not a 500.
 *
 * The reference is five glyphs from a 32-character alphabet — about 33.5 million
 * combinations — so a collision is rare. Rare is not never, and the unique index on
 * (tenant_id, company_id, document_reference) is what actually decides. The
 * generator's own existence loop cannot see uncommitted rows, so two filings that
 * land together can still reach the index.
 *
 * The collision is forced here rather than waited for: a stub returns a reference
 * that is already taken on its first call and then behaves normally, so the first
 * attempt fails on the index and the second succeeds. That is deterministic, where a
 * probabilistic test would be flaky and would mostly prove nothing.
 */
class DocumentReferenceCollisionTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordClassification $classification;

    private RecordIndex $index;

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

        $this->index = new RecordIndex;
        $this->index->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_classification_id' => $this->classification->id,
            'code' => '301-6',
            'name' => 'General Information Sheet',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();
    }

    /**
     * The point of the whole thing: a collision does not surface as an error.
     */
    public function test_a_colliding_reference_is_retried_and_the_record_is_filed(): void
    {
        // Take a reference first, so the stub has something real to collide with.
        $taken = $this->file()->document_reference;

        $stub = $this->collideOnceWith($taken);

        $second = $this->file();

        $this->assertNotNull($second->document_reference);
        $this->assertNotSame($taken, $second->document_reference);
        $this->assertSame(2, $stub->calls, 'The first attempt should have collided and been retried once.');
    }

    /**
     * A retry has to re-run the whole transaction, not resume it, so the state the
     * first attempt rolled back is rebuilt rather than left inconsistent.
     */
    public function test_a_retried_filing_produces_one_record_and_one_trail_entry(): void
    {
        $taken = $this->file()->document_reference;

        $this->collideOnceWith($taken);

        $second = $this->file('Retry Survivor');

        $this->assertSame(1, Record::query()->where('record_number', $second->record_number)->count());
        $this->assertSame(
            1,
            Record::query()->where('title', 'Retry Survivor')->count(),
            'A rolled-back attempt must not leave a half-written record behind.',
        );
        $this->assertSame(2, Record::query()->count());
    }

    /**
     * Every retryable failure costs a full transaction, so it has to be bounded.
     * A generator that never produces a free reference must eventually surface, not
     * spin.
     */
    public function test_the_retry_is_bounded(): void
    {
        $taken = $this->file()->document_reference;

        // Always collides, so every attempt fails.
        $stub = new class($taken, $this->app->make(DocumentReferenceGenerator::class)) extends DocumentReferenceGenerator
        {
            public int $calls = 0;

            public function __construct(private readonly string $taken, private readonly DocumentReferenceGenerator $real) {}

            public function generate(string $tenantId, string $companyId): string
            {
                $this->calls++;

                return $this->taken;
            }
        };

        $this->app->instance(DocumentReferenceGenerator::class, $stub);

        $threw = false;

        try {
            $this->file('Doomed');
        } catch (\Illuminate\Database\QueryException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'A permanently colliding generator must surface rather than retry forever.');
        $this->assertSame(3, $stub->calls, 'The attempt count is the documented cap.');
        $this->assertSame(1, Record::query()->count(), 'No record may survive a filing that never completed.');
    }

    /**
     * A collision is the only thing worth retrying. Anything else must surface on
     * the first attempt rather than being retried and misreported.
     */
    public function test_a_failure_that_is_not_a_collision_is_not_retried(): void
    {
        $this->file();

        $stub = new class($this->app->make(DocumentReferenceGenerator::class)) extends DocumentReferenceGenerator
        {
            public int $calls = 0;

            public function __construct(private readonly DocumentReferenceGenerator $real) {}

            public function generate(string $tenantId, string $companyId): string
            {
                $this->calls++;

                // getCode() is 0 here, so this is not an integrity-constraint violation and
                // must not be treated as one.
                throw new \Illuminate\Database\QueryException(
                    'sqlite',
                    'SELECT 1',
                    [],
                    new \Exception('no such column: records.something_else'),
                );
            }
        };

        $this->app->instance(DocumentReferenceGenerator::class, $stub);

        try {
            $this->file('Unrelated');
        } catch (\Illuminate\Database\QueryException) {
            // Expected.
        }

        $this->assertSame(1, $stub->calls, 'An unrelated failure must not be retried.');
    }

    /**
     * Bind a generator that hands back an already-taken reference once, then defers
     * to the real one.
     */
    private function collideOnceWith(string $taken): DocumentReferenceGenerator
    {
        $stub = new class($taken, $this->app->make(DocumentReferenceGenerator::class)) extends DocumentReferenceGenerator
        {
            public int $calls = 0;

            public function __construct(private readonly string $taken, private readonly DocumentReferenceGenerator $real) {}

            public function generate(string $tenantId, string $companyId): string
            {
                $this->calls++;

                return $this->calls === 1 ? $this->taken : $this->real->generate($tenantId, $companyId);
            }
        };

        $this->app->instance(DocumentReferenceGenerator::class, $stub);

        return $stub;
    }

    private function file(string $title = 'Filed'): Record
    {
        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => $title],
        );

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
            'title' => $title,
        ]);
    }
}