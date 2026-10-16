<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\User;
use App\Policies\RecordPolicy;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Support\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * A confidential record must stay out of reach of a role that cannot see one,
 * on every surface that can reach it by id or by listing.
 *
 * The register, the picker and the dashboard already hid confidential records
 * through RecordSearchService::scoped(). The Audit Log did not: it queries the
 * trail directly, and the trail names its subject — which for a filed record is
 * the title and the Document Reference. That made the log, and both of its
 * exports, a way to read exactly what the register refuses to show.
 *
 * The second surface is every id-addressable route. ScopedRecordLookup is the one
 * place they all pass through, so the confidentiality clause lives there as well
 * as in RecordPolicy::view(). Both answer 404 rather than 403, for the same
 * reason the company scope does: a 403 would confirm the id exists.
 */
class ConfidentialRecordExposureTest extends TestCase
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

    // ---------------------------------------------------------------------
    // The Audit Log and its two exports.
    // ---------------------------------------------------------------------

    /**
     * The trail resolves each row's subject to the record's title, so one
     * entry about one confidential record is enough to leak it.
     */
    public function test_the_audit_log_hides_a_confidential_record_title_from_a_standard_user(): void
    {
        $secret = $this->confidentialRecord(['title' => 'Board Resolution']);

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $content = Auth::as($standard, fn () => $this->get(route('records.logs'))->getContent());

        $this->assertStringNotContainsString('Board Resolution', $content);
        $this->assertStringNotContainsString((string) $secret->document_reference, $content);
    }

    public function test_the_audit_log_csv_export_hides_a_confidential_record(): void
    {
        $secret = $this->confidentialRecord(['title' => 'Board Resolution']);

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $content = Auth::as(
            $standard,
            fn () => $this->get(route('records.logs.export', ['format' => 'csv']))->getContent(),
        );

        $this->assertStringNotContainsString('Board Resolution', $content);
        $this->assertStringNotContainsString((string) $secret->document_reference, $content);
    }

    /**
     * The JSON form is the compliance envelope, so it is the one most likely to
     * be attached to something and sent somewhere.
     */
    public function test_the_audit_log_json_export_hides_a_confidential_record(): void
    {
        $secret = $this->confidentialRecord(['title' => 'Board Resolution']);

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $content = Auth::as(
            $standard,
            fn () => $this->get(route('records.logs.export', ['format' => 'json']))->getContent(),
        );

        $this->assertStringNotContainsString('Board Resolution', $content);
        $this->assertStringNotContainsString((string) $secret->document_reference, $content);
    }

    /**
     * Hiding confidential rows must not turn the trail into a shorter, wrong
     * history. An ordinary record's entry still has to be there.
     */
    public function test_the_audit_log_still_shows_an_ordinary_record_to_a_standard_user(): void
    {
        $open = $this->record(['title' => 'Ordinary Record']);

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $content = Auth::as($standard, fn () => $this->get(route('records.logs'))->getContent());

        $this->assertStringContainsString('Ordinary Record', $content);
        $this->assertStringContainsString((string) $open->document_reference, $content);
    }

    /**
     * An entry with no record subject — an intake capture, a settings change, an
     * export — has nothing confidential to hide. Dropping those would quietly
     * rewrite the history the page exists to show.
     *
     * A numbering change is used here because it writes a row with no record
     * attached, and because it happens while a confidential record also exists.
     */
    public function test_the_audit_log_keeps_entries_that_are_not_about_a_record(): void
    {
        $this->confidentialRecord(['title' => 'Board Resolution']);

        $admin = User::factory()->create(['role' => UserRole::SystemAdmin]);

        Auth::as($admin, fn () => $this->put(route('records.numbering.update'), [
            'record_prefix' => 'REC',
            'record_sequence_digits' => 6,
            'record_next_sequence' => 1,
            'record_include_year' => 'yes',
        ])->assertRedirect());

        // Premise: the change really did leave a record-less row behind.
        $this->assertSame(
            1,
            RecordActivityLog::query()
                ->where('company_id', $this->company->id)
                ->whereNull('record_id')
                ->where('action', 'record.numbering_changed')
                ->count(),
        );

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $content = Auth::as($standard, fn () => $this->get(route('records.logs'))->getContent());

        $this->assertStringContainsString('Record numbering set to', $content);
        $this->assertStringNotContainsString('Board Resolution', $content);
    }

    /**
     * The filter has to be role-aware, not a blanket hide: an Auditor is exactly
     * the role that is supposed to be able to audit confidential work.
     */
    public function test_the_audit_log_still_shows_confidential_entries_to_an_auditor(): void
    {
        $secret = $this->confidentialRecord(['title' => 'Board Resolution']);

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);

        $content = Auth::as($auditor, fn () => $this->get(route('records.logs'))->getContent());

        $this->assertStringContainsString('Board Resolution', $content);
        $this->assertStringContainsString((string) $secret->document_reference, $content);
    }

    // ---------------------------------------------------------------------
    // The id-addressable routes, via ScopedRecordLookup and RecordPolicy.
    // ---------------------------------------------------------------------

    public function test_a_confidential_record_is_404_for_a_standard_user_on_every_record_route(): void
    {
        $secret = $this->confidentialRecord();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $responses = Auth::as($standard, fn () => [
            $this->get(route('records.preview', ['record' => $secret->id])),
            $this->get(route('records.links.picker', ['record' => $secret->id])),
        ]);

        foreach ($responses as $response) {
            // 404 rather than 403: a 403 confirms the record exists.
            $this->assertSame(404, $response->getStatusCode());
        }
    }

    /**
     * The same URLs have to keep working for a role that may see the record,
     * or the clause is hiding records rather than protecting them.
     */
    public function test_a_confidential_record_is_still_reachable_for_an_auditor(): void
    {
        $secret = $this->confidentialRecord();

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);

        Auth::as($auditor, function () use ($secret): void {
            $this->get(route('records.preview', ['record' => $secret->id]))->assertOk();
        });
    }

    public function test_the_policy_refuses_view_of_a_confidential_record_to_a_standard_user(): void
    {
        $secret = $this->confidentialRecord();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);
        $auditor = User::factory()->create(['role' => UserRole::Auditor]);

        $this->assertFalse(Gate::forUser($standard)->allows('view', $secret));
        $this->assertTrue(Gate::forUser($auditor)->allows('view', $secret));
    }

    /**
     * view() is what the eventual `can:view,record` middleware will call. It has
     * to be able to deny a confidential record, or wiring that middleware up later
     * would close nothing.
     */
    public function test_view_is_the_ability_that_carries_the_confidentiality_rule(): void
    {
        $secret = $this->confidentialRecord();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);
        $policy = $this->app->make(RecordPolicy::class);

        $this->assertFalse($policy->view($standard, $secret));
        $this->assertTrue($policy->view(User::factory()->create(['role' => UserRole::RecordsManager]), $secret));
    }

    /**
     * An ordinary record must stay visible to everyone who could see it before.
     */
    public function test_an_ordinary_record_is_unaffected(): void
    {
        $open = $this->record();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $this->assertTrue(Gate::forUser($standard)->allows('view', $open));
    }

    /**
     * Company scope is checked before confidentiality, and still answers 404.
     * Without the ordering clause a foreign confidential record would 404 for the
     * wrong reason and stop being distinguishable from a missing one.
     */
    public function test_a_foreign_confidential_record_is_still_404_not_403(): void
    {
        $foreign = $this->recordIn($this->seedSecondCompany($this->company));
        $foreign->forceFill(['is_confidential' => true])->save();

        $this->actingInCompany($this->company);

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $status = Auth::as(
            $standard,
            fn () => $this->get(route('records.preview', ['record' => $foreign->id]))->getStatusCode(),
        );

        $this->assertSame(404, $status);
    }

    // ---------------------------------------------------------------------
    // Helpers.
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function confidentialRecord(array $attributes = []): Record
    {
        $record = $this->record($attributes);

        $record->forceFill(['is_confidential' => true])->save();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(array $attributes = []): Record
    {
        return $this->recordIn($this->company, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordIn(Company $company, array $attributes = []): Record
    {
        $this->actingInCompany($company);

        $classification = $this->classifications[$company->id]['301'] ??= $this->seedClassification($company);
        $index = $this->indexes[$company->id]['301'] ??= $this->seedIndex($company, $classification);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => (string) ($attributes['title'] ?? 'Untitled')],
        );

        $record = $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ]);

        // The filing writes the audit entry the log reads, so the leak under test
        // has to exist before the assertion runs.
        $this->actingInCompany($company);

        return $record;
    }

    private function seedClassification(Company $company): RecordClassification
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
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
            'code' => '301-6',
            'name' => 'General Information Sheet',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        return $index;
    }
}