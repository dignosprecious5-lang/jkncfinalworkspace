<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/**
 * Definitions only. No guard, environment change, directory creation or Laravel boot runs on inclusion.
 * The dedicated runner calls prepare before Composer autoloading.
 * The V2 TestCase checks its traits before boot, calls prepareApplication before kernel
 * bootstrap, configure after LoadConfiguration, fakeExternalEffects after RegisterProviders,
 * and assertBootedGuards after startup and fixture setup. V1 infrastructure is not reused at runtime.
 */
final class TransmittalV2Isolation
{
    public const CONNECTION = 'transmittal_v2_regression';

    private static ?array $state = null;

    public static function prepare(string $root): array
    {
        if (! defined('TRANSMITTAL_V2_LAUNCHER') || TRANSMITTAL_V2_LAUNCHER !== true) {
            throw new RuntimeException('V2 isolation requires its dedicated launcher.');
        }
        if (self::$state !== null) {
            self::assertEnvironment();
            return self::$state;
        }
        self::assertPhpRestrictions();
        $root = realpath($root);
        if ($root === false || $root !== realpath(dirname(__DIR__, 2))
            || ! is_file($root.'/bootstrap/app.php')) {
            throw new RuntimeException('V2 application root cannot be verified.');
        }
        if (in_array(realpath($root.'/bootstrap/app.php'), array_map('realpath', get_included_files()), true)) {
            throw new RuntimeException('V2 preparation must precede Laravel application loading.');
        }
        foreach (glob($root.'/.env*') ?: [] as $file) {
            if (basename($file) !== '.env.example') {
                throw new RuntimeException('V2 refuses environment files; no contents were read.');
            }
        }
        foreach (['app', 'bootstrap', 'config', 'public', 'vendor'] as $directory) {
            $resolved = realpath($root.'/'.$directory);
            if ($resolved === false || ! self::inside($root, $resolved)) {
                throw new RuntimeException('V2 application directory escapes the verified worktree.');
            }
        }
        // Inspect each source independently so an unsafe value cannot be masked by another source.
        foreach ([getenv() ?: [], $_SERVER, $_ENV] as $environment) {
            foreach ($environment as $name => $value) {
                if (! is_string($name) || $value === '' || $value === null) {
                    continue;
                }
                $name = strtoupper($name);
                if (preg_match('/^(DB_|DATABASE_|MYSQL_|PGSQL_|SQLSRV_|SQLITE_|REDIS_)/', $name)) {
                    $allowed = [
                        'DB_CONNECTION' => ['sqlite', self::CONNECTION],
                        'DB_DATABASE' => [':memory:'], 'DB_FOREIGN_KEYS' => ['true'],
                    ];
                    if (! isset($allowed[$name]) || ! in_array($value, $allowed[$name], true)) {
                        throw new RuntimeException('V2 refuses a database override; name only: '.$name);
                    }
                }
                if (($name === 'APP_ENV' && $value !== 'testing')
                    || ($name === 'APP_DEBUG' && $value !== 'false')) {
                    throw new RuntimeException('V2 refuses a non-testing environment/debug override.');
                }
                if (preg_match('/^(APP_KEY|APP_PREVIOUS_KEYS|APP_BASE_PATH|APP_ENV_FILE|APP_STORAGE|LARAVEL_STORAGE_PATH|APP_CONFIG_CACHE|APP_PACKAGES_CACHE|APP_SERVICES_CACHE|APP_ROUTES_CACHE|APP_EVENTS_CACHE|APP_BOOTSTRAP_PATH|COMPOSER_VENDOR_DIR|COMPOSER_AUTH)$/', $name)) {
                    throw new RuntimeException('V2 refuses inherited keys, loader paths or caches; name only: '.$name);
                }
                if (preg_match('/^(AWS_|AZURE_|BREVO_|OPENAI_|RESEND_|POSTMARK_|MAILGUN_|PUSHER_|REVERB_|GOOGLE_|GMAIL_|MESSENGER_|SLACK_|MAIL_|SENTRY_|BUGSNAG_)/', $name)) {
                    $disabled = is_string($value) && str_ends_with($name, '_ENABLED')
                        && in_array(strtolower($value), ['false', '0'], true);
                    if (! $disabled && ! ($name === 'MAIL_MAILER' && $value === 'array')) {
                        throw new RuntimeException('V2 refuses inherited service configuration; name only: '.$name);
                    }
                }
            }
        }
        foreach (['http', 'https', 'ftp', 'ftps'] as $wrapper) {
            if (in_array($wrapper, stream_get_wrappers(), true) && ! stream_wrapper_unregister($wrapper)) {
                throw new RuntimeException('V2 could not disable a network stream wrapper.');
            }
        }
        $temporary = realpath(sys_get_temp_dir());
        if ($temporary === false || str_starts_with($temporary, '\\\\')
            || str_starts_with($temporary, '//') || self::inside($root, $temporary)
            || self::normalized($temporary) === self::normalized($root)) {
            throw new RuntimeException('V2 requires a verified local temporary root outside the worktree.');
        }
        $runtime = $temporary.'/jknc-transmittal-v2-'.bin2hex(random_bytes(12));
        if (file_exists($runtime) || is_link($runtime)) {
            throw new RuntimeException('V2 temporary runtime already exists.');
        }
        $values = [
            'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)), 'APP_PREVIOUS_KEYS' => '',
            'APP_URL' => 'https://transmittal-v2.invalid',
            'APP_CONFIG_CACHE' => $runtime.'/cache/config.php',
            'APP_PACKAGES_CACHE' => $runtime.'/cache/packages.php',
            'APP_SERVICES_CACHE' => $runtime.'/cache/services.php',
            'APP_ROUTES_CACHE' => $runtime.'/cache/routes.php',
            'APP_EVENTS_CACHE' => $runtime.'/cache/events.php',
            'DB_CONNECTION' => self::CONNECTION, 'DB_DATABASE' => ':memory:', 'DB_FOREIGN_KEYS' => 'true',
            'DB_URL' => '', 'DATABASE_URL' => '', 'DB_USERNAME' => '', 'DB_PASSWORD' => '',
            'DB_HOST' => '', 'DB_PORT' => '', 'DB_SOCKET' => '',
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'null', 'SESSION_DRIVER' => 'array',
            'CACHE_STORE' => 'array', 'BROADCAST_CONNECTION' => 'null', 'FILESYSTEM_DISK' => 'local',
            'LOG_CHANNEL' => 'null', 'GMAIL_ENABLED' => 'false', 'MESSENGER_ENABLED' => 'false',
            'MESSENGER_OUTBOUND_ENABLED' => 'false', 'MESSENGER_QUEUE_CONNECTION' => 'null',
        ];
        foreach ($values as $name => $value) {
            if (! putenv($name.'='.$value)) {
                throw new RuntimeException('V2 could not enforce an isolated process variable.');
            }
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        self::$state = [
            'root' => $root,
            'temporary' => $temporary,
            'runtime' => $runtime,
            'runtime_root_initialized' => false,
            'environment' => $values,
        ];
        self::assertEnvironment();
        return self::$state;
    }

    private static function assertPhpRestrictions(): void
    {
        if (PHP_SAPI !== 'cli'
            || realpath((string) php_ini_loaded_file()) !== realpath(__DIR__.'/transmittal-v2.ini')
            || trim((string) php_ini_scanned_files()) !== '') {
            throw new RuntimeException('V2 requires its dedicated ini without additional ini scans.');
        }
        foreach (['auto_prepend_file', 'auto_append_file', 'opcache.preload'] as $setting) {
            if (trim((string) ini_get($setting)) !== '') {
                throw new RuntimeException('V2 refuses startup/preload overrides.');
            }
        }
        foreach (['allow_url_fopen', 'allow_url_include', 'enable_dl'] as $setting) {
            if (filter_var(ini_get($setting), FILTER_VALIDATE_BOOLEAN)) {
                throw new RuntimeException('V2 refuses URL file access or dynamic extension loading.');
            }
        }
        foreach (['exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'pcntl_exec', 'mail',
            'fsockopen', 'pfsockopen', 'stream_socket_client', 'stream_socket_server',
            'socket_connect', 'socket_create', 'socket_sendto', 'curl_exec', 'curl_multi_exec',
            'dns_get_record', 'gethostbyname', 'gethostbynamel', 'gethostbyaddr', 'getmxrr', 'checkdnsrr',
            'stream_socket_accept', 'stream_socket_pair', 'socket_bind', 'socket_listen', 'socket_accept',
            'socket_send', 'socket_write', 'ftp_connect', 'ftp_ssl_connect'] as $function) {
            if (function_exists($function)) {
                throw new RuntimeException('V2 refuses enabled native network/process transports.');
            }
        }
        $drivers = \PDO::getAvailableDrivers();
        sort($drivers);
        if ($drivers !== ['sqlite']) {
            throw new RuntimeException('V2 requires SQLite as the only PDO driver.');
        }
        foreach (['mysqli', 'pgsql', 'sqlsrv', 'odbc', 'ffi', 'sockets', 'com_dotnet'] as $extension) {
            if (extension_loaded($extension)) {
                throw new RuntimeException('V2 refuses external database/native transport extensions.');
            }
        }
        foreach (['pdo_sqlite', 'mbstring', 'dom', 'xml', 'xmlwriter', 'fileinfo', 'openssl'] as $extension) {
            if (! extension_loaded($extension)) {
                throw new RuntimeException('A required isolated V2 extension is missing.');
            }
        }
    }

    public static function assertEnvironment(): void
    {
        if (self::$state === null) {
            throw new RuntimeException('V2 preparation has not completed.');
        }
        self::assertPhpRestrictions();
        foreach (self::$state['environment'] as $name => $value) {
            if (getenv($name) !== $value || ($_ENV[$name] ?? null) !== $value || ($_SERVER[$name] ?? null) !== $value) {
                throw new RuntimeException('V2 process configuration drifted; name only: '.$name);
            }
        }
        if (array_intersect(['http', 'https', 'ftp', 'ftps'], stream_get_wrappers()) !== []) {
            throw new RuntimeException('A disabled V2 network wrapper was re-enabled.');
        }
        $root = self::normalized(self::$state['root']);
        foreach (get_included_files() as $file) {
            $file = self::normalized($file);
            if (str_starts_with($file, $root.'/database/migrations/')
                || str_starts_with($file, $root.'/database/seeders/')) {
                throw new RuntimeException('V2 refuses loading source migrations or seeders.');
            }
        }
    }

    public static function createRuntimeDirectories(): void
    {
        self::assertEnvironment();
        self::createRuntimeRoot();
        foreach (['cache', 'storage/framework/cache/data', 'storage/framework/sessions',
            'storage/framework/views', 'storage/framework/testing/disks', 'storage/logs',
            'storage/app/private', 'storage/app/public'] as $suffix) {
            $directory = self::$state['runtime'].'/'.$suffix;
            self::createRuntimeDirectory($directory);
        }
    }

    private static function createRuntimeRoot(): void
    {
        $temporary = self::$state['temporary'];
        $runtime = self::$state['runtime'];
        self::assertTemporaryAndRuntimeParent($temporary, $runtime);

        if (self::$state['runtime_root_initialized']) {
            self::assertCanonicalRuntimeRoot($temporary, $runtime);
            return;
        }

        if (file_exists($runtime) || is_link($runtime)) {
            throw new RuntimeException('V2 runtime root already exists before initialization.');
        }

        if (! mkdir($runtime, 0700, false)) {
            throw new RuntimeException('V2 runtime root creation failed safely.');
        }

        self::assertCanonicalRuntimeRoot($temporary, $runtime);
        self::$state['runtime_root_initialized'] = true;
    }

    private static function assertTemporaryAndRuntimeParent(string $temporary, string $runtime): void
    {
        $temporaryCanonical = realpath($temporary);
        $temporaryPath = self::normalized($temporary);
        $runtimePath = self::normalized($runtime);

        if ($temporaryCanonical === false || ! is_dir($temporary) || is_link($temporary)
            || self::normalized($temporaryCanonical) !== $temporaryPath
            || self::normalized(dirname($runtimePath)) !== $temporaryPath
            || ! self::inside($temporaryPath, $runtimePath)) {
            throw new RuntimeException('V2 temporary root or runtime parent is not canonical.');
        }
    }

    private static function assertCanonicalRuntimeRoot(string $temporary, string $runtime): void
    {
        self::assertTemporaryAndRuntimeParent($temporary, $runtime);
        $runtimeCanonical = realpath($runtime);

        if ($runtimeCanonical === false || ! is_dir($runtime) || is_link($runtime)
            || self::normalized($runtimeCanonical) !== self::normalized($runtime)
            || ! self::inside(self::normalized($temporary), self::normalized($runtimeCanonical))) {
            throw new RuntimeException('V2 runtime root is redirected or outside the temporary root.');
        }
    }

    public static function prepareApplication($app): void
    {
        self::createRuntimeDirectories();
        if (realpath($app->basePath()) !== self::$state['root']) {
            throw new RuntimeException('V2 application base path changed.');
        }
        $app->addAbsoluteCachePathPrefix(self::$state['runtime'].'/cache/');
        $app->useStoragePath(self::$state['runtime'].'/storage');
        $app->loadEnvironmentFrom('.env.transmittal-v2-isolated-never-load');
        self::assertApplicationPaths($app);
    }

    private static function assertApplicationPaths($app): void
    {
        self::assertEnvironment();
        foreach ([
            'getCachedConfigPath' => 'APP_CONFIG_CACHE', 'getCachedPackagesPath' => 'APP_PACKAGES_CACHE',
            'getCachedServicesPath' => 'APP_SERVICES_CACHE', 'getCachedRoutesPath' => 'APP_ROUTES_CACHE',
            'getCachedEventsPath' => 'APP_EVENTS_CACHE',
        ] as $method => $variable) {
            self::assertRuntimePath($app->$method());
            if (self::normalized($app->$method()) !== self::normalized(self::$state['environment'][$variable])) {
                throw new RuntimeException('A V2 cache path escaped its expected temporary destination.');
            }
        }
        self::assertRuntimePath($app->storagePath(), true);
        if (self::normalized($app->storagePath()) !== self::normalized(self::$state['runtime'].'/storage')) {
            throw new RuntimeException('V2 application storage path escaped its temporary destination.');
        }
    }

    public static function configure($app): void
    {
        self::assertApplicationPaths($app);
        $runtime = self::$state['runtime'];
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'url' => null,
            'prefix' => '', 'foreign_key_constraints' => true];
        $app['config']->set([
            'app.env' => 'testing', 'app.debug' => false, 'app.key' => self::$state['environment']['APP_KEY'],
            'database.default' => self::CONNECTION,
            'database.connections' => [self::CONNECTION => $sqlite], 'database.redis' => [],
            'mail.default' => 'array', 'mail.mailers' => ['array' => ['transport' => 'array']],
            'queue.default' => 'null', 'queue.connections' => ['null' => ['driver' => 'null']],
            'broadcasting.default' => 'null', 'broadcasting.connections' => ['null' => ['driver' => 'null']],
            'cache.default' => 'array', 'cache.stores' => ['array' => ['driver' => 'array', 'serialize' => false]],
            'session.driver' => 'array', 'session.files' => $runtime.'/storage/framework/sessions',
            'filesystems.default' => 'local', 'filesystems.disks' => [
                'local' => ['driver' => 'local', 'root' => $runtime.'/storage/app/private'],
                'public' => ['driver' => 'local', 'root' => $runtime.'/storage/app/public', 'url' => 'https://transmittal-v2.invalid/storage'],
            ],
            'view.compiled' => $runtime.'/storage/framework/views', 'logging.default' => 'null',
            'services' => [], 'messaging.messenger.enabled' => false,
            'messaging.messenger.outbound_enabled' => false, 'messaging.messenger.queue_connection' => 'null',
            'ordo.brevo.api_key' => null,
        ]);
        self::installRefusingTransports($app);
        self::installMigrationGuards($app);
        self::assertConfiguration($app);
    }

    private static function installRefusingTransports($app): void
    {
        $http = new \Illuminate\Http\Client\Factory;
        $http->fake([]);
        $http->preventStrayRequests();
        $process = new \Illuminate\Process\Factory;
        $process->fake([]);
        $process->preventStrayProcesses();
        $app->instance(\Illuminate\Http\Client\Factory::class, $http);
        $app->instance(\Illuminate\Process\Factory::class, $process);
        \Illuminate\Support\Facades\Http::clearResolvedInstance(\Illuminate\Http\Client\Factory::class);
        \Illuminate\Support\Facades\Process::clearResolvedInstance(\Illuminate\Process\Factory::class);
        self::$state['http'] = $http;
        self::$state['process'] = $process;
    }

    private static function installMigrationGuards($app): void
    {
        if ((self::$state['migration_guard_app'] ?? null) === $app) {
            self::assertMigrationGuards($app);
            return;
        }

        $sentinel = 'transmittal.v2.migration_guard_sentinel';
        $services = [
            'migrator', 'migration.repository', 'migration.creator',
            \Illuminate\Database\Migrations\Migrator::class,
            \Illuminate\Database\Migrations\DatabaseMigrationRepository::class,
            \Illuminate\Database\Migrations\MigrationRepositoryInterface::class,
            \Illuminate\Database\Migrations\MigrationCreator::class,
            \Illuminate\Database\Seeder::class,
        ];
        $commands = [
            \Illuminate\Database\Console\Migrations\MigrateCommand::class,
            \Illuminate\Database\Console\Migrations\FreshCommand::class,
            \Illuminate\Database\Console\Migrations\RefreshCommand::class,
            \Illuminate\Database\Console\Migrations\ResetCommand::class,
            \Illuminate\Database\Console\Migrations\RollbackCommand::class,
            \Illuminate\Database\Console\Migrations\InstallCommand::class,
            \Illuminate\Database\Console\Migrations\MigrateMakeCommand::class,
            \Illuminate\Database\Console\Seeds\SeedCommand::class,
            \Illuminate\Database\Console\Seeds\SeederMakeCommand::class,
            \Illuminate\Database\Console\WipeCommand::class,
        ];
        $protected = [...$services, ...$commands, $sentinel];
        $denial = new RuntimeException(
            'V2 migration, seeding, and database-destructive operations are prohibited.'
        );

        // This global callback remains registered if providers later rebind these identifiers.
        // Laravel loads a deferred provider before firing container resolution callbacks; this
        // callback then refuses the requested identifier before its binding is resolved.
        $app->beforeResolving(static function ($abstract, $parameters, $container) use ($protected, $denial, $app): void {
            if ($container !== $app) {
                throw $denial;
            }

            foreach ($protected as $identifier) {
                if ($abstract === $identifier || $abstract === $container->getAlias($identifier)) {
                    throw $denial;
                }
            }
        });

        $blocked = static function (...$arguments) use ($denial): never {
            throw $denial;
        };
        foreach ([...$services, ...$commands] as $binding) {
            $app->bind($binding, $blocked);
        }

        // Secondary protection where Laravel emits this event; container denial is primary.
        $app['events']->listen(\Illuminate\Console\Events\CommandStarting::class, $blocked);
        self::$state['migration_guard_app'] = $app;
        self::$state['migration_guard_denial'] = $denial;
        self::$state['migration_guard_sentinel'] = $sentinel;
        self::assertMigrationGuards($app);
    }

    private static function assertMigrationGuards($app): void
    {
        if ((self::$state['migration_guard_app'] ?? null) !== $app) {
            throw new RuntimeException('V2 migration guards are not registered on this application.');
        }

        // The sentinel is refused by the same callback; no migration or command is resolved.
        try {
            $app->make(self::$state['migration_guard_sentinel']);
        } catch (\Throwable $exception) {
            if ($exception === self::$state['migration_guard_denial']) {
                return;
            }
            throw new RuntimeException('V2 migration guard sentinel failed unexpectedly.');
        }

        throw new RuntimeException('V2 migration guard callback did not refuse its sentinel.');
    }

    public static function assertNoDatabaseLifecycleTraits(string $testClass): void
    {
        $pending = [$testClass, ...array_values(class_parents($testClass) ?: [])];
        $seen = [];
        $blocked = [
            'Illuminate\\Foundation\\Testing\\RefreshDatabase',
            'Illuminate\\Foundation\\Testing\\LazilyRefreshDatabase',
            'Illuminate\\Foundation\\Testing\\DatabaseMigrations',
            'Illuminate\\Foundation\\Testing\\DatabaseTruncation',
            'Illuminate\\Foundation\\Testing\\WithCachedConfig',
            'Illuminate\\Foundation\\Testing\\WithCachedRoutes',
        ];
        while ($pending !== []) {
            $class = array_pop($pending);
            if (isset($seen[$class])) {
                continue;
            }
            $seen[$class] = true;
            if (in_array($class, $blocked, true)) {
                throw new RuntimeException('V2 refuses automatic database lifecycle or shared-cache traits.');
            }
            foreach (class_uses($class) ?: [] as $trait) {
                $pending[] = $trait;
            }
        }
    }

    public static function fakeExternalEffects($app): void
    {
        self::assertConfiguration($app);
        self::installRefusingTransports($app);
        self::installMigrationGuards($app);
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Bus::fake();
        \Illuminate\Support\Facades\Queue::fake();
    }

    public static function assertConfiguration($app): void
    {
        self::assertApplicationPaths($app);
        $config = $app['config'];
        $connections = $config->get('database.connections', []);
        if ($config->get('app.env') !== 'testing' || $config->get('app.debug') !== false
            || $config->get('app.key') !== self::$state['environment']['APP_KEY']
            || $config->get('database.default') !== self::CONNECTION
            || array_keys($connections) !== [self::CONNECTION]) {
            throw new RuntimeException('V2 application/database isolation changed.');
        }
        $connection = $connections[self::CONNECTION];
        if (($connection['driver'] ?? null) !== 'sqlite' || ($connection['database'] ?? null) !== ':memory:'
            || ! empty($connection['url']) || ! empty($connection['read']) || ! empty($connection['write'])
            || ! empty($config->get('database.redis', []))) {
            throw new RuntimeException('V2 refuses external, file-backed or alternate database targets.');
        }
        foreach (['mail.default' => 'array', 'queue.default' => 'null', 'broadcasting.default' => 'null',
            'cache.default' => 'array', 'session.driver' => 'array', 'logging.default' => 'null'] as $key => $expected) {
            if ($config->get($key) !== $expected) {
                throw new RuntimeException('A V2 external-effect backend changed.');
            }
        }
        $disks = $config->get('filesystems.disks', []);
        if (array_keys($disks) !== ['local', 'public']) {
            throw new RuntimeException('V2 refuses additional filesystem disks.');
        }
        foreach ($disks as $disk) {
            self::assertRuntimePath((string) ($disk['root'] ?? ''), true);
            if (($disk['driver'] ?? null) !== 'local' || ! is_dir((string) ($disk['root'] ?? ''))) {
                throw new RuntimeException('A V2 disk escaped the temporary local runtime.');
            }
        }
        foreach (['session.files', 'view.compiled'] as $key) {
            self::assertRuntimePath((string) $config->get($key), true);
        }
        self::assertMigrationGuards($app);
    }

