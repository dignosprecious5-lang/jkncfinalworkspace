<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transmittal;
use App\Services\TransmittalWorkspaceItemSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TransmittalWorkspaceItemSyncServiceDatabaseTest extends TestCase
{
    private TransmittalWorkspaceItemSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createFixtureSchema();
        $this->service = new TransmittalWorkspaceItemSyncService;
    }

    public function test_missing_items_payload_leaves_database_untouched(): void
    {
        $transmittal = new Transmittal;
        $this->service->synchronize($transmittal, ['subject' => 'unchanged']);

        self::assertFalse($transmittal->exists);
        self::assertSame(0, DB::table('transmittals')->count());
    }

    public function test_null_items_payload_is_rejected_without_writes(): void
    {
        $transmittalId = $this->transmittal();

        try {
            $this->service->synchronize(Transmittal::findOrFail($transmittalId), ['items' => null]);
            self::fail('Null item payload should be rejected.');
        } catch (ValidationException) {
            self::assertSame(0, DB::table('transmittal_workspace_items')->count());
        }
    }

    public function test_empty_items_payload_clears_only_v2_items_and_preserves_v1_data(): void
    {
        $transmittalId = $this->transmittal();
        DB::table('transmittal_items')->insert([
            'transmittal_id' => $transmittalId,
            'item_no' => 7,
            'qty' => 4,
            'attachment_path' => 'legacy/transmittal-item.pdf',
        ]);
        DB::table('transmittal_attachments')->insert([
            'transmittal_id' => $transmittalId,
            'file_path' => 'legacy/transmittal-attachment.pdf',
        ]);

        $transmittal = Transmittal::findOrFail($transmittalId);
        $this->service->synchronize($transmittal, [
            'items' => [['line_number' => 1, 'quantity' => '2.50']],
        ]);
        self::assertSame(1, DB::table('transmittal_workspace_items')->count());

        $this->service->synchronize($transmittal, ['items' => []]);

        self::assertSame(0, DB::table('transmittal_workspace_items')->count());
        self::assertDatabaseHas('transmittal_items', [
            'transmittal_id' => $transmittalId,
            'item_no' => 7,
            'qty' => 4,
            'attachment_path' => 'legacy/transmittal-item.pdf',
        ]);
        self::assertDatabaseHas('transmittal_attachments', [
            'transmittal_id' => $transmittalId,
            'file_path' => 'legacy/transmittal-attachment.pdf',
        ]);
    }

    public function test_items_can_be_created_updated_reordered_and_omitted_items_are_deleted(): void
    {
        $transmittalId = $this->transmittal();
        $transmittal = Transmittal::findOrFail($transmittalId);
        $this->service->synchronize($transmittal, ['items' => [
            ['line_number' => 10, 'particular' => 'First', 'quantity' => '0.25'],
            ['line_number' => 20, 'particular' => 'Second', 'quantity' => '2 boxes'],
            ['line_number' => 30, 'particular' => 'Omitted', 'quantity' => '3'],
        ]]);

        $items = DB::table('transmittal_workspace_items')->orderBy('position')->get();
        self::assertCount(3, $items);
        $firstId = (int) $items[0]->id;
        $secondId = (int) $items[1]->id;
        $omittedId = (int) $items[2]->id;

        $this->service->synchronize($transmittal, ['items' => [
            ['id' => $secondId, 'line_number' => 21, 'particular' => 'Moved first', 'quantity' => '2.50'],
            ['id' => $firstId, 'line_number' => 11, 'particular' => 'Moved second', 'quantity' => 'one'],
        ]]);

        $reordered = DB::table('transmittal_workspace_items')->orderBy('position')->get();
        self::assertCount(2, $reordered);
        self::assertSame([$secondId, $firstId], [(int) $reordered[0]->id, (int) $reordered[1]->id]);
        self::assertSame([0, 1], [(int) $reordered[0]->position, (int) $reordered[1]->position]);
        self::assertSame('Moved first', $reordered[0]->particular);
        self::assertSame('2.50', $reordered[0]->quantity);
        self::assertSame('Moved second', $reordered[1]->particular);
        self::assertSame('one', $reordered[1]->quantity);
        self::assertDatabaseMissing('transmittal_workspace_items', ['id' => $omittedId]);
    }

    public function test_cross_transmittal_item_id_is_rejected(): void
    {
        $firstTransmittal = $this->transmittal();
        $secondTransmittal = $this->transmittal();
        $foreignItemId = $this->workspaceItem($secondTransmittal, 'foreign');

        try {
            $this->service->synchronize(Transmittal::findOrFail($firstTransmittal), [
                'items' => [['id' => $foreignItemId, 'line_number' => 1, 'quantity' => 'changed']],
            ]);
            self::fail('Cross-transmittal item ID should be rejected.');
        } catch (ValidationException) {
            self::assertDatabaseHas('transmittal_workspace_items', [
                'id' => $foreignItemId,
                'transmittal_id' => $secondTransmittal,
                'quantity' => 'foreign',
            ]);
        }
    }

    public function test_cross_transmittal_document_id_is_rejected(): void
    {
        $firstTransmittal = $this->transmittal();
        $secondTransmittal = $this->transmittal();
        $documentId = $this->document($secondTransmittal);

        try {
            $this->service->synchronize(Transmittal::findOrFail($firstTransmittal), [
                'items' => [['line_number' => 1, 'document_id' => $documentId, 'quantity' => '1']],
            ]);
            self::fail('Cross-transmittal document ID should be rejected.');
        } catch (ValidationException) {
            self::assertSame(0, DB::table('transmittal_workspace_items')->count());
            self::assertDatabaseHas('transmittal_documents', [
                'id' => $documentId,
                'transmittal_id' => $secondTransmittal,
            ]);
        }
    }

    public function test_textual_and_decimal_quantities_are_preserved_exactly(): void
    {
        $transmittal = Transmittal::findOrFail($this->transmittal());
        $this->service->synchronize($transmittal, ['items' => [
            ['line_number' => 1, 'quantity' => '0.2500'],
            ['line_number' => 2, 'quantity' => '2 boxes, sealed'],
            ['line_number' => 3, 'quantity' => ''],
        ]]);

        self::assertSame(
            ['0.2500', '2 boxes, sealed', ''],
            DB::table('transmittal_workspace_items')->orderBy('position')->pluck('quantity')->all()
        );
    }

    public function test_approval_snapshot_and_approval_evidence_block_item_changes(): void
    {
        $snapshotTransmittal = $this->transmittal(['approved_snapshot' => json_encode(['items' => []])]);
        $statusTransmittal = $this->transmittal(['status' => 'approved']);
        $approvalStatusTransmittal = $this->transmittal(['approval_status' => 'approved']);
        $approvedAtTransmittal = $this->transmittal(['approved_at' => now()]);
        $authorityTransmittal = $this->transmittal();
        DB::table('approval_authority_codes')->insert(['transmittal_id' => $authorityTransmittal]);
        $proofTransmittal = $this->transmittal();
        DB::table('proof_of_transmissions')->insert(['transmittal_id' => $proofTransmittal]);
        $acknowledgmentTransmittal = $this->transmittal();
        DB::table('external_acknowledgments')->insert(['transmittal_id' => $acknowledgmentTransmittal]);

        foreach ([
            $snapshotTransmittal,
            $statusTransmittal,
            $approvalStatusTransmittal,
            $approvedAtTransmittal,
            $authorityTransmittal,
            $proofTransmittal,
            $acknowledgmentTransmittal,
        ] as $id) {
            try {
                $this->service->synchronize(Transmittal::findOrFail($id), ['items' => []]);
                self::fail("Evidence should block changes for transmittal {$id}.");
            } catch (ValidationException) {
                self::assertSame(0, DB::table('transmittal_workspace_items')
                    ->where('transmittal_id', $id)->count());
            }
        }
    }

    public function test_database_rollback_restores_prior_item_when_later_item_is_invalid(): void
    {
        $transmittalId = $this->transmittal();
        $itemId = $this->workspaceItem($transmittalId, 'before');
        $foreignTransmittalId = $this->transmittal();
        $foreignDocumentId = $this->document($foreignTransmittalId);

        try {
            $this->service->synchronize(Transmittal::findOrFail($transmittalId), ['items' => [
                ['id' => $itemId, 'line_number' => 1, 'quantity' => 'after'],
                ['line_number' => 2, 'document_id' => $foreignDocumentId, 'quantity' => 'must rollback'],
            ]]);
            self::fail('The foreign document should abort the replacement transaction.');
        } catch (ValidationException) {
            self::assertDatabaseHas('transmittal_workspace_items', [
                'id' => $itemId,
                'transmittal_id' => $transmittalId,
                'quantity' => 'before',
            ]);
            self::assertSame(1, DB::table('transmittal_workspace_items')
                ->where('transmittal_id', $transmittalId)->count());
        }
    }

    public function test_existing_legacy_receipt_blocks_v2_item_changes(): void
    {
        $transmittalId = $this->transmittal();
        $itemId = $this->workspaceItem($transmittalId, 'preserved');
        DB::table('transmittal_receipts')->insert([
            'transmittal_id' => $transmittalId,
            'receipt_no' => 'LEGACY-001',
        ]);

        try {
            $this->service->synchronize(Transmittal::findOrFail($transmittalId), ['items' => []]);
            self::fail('A retained receipt should block item changes.');
        } catch (ValidationException) {
            self::assertDatabaseHas('transmittal_workspace_items', [
                'id' => $itemId,
                'quantity' => 'preserved',
            ]);
        }
    }

    private function createFixtureSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('transmittals', function (Blueprint $table): void {
            $table->id();
            $table->string('workflow_version', 8)->nullable();
            $table->string('status');
            $table->string('approval_status')->nullable();
            $table->json('approved_snapshot')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
        Schema::create('transmittal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('item_no')->default(1);
            $table->unsignedInteger('qty')->nullable();
            $table->string('attachment_path')->nullable();
        });
        Schema::create('transmittal_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
        });
        Schema::create('transmittal_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->string('storage_path')->unique();
        });
        Schema::create('transmittal_workspace_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('transmittal_documents')->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->unsignedInteger('line_number');
            $table->string('particular')->nullable();
            $table->string('unique_id')->nullable();
            $table->string('quantity', 40)->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['transmittal_id', 'position', 'id']);
        });
        Schema::create('transmittal_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
            $table->string('receipt_no')->unique();
        });
        Schema::create('approval_authority_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->unique()->restrictOnDelete();
        });
        Schema::create('proof_of_transmissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
        });
        Schema::create('external_acknowledgments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transmittal_id')->constrained()->restrictOnDelete();
        });
    }

    private function transmittal(array $overrides = []): int
    {
        return (int) DB::table('transmittals')->insertGetId(array_merge([
            'workflow_version' => 'v2',
            'status' => 'draft',
            'approval_status' => 'pending',
        ], $overrides));
    }

    private function document(int $transmittalId): int
    {
        return (int) DB::table('transmittal_documents')->insertGetId([
            'transmittal_id' => $transmittalId,
            'storage_path' => 'private/document-'.$transmittalId.'-'.bin2hex(random_bytes(4)).'.pdf',
        ]);
    }

    private function workspaceItem(int $transmittalId, string $quantity): int
    {
        return (int) DB::table('transmittal_workspace_items')->insertGetId([
            'transmittal_id' => $transmittalId,
            'position' => 0,
            'line_number' => 1,
            'quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
