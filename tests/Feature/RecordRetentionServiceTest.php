<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use App\Services\Records\RecordRetentionService;
use App\Services\Records\RecordService;
use LogicException;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordRetentionServiceTest extends TestCase
{
    use FakeUploads;

    private RecordRetentionService $retention;

    private RecordService $records;

    private Company $company;

    private ?User $user = null;

    /** @var array<string, array<string, RecordClassification>> */
    private array $classifications = [];

    /** @var array<string, array<string, RecordIndex>> */
    private array $indexes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->retention = $this->app->make(RecordRetentionService::class);
        $this->records = $this->app->make(RecordService::class);
        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
        $this->user = User::factory()->create();
    }

    public function test_applying_a_hold_marks_the_record_and_records_who_set_it(): void
    {
        $record = $this->record();

        $held = $this->retention->applyLegalHold($record, 'Litigation pending over the 2026 share issue', $this->user);

        $this->assertTrue($held->isOnLegalHold());
        $this->assertSame('Litigation pending over the 2026 share issue', $held->legal_hold_reason);
        $this->assertNotNull($held->legal_hold_set_at);
        $this->assertSame((string) $this->user->id, (string) $held->legal_hold_set_by);
    }

    public function test_applying_a_hold_is_written_to_the_activity_log(): void
    {
        $record = $this->record();

        $this->retention->applyLegalHold($record, 'Regulatory request', $this->user);

        $entry = $record->activityLogs()->latest('id')->firstOrFail();

        $this->assertSame('record.legal_hold_applied', $entry->action);
        $this->assertStringContainsString('Regulatory request', (string) $entry->description);
        $this->assertSame('Regulatory request', $entry->properties['legal_hold_reason']);
    }

    public function test_releasing_a_hold_clears_every_hold_field(): void
    {
        $record = $this->record();

        $held = $this->retention->applyLegalHold($record, 'Pending review', $this->user);

        $released = $this->retention->releaseLegalHold($held, $this->user);

        $this->assertFalse($released->isOnLegalHold());
        $this->assertNull($released->legal_hold_reason);
        $this->assertNull($released->legal_hold_set_at);
        $this->assertNull($released->legal_hold_set_by);
    }

    public function test_releasing_a_hold_keeps_the_original_reason_in_the_log(): void
    {
        $record = $this->record();

        $held = $this->retention->applyLegalHold($record, 'Audit finding 14', $this->user);

        $this->retention->releaseLegalHold($held, $this->user);

        $entry = $record->activityLogs()->latest('id')->firstOrFail();

        $this->assertSame('record.legal_hold_released', $entry->action);
        $this->assertSame('Audit finding 14', $entry->properties['previous_legal_hold_reason']);
    }

    public function test_a_hold_needs_a_reason(): void
    {
        $record = $this->record();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('needs a reason');

        $this->retention->applyLegalHold($record, '   ', $this->user);
    }

    public function test_a_record_cannot_be_held_twice(): void
    {
        $record = $this->record();

        $held = $this->retention->applyLegalHold($record, 'First hold', $this->user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already under a legal hold');

        $this->retention->applyLegalHold($held, 'Second hold', $this->user);
    }

    public function test_a_record_that_is_not_held_cannot_be_released(): void
    {
        $record = $this->record();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('not under a legal hold');

        $this->retention->releaseLegalHold($record, $this->user);
    }

    public function test_a_held_record_cannot_have_its_metadata_edited(): void
    {
        $record = $this->record();

        $this->retention->applyLegalHold($record, 'Frozen for litigation', $this->user);

        try {
            $this->records->updateMetadata($record, ['title' => 'A New Title'], $this->user);
            $this->fail('Editing a held record should have been refused.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('legal hold', $e->getMessage());
        }

        $this->assertSame('Untitled', $record->refresh()->title);
    }

    public function test_a_held_record_cannot_be_archived(): void
    {
        $record = $this->record();

        $this->retention->applyLegalHold($record, 'Frozen for litigation', $this->user);

        try {
            $this->records->archive($record, $this->user, 'No longer needed');
            $this->fail('Archiving a held record should have been refused.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('legal hold', $e->getMessage());
        }

        $this->assertNotSame('archived', $record->refresh()->status->value);
    }

    public function test_a_refused_edit_leaves_no_metadata_log_entry(): void
    {
        $record = $this->record();

        $this->retention->applyLegalHold($record, 'Frozen', $this->user);

        $before = $record->activityLogs()->count();

        try {
            $this->records->updateMetadata($record, ['title' => 'Changed'], $this->user);
        } catch (LogicException) {
            // Expected.
        }

        $this->assertSame($before, $record->activityLogs()->count());
        $this->assertNull(
            $record->activityLogs()->where('action', 'record.metadata_updated')->first(),
        );
    }

    public function test_editing_works_again_once_the_hold_is_released(): void
    {
        $record = $this->record();

        $held = $this->retention->applyLegalHold($record, 'Temporary freeze', $this->user);
        $released = $this->retention->releaseLegalHold($held, $this->user);

        $updated = $this->records->updateMetadata($released, ['title' => 'Restored Title'], $this->user);

        $this->assertSame('Restored Title', $updated->title);
    }

    public function test_a_hold_does_not_change_the_document_reference_or_record_unit(): void
    {
        $record = $this->record();

        $reference = $record->document_reference;
        $units = $record->recordUnits();

        $this->retention->applyLegalHold($record, 'Frozen', $this->user);

        $record->refresh();

        $this->assertSame($reference, $record->document_reference);
        $this->assertSame($units, $record->recordUnits());
        $this->assertSame(1, $record->recordUnits());
    }

    public function test_expiry_is_calculated_from_the_classification_retention_months(): void
    {
        $record = $this->record('301', 60, ['document_date' => '2020-01-15']);

        $expiry = $this->retention->calculateRetentionExpiry($record);

        $this->assertNotNull($expiry);
        $this->assertSame('2025-01-15', $expiry->format('Y-m-d'));
        $this->assertSame('2025-01-15', $record->refresh()->retention_until->format('Y-m-d'));
    }

    public function test_expiry_uses_twelve_months_for_one_year(): void
    {
        $record = $this->record('301', 12, ['document_date' => '2024-03-10']);

        $this->assertSame('2025-03-10', $this->retention->calculateRetentionExpiry($record)?->format('Y-m-d'));
    }

    public function test_a_classification_without_retention_months_yields_no_expiry(): void
    {
        $record = $this->record('301', null, ['document_date' => '2020-01-15']);

        $this->assertNull($this->retention->calculateRetentionExpiry($record));
        $this->assertNull($record->refresh()->retention_until);
    }

    public function test_expiry_does_not_overwrite_an_existing_date_when_no_rule_applies(): void
    {
        $record = $this->record('301', null, ['document_date' => '2020-01-15']);
        $record->forceFill(['retention_until' => '2030-06-30'])->save();

        $this->assertNull($this->retention->calculateRetentionExpiry($record->refresh()));
        $this->assertSame('2030-06-30', $record->refresh()->retention_until->format('Y-m-d'));
    }

    public function test_expiry_falls_back_to_the_filing_date_when_the_document_has_no_date(): void
    {
        $record = $this->record('301', 12, ['document_date' => null]);

        $expiry = $this->retention->calculateRetentionExpiry($record);

        $this->assertNotNull($expiry);
        $this->assertSame(
            $record->filed_at->copy()->addMonthsNoOverflow(12)->format('Y-m-d'),
            $expiry->format('Y-m-d'),
        );
    }

    public function test_expiry_cannot_be_changed_while_the_record_is_held(): void
    {
        $record = $this->record('301', 12, ['document_date' => '2020-01-15']);

        $this->retention->applyLegalHold($record, 'Frozen for litigation', $this->user);

        try {
            $this->retention->calculateRetentionExpiry($record->fresh('classification'));
            $this->fail('Setting an expiry on a held record should have been refused.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('legal hold', $e->getMessage());
        }

        $this->assertNull($record->refresh()->retention_until);
    }

    public function test_expiry_works_again_once_the_hold_is_released(): void
    {
        $record = $this->record('301', 12, ['document_date' => '2020-01-15']);

        $held = $this->retention->applyLegalHold($record, 'Temporary freeze', $this->user);
        $released = $this->retention->releaseLegalHold($held, $this->user);

        $expiry = $this->retention->calculateRetentionExpiry($released->fresh('classification'));

        $this->assertSame('2021-01-15', $expiry?->format('Y-m-d'));
        $this->assertSame('2021-01-15', $released->refresh()->retention_until->format('Y-m-d'));
    }

    public function test_expiry_does_not_change_the_document_reference(): void
    {
        $record = $this->record('301', 12, ['document_date' => '2020-01-15']);
        $reference = $record->document_reference;

        $this->retention->calculateRetentionExpiry($record);

        $this->assertSame($reference, $record->refresh()->document_reference);
    }

    public function test_expired_lists_records_past_their_retention_date(): void
    {
        $past = $this->record('301', 12, ['document_date' => '2015-01-01']);
        $past->forceFill(['retention_until' => '2016-01-01'])->save();

        $future = $this->record('301', 12, ['document_date' => '2024-01-01']);
        $future->forceFill(['retention_until' => '2099-01-01'])->save();

        $expired = $this->retention->expired();

        $this->assertCount(1, $expired);
        $this->assertSame((string) $past->id, (string) $expired->first()->id);
    }

    public function test_status_reports_the_hold_and_retention_together(): void
    {
        $record = $this->record('301', 60, ['document_date' => '2020-01-15']);

        $this->retention->calculateRetentionExpiry($record);
        $this->retention->applyLegalHold($record->fresh(), 'Frozen', $this->user);

        $status = $this->retention->status($record->fresh('classification'));

        $this->assertTrue($status['is_legal_hold']);
        $this->assertSame('Frozen', $status['legal_hold_reason']);
        $this->assertSame(60, $status['retention_months']);
        $this->assertSame('2025-01-15', $status['retention_until']);
    }

    public function test_a_record_from_another_company_cannot_be_held(): void
    {
        $record = $this->record();

        $this->actingInCompany($this->seedSecondCompany($this->company));

        $this->expectException(LogicException::class);

        $this->retention->applyLegalHold($record, 'Should not apply', $this->user);
    }

    public function test_a_record_from_another_company_cannot_be_released(): void
    {
        $record = $this->record();

        $this->retention->applyLegalHold($record, 'Frozen', $this->user);

        $this->actingInCompany($this->seedSecondCompany($this->company));

        $this->expectException(LogicException::class);

        $this->retention->releaseLegalHold($record, $this->user);
    }

    public function test_another_companys_hold_does_not_block_this_company(): void
    {
        $mine = $this->record();
        $this->retention->applyLegalHold($mine, 'Frozen', $this->user);

        $second = $this->seedSecondCompany($this->company);
        $this->actingInCompany($second);
        $theirs = $this->record();

        $this->records->updateMetadata($theirs, ['title' => 'Editable'], $this->user);

        $this->assertSame('Editable', $theirs->refresh()->title);
    }

    private function record(string $classificationCode = '301', ?int $retentionMonths = null, array $attributes = []): Record
    {
        $company = $this->current();
        $code = $classificationCode;

        $classification = $this->classifications[$company->id][$code] ??= $this->seedClassification($company, $code, $retentionMonths);
        $index = $this->indexes[$company->id][$code] ??= $this->seedIndex($company, $classification);

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => (string) ($attributes['title'] ?? 'Untitled')],
        );

        $record = $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'document_date' => $attributes['document_date'] ?? null,
        ]);

        return $record->fresh(['classification']);
    }

    private function current(): Company
    {
        return $this->app->make(CompanyContext::class)->current();
    }

    private function seedClassification(Company $company, string $code, ?int $retentionMonths = null): RecordClassification
    {
        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $company->tenant_id,
            'company_id' => $company->id,
            'code' => $code,
            'name' => "Classification {$code}",
            'retention_months' => $retentionMonths,
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
