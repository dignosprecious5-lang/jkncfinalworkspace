<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\TransmittalV2\ExternalAcknowledgmentController;
use App\Http\Controllers\TransmittalV2\TransmittalController;
use App\Http\Controllers\TransmittalV2\TransmittalUploadController;
use App\Http\Middleware\TransmittalV2\EnsureTransmittalV2Enabled;
use PHPUnit\Framework\TestCase;

/** Source-only checks. Composer autoload is loaded, but Laravel is not booted. */
final class TransmittalV2Phase9ContractTest extends TestCase
{
    private static string $root;

    public static function setUpBeforeClass(): void
    {
        self::$root = dirname(__DIR__, 2);
        require_once self::$root.'/vendor/autoload.php';
    }

    private function source(string $path): string
    {
        $contents = file_get_contents(self::$root.'/'.$path);
        self::assertIsString($contents, "Unable to read {$path}");

        return $contents;
    }

    public function test_v2_controllers_are_loadable_and_all_have_controller_level_feature_gate(): void
    {
        foreach ([TransmittalController::class, TransmittalUploadController::class, ExternalAcknowledgmentController::class] as $controller) {
            self::assertTrue(class_exists($controller), "{$controller} must resolve through the App PSR-4 autoloader.");
            self::assertContains(EnsureTransmittalV2Enabled::class, $controller::middleware());
        }

        self::assertTrue(class_exists(EnsureTransmittalV2Enabled::class));
    }

    public function test_feature_gate_is_default_disabled_and_does_not_replace_authorization(): void
    {
        $config = $this->source('config/transmittal_v2.php');
        $gate = $this->source('app/Http/Middleware/TransmittalV2/EnsureTransmittalV2Enabled.php');
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');

        self::assertMatchesRegularExpression("~'enabled'\\s*=>\\s*\\(bool\\)\\s*env\\('ORDO_TRANSMITTAL_V2_ENABLED',\\s*false\\)~", $config);
        self::assertStringContainsString("config('transmittal_v2.enabled', false)", $gate);
        self::assertStringContainsString('abort_unless', $gate);
        self::assertStringContainsString('404', $gate);
        self::assertStringContainsString('canManageLifecycle', $controller);
        self::assertStringContainsString('$this->authorize(\'transmittal-v2.approve\'', $controller);
        self::assertStringContainsString('canApproveTransmittals', $controller);
        self::assertFileExists(self::$root.'/app/Services/TransmittalV2Authorization.php');
    }

    public function test_upload_and_acknowledgment_keep_method_level_gate_checks(): void
    {
        $contracts = [
            'app/Http/Controllers/TransmittalV2/TransmittalUploadController.php' => ['start', 'putChunk', 'complete', 'analyze'],
            'app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php' => ['show', 'store'],
        ];

        foreach ($contracts as $path => $methods) {
            $source = $this->source($path);
            foreach ($methods as $method) {
                self::assertMatchesRegularExpression(
                    '~public\\s+function\\s+'.preg_quote($method, '~').'\\s*\\(.*?\\)\\s*(?::[^\\{]+)?\\{\\s*\\$this->assertEnabled\\(\\);~s',
                    $source,
                    "{$path}::{$method} must retain its method-level feature check."
                );
            }
        }
    }

    public function test_smart_upload_frontend_fails_clearly_without_pdf_or_archive_dependencies(): void
    {
        $tools = $this->source('resources/js/transmittal-v2-file-tools.js');
        $view = $this->source('resources/views/transmittals/smart-upload.blade.php');

        self::assertStringContainsString('formatUploadSize', $tools);
        self::assertStringContainsString('fflate', $tools);
        self::assertStringContainsString('fflate dependency', $view);
        self::assertStringContainsString('compressOversizedFile(item.file, maxUploadBytes)', $view);
        self::assertStringContainsString('const formatUploadSize =', $view);
        self::assertStringNotContainsString('window.ORDOFileTools.formatSize(', $view);
        self::assertMatchesRegularExpression(
            "~if\\s*\\(\\s*window\\.ORDOFileTools\\s*&&\\s*typeof\\s+window\\.ORDOFileTools\\.compress\\s*===\\s*'function'\\s*\\)\\s*\\{\\s*try\\s*\\{\\s*const\\s+compressedByTool\\s*=\\s*await\\s+window\\.ORDOFileTools\\.compress~s",
            $view,
            'Optional compression helper must only be called after checking that it is callable.'
        );
        self::assertStringNotContainsString("from 'pdf-lib'", $tools);
        self::assertStringNotContainsString("from 'fflate'", $tools);
    }

    public function test_route_manifest_is_default_off_and_vite_includes_local_transmittal_assets(): void
    {
        $manifest = json_decode($this->source('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $vite = $this->source('vite.config.js');
        $routes = $this->source('routes/transmittal-v2.php');

        self::assertSame('inactive', $manifest['status']);
        foreach (['resources/css/app.css', 'resources/js/app.js', 'resources/css/transmittal.css',
            'resources/js/transmittal.js', 'resources/js/transmittal-v2-file-tools.js'] as $asset) {
            self::assertStringContainsString("'{$asset}'", $vite);
        }
        self::assertStringContainsString('transmittals.index', $routes);
        self::assertStringContainsString('transmittal-v2.acknowledgments', $routes);
        self::assertStringContainsString('EnsureTransmittalV2Enabled::class', $routes);
    }
}
