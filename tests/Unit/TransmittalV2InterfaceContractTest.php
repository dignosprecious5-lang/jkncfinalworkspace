<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Source-only contract checks. This test does not bootstrap Laravel or connect to a database. */
final class TransmittalV2InterfaceContractTest extends TestCase
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

    private function manifest(): array
    {
        $manifest = json_decode($this->source('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);

        return $manifest;
    }

    public function test_route_manifest_is_inactive_and_every_blade_route_reference_is_accounted_for(): void
    {
        $manifest = $this->manifest();
        self::assertSame('inactive', $manifest['status']);
        self::assertNotEmpty($manifest['routes']);
        $names = array_column($manifest['routes'], 'name');
        self::assertCount(count($names), array_unique($names), 'Route names must be unique.');

        $viewFiles = array_merge(
            glob($this->root().'/resources/views/transmittals/*.blade.php') ?: [],
            glob($this->root().'/resources/views/transmittal-v2/acknowledgments/*.blade.php') ?: [],
        );
        self::assertNotEmpty($viewFiles);
        foreach ($viewFiles as $file) {
            $view = file_get_contents($file);
            self::assertIsString($view);
            preg_match_all('~(?<!->)route\(\s*[\'\"]([^\'\"]+)~', $view, $matches);
            foreach ($matches[1] as $name) {
                self::assertContains($name, $names, basename($file)." references unmanifested route {$name}.");
            }

            preg_match_all('~@(extends|include)\(\s*[\'\"]([^\'\"]+)~', $view, $viewMatches);
            foreach ($viewMatches[2] as $reference) {
                $relative = str_replace('.', '/', $reference).'.blade.php';
                self::assertFileExists($this->root().'/resources/views/'.$relative, basename($file)." references missing view {$reference}.");
            }
        }
    }

    public function test_sensitive_routes_require_authentication_and_explicit_authorization(): void
    {
        $routes = array_column($this->manifest()['routes'], null, 'name');
        foreach (['transmittals.destroy', 'transmittals.restore', 'transmittals.force-delete'] as $excluded) {
            self::assertArrayNotHasKey($excluded, $routes);
        }
        foreach ([
            'transmittals.approve',
            'transmittals.reject',
            'transmittals.proofs.update',
            'transmittals.recipients.revoke-link',
            'transmittals.submit-for-approval',
        ] as $name) {
            self::assertContains('auth', $routes[$name]['middleware'], "{$name} must require authentication.");
            self::assertNotSame('', $routes[$name]['authorization']);
        }

        foreach (['transmittal-v2.acknowledgments.show', 'transmittal-v2.acknowledgments.store'] as $name) {
            self::assertContains('web', $routes[$name]['middleware']);
            self::assertStringContainsString('token', strtolower($routes[$name]['authorization']));
        }
        self::assertContains('throttle:6,1', $routes['transmittal-v2.acknowledgments.store']['middleware']);
    }

    public function test_manifest_controller_methods_exist_in_the_registered_v2_sources(): void
    {
        $sources = [
            'App\\Http\\Controllers\\TransmittalV2\\TransmittalController' => $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php'),
            'App\\Http\\Controllers\\TransmittalV2\\TransmittalUploadController' => $this->source('app/Http/Controllers/TransmittalV2/TransmittalUploadController.php'),
            'App\\Http\\Controllers\\TransmittalV2\\ExternalAcknowledgmentController' => $this->source('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php'),
            'App\\Http\\Controllers\\TransmittalV2\\TransmittalProviderEventController' => $this->source('app/Http/Controllers/TransmittalV2/TransmittalProviderEventController.php'),
        ];

        foreach ($this->manifest()['routes'] as $route) {
            [$class, $method] = explode('@', $route['controller'], 2);
            self::assertArrayHasKey($class, $sources, "No inactive source is mapped for {$class}.");
            self::assertMatchesRegularExpression(
                '~public\s+function\s+'.preg_quote($method, '~').'\s*\(~',
                $sources[$class],
                "{$route['name']} references missing method {$class}@{$method}."
            );
        }
    }

    public function test_v2_routes_and_policy_provider_are_registered_behind_default_off_gate(): void
    {
        $routes = $this->source('routes/transmittal-v2.php');
        self::assertStringContainsString('transmittal-v2.acknowledgments.show', $routes);
        self::assertStringContainsString('TransmittalController::class', $routes);
        self::assertStringContainsString('EnsureTransmittalV2Enabled::class', $routes);
        self::assertStringContainsString('TransmittalV2AuthorizationServiceProvider', $this->source('bootstrap/providers.php'));
    }

    public function test_v2_view_references_are_registered_as_vite_entries_without_route_activation(): void
    {
        self::assertFileExists($this->root().'/resources/views/layouts/app.blade.php');
        foreach ([
            'resources/css/transmittal.css',
            'resources/js/transmittal.js',
            'resources/js/transmittal-v2-file-tools.js',
        ] as $asset) {
            self::assertFileExists($this->root().'/'.$asset);
            self::assertContains($asset, $this->manifest()['vite_inputs']['required_inputs']);
        }
        $vite = $this->source('vite.config.js');
        foreach (['resources/css/app.css', 'resources/js/app.js', 'resources/css/transmittal.css',
            'resources/js/transmittal.js', 'resources/js/transmittal-v2-file-tools.js'] as $asset) {
            self::assertStringContainsString("'{$asset}'", $vite);
        }
        self::assertSame('configured locally; isolated build verified; production artifact and activation remain pending', $this->manifest()['vite_inputs']['status']);
    }

    public function test_untrusted_upload_queue_text_uses_text_nodes_and_large_uploads_support_resume(): void
    {
        $view = $this->source('resources/views/transmittals/smart-upload.blade.php');
        self::assertStringContainsString('fileName.textContent = item.file.name', $view);
        self::assertStringContainsString('duplicate.textContent = item.duplicateWarning', $view);
        self::assertStringNotContainsString('card.innerHTML', $view);
        self::assertStringNotContainsString('row.innerHTML', $view);
        self::assertStringContainsString('sourceBadge.textContent = data.sourceLabel', $view);
        self::assertStringContainsString('sessionStorage.getItem(resumeKey)', $view);
        self::assertStringContainsString('sessionStorage.setItem(resumeKey', $view);
        self::assertStringContainsString('for (let attempt = 0; attempt < 3; attempt++)', $view);
        self::assertStringContainsString("failedItem.ocrStatus = 'error'", $view);
    }

    public function test_views_use_the_v2_authorization_helper_instead_of_an_unavailable_shared_user_method(): void
    {
        $show = $this->source('resources/views/transmittals/show.blade.php');
        self::assertStringContainsString('TransmittalV2Authorization::canApprove(auth()->user())', $show);
        foreach (['resources/views/transmittals/index.blade.php', 'resources/views/transmittals/show.blade.php'] as $path) {
            self::assertStringNotContainsString('canApproveTransmittals()', $this->source($path));
        }
        self::assertStringContainsString("Gate::define('transmittal-v2.'", $this->source('app/Providers/TransmittalV2AuthorizationServiceProvider.php'));
    }

    public function test_smart_upload_document_picker_is_scoped_to_visible_transmittals(): void
    {
        $controller = $this->source('patches/transmittal-v2/staged/TransmittalController.php.candidate');
        self::assertStringContainsString('namespace App\\Http\\Controllers\\TransmittalV2;', $controller);
        self::assertStringContainsString('$this->visibleTransmittalQuery($request)->select(\'transmittals.id\')', $controller);
        self::assertStringContainsString('->whereIn(\'transmittal_id\', $visibleTransmittals)', $controller);
    }

    public function test_workspace_back_link_uses_the_v2_plural_route_name(): void
    {
        self::assertStringContainsString("route('transmittals.index')", $this->source('resources/views/transmittals/workspace.blade.php'));
        self::assertStringNotContainsString("route('transmittal.index')", $this->source('resources/views/transmittals/workspace.blade.php'));
    }

    public function test_acknowledgment_form_fields_match_controller_validation_and_css_has_responsive_breakpoints(): void
    {
        $view = $this->source('resources/views/transmittal-v2/acknowledgments/show.blade.php');
        $controller = $this->source('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
        foreach (['signed_name', 'comments', 'confirmation_check'] as $field) {
            self::assertStringContainsString('name="'.$field.'"', $view);
            self::assertStringContainsString("'{$field}' =>", $controller);
        }
        self::assertStringContainsString('@csrf', $view);
        self::assertStringContainsString('@media (max-width: 760px)', $this->source('resources/css/transmittal.css'));
    }
}
