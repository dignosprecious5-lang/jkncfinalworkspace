<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Tests\Support\TransmittalV1Isolation;

/**
 * Loaded as Tests\TestCase only by the dedicated bootstrap's in-memory class map.
 * The normal tests/TestCase.php and all existing test definitions stay untouched.
 */
abstract class TestCase extends LaravelTestCase
{
    public function createApplication()
    {
        $root = dirname(__DIR__, 2);
        $state = TransmittalV1Isolation::prepare($root);
        TransmittalV1Isolation::assertEnvironment();
        TransmittalV1Isolation::createRuntimeDirectories();
        $app = require $root.'/bootstrap/app.php';
        $app->addAbsoluteCachePathPrefix($state['runtime'].'/cache/');
        $app->useStoragePath($state['runtime'].'/storage');
        $app->loadEnvironmentFrom('.env.transmittal-v1-isolated-never-load');
        $app->afterBootstrapping(LoadConfiguration::class, function ($app): void {
            TransmittalV1Isolation::configure($app);
        });
        $app->afterBootstrapping(RegisterProviders::class, function ($app): void {
            TransmittalV1Isolation::fakeExternalEffects();
        });
        $app->make(Kernel::class)->bootstrap();
        TransmittalV1Isolation::assertBootedGuards($app);

        return $app;
    }
}
