<?php

namespace Tests\Feature;

use App\Enums\FileRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Services\Records\RecordAttachmentService;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The master file has to be the one a preview opens, on both sides.
 *
 * RecordFile::scopeCurrentMasterFirst() is the server's rule. The drawers
 * re-apply it in JavaScript, because the payload hands out a per-file preview
 * URL and so lets the browser choose for itself. The two have to agree, and the
 * classify drawer was quietly not: its payload carried neither `fileRole` nor
 * `version`, so the browser's master-first and version terms both evaluated to
 * nothing and the sort collapsed to id order. It looked right only because every
 * intake file happens to be master at version 1 — an invariant, not the rule.
 *
 * These pin the payload contract the JavaScript sort reads and the ordering the
 * scope actually produces.
 */
class MasterFirstOrderingTest extends TestCase
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
     * The sort's first term. Without `fileRole` in the payload the browser cannot
     * evaluate it at all — every file ranked the same and the term did nothing.
     */
public function test_the_classify_drawer_payload_carries_the_file_role(): void
    {
        $this->intakeItem();

        $payload = $this->classifyPayload();

        $this->assertStringContainsString($this->jsonKey('fileRole'), $payload);
        $this->assertStringContainsString($this->jsonKey('fileRole').':'.$this->jsonKey(FileRole::Master->value), $payload);
    }

    /**
     * The sort's second term. Without `version` the newest-file tiebreak is lost
     * along with it.
     */
    public function test_the_classify_drawer_payload_carries_the_version(): void
    {
        $this->intakeItem();

        $payload = $this->classifyPayload();

        $this->assertStringContainsString($this->jsonKey('version'), $payload);
        $this->assertStringContainsString($this->jsonKey('version').':1', $payload);
    }

    /**
     * Both terms together, because restoring one without the other would still
     * leave the drawer's sort unable to match the server's.
     */
    public function test_the_classify_payload_carries_both_terms_the_javascript_sort_reads(): void
    {
        $this->intakeItem();

        $payload = $this->classifyPayload();

        $this->assertStringContainsString($this->jsonKey('fileRole'), $payload);
        $this->assertStringContainsString($this->jsonKey('version'), $payload);
    }

    /**
     * The scope: master first even when a supporting file sits at a higher
     * version. This is the case plain `ORDER BY file_role` gets wrong, because
     * 'supporting' sorts before 'master' as a string.
     */
    public function test_the_scope_puts_the_master_ahead_of_a_higher_versioned_supporting_file(): void
    {
        $record = $this->filedRecord();

        $attachments = $this->app->make(RecordAttachmentService::class);
        $attachments->addSupportingFile($record, $this->fakeImage('extra-one.jpg'));
        $attachments->addSupportingFile($record, $this->fakeImage('extra-two.jpg'));

        $roles = $record->files()->pluck('version', 'file_role')->all();

        $this->assertGreaterThan(
            (int) $roles[FileRole::Master->value],
            (int) $roles[FileRole::Supporting->value],
            'Precondition: the supporting file must outrank the master on version alone.',
        );

        $ordered = $record->files()->currentMasterFirst()->get();

        $this->assertSame(FileRole::Master->value, $ordered->first()->file_role->value);
    }

    /**
     * The master is first in what the record drawer is handed, not just in what
     * the scope would return — the eager load has to carry the ordering too.
     */
    public function test_the_register_eager_load_is_ordered_master_first(): void
    {
        $record = $this->filedRecord();

        $this->app->make(RecordAttachmentService::class)
            ->addSupportingFile($record, $this->fakeImage('extra.jpg'));

        $payload = $this->registerPayload();

        $firstFileRole = $this->firstFileRoleIn($payload);

        $this->assertSame(FileRole::Master->value, $firstFileRole);
    }

    /**
     * The page hands the drawer its rows as a JSON literal, HTML-escaped. Decoding
     * first means the assertions read the same as the payload does in the source.
     */
    private function classifyPayload(): string
    {
        return html_entity_decode(
            $this->get(route('records.unclassified'))->getContent(),
            ENT_QUOTES,
        );
    }

    private function registerPayload(): string
    {
        return html_entity_decode(
            $this->get(route('records.index'))->getContent(),
            ENT_QUOTES,
        );
    }

    private function firstFileRoleIn(string $payload): ?string
    {
        $key = $this->jsonKey('fileRole');
        $at = strpos($payload, $key);

        if ($at === false) {
            $this->fail('No fileRole key was present in the payload.');
        }

        $rest = substr($payload, $at + strlen($key));

if (! str_starts_with($rest, ':')) {
            return null;
        }

        $rest = substr($rest, 1);

        // The first fileRole in the payload belongs to the first file the drawer
        // receives, which is the one a preview opens.
        foreach ([FileRole::Master->value, FileRole::Supporting->value] as $role) {
            if (str_starts_with($rest, $this->jsonKey($role))) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Blade's @js() hands the browser `JSON.parse('…')` with every double quote
     * written as �, so a payload key is searchable but not in plain JSON form.
     */
    private function jsonKey(string $value): string
    {
        return '\u0022'.trim($value, '()').'\u0022';
    }

    private function intakeItem(): \App\Models\RecordIntakeItem
    {
        return $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'Queued Document'],
        );
    }

    private function filedRecord(): Record
    {
        $item = $this->intakeItem();

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
            'title' => 'Filed Document',
        ]);
    }
}