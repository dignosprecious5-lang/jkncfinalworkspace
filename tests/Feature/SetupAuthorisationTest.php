<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\User;
use App\Support\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Two Setup writes that were reachable by anyone who could open the modal.
 *
 * Neither had a guard on any layer: the FormRequest returned true, the controller
 * made no Gate call, and the route carried no `can:` middleware. So an Auditor or a
 * Standard User could reach the endpoint directly and do either of these —
 *
 *   - rewrite the record-numbering scheme, including resetting Next Sequence
 *   - file a real record through Integration → Simulate, which bills 1 Record Unit
 *
 * Both are reference data or billing events, and both are System Admin territory
 * for the first and records-write territory for the second. That is what the two
 * tests below pin down.
 */
class SetupAuthorisationTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function rolesThatMayNotManageTheScheme(): array
    {
        return [
            'records manager' => [UserRole::RecordsManager],
            'auditor' => [UserRole::Auditor],
            'standard user' => [UserRole::StandardUser],
        ];
    }

    #[DataProvider('rolesThatMayNotManageTheScheme')]
    public function test_the_numbering_scheme_cannot_be_rewritten_without_manage_scheme(UserRole $role): void
    {
        $before = $this->company->fresh();

        Auth::as(User::factory()->create(['role' => $role]), fn () => $this->put(
            route('records.numbering.update'),
            [
                'record_prefix' => 'HIJACKED',
                'record_sequence_digits' => 4,
                'record_next_sequence' => 9999,
                'record_include_year' => 'no',
            ],
        ));

        $after = $this->company->fresh();

        $this->assertSame($before->record_prefix, $after->record_prefix);
        $this->assertSame((int) $before->record_sequence_digits, (int) $after->record_sequence_digits);
        $this->assertSame((int) $before->record_next_sequence, (int) $after->record_next_sequence);
        $this->assertSame((bool) $before->record_include_year, (bool) $after->record_include_year);
    }

    /**
     * A refusal that also changed the row would mean the write happened and was
     * only reported as a failure, so this asserts on the row rather than the code.
     */
    public function test_a_refused_numbering_change_writes_no_audit_entry(): void
    {
        Auth::as(
            User::factory()->create(['role' => UserRole::StandardUser]),
            fn () => $this->put(route('records.numbering.update'), [
                'record_prefix' => 'HIJACKED',
                'record_sequence_digits' => 6,
                'record_next_sequence' => 1,
                'record_include_year' => 'yes',
            ]),
        );

        $this->assertDatabaseMissing('record_activity_logs', [
            'company_id' => $this->company->id,
            'action' => 'record.numbering_changed',
        ]);
    }

    public function test_a_system_admin_can_still_change_the_numbering_scheme(): void
    {
        Auth::as(User::factory()->create(['role' => UserRole::SystemAdmin]), function (): void {
            $this->put(route('records.numbering.update'), [
                'record_prefix' => 'ADM',
                'record_sequence_digits' => 4,
                'record_next_sequence' => 50,
                'record_include_year' => 'no',
            ])->assertRedirect();
        });

        $this->assertSame('ADM', $this->company->fresh()->record_prefix);
    }

    /**
     * The company is read from the context, so a crafted company_id cannot renumber
     * another tenant. Guarding the role without guarding the scope would still leave
     * this open to a System Admin of the wrong company.
     */
    public function test_numbering_cannot_be_changed_for_another_company(): void
    {
        $second = $this->seedSecondCompany($this->company);

        $theirs = $second->fresh()->record_prefix;

        Auth::as(User::factory()->create(['role' => UserRole::SystemAdmin]), function () use ($second): void {
            $this->put(route('records.numbering.update'), [
                'record_prefix' => 'THEIRS',
                'record_sequence_digits' => 6,
                'record_next_sequence' => 1,
                'record_include_year' => 'yes',
                'company_id' => (string) $second->id,
            ])->assertRedirect();
        });

        // The request succeeded, and it landed on the active company only.
        $this->assertSame('THEIRS', $this->company->fresh()->record_prefix);
        $this->assertSame($theirs, $second->fresh()->record_prefix);
    }

    // ---------------------------------------------------------------------
    // Integration → Simulate Generated Document.
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{UserRole}>
     */
    public static function rolesThatMayNotFileRecords(): array
    {
        return [
            'auditor' => [UserRole::Auditor],
            'standard user' => [UserRole::StandardUser],
        ];
    }

    #[DataProvider('rolesThatMayNotFileRecords')]
    public function test_simulating_a_generated_document_is_refused_without_records_write(UserRole $role): void
    {
        $this->scheme();

        $before = Record::query()->count();

        Auth::as(User::factory()->create(['role' => $role]), fn () => $this->post(
            route('records.integration.simulate'),
            ['mapping' => 0],
        ));

        $this->assertSame($before, Record::query()->count(), 'A refused simulate must not file a record.');
    }

    /**
     * Simulate is not a dry run: it builds an intake item and stores a file before
     * it files the record. A refusal has to land before any of that, so nothing is
     * left on disk or in the queue either.
     */
    #[DataProvider('rolesThatMayNotFileRecords')]
    public function test_a_refused_simulate_leaves_nothing_behind(UserRole $role): void
    {
        $this->scheme();

        Auth::as(User::factory()->create(['role' => $role]), fn () => $this->post(
            route('records.integration.simulate'),
            ['mapping' => 0],
        ));

        $this->assertDatabaseCount('record_files', 0);
        $this->assertDatabaseCount('record_intake_items', 0);
        $this->assertDatabaseMissing('record_activity_logs', [
            'company_id' => $this->company->id,
            'action' => 'record.filed',
        ]);
    }

    public function test_a_records_manager_can_still_simulate(): void
    {
        $this->scheme();

        Auth::as(User::factory()->create(['role' => UserRole::RecordsManager]), function (): void {
            $this->post(route('records.integration.simulate'), ['mapping' => 0])
                ->assertRedirect();
        });

        $this->assertSame(1, Record::query()->where('title', 'Board Resolution')->count());
    }

    // ---------------------------------------------------------------------

    /**
     * The simulate endpoint files against 301 / 301-13, so the scheme has to carry
     * them or the request is refused for a different reason and the test proves
     * nothing.
     */
    private function scheme(): void
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity & Governance',
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
    }
}