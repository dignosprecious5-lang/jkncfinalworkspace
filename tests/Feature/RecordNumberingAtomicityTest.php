<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The guarantee behind the Record ID counter: no two records share a number, and
 * the company row is what stands in front of that.
 *
 * The counter lives on `companies.record_next_sequence`, so filing has to take a
 * row lock on the company — not on the intake item. Locking the item does not
 * help: two *different* items filed at the same moment would each read the same
 * counter and take the same number, and neither lock would see the other.
 *
 * The suite runs against SQLite, which has no `FOR UPDATE` and which Laravel's
 * grammar therefore silently omits. The lock assertions below are real on MySQL
 * and skip here rather than passing on a database that cannot express the thing
 * being tested. Everything that does not depend on the lock — the counter
 * advancing, uniqueness, and the counter being per-company — is asserted
 * unconditionally.
 *
 * The walk-forward in RecordNumberGenerator and the unique index are the backstop
 * regardless; the lock is what keeps that backstop from being reached.
 */
class RecordNumberingAtomicityTest extends TestCase
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

        $this->seedSchemeFor($this->company);
    }

    /**
     * A real concurrency test needs two connections and a row-locking database.
     * Neither is available here, so what is asserted is that the lock is taken on
     * the table that owns the counter.
     */
    public function test_filing_locks_the_company_row_which_owns_the_counter(): void
    {
        $this->requireRowLocks();

        $tables = $this->lockedTablesDuring(fn () => $this->file('First'));

        $this->assertContains(
            'companies',
            $tables,
            'Filing must take a row lock on the company row, because that row owns the counter.',
        );
    }

    /**
     * Order is the whole point. Locking after reading the counter would serialise
     * the writes but not the reads, which is exactly the failure the comment in
     * RecordFilingService describes.
     */
    public function test_the_lock_is_taken_before_the_counter_is_read(): void
    {
        $this->requireRowLocks();

        $order = [];

        DB::listen(function ($query) use (&$order): void {
            $sql = strtolower((string) $query->sql);

            if (str_contains($sql, 'for update')) {
                $order[] = 'lock';
            }

            if (str_contains($sql, 'record_next_sequence')) {
                $order[] = 'counter';
            }
        });

        $this->file('First');

        $lockAt = array_search('lock', $order, true);
        $counterAt = array_search('counter', $order, true);

        $this->assertIsInt($lockAt, 'No row lock was taken during filing.');
        $this->assertIsInt($counterAt, 'The counter was never read.');

        $this->assertLessThan(
            $counterAt,
            $lockAt,
            'The counter was read before the lock was taken, so two simultaneous filings could still collide.',
        );
    }

    /**
     * What the lock protects. Two separate items, which is the case an
     * intake-item lock could never catch.
     */
    public function test_two_different_items_never_take_the_same_number(): void
    {
        $numbers = [$this->file('First')->record_number, $this->file('Second')->record_number];

        foreach ($numbers as $number) {
            $this->assertNotNull($number);
        }

        $this->assertNotSame($numbers[0], $numbers[1]);
    }

    public function test_the_counter_advances_past_every_number_it_hands_out(): void
    {
        $this->file('First');
        $this->file('Second');
        $this->file('Third');

        $issued = Record::query()->pluck('record_number')->all();

        $this->assertCount(3, $issued);
        $this->assertSame(3, count(array_unique($issued)));
        $this->assertSame(4, (int) $this->company->fresh()->record_next_sequence);
    }

    /**
     * The counter belongs to a company, so a second company starts at one and is
     * untouched by the first company's filings. Both companies legitimately issue
     * REC-2026-000001 — the number is scoped, not global.
     */
    public function test_each_company_numbers_from_its_own_counter(): void
    {
        // Each company's first filing is number 1, so the two agree by design:
        // the number is scoped to a company, not global.
        $mine = $this->file('Mine A');
        $this->file('Mine B');

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $this->seedSchemeFor($second);

        $theirs = $this->file('Theirs');

        $this->assertSame($mine->record_number, $theirs->record_number);
        $this->assertSame('REC-2026-000001', $theirs->record_number);
    }

    /**
     * And the counters move independently: A has issued two, B one, so A is two
     * ahead without B having been touched.
     */
    public function test_a_companys_filings_do_not_advance_another_companys_counter(): void
    {
        $this->file('Mine A');
        $this->file('Mine B');

        $second = $this->seedSecondCompany($this->company);

        $this->assertSame(3, (int) $this->company->fresh()->record_next_sequence);
        $this->assertSame(1, (int) $second->fresh()->record_next_sequence);

        $this->actingInCompany($second);
        $this->seedSchemeFor($second);
        $this->file('Theirs');

        $this->assertSame(3, (int) $this->company->fresh()->record_next_sequence);
        $this->assertSame(2, (int) $second->fresh()->record_next_sequence);
    }

    /**
     * The operator save has to take the same lock as filing, or the two can
     * interleave and rewind the counter. Same caveat as above: unobservable on
     * SQLite.
     */
    public function test_the_numbering_save_locks_the_same_row_filing_does(): void
    {
        $this->requireRowLocks();

        $admin = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::SystemAdmin]);

        $tables = $this->lockedTablesDuring(function () use ($admin): void {
            \App\Support\Auth::as($admin, fn () => $this->put(route('records.numbering.update'), [
                'record_prefix' => 'LOCK',
                'record_sequence_digits' => 6,
                'record_next_sequence' => 1,
                'record_include_year' => 'yes',
            ])->assertRedirect());
        });

        $this->assertContains(
            'companies',
            $tables,
            'The numbering save must serialise against filing on the company row.',
        );
    }

    /**
     * @param  callable(): void  $during
     * @return list<string>
     */
    private function lockedTablesDuring(callable $during): array
    {
        $tables = [];

        DB::listen(function ($query) use (&$tables): void {
            if (! str_contains(strtolower((string) $query->sql), 'for update')) {
                return;
            }

            if (preg_match('/from [`"](\w+)[`"]/', (string) $query->sql, $m) === 1) {
                $tables[] = $m[1];
            }
        });

        $during();

        return $tables;
    }

    private function requireRowLocks(): void
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            $this->markTestSkipped(
                "The [{$driver}] grammar omits FOR UPDATE, so a row lock cannot be observed on this connection. "
                .'Re-run against MySQL to assert the locking behaviour.',
            );
        }
    }

    private function file(string $title): Record
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

    private function seedSchemeFor(Company $company): void
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
            'is_active' => true,
        ])->save();

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

        $this->classification = $classification;
        $this->index = $index;
    }
}