<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TransmittalOutboxForeignKeyContractTest extends TestCase
{
    private function migration(string $name): string
    {
        $path = dirname(__DIR__, 2).'/patches/transmittal-v2/migrations/'.$name;
        $contents = file_get_contents($path);
        self::assertIsString($contents, "Unable to read {$name}");

        return $contents;
    }

    public function test_outbox_relationships_are_restrictive_and_tied_to_the_correct_v2_parent(): void
    {
        $recipient = $this->migration('2026_11_09_000002_create_transmittal_recipients_table.php.candidate');
        $workflow = $this->migration('2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate');
        $dispatch = $this->migration('2026_11_12_000001_create_transmittal_email_dispatches_table.php.candidate');
        $attempt = $this->migration('2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php.candidate');
        $provider = $this->migration('2026_11_12_000003_create_transmittal_provider_events_table.php.candidate');

        self::assertStringContainsString("unique(['id', 'transmittal_id'], 'transmittal_recipients_id_parent_unique')", $recipient);
        self::assertStringContainsString("unique(['workflow_version', 'id'], 'transmittals_workflow_version_id_unique')", $workflow);
        self::assertStringContainsString("string('workflow_version', 2)", $dispatch);
        self::assertStringContainsString("foreign(['workflow_version', 'transmittal_id']", $dispatch);
        self::assertStringContainsString("references(['workflow_version', 'id'])->on('transmittals')->restrictOnDelete()", $dispatch);
        self::assertStringContainsString("foreign(['recipient_id', 'transmittal_id']", $dispatch);
        self::assertStringContainsString("references(['id', 'transmittal_id'])->on('transmittal_recipients')->restrictOnDelete()", $dispatch);
        self::assertStringContainsString("foreign('requested_by'", $dispatch);
        self::assertStringContainsString("foreign('resend_of_dispatch_id'", $dispatch);
        self::assertStringContainsString("foreign('dispatch_id'", $attempt);
        self::assertStringContainsString("on('transmittal_email_dispatches')->restrictOnDelete()", $attempt);
        self::assertStringContainsString("foreign('dispatch_uuid'", $provider);
        self::assertStringContainsString("references('dispatch_uuid')->on('transmittal_email_dispatches')->restrictOnDelete()", $provider);

        foreach ([$recipient, $workflow, $dispatch, $attempt, $provider] as $source) {
            self::assertStringNotContainsString('cascadeOnDelete', $source);
            self::assertStringNotContainsString('nullOnDelete', $source);
        }
    }
}