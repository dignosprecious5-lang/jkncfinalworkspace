<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\CachedState;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class DealsMysqlTestCase extends TestCase
{
    private static bool $initialDealsSchemaVerifiedEmpty = false;

    private array $dealsDisposableStorageRoots = [];

    public function createApplication()
    {
        if (getenv('ORDO_DEALS_ISOLATED_TEST') !== '1' || getenv('APP_ENV') !== 'testing') {
            throw new RuntimeException('STOP: Deals tests require explicit isolated testing markers.');
        }

        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $temporaryRoot = realpath(sys_get_temp_dir());
        if ($temporaryRoot === false || ! is_dir($temporaryRoot)) {
            throw new RuntimeException('STOP: Temporary storage root is unavailable.');
        }

        $storageRoot = $temporaryRoot.DIRECTORY_SEPARATOR.'ordo-deals-test-'.bin2hex(random_bytes(16));
        if (file_exists($storageRoot) || is_link($storageRoot) || ! @mkdir($storageRoot, 0700, false)) {
            throw new RuntimeException('STOP: Could not create an exclusive test storage root.');
        }
        $this->dealsDisposableStorageRoots[] = $storageRoot;
        foreach (['app', 'app/private', 'app/public', 'framework', 'framework/cache', 'framework/cache/data', 'framework/sessions', 'framework/testing', 'framework/testing/disks', 'framework/views', 'logs'] as $relative) {
            $path = $storageRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (! @mkdir($path, 0700, false) || is_link($path) || realpath($path) !== $path) {
                throw new RuntimeException('STOP: Could not safely initialize isolated test storage.');
            }
        }
        if (is_link($storageRoot) || realpath($storageRoot) !== $storageRoot) {
            throw new RuntimeException('STOP: Test storage root verification failed.');
        }
        $app->useStoragePath($storageRoot);

        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        if (isset(CachedState::$cachedConfig) && isset($this->traitsUsedByTest[WithCachedConfig::class])) {
            $this->markConfigCached($app);
        }
        if (isset(CachedState::$cachedRoutes) && isset($this->traitsUsedByTest[WithCachedRoutes::class])) {
            $app->booting(fn () => $this->markRoutesCached($app));
        }

        $app->afterBootstrapping(LoadConfiguration::class, function ($application): void {
            $config = $application['config'];
            if (
                $config->get('app.env') !== 'testing' ||
                getenv('APP_KEY') === false ||
                $config->get('app.key') !== getenv('APP_KEY') ||
                $config->get('database.default') !== 'mysql' ||
                ! empty($config->get('database.connections.mysql.url')) ||
                ! empty($config->get('database.connections.mysql.unix_socket')) ||
                $config->get('database.connections.mysql.host') !== '127.0.0.1' ||
                (int) $config->get('database.connections.mysql.port') !== 13321 ||
                $config->get('database.connections.mysql.database') !== 'ordo_deals_regression_20261109_a1' ||
                $config->get('database.connections.mysql.username') !== 'ordo_deals_test_20261109_a1'
            ) {
                throw new RuntimeException('STOP: Deals test database configuration mismatch.');
            }
        });

        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
    }

    protected function assertDealsMysqlIsolationIdentity()
    {
        $config = $this->app['config'];
        $dbConfig = $config->get('database.connections.mysql', []);
        if (
            $config->get('app.env') !== 'testing' ||
            getenv('APP_KEY') === false ||
            $config->get('app.key') !== getenv('APP_KEY') ||
            $config->get('database.default') !== 'mysql' ||
            ($dbConfig['host'] ?? null) !== '127.0.0.1' ||
            (int) ($dbConfig['port'] ?? 0) !== 13321 ||
            ($dbConfig['database'] ?? null) !== 'ordo_deals_regression_20261109_a1' ||
            ($dbConfig['username'] ?? null) !== 'ordo_deals_test_20261109_a1' ||
            ! empty($dbConfig['url']) || ! empty($dbConfig['unix_socket'])
        ) {
            throw new RuntimeException('STOP: Deals MySQL test identity/configuration mismatch.');
        }

        return $this->assertDealsMysqlConnectionIdentity($this->app['db']->connection('mysql'));
    }

    protected function assertDealsMysqlConnectionIdentity($connection)
    {
        if (
            $connection->getDriverName() !== 'mysql' ||
            $connection->getDatabaseName() !== 'ordo_deals_regression_20261109_a1'
        ) {
            throw new RuntimeException('STOP: Actual connection is not the isolated Deals test schema.');
        }

        $identity = $connection->selectOne('SELECT @@port AS server_port, DATABASE() AS database_name, CURRENT_USER() AS authenticated_user');
        if (
            ! $identity ||
            (int) $identity->server_port !== 13321 ||
            $identity->database_name !== 'ordo_deals_regression_20261109_a1' ||
            ! in_array($identity->authenticated_user, ['ordo_deals_test_20261109_a1@localhost', 'ordo_deals_test_20261109_a1@127.0.0.1'], true)
        ) {
            throw new RuntimeException('STOP: Actual MySQL server identity is not the isolated Deals test target.');
        }

        $grants = $connection->select('SHOW GRANTS');
        foreach ($grants as $grantRow) {
            $grantValues = array_values((array) $grantRow);
            $grant = (string) ($grantValues[0] ?? '');
            if (preg_match('/\bON\s+\*\.\*/i', $grant) && ! preg_match('/\bGRANT\s+USAGE\s+ON\s+\*\.\*/i', $grant)) {
                throw new RuntimeException('STOP: Deals test account has global MySQL privileges.');
            }
            if (preg_match('/\bON\s+`([^`]+)`\.\*/i', $grant, $match) && $match[1] !== 'ordo_deals_regression_20261109_a1') {
                throw new RuntimeException('STOP: Deals test account has privileges on another schema.');
            }
        }

        if (! self::$initialDealsSchemaVerifiedEmpty) {
            $objects = $connection->selectOne('SELECT COUNT(*) AS object_count FROM information_schema.tables WHERE table_schema = DATABASE()');
            if ((int) ($objects->object_count ?? -1) !== 0) {
                throw new RuntimeException('STOP: Disposable Deals test schema was not empty before tests; no tables were changed.');
            }
            self::$initialDealsSchemaVerifiedEmpty = true;
        }

        return $connection;
    }

    protected function resetDealsMysqlFixtureTables(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
        if (! self::$initialDealsSchemaVerifiedEmpty) {
            throw new RuntimeException('STOP: Initial empty schema check was not completed.');
        }

        $connection = $this->app['db']->connection('mysql');
        $tables = $connection->select('SELECT table_name AS fixture_table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = ?', ['BASE TABLE']);
        $connection->statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($tables as $row) {
                $table = (string) ($row->fixture_table_name ?? '');
                if (preg_match('/\A[A-Za-z0-9_]+\z/D', $table) !== 1) {
                    throw new RuntimeException('STOP: Unexpected table identifier in isolated fixture schema: '.json_encode($table, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                $connection->getSchemaBuilder()->dropIfExists($table);
            }
        } finally {
            $connection->statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $schema = $connection->getSchemaBuilder();
        if (! $schema->hasTable('migrations')) {
            $schema->create('migrations', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        RefreshDatabaseState::$migrated = false;
    }

    protected function createBaseCatalogSchema(): void
    {
        $this->assertDealsMysqlIsolationIdentity();
        Schema::create('services', function (Blueprint $table): void {
            $table->id(); $table->string('service_id', 80)->nullable(); $table->string('service_name')->nullable();
            $table->text('service_description')->nullable(); $table->text('service_activity_output')->nullable();
            $table->longText('service_area')->nullable(); $table->longText('engagement_structure')->nullable();
            $table->decimal('price_fee', 15, 2)->nullable(); $table->string('tax_type')->nullable();
            $table->string('status')->nullable(); $table->longText('requirements')->nullable();
            $table->text('scope_of_work')->nullable(); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id(); $table->string('product_id', 80)->nullable(); $table->string('product_name')->nullable();
            $table->text('product_description')->nullable(); $table->text('product_inclusions')->nullable();
            $table->longText('product_area')->nullable(); $table->decimal('price', 15, 2)->nullable();
            $table->string('tax_type')->nullable(); $table->string('status')->nullable();
            $table->longText('linked_service_ids')->nullable(); $table->unsignedBigInteger('linked_service_id')->nullable();
            $table->longText('requirements')->nullable(); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            $tempRoot = realpath(sys_get_temp_dir());
            foreach ($this->dealsDisposableStorageRoots as $root) {
                if ($tempRoot === false || dirname($root) !== $tempRoot || ! preg_match('/^ordo-deals-test-[a-f0-9]{32}$/D', basename($root))) {
                    throw new RuntimeException('STOP: Unexpected isolated test storage path.');
                }
            }
            // Windows junction cleanup is intentionally left to a separately verified cleanup.
            $this->dealsDisposableStorageRoots = [];
        }
    }
}
