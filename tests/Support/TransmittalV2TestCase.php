<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\Support\TransmittalV2Isolation;

abstract class TestCase extends LaravelTestCase
{
    public function createApplication()
    {
        $root = dirname(__DIR__, 2);
        $state = TransmittalV2Isolation::prepare($root);
        TransmittalV2Isolation::assertEnvironment();

        $app = require $root.'/bootstrap/app.php';
        TransmittalV2Isolation::prepareApplication($app);
        $app->afterBootstrapping(LoadConfiguration::class, function ($app): void {
            TransmittalV2Isolation::configure($app);
        });
        $app->afterBootstrapping(RegisterProviders::class, function ($app): void {
            TransmittalV2Isolation::fakeExternalEffects($app);
        });
        $app->make(Kernel::class)->bootstrap();
        TransmittalV2Isolation::assertBootedGuards($app);

        return $app;
    }

    protected function setUp(): void
    {
        TransmittalV2Isolation::assertNoDatabaseLifecycleTraits(static::class);
        parent::setUp();
        TransmittalV2Isolation::assertBootedGuards($this->app);

        $connection = DB::connection();
        self::assertSame(TransmittalV2Isolation::CONNECTION, $connection->getName());
        self::assertSame(':memory:', $connection->getDatabaseName());
        self::assertSame('sqlite', $connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
        self::assertSame(1, (int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys);
    }
}