    public static function assertBootedGuards($app): void
    {
        self::assertConfiguration($app);
        foreach ([
            \Illuminate\Support\Facades\Mail::class => \Illuminate\Support\Testing\Fakes\MailFake::class,
            \Illuminate\Support\Facades\Notification::class => \Illuminate\Support\Testing\Fakes\NotificationFake::class,
            \Illuminate\Support\Facades\Bus::class => \Illuminate\Support\Testing\Fakes\BusFake::class,
            \Illuminate\Support\Facades\Queue::class => \Illuminate\Support\Testing\Fakes\QueueFake::class,
        ] as $facade => $fake) {
            if (! ($facade::getFacadeRoot() instanceof $fake)) {
                throw new RuntimeException('A V2 external-effect fake was replaced.');
            }
        }
        $http = \Illuminate\Support\Facades\Http::getFacadeRoot();
        $process = \Illuminate\Support\Facades\Process::getFacadeRoot();
        if ($http !== (self::$state['http'] ?? null) || ! $http->preventingStrayRequests()
            || $process !== (self::$state['process'] ?? null) || ! $process->isRecording()
            || ! $process->preventingStrayProcesses()) {
            throw new RuntimeException('V2 HTTP/process deny-by-default protection changed.');
        }
    }

    private static function normalized(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, "\0")
            || preg_match('~(^|/)\.\.(/|$)~', $path)
            || preg_match('~^(?:/{2}|[A-Za-z]:[^/])~', $path)) {
            throw new RuntimeException('V2 refuses traversing, UNC, device, or drive-relative paths.');
        }

        $drive = '';
        if (preg_match('~^([A-Za-z]:)(/.*)?$~', $path, $matches)) {
            $drive = strtolower($matches[1]);
            $path = $matches[2] ?? '/';
        } elseif (! str_starts_with($path, '/')) {
            throw new RuntimeException('V2 requires absolute paths.');
        }

        $parts = [];
        foreach (explode('/', $path) as $component) {
            if ($component !== '' && $component !== '.') {
                $parts[] = $component;
            }
        }

        return strtolower(($drive !== '' ? $drive.'/' : '/').implode('/', $parts));
    }

    private static function inside(string $root, string $path): bool
    {
        $root = self::normalized($root);
        $path = self::normalized($path);
        return $path === $root || str_starts_with($path, str_ends_with($root, '/') ? $root : $root.'/');
    }

    private static function assertRuntimePath(string $path, bool $mustExist = false): void
    {
        $temporary = self::normalized(self::$state['temporary']);
        $runtime = self::normalized(self::$state['runtime']);
        $path = self::normalized($path);
        $temporaryCanonical = realpath(self::$state['temporary']);

        if ($temporaryCanonical === false || is_link(self::$state['temporary'])
            || self::normalized($temporaryCanonical) !== $temporary
            || ! self::inside($temporary, $runtime)
            || self::normalized(dirname($runtime)) !== $temporary
            || ! self::inside($runtime, $path)
            || ! self::$state['runtime_root_initialized']) {
            throw new RuntimeException('V2 path is outside its initialized temporary runtime.');
        }

        self::assertCanonicalRuntimeRoot(self::$state['temporary'], self::$state['runtime']);
        $relative = ltrim(substr($path, strlen($runtime)), '/');
        $cursor = self::$state['runtime'];
        foreach ($relative === '' ? [] : explode('/', $relative) as $component) {
            $cursor = rtrim($cursor, '\\/').DIRECTORY_SEPARATOR.$component;
            if (is_link($cursor)) {
                throw new RuntimeException('V2 refuses linked runtime path components.');
            }
            if (! file_exists($cursor)) {
                break;
            }
            $resolved = realpath($cursor);
            if ($resolved === false || self::normalized($resolved) !== self::normalized($cursor)
                || ! self::inside($runtime, self::normalized($resolved))) {
                throw new RuntimeException('V2 existing path component escapes the runtime.');
            }
        }

        if ($mustExist) {
            $resolved = realpath($path);
            if (! is_dir($path) || $resolved === false || self::normalized($resolved) !== $path
                || ! self::inside($runtime, self::normalized($resolved))) {
                throw new RuntimeException('V2 required runtime directory is not canonical.');
            }
        }
    }

    private static function createRuntimeDirectory(string $directory): void
    {
        self::assertRuntimePath($directory);
        if (is_dir($directory)) {
            self::assertRuntimePath($directory, true);
            return;
        }

        $parent = dirname($directory);
        if (self::normalized($parent) !== self::normalized(self::$state['runtime'])) {
            self::createRuntimeDirectory($parent);
        }
        self::assertRuntimePath($directory);
        $parentCanonical = realpath($parent);
        if (! is_dir($parent) || $parentCanonical === false
            || self::normalized($parentCanonical) !== self::normalized($parent)
            || ! self::inside(self::$state['runtime'], self::normalized($parentCanonical))
            || ! mkdir($directory, 0700, false)) {
            throw new RuntimeException('V2 temporary directory creation failed safely.');
        }
        self::assertRuntimePath($directory, true);
    }
}
