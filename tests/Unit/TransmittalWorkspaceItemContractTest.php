<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TransmittalWorkspaceItemContractTest extends TestCase
{
    private function source(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$relativePath);
        self::assertIsString($contents, "Unable to read {$relativePath}");

        return $contents;
    }

    public function test_v1_and_v2_item_relationships_use_separate_models_and_ordering(): void
    {
        $source = $this->source('app/Models/Transmittal.php');

        self::assertMatchesRegularExpression(
            '/function items\(\).*?hasMany\(TransmittalItem::class\)->orderBy\(\'item_no\'\)/s',
            $source
        );
        self::assertMatchesRegularExpression(
            '/function workspaceItems\(\).*?hasMany\(TransmittalWorkspaceItem::class\)->orderBy\(\'position\'\)->orderBy\(\'id\'\)/s',
            $source
        );
    }

    public function test_workspace_item_model_preserves_quantity_as_a_string_and_uses_v2_relations(): void
    {
        $source = $this->source('app/Models/TransmittalWorkspaceItem.php');

        self::assertStringContainsString("'quantity' => 'string'", $source);
        self::assertStringContainsString("belongsTo(Transmittal::class)", $source);
        self::assertStringContainsString("belongsTo(TransmittalDocument::class)", $source);
        self::assertStringNotContainsString('TransmittalItem::class', $source);
    }

    public function test_workspace_item_schema_requires_ownership_validation_and_restricts_deletion(): void
    {
        $draft = $this->source('patches/transmittal-v2/transmittal-workspace-items-migration-draft.md');

        self::assertStringContainsString("->constrained('transmittals')", $draft);
        self::assertStringContainsString("->constrained('transmittal_documents')", $draft);
        self::assertSame(2, substr_count($draft, '->restrictOnDelete()'));
        self::assertMatchesRegularExpression(
            '/must validate\s+`document\.transmittal_id === item\.transmittal_id`/',
            $draft
        );
        self::assertStringContainsString('No migration `down()` that drops a populated table', $draft);
    }

    public function test_document_prerequisite_uses_nullable_uploader_and_restrictive_foreign_keys(): void
    {
        $draft = $this->source('patches/transmittal-v2/transmittal-documents-migration-draft.md');

        self::assertSame(2, substr_count($draft, '->restrictOnDelete()'));
        self::assertStringContainsString('->nullable()', $draft);
        self::assertStringContainsString("->string('quantity', 40)", $this->source(
            'patches/transmittal-v2/transmittal-workspace-items-migration-draft.md'
        ));
    }
}
