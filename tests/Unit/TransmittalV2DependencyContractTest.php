<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TransmittalV2DependencyContractTest extends TestCase
{
    private function source(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);
        self::assertIsString($contents, "Unable to read {$path}");

        return $contents;
    }

    public function test_v2_configuration_is_disabled_by_default_and_uses_separate_namespace(): void
    {
        $config = $this->source('config/transmittal_v2.php');
        self::assertStringContainsString("'enabled' => (bool) env('ORDO_TRANSMITTAL_V2_ENABLED', false)", $config);
        self::assertStringContainsString("'max_bytes' => (int) env('ORDO_TRANSMITTAL_UPLOAD_MAX_BYTES'", $config);
        self::assertStringNotContainsString("config('ordo.upload", $this->source('patches/transmittal-v2/staged/TransmittalController.php.candidate'));
    }

    public function test_namespaced_provider_is_registered_without_mapping_the_shared_model_policy(): void
    {
        $bootstrap = $this->source('bootstrap/providers.php');
        self::assertStringContainsString('TransmittalV2AuthorizationServiceProvider', $bootstrap);
        $provider = $this->source('app/Providers/TransmittalV2AuthorizationServiceProvider.php');
        self::assertStringContainsString("Gate::define('transmittal-v2.'", $provider);
        self::assertStringNotContainsString('Gate::policy(Transmittal::class', $provider);
    }

    public function test_resumable_upload_retains_files_after_uncertain_failures(): void
    {
        $service = $this->source('app/Services/TransmittalV2/ResumableTransmittalUploadService.php');
        self::assertStringContainsString('Preserve the assembled file: the database commit outcome may be uncertain.', $service);
        self::assertStringNotContainsString('public function cleanupExpired(', $service);
        self::assertStringNotContainsString("config('ordo.upload", $service);
    }

    public function test_brevo_transport_never_disables_tls_verification(): void
    {
        $service = $this->source('app/Services/TransmittalV2/BrevoTransmittalService.php');
        self::assertStringContainsString("config('transmittal_v2.brevo.api_key')", $service);
        self::assertStringNotContainsString('withoutVerifying', $service);
        self::assertStringContainsString('connectTimeout(5)', $service);
    }

    public function test_upload_analyze_does_not_reveal_record_matches_to_guests(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalUploadController.php');
        self::assertStringContainsString('TransmittalV2Authorization::canApprove($request->user())', $controller);
        self::assertStringContainsString('$existingDoc = $canMatchExistingRecords', $controller);
        self::assertStringContainsString('$refMatch = $canMatchExistingRecords', $controller);
    }
}
