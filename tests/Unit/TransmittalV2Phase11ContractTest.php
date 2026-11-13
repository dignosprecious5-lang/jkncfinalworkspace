<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Source-only contracts; does not bootstrap Laravel or open a database. */
final class TransmittalV2Phase11ContractTest extends TestCase
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

    public function test_locked_frontend_toolchain_matches_the_installed_node_runtime(): void
    {
        $lock = json_decode($this->source('package-lock.json'), true, 512, JSON_THROW_ON_ERROR);
        $packages = $lock['packages'];

        self::assertSame('7.3.1', $packages['node_modules/vite']['version']);
        self::assertSame('2.1.0', $packages['node_modules/laravel-vite-plugin']['version']);
        self::assertSame('4.2.1', $packages['node_modules/@tailwindcss/vite']['version']);
        self::assertStringContainsString('>=22.12.0', $packages['node_modules/vite']['engines']['node']);
        self::assertStringContainsString('^7.0.0', $packages['node_modules/laravel-vite-plugin']['peerDependencies']['vite']);
        self::assertStringContainsString('^7', $packages['node_modules/@tailwindcss/vite']['peerDependencies']['vite']);
    }

    public function test_rejected_packages_remain_absent_while_approved_zip_library_is_installed(): void
    {
        $package = json_decode($this->source('package.json'), true, 512, JSON_THROW_ON_ERROR);
        $lock = json_decode($this->source('package-lock.json'), true, 512, JSON_THROW_ON_ERROR);
        $declared = array_merge($package['dependencies'] ?? [], $package['devDependencies'] ?? []);

        self::assertSame('2.24.0', $declared['@zip.js/zip.js']);
        self::assertSame('2.24.0', $lock['packages']['node_modules/@zip.js/zip.js']['version']);

        foreach (['pdf-lib', 'fflate'] as $dependency) {
            self::assertArrayNotHasKey($dependency, $declared);
            self::assertArrayNotHasKey('node_modules/'.$dependency, $lock['packages']);
        }
    }

    public function test_smart_upload_references_only_the_v2_file_tools_entry(): void
    {
        $view = $this->source('resources/views/transmittals/smart-upload.blade.php');
        $tools = $this->source('resources/js/transmittal-v2-file-tools.js');
        $compression = $this->source('resources/js/transmittal-v2-upload-compression.js');

        self::assertStringContainsString("@vite('resources/js/transmittal-v2-file-tools.js')", $view);
        self::assertStringContainsString("from './transmittal-v2-upload-compression.js'", $tools);
        self::assertStringContainsString('compress: compressUploadFile', $tools);
        self::assertStringContainsString('formatSize: formatUploadSize', $tools);
        self::assertStringContainsString('extractArchive: extractUploadArchive', $tools);
        self::assertStringContainsString('PDF compression is unavailable', $compression);
        self::assertStringContainsString('until the reviewed fflate dependency is integrated', $tools);
    }

    public function test_v2_routes_and_assets_are_registered_behind_default_off_gate(): void
    {
        $vite = $this->source('vite.config.js');
        $routes = $this->source('routes/transmittal-v2.php');
        $providers = $this->source('bootstrap/providers.php');
        $manifest = json_decode($this->source('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('inactive', $manifest['status']);
        self::assertCount(32, $manifest['routes']);
        self::assertStringContainsString("'resources/css/transmittal.css'", $vite);
        self::assertStringContainsString("'resources/js/transmittal.js'", $vite);
        self::assertStringContainsString("'resources/js/transmittal-v2-file-tools.js'", $vite);
        self::assertStringContainsString('transmittals.index', $routes);
        self::assertStringContainsString('transmittal-v2.acknowledgments', $routes);
        self::assertStringContainsString('EnsureTransmittalV2Enabled::class', $routes);
        self::assertStringContainsString('TransmittalV2AuthorizationServiceProvider', $providers);
    }

    public function test_route_manifest_uses_real_password_middleware_class_and_registered_history_alias(): void
    {
        $manifestSource = $this->source('patches/transmittal-v2/transmittal-v2-route-manifest.json');
        $manifest = json_decode($manifestSource, true, 512, JSON_THROW_ON_ERROR);
        $bootstrap = $this->source('bootstrap/app.php');

        self::assertStringNotContainsString('"EnsurePasswordChanged"', $manifestSource);
        self::assertFileExists($this->root().'/app/Http/Middleware/EnsurePasswordChanged.php');
        self::assertStringContainsString("'prevent-back-history' =>", $bootstrap);

        foreach ($manifest['routes'] as $route) {
            if (in_array('auth', $route['middleware'], true) && in_array('prevent-back-history', $route['middleware'], true)) {
                self::assertContains('App\\Http\\Middleware\\EnsurePasswordChanged', $route['middleware'], $route['name'].' must use the real class name rather than an undefined alias.');
            }
        }
    }

    public function test_browser_runtime_matrix_separates_sqlite_source_checks_from_mysql_requirements(): void
    {
        $matrix = $this->source('patches/transmittal-v2/browser-runtime-acceptance-matrix.md');

        self::assertStringContainsString('These cases have not been run', $matrix);
        self::assertStringContainsString('disposable MySQL', $matrix);
        self::assertStringContainsString('PDF compression', $matrix);
        self::assertStringContainsString('ZIP extraction', $matrix);
        self::assertStringContainsString('Accessibility/responsive', $matrix);
    }
}
