<?php

namespace Tests\Unit;

use App\Models\Transmittal;
use App\Services\TransmittalWorkspaceItemSyncService;
use PHPUnit\Framework\TestCase;

class TransmittalWorkspaceItemSyncServiceTest extends TestCase
{
    public function test_absent_items_payload_returns_without_database_work(): void
    {
        $service = new TransmittalWorkspaceItemSyncService;
        $transmittal = new Transmittal;

        $service->synchronize($transmittal, ['subject' => 'unchanged']);

        self::assertFalse($transmittal->exists);
    }

    public function test_null_items_payload_is_rejected_before_transaction(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/Services/TransmittalWorkspaceItemSyncService.php');
        self::assertIsString($source);
        self::assertStringContainsString("if (! is_array(\$items))", $source);
        self::assertStringContainsString(
            "\$this->invalid('items', 'The items payload must be an array.')",
            $source
        );
        self::assertLessThan(
            strpos($source, 'DB::transaction('),
            strpos($source, "The items payload must be an array.")
        );
    }

    public function test_sync_service_is_scoped_to_workspace_items_and_guards_approval_and_ownership(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/app/Services/TransmittalWorkspaceItemSyncService.php');
        self::assertIsString($source);

        self::assertStringContainsString('workspaceItems()', $source);
        self::assertStringContainsString('lockForUpdate()', $source);
        self::assertStringContainsString('approved_snapshot', $source);
        self::assertStringContainsString('authorityCode()->exists()', $source);
        self::assertStringContainsString('The item does not belong to this transmittal.', $source);
        self::assertStringContainsString('The document does not belong to this transmittal.', $source);
        self::assertStringNotContainsString('->items()', $source);
        self::assertStringNotContainsString('TransmittalItem::class', $source);
        self::assertDoesNotMatchRegularExpression('/quantity.{0,100}(float|intval|is_numeric)/s', $source);
    }
}
