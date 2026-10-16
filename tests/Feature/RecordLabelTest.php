<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\User;
use App\Support\Auth;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The printable Record Label, and the QR on it.
 *
 * The label carries a record's whole physical identity onto a folder, so the two
 * things that matter are that it renders the right record and that it is not a way
 * to see a record the reader may not see.
 *
 * The confidentiality cases are here because a label is a new surface for a record
 * that already has a confidentiality rule, and the last audit found three surfaces
 * that quietly missed it.
 */
class RecordLabelTest extends TestCase
{
    use FakeUploads;

    /**
     * See the budget test for why this is eight and not the preview's five.
     */
    private const LABEL_QUERY_BUDGET = 8;

    private Company $company;

    private Record $record;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
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

        $this->record = $this->file('Board Resolution', $classification, $index);
    }

    public function test_the_label_renders_the_records_identifiers(): void
    {
        $response = $this->get(route('records.label', ['record' => $this->record->id]));

        $response->assertOk();
        $response->assertSee('Board Resolution');
        $response->assertSee((string) $this->record->document_reference);
        $response->assertSee((string) $this->record->record_number);
        $response->assertSee('301');
        $response->assertSee('301-13');
    }

    /**
     * The QR has to resolve to something the reader can actually open, which is the
     * preview route rather than a bare id.
     */
    public function test_the_label_encodes_the_preview_url_in_its_qr_code(): void
    {
        $spy = new class extends \App\Services\Records\RecordQrCode
        {
            /** @var list<string> */
            public array $encoded = [];

            public function svg(string $value, string $foreground = '#0f172a', string $background = '#ffffff'): string
            {
                $this->encoded[] = $value;

                return '<svg data-stub="1"></svg>';
            }
        };

        $this->app->instance(\App\Services\Records\RecordQrCode::class, $spy);

        $this->get(route('records.label', ['record' => $this->record->id]))->assertOk();

        $this->assertSame(
            [route('records.preview', ['record' => $this->record->id])],
            $spy->encoded,
            'The QR must resolve to the authorised preview route, not a bare id or a disk path.',
        );
    }

    /**
     * Inline SVG rather than an <img>: the label must not need a second route or a
     * data URI for one small graphic, and it has to survive being scaled by a
     * printer.
     */
    public function test_the_qr_is_inlined_as_svg(): void
    {
        $response = $this->get(route('records.label', ['record' => $this->record->id]));

        $response->assertOk();

        // Real markup, not escaped text: {{ }} would have rendered the tags as
        // visible characters and the label would have printed a wall of XML.
        $response->assertSee('<svg', false);
        $response->assertDontSee('&lt;svg', false);
        $response->assertDontSee('<img src="data:image', false);
    }

    /**
     * A confidential record's label is the physical copy of something the screen
     * refuses to show, so it inherits the same rule.
     */
    public function test_a_confidential_records_label_is_404_for_a_standard_user(): void
    {
        $this->record->forceFill(['is_confidential' => true])->save();

        $standard = User::factory()->create(['role' => UserRole::StandardUser]);

        $status = Auth::as(
            $standard,
            fn () => $this->get(route('records.label', ['record' => $this->record->id]))->getStatusCode(),
        );

        $this->assertSame(404, $status);
    }

    /**
     * And it has to stay reachable for a role that may see it, or the clause is
     * hiding records rather than protecting them.
     */
    public function test_a_confidential_records_label_is_still_reachable_for_an_auditor(): void
    {
        $this->record->forceFill(['is_confidential' => true])->save();

        $auditor = User::factory()->create(['role' => UserRole::Auditor]);

        Auth::as($auditor, function (): void {
            $this->get(route('records.label', ['record' => $this->record->id]))
                ->assertOk()
                ->assertSee('Board Resolution');
        });
    }

    /**
     * Another company's label is a 404 for the same reason as everywhere else: a
     * 403 would confirm the id exists.
     */
    public function test_another_companys_label_is_404_not_403(): void
    {
        $foreign = $this->recordIn($this->seedSecondCompany($this->company));

        $this->actingInCompany($this->company);

        $response = $this->get(route('records.label', ['record' => $foreign->id]));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_a_missing_record_is_also_404(): void
    {
        $this->get(route('records.label', ['record' => '01JZZZZZZZZZZZZZZZZZZZZZZ']))
            ->assertNotFound();
    }

    /**
     * Every role that can view a record can print its label: the label is a view of
     * the record, not a separate privilege. Asserted through the role matrix so a
     * future tightening of can:view is a deliberate change rather than a surprise.
     */
    public function test_the_label_is_reachable_for_every_role_that_may_view_a_record(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            Auth::as($user, function () use ($role): void {
                $this->get(route('records.label', ['record' => $this->record->id]))
                    ->assertOk();
            });
        }
    }

    /**
     * A single-record page, budgeted like one.
     *
     * Eight, rather than the preview's five, because the label resolves relations the
     * streaming preview never touches: the classification and index whose codes it
     * prints, and the company whose name heads it. Five was reachable only by
     * dropping information the label is required to carry, so the budget sits where
     * the page actually is rather than where its sibling does. Still well inside
     * AGENTS.md's ceiling of thirteen.
     */
    public function test_the_label_stays_within_its_query_budget(): void
    {
        $queries = 0;

        \Illuminate\Support\Facades\DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get(route('records.label', ['record' => $this->record->id]))->assertOk();

        $this->assertLessThanOrEqual(
            self::LABEL_QUERY_BUDGET,
            $queries,
            "The label issued {$queries} queries; the budget is ".self::LABEL_QUERY_BUDGET.'.',
        );
    }

    private function recordIn(Company $company): Record
    {
        $this->actingInCompany($company);

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

        return $this->file('Foreign Record', $classification, $index);
    }

    private function file(string $title, RecordClassification $classification, RecordIndex $index): Record
    {
        $item = $this->app->make(\App\Services\Records\RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => $title],
        );

        return $this->app->make(\App\Services\Records\RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'title' => $title,
        ]);
    }
}