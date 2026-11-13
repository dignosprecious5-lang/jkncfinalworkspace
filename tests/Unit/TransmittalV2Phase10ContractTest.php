<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Static contracts only: no Laravel application bootstrap or database connection. */
final class TransmittalV2Phase10ContractTest extends TestCase
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
        return json_decode($this->source('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_v2_authorization_uses_only_namespaced_gates_and_never_maps_the_shared_model(): void
    {
        $provider = $this->source('app/Providers/TransmittalV2AuthorizationServiceProvider.php');
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');
        $v1Controller = $this->source('app/Http/Controllers/TransmittalController.php');
        $providers = $this->source('bootstrap/providers.php');

        self::assertStringNotContainsString('Gate::policy(', $provider);
        self::assertStringContainsString("Gate::define('transmittal-v2.'", $provider);
        self::assertStringContainsString('$this->authorize(\'transmittal-v2.approve\'', $controller);
        self::assertStringContainsString('$this->authorize(\'transmittal-v2.reject\'', $controller);
        self::assertStringContainsString('$this->authorize(\'transmittal-v2.transmit\'', $controller);
        self::assertStringContainsString('$this->authorize(\'transmittal-v2.upload-proof\'', $controller);
        self::assertStringContainsString("hasPermission('approve_corporate')", $v1Controller);
        self::assertStringContainsString('TransmittalV2AuthorizationServiceProvider', $providers);
        self::assertStringContainsString("'view' => 'view'", $provider);
        self::assertStringContainsString("'view-attachments' => 'viewAttachments'", $provider);
    }

    public function test_v2_policy_keeps_record_level_ownership_status_and_recipient_checks(): void
    {
        $policy = $this->source('app/Policies/TransmittalV2Policy.php');
        $helper = $this->source('app/Services/TransmittalV2Authorization.php');
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');

        self::assertStringContainsString('ownsTransmittal($user, $transmittal->prepared_by)', $policy);
        self::assertStringContainsString('$transmittal->status === \'pending_approval\'', $policy);
        self::assertStringContainsString('$transmittal->recipients()', $policy);
        self::assertStringContainsString('$user !== null', $helper);
        self::assertStringContainsString('$preparedBy !== null', $helper);
        self::assertStringContainsString('canManageLifecycle', $controller);
        $methodStart = strpos($controller, 'public function updateProof(');
        self::assertNotFalse($methodStart);
        $methodEnd = strpos($controller, "\n    public function ", $methodStart + 1);
        self::assertNotFalse($methodEnd);
        $method = substr($controller, $methodStart, $methodEnd - $methodStart);
        $authorization = strpos($method, '$this->authorize(\'transmittal-v2.upload-proof\'');
        $scopedLookup = strpos($method, '$record = $transmittal->proofs()->findOrFail($proof);');
        self::assertNotFalse($authorization, 'Proof update must authorize the transmittal first.');
        self::assertNotFalse($scopedLookup, 'Proof lookup must be scoped through the transmittal relationship.');
        self::assertLessThan($scopedLookup, $authorization, 'Authorization must occur before the scoped proof lookup.');
        self::assertStringContainsString('can(\'transmittal-v2.view\', $transmittal)', $controller);
        self::assertStringContainsString('can(\'transmittal-v2.view-attachments\', $transmittal)', $controller);
    }

    public function test_show_session_token_is_scoped_to_record_and_checked_before_relationship_loading(): void
    {
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');
        $sessionConfig = $this->source('config/session.php');
        $showStart = strpos($controller, 'public function show(');
        self::assertNotFalse($showStart);
        $showEnd = strpos($controller, "\n    public function ", $showStart + 1);
        self::assertNotFalse($showEnd);
        $show = substr($controller, $showStart, $showEnd - $showStart);

        self::assertStringContainsString('$this->canManageLifecycle($request, $transmittal)', $show);
        self::assertLessThan(strpos($show, '$transmittal->load('), strpos($show, '$this->canManageLifecycle('));

        self::assertStringContainsString('"transmittal_draft_tokens.{$transmittal->id}"', $controller);
        self::assertStringContainsString('hash_equals($transmittal->edit_token_hash, hash(\'sha256\', $token))', $controller);
        self::assertStringContainsString("'lifetime' => (int) env('SESSION_LIFETIME', 120)", $sessionConfig);
    }

    public function test_all_32_manifest_endpoints_have_controller_contracts_web_csrf_authorization_and_throttled_writes(): void
    {
        $manifest = $this->manifest();
        self::assertSame('inactive', $manifest['status']);
        self::assertCount(32, $manifest['routes']);
        self::assertStringContainsString('CSRF', $manifest['csrf']);
        self::assertSame(32, $manifest['endpoint_security_review']['reviewed_endpoint_count']);
        self::assertContains('App\\Http\\Middleware\\TransmittalV2\\EnsureTransmittalV2Enabled (controller-level; default-disabled)', $manifest['middleware_baseline']);

        $names = [];
        foreach ($manifest['routes'] as $route) {
            self::assertNotContains($route['name'], $names, 'Route names must be unique.');
            $names[] = $route['name'];
            self::assertContains('web', $route['middleware'], $route['name'].' must retain web/CSRF middleware.');
            self::assertNotSame('', trim($route['authentication']), $route['name'].' needs an explicit authentication model.');
            self::assertNotSame('', trim($route['authorization']), $route['name'].' needs an explicit authorization rule.');

            [$controllerClass, $method] = explode('@', $route['controller'], 2);
            self::assertStringStartsWith('App\\Http\\Controllers\\TransmittalV2\\', $controllerClass);
            $relative = 'app/'.str_replace('\\', '/', substr($controllerClass, 4)).'.php';
            $source = $this->source($relative);
            self::assertMatchesRegularExpression('~public\s+function\s+'.preg_quote($method, '~').'\s*\(~', $source, $route['name'].' method is missing.');

            if (in_array($route['method'], ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $throttled = false;
                foreach ($route['middleware'] as $item) {
                    if (str_starts_with($item, 'throttle:')) $throttled = true;
                }
                self::assertTrue($throttled, $route['name'].' state-changing route needs a throttle.');
            }

            $authenticationText = strtolower($route['authentication']);
            $allowsUnauthenticatedTokenAccess = str_contains($authenticationText, 'valid session token only for own draft')
                || str_contains($authenticationText, 'session-token access only for that draft')
                || str_contains($authenticationText, 'authorized recipient');
            if (str_starts_with($authenticationText, 'authenticated') && ! $allowsUnauthenticatedTokenAccess) {
                self::assertContains('auth', $route['middleware'], $route['name'].' must use auth middleware.');
            }
        }

        $routesByName = array_column($manifest['routes'], null, 'name');
        self::assertStringContainsString('authorized recipient', $routesByName['transmittals.documents.download']['authentication']);
        self::assertStringContainsString('valid session token', $routesByName['transmittals.documents.download']['authentication']);
    }

    public function test_installed_zip_dependency_does_not_activate_frontend_capabilities(): void
    {
        $tools = $this->source('resources/js/transmittal-v2-file-tools.js');
        $compression = $this->source('resources/js/transmittal-v2-upload-compression.js');
        $plan = $this->source('patches/transmittal-v2/vite-integration-plan.md');
        $package = $this->source('package.json');
        $lock = $this->source('package-lock.json');

        self::assertStringContainsString("from './transmittal-v2-upload-compression.js'", $tools);
        self::assertStringContainsString('compress: compressUploadFile', $tools);
        self::assertStringContainsString('fflate', $tools);
        self::assertStringContainsString('pdf-lib', $compression);
        self::assertStringContainsString('maxBytes', $compression);
        self::assertStringContainsString('Vite', $plan);
        self::assertStringNotContainsString('pdf-lib', $package);
        self::assertStringNotContainsString('fflate', $package);
        self::assertStringNotContainsString('pdf-lib', $lock);
        self::assertStringNotContainsString('fflate', $lock);
        self::assertSame('2.24.0', json_decode($package, true, 512, JSON_THROW_ON_ERROR)['dependencies']['@zip.js/zip.js']);
        self::assertSame('2.24.0', json_decode($lock, true, 512, JSON_THROW_ON_ERROR)['packages']['node_modules/@zip.js/zip.js']['version']);
    }

    public function test_v2_routes_and_namespaced_provider_are_registered_behind_default_off_gate(): void
    {
        $routes = $this->source('routes/transmittal-v2.php');
        $providers = $this->source('bootstrap/providers.php');
        $vite = $this->source('vite.config.js');

        self::assertStringContainsString('transmittals.index', $routes);
        self::assertStringContainsString('transmittal-v2.acknowledgments', $routes);
        self::assertStringContainsString('EnsureTransmittalV2Enabled::class', $routes);
        self::assertStringContainsString('TransmittalV2AuthorizationServiceProvider', $providers);
        self::assertStringContainsString("env('ORDO_TRANSMITTAL_V2_ENABLED', false)", $this->source('config/transmittal_v2.php'));
        self::assertStringContainsString('resources/js/transmittal-v2-file-tools.js', $vite);
        self::assertStringContainsString('resources/css/transmittal.css', $vite);
        self::assertStringContainsString("'resources/js/transmittal.js'", $vite);
    }
}
