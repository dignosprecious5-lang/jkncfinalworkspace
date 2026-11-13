<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transmittal;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TransmittalV2Isolation;
use Tests\TestCase;

if (! defined('TRANSMITTAL_V2_BOOTSTRAPPED') || TRANSMITTAL_V2_BOOTSTRAPPED !== true) {
    throw new \RuntimeException('Classification tests require the dedicated V2 isolated launcher.');
}

/**
 * Prepared model acceptance cases; preparation does not mean these tests passed.
 * No migrations, lifecycle traits, production inputs or physical test files.
 * HTTP/controller acceptance, MySQL and OS confinement require separate evidence.
 */
final class TransmittalWorkflowVersionClassificationTest extends TestCase
{
    private bool $ownsMemoryConnection = false;

    protected function setUp(): void
    {
        parent::setUp();
        TransmittalV2Isolation::assertBootedGuards($this->app);

        // Discard only the dedicated, configuration-verified memory connection.
        DB::purge(TransmittalV2Isolation::CONNECTION);
        $connection = DB::connection(TransmittalV2Isolation::CONNECTION);
        self::assertSame(TransmittalV2Isolation::CONNECTION, $connection->getName());
        self::assertSame(':memory:', $connection->getDatabaseName());
        self::assertSame('sqlite', $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->ownsMemoryConnection = true;
        self::assertSame([], $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"));
        $this->createSyntheticSchema();
        TransmittalV2Isolation::assertBootedGuards($this->app);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->ownsMemoryConnection) {
                TransmittalV2Isolation::assertBootedGuards($this->app);
                DB::purge(TransmittalV2Isolation::CONNECTION);
                $this->ownsMemoryConnection = false;
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_factory_assigns_exact_v1_and_v2(): void
    {
        foreach (['v1', 'v2'] as $version) {
            $record = Transmittal::createForWorkflowVersion($version, $this->attributes($version));
            self::assertTrue($record->exists);
            self::assertSame($version, $record->fresh()->workflow_version);
        }
        self::assertSame(2, DB::table('transmittals')->count());
    }

    public function test_invalid_factory_versions_are_rejected_before_insert(): void
    {
        $attributes = $this->attributes('invalid');
        foreach (['', 'V1', 'V2', ' v1', 'v2 ', 'v3', 'null'] as $version) {
            $this->assertClassificationThrows(\InvalidArgumentException::class, fn () =>
                Transmittal::createForWorkflowVersion($version, $attributes));
        }
        self::assertSame(0, DB::table('transmittals')->count());
    }

    public function test_factory_rejects_embedded_override_even_when_empty_or_matching(): void
    {
        foreach ([null, '', 'v1', 'v2', 0, [], ['v2']] as $value) {
            $attributes = $this->attributes('embedded');
            $attributes['workflow_version'] = $value;
            $this->assertClassificationThrows(\InvalidArgumentException::class, fn () =>
                Transmittal::createForWorkflowVersion('v2', $attributes));
        }
        self::assertSame(0, DB::table('transmittals')->count());
    }

    public function test_version_is_not_fillable_and_all_mass_assignment_values_are_rejected(): void
    {
        self::assertNotContains('workflow_version', (new Transmittal)->getFillable());
        foreach ([null, '', 'v1', 'v2', 0, [], ['v1']] as $value) {
            $this->assertClassificationThrows(\InvalidArgumentException::class, fn () => new Transmittal(['workflow_version' => $value]));
            $this->assertClassificationThrows(\InvalidArgumentException::class, fn () => (new Transmittal)->fill(['workflow_version' => $value]));
            $this->assertClassificationThrows(\InvalidArgumentException::class, fn () => (new Transmittal)->forceFill(['workflow_version' => $value]));
        }
        self::assertSame(0, DB::table('transmittals')->count());
    }

    public function test_classification_is_present_before_the_initial_insert_and_save_occurs_once(): void
    {
        $this->withClonedModelDispatcher(function (): void {
            $saving = 0;
            $creating = [];
            Transmittal::saving(function (Transmittal $record) use (&$saving): void { $saving++; });
            Transmittal::creating(function (Transmittal $record) use (&$creating): void {
                $creating[] = $record->getAttributes()['workflow_version'] ?? null;
            });
            $record = Transmittal::createForWorkflowVersion('v2', $this->attributes('initial'));
            self::assertSame(1, $saving);
            self::assertSame(['v2'], $creating);
            self::assertSame('v2', DB::table('transmittals')->where('id', $record->id)->value('workflow_version'));
        });
    }

    public function test_saving_cancellation_is_reported_as_factory_failure(): void
    {
        $this->withClonedModelDispatcher(function (): void {
            $attempts = 0;
            $attributes = $this->attributes('cancelled');
            Transmittal::saving(function () use (&$attempts): bool { $attempts++; return false; });
            $this->assertClassificationThrows(\RuntimeException::class, fn () =>
                Transmittal::createForWorkflowVersion('v1', $attributes));
            self::assertSame(1, $attempts);
            self::assertSame(0, DB::table('transmittals')->count());
        });
    }

    public function test_insert_exception_is_not_returned_as_success(): void
    {
        Transmittal::createForWorkflowVersion('v1', $this->attributes('duplicate'));
        $attributes = $this->attributes('duplicate');
        $this->assertClassificationThrows(QueryException::class, fn () =>
            Transmittal::createForWorkflowVersion('v2', $attributes));
        self::assertSame(1, DB::table('transmittals')->count());
        self::assertSame('v1', DB::table('transmittals')->value('workflow_version'));
    }

    public function test_null_to_version_version_to_null_and_cross_version_changes_are_rejected(): void
    {
        $records = [
            $this->historicalRecord(),
            Transmittal::createForWorkflowVersion('v1', $this->attributes('immutable-v1')),
            Transmittal::createForWorkflowVersion('v2', $this->attributes('immutable-v2')),
        ];
        foreach ($records as $record) {
            $original = $record->fresh()->workflow_version;
            foreach ([null, 'v1', 'v2'] as $target) {
                if ($target === $original) continue;
                $candidate = $record->fresh();
                $candidate->setAttribute('workflow_version', $target);
                $this->assertClassificationThrows(\LogicException::class, fn () => $candidate->save());
                self::assertSame($original, $record->fresh()->workflow_version);
            }
        }
    }

    public function test_invalid_direct_version_values_are_rejected_on_save(): void
    {
        foreach (['', 'V1', 'v3', 1, []] as $value) {
            $record = $this->historicalRecord();
            $record->setAttribute('workflow_version', $value);
            $this->assertClassificationThrows(\InvalidArgumentException::class, fn () => $record->save());
            self::assertNull($record->fresh()->workflow_version);
        }
    }

    public function test_historical_null_and_same_classifications_survive_unrelated_updates(): void
    {
        foreach ([null, 'v1', 'v2'] as $index => $version) {
            $record = $version === null ? $this->historicalRecord()
                : Transmittal::createForWorkflowVersion($version, $this->attributes('unchanged-'.$index));
            $record->setAttribute('workflow_version', $version);
            $record->subject = 'Synthetic updated subject';
            self::assertTrue($record->save());
            self::assertSame($version, $record->fresh()->workflow_version);
            self::assertSame('Synthetic updated subject', $record->fresh()->subject);
        }
    }

    public function test_partial_models_allow_unrelated_updates_but_refuse_unverifiable_assignment(): void
    {
        $record = Transmittal::createForWorkflowVersion('v2', $this->attributes('partial'));
        $partial = Transmittal::query()->select(['id', 'subject'])->findOrFail($record->id);
        $partial->subject = 'Synthetic partial update';
        self::assertTrue($partial->save());
        self::assertSame('v2', $record->fresh()->workflow_version);
        $partial = Transmittal::query()->select(['id', 'subject'])->findOrFail($record->id);
        $partial->setAttribute('workflow_version', 'v2');
        $this->assertClassificationThrows(\LogicException::class, fn () => $partial->save());
        self::assertSame('v2', $record->fresh()->workflow_version);
    }

    public function test_existing_record_mass_assignment_rejects_even_the_same_version(): void
    {
        $record = Transmittal::createForWorkflowVersion('v1', $this->attributes('existing-fill'));
        $this->assertClassificationThrows(\InvalidArgumentException::class, fn () => $record->update(['workflow_version' => 'v1']));
        self::assertSame('v1', $record->fresh()->workflow_version);
    }

    public function test_factory_participates_in_the_callers_transaction(): void
    {
        $failure = new \RuntimeException('Synthetic caller failure.');
        try {
            DB::transaction(function () use ($failure): void {
                Transmittal::createForWorkflowVersion('v2', $this->attributes('rolled-back'));
                throw $failure;
            });
            self::fail('The synthetic transaction must fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
        self::assertSame(0, DB::table('transmittals')->count());
    }

    public function test_casts_and_existing_signatory_mutator_are_preserved(): void
    {
        $record = Transmittal::createForWorkflowVersion('v1', array_merge($this->attributes('casts'), [
            'transmittal_date' => '2026-11-11', 'action_email' => true,
            'delivery_actions' => ['email'], 'approved_snapshot' => ['synthetic' => true],
            'approved_by_name' => '  Synthetic Operations Manager  ',
        ]));
        $record = $record->fresh();
        self::assertSame('2026-11-11', $record->transmittal_date->format('Y-m-d'));
        self::assertTrue($record->action_email);
        self::assertSame(['email'], $record->delivery_actions);
        self::assertSame(['synthetic' => true], $record->approved_snapshot);
        self::assertSame('Synthetic Operations Manager', $record->approved_by_name);
        $record->approved_by_name = 'Different Synthetic Approver';
        $record->subject = 'Unrelated permitted update';
        $record->save();
        self::assertSame('Synthetic Operations Manager', $record->fresh()->approved_by_name);
        self::assertSame('v1', $record->fresh()->workflow_version);
    }

    public function test_legacy_and_v2_relationship_targets_and_ordering_are_preserved(): void
    {
        $record = Transmittal::createForWorkflowVersion('v2', $this->attributes('relations'));
        foreach ([9, 2] as $number) DB::table('transmittal_items')->insert(['transmittal_id' => $record->id, 'item_no' => $number]);
        foreach ([5, 1] as $position) DB::table('transmittal_workspace_items')->insert(['transmittal_id' => $record->id, 'position' => $position]);
        foreach (['recipients', 'documents', 'proofs', 'histories', 'acknowledgments'] as $relation) {
            $table = $record->$relation()->getRelated()->getTable();
            DB::table($table)->insert(['transmittal_id' => $record->id]);
            self::assertSame(1, $record->$relation()->count());
        }
        DB::table('transmittal_receipts')->insert(['transmittal_id' => $record->id]);
        DB::table('transmittal_attachments')->insert(['transmittal_id' => $record->id, 'created_at' => '2026-11-10 00:00:00']);
        DB::table('transmittal_attachments')->insert(['transmittal_id' => $record->id, 'created_at' => '2026-11-11 00:00:00']);
        DB::table('approval_authority_codes')->insert(['transmittal_id' => $record->id]);
        self::assertSame([2, 9], $record->items()->pluck('item_no')->all());
        self::assertSame([1, 5], $record->workspaceItems()->pluck('position')->all());
        self::assertNotNull($record->receipt()->first());
        self::assertSame(1, $record->receipts()->count());
        self::assertSame('2026-11-11 00:00:00', $record->attachments()->first()->getRawOriginal('created_at'));
        self::assertNotNull($record->authorityCode()->first());
        foreach (['preparer', 'approver', 'rejector'] as $relation) self::assertNotNull($record->$relation()->first());
    }

    private function attributes(string $reference): array
    {
        $userId = (int) DB::table('users')->insertGetId(['name' => 'Synthetic User']);
        return [
            'transmittal_no' => 'SYNTHETIC-'.$reference, 'subject' => 'Synthetic classification record',
            'mode' => 'SEND', 'office_name' => 'Synthetic Office', 'party_name' => 'Synthetic Party',
            'workflow_status' => 'Uploaded', 'approval_status' => 'Pending',
            'prepared_by' => $userId, 'approved_by' => $userId, 'rejected_by' => $userId,
        ];
    }

    private function historicalRecord(): Transmittal
    {
        // Deliberate synthetic fixture insertion, not a historical reconciliation API.
        $id = DB::table('transmittals')->insertGetId([
            'subject' => 'Synthetic historical NULL', 'mode' => 'SEND',
            'workflow_status' => 'Uploaded', 'approval_status' => 'Pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return Transmittal::findOrFail($id);
    }

    private function withClonedModelDispatcher(callable $operation): void
    {
        $original = Transmittal::getEventDispatcher();
        self::assertNotNull($original);

        // Register boot-time classification guards on the original dispatcher first.
        new Transmittal;
        self::assertSame($original, Transmittal::getEventDispatcher());
        self::assertTrue($original->hasListeners('eloquent.saving: '.Transmittal::class));

        Transmittal::setEventDispatcher(clone $original);
        try { $operation(); } finally { Transmittal::setEventDispatcher($original); }
    }

    private function assertClassificationThrows(string $expected, callable $operation): void
    {
        // Fixture preparation precedes the measured operation; reject-time writes do not.
        $parentsBefore = DB::table('transmittals')->count();
        $usersBefore = DB::table('users')->count();
        try { $operation(); } catch (\Throwable $exception) {
            self::assertInstanceOf($expected, $exception);
            self::assertSame($parentsBefore, DB::table('transmittals')->count(), 'Rejected operation changed the parent-record count.');
            self::assertSame($usersBefore, DB::table('users')->count(), 'Rejected operation changed the synthetic user-fixture count.');
            return;
        }
        self::fail('Expected exception: '.$expected);
    }

    private function createSyntheticSchema(): void
    {
        $schema = Schema::connection(TransmittalV2Isolation::CONNECTION);
        $schema->create('users', function (Blueprint $table): void { $table->id(); $table->string('name'); });
        $schema->create('transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('workflow_version', 2)->nullable();
            $table->string('transmittal_no')->nullable()->unique();
            foreach (['mode', 'party_name', 'office_name', 'delivery_type', 'by_person_who', 'registered_mail_provider', 'electronic_method', 'recipient_email', 'prepared_by_name', 'approved_by_name', 'approved_position', 'document_custodian', 'delivered_by', 'received_by', 'receiver_affiliation', 'workflow_status', 'approval_status', 'tracking_number', 'external_reference', 'subject', 'direction', 'origin_office', 'related_company', 'delivery_method', 'operations_manager', 'processing_company', 'confidentiality', 'status', 'approval_authority_code', 'cancelled_from_status'] as $column) $table->string($column)->nullable();
            foreach (['address', 'review_note', 'purpose', 'recipient_address', 'rejection_reason', 'cancellation_reason'] as $column) $table->text($column)->nullable();
            foreach (['submitted_by', 'approved_by', 'prepared_by', 'rejected_by', 'cancelled_by'] as $column) $table->foreignId($column)->nullable()->constrained('users')->restrictOnDelete();
            $table->date('transmittal_date')->nullable();
            foreach (['prepared_at', 'received_at', 'approved_at', 'rejected_at', 'cancelled_at'] as $column) $table->dateTime($column)->nullable();
            foreach (['action_delivery', 'action_pick_up', 'action_drop_off', 'action_email'] as $column) $table->boolean($column)->default(false);
            $table->json('approved_snapshot')->nullable();
            $table->json('delivery_actions')->nullable();
            $table->timestamps();
        });
        $schema->create('transmittal_items', function (Blueprint $table): void {
            $table->id(); $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('item_no'); $table->unsignedInteger('qty')->nullable(); $table->string('attachment_path')->nullable(); $table->timestamps();
        });
        $schema->create('transmittal_workspace_items', function (Blueprint $table): void {
            $table->id(); $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position'); $table->string('quantity', 40)->nullable(); $table->timestamps();
        });
        // Relationship smoke fixtures use metadata only; no files or live recipients.
        foreach (['transmittal_recipients', 'transmittal_documents', 'proof_of_transmissions', 'transmittal_histories', 'external_acknowledgments', 'transmittal_receipts', 'transmittal_attachments', 'approval_authority_codes'] as $name) {
            $schema->create($name, function (Blueprint $table): void {
                $table->id(); $table->foreignId('transmittal_id')->constrained()->restrictOnDelete(); $table->timestamps();
            });
        }
    }
}
