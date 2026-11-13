<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Source-only documentation and isolation contracts; no Laravel bootstrap or database. */
final class TransmittalV2Phase12ContractTest extends TestCase
{
    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function source(string $path): string
    {
        $contents = file_get_contents($this->root().'/'.$path);
        self::assertIsString($contents, "Unable to read {$path}");

        return $contents;
    }

    public function test_runtime_plan_requires_os_network_and_unc_isolation(): void
    {
        $plan = $this->source('patches/transmittal-v2/phase12-runtime-isolation-plan.md');

        foreach (['network access disabled', 'UNC/SMB', 'existing MySQL96 service', 'credentials', 'in-memory SQLite', 'separate ephemeral MySQL instance', 'Do not stop, reuse, or connect to the existing MySQL96 service', 'do not run Laravel or MySQL tests'] as $required) {
            self::assertStringContainsString($required, $plan);
        }
    }

    public function test_approved_zip_dependency_is_recorded_without_activating_features(): void
    {
        $plan = $this->source('patches/transmittal-v2/vite-integration-plan.md');
        $package = json_decode($this->source('package.json'), true, 512, JSON_THROW_ON_ERROR);
        $lock = json_decode($this->source('package-lock.json'), true, 512, JSON_THROW_ON_ERROR);
        $declared = array_merge($package['dependencies'] ?? [], $package['devDependencies'] ?? []);

        self::assertStringContainsString('pdf-lib@1.17.1', $plan);
        self::assertStringContainsString('fflate@0.8.3', $plan);
        self::assertSame('2.24.0', $declared['@zip.js/zip.js']);
        self::assertSame('2.24.0', $lock['packages']['node_modules/@zip.js/zip.js']['version']);

        self::assertStringContainsString('@zip.js/zip.js@2.24.0', $plan);
        self::assertStringContainsString('Security acceptance remains pending.', $plan);
        foreach (['pdf-lib', 'fflate'] as $dependency) {
            self::assertArrayNotHasKey($dependency, $declared);
            self::assertArrayNotHasKey('node_modules/'.$dependency, $lock['packages']);
        }
    }

    public function test_optional_pdf_and_archive_features_fail_clearly_and_preserve_sources(): void
    {
        $compression = $this->source('resources/js/transmittal-v2-upload-compression.js');
        $tools = $this->source('resources/js/transmittal-v2-file-tools.js');

        self::assertStringContainsString('PDF compression is unavailable', $compression);
        self::assertStringContainsString('ZIP extraction is unavailable', $tools);
        self::assertStringContainsString('return new File([blob]', $compression);
        self::assertStringContainsString('bitmap.close?.()', $compression);
    }

    public function test_v2_smart_upload_is_linked_to_isolated_helper_behind_default_off_gate(): void
    {
        $view = $this->source('resources/views/transmittals/smart-upload.blade.php');
        $vite = $this->source('vite.config.js');
        $routes = $this->source('routes/transmittal-v2.php');
        $providers = $this->source('bootstrap/providers.php');
        $manifest = json_decode($this->source('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertStringContainsString("@vite('resources/js/transmittal-v2-file-tools.js')", $view);
        self::assertStringContainsString("'resources/css/transmittal.css'", $vite);
        self::assertStringContainsString("'resources/js/transmittal.js'", $vite);
        self::assertStringContainsString('resources/js/transmittal-v2-file-tools.js', $vite);
        self::assertStringContainsString('transmittals.index', $routes);
        self::assertStringContainsString('EnsureTransmittalV2Enabled::class', $routes);
        self::assertStringContainsString('TransmittalV2AuthorizationServiceProvider', $providers);
        self::assertSame('inactive', $manifest['status']);
        self::assertCount(32, $manifest['routes']);
    }
}
