<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/** Process-local safeguards for the dedicated suite; never boot production. */
final class TransmittalV1Isolation
{
    private static ?array $state = null;

    public static function prepare(string $root): array
    {
        if (self::$state !== null) {
            self::assertEnvironment();
            return self::$state;
        }
        if (PHP_SAPI !== 'cli'
            || realpath((string) php_ini_loaded_file()) !== realpath(__DIR__.'/transmittal-v1.ini')
            || trim((string) php_ini_scanned_files()) !== '') {
            throw new RuntimeException('V1 isolation requires its dedicated PHP ini without additional ini scans.');
        }
        foreach (['auto_prepend_file', 'auto_append_file', 'opcache.preload'] as $setting) {
            if (trim((string) ini_get($setting)) !== '') {
                throw new RuntimeException('V1 isolation refuses PHP startup/preload overrides.');
            }
        }
        if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)
            || filter_var(ini_get('allow_url_include'), FILTER_VALIDATE_BOOLEAN)
            || filter_var(ini_get('enable_dl'), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('V1 isolation requires URL file access and dynamic extension loading disabled.');
        }
        foreach (['exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'pcntl_exec', 'mail',
            'fsockopen', 'pfsockopen', 'stream_socket_client', 'stream_socket_server',
            'socket_connect', 'socket_create', 'socket_sendto', 'curl_exec', 'curl_multi_exec',
            'dns_get_record', 'gethostbyname', 'gethostbynamel', 'gethostbyaddr', 'getmxrr', 'checkdnsrr'] as $function) {
            if (function_exists($function)) {
                throw new RuntimeException('V1 isolation refuses an enabled network/process transport: '.$function);
            }
        }
        $drivers = \PDO::getAvailableDrivers();
        sort($drivers);
        if ($drivers !== ['sqlite']) {
            throw new RuntimeException('V1 isolation requires SQLite as the only loaded PDO driver.');
        }
        foreach (['mysqli', 'pgsql', 'sqlsrv', 'odbc', 'ffi', 'sockets'] as $extension) {
            if (extension_loaded($extension)) {
                throw new RuntimeException('V1 isolation refuses external database or native transport extensions.');
            }
        }
        foreach (['pdo_sqlite', 'mbstring', 'dom', 'xml', 'xmlwriter', 'fileinfo', 'openssl'] as $extension) {
            if (! extension_loaded($extension)) {
                throw new RuntimeException('A required isolated test extension is missing: '.$extension);
            }
        }
        $root = realpath($root);
        if ($root === false || ! is_file($root.'/bootstrap/app.php')) {
            throw new RuntimeException('The isolated application root cannot be verified.');
        }
        foreach (glob($root.'/.env*') ?: [] as $file) {
            if (basename($file) !== '.env.example') {
                throw new RuntimeException('Remove environment-file access from the isolated worktree before testing; no contents were read.');
            }
        }
        foreach (['app', 'bootstrap', 'config', 'public', 'vendor'] as $directory) {
            $candidate = $root.'/'.$directory;
            if (file_exists($candidate) && ! self::inside($root, (string) realpath($candidate))) {
                throw new RuntimeException('An application directory resolves outside the isolated worktree.');
            }
        }
        $environment = array_merge(getenv() ?: [], $_SERVER, $_ENV);
        $allowedDatabase = ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_FOREIGN_KEYS' => 'true'];
        foreach ($environment as $name => $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }
            $name = strtoupper((string) $name);
            if (preg_match('/^(DB_|DATABASE_|MYSQL_|PGSQL_|SQLSRV_|SQLITE_|REDIS_)/', $name)) {
                if (! isset($allowedDatabase[$name]) || $value !== $allowedDatabase[$name]) {
                    throw new RuntimeException('External database override refused; variable name only: '.$name);
                }
            }
            if (preg_match('/^(APP_KEY|APP_PREVIOUS_KEYS|APP_BASE_PATH|APP_ENV_FILE|APP_STORAGE|LARAVEL_STORAGE_PATH|APP_CONFIG_CACHE|APP_PACKAGES_CACHE|APP_SERVICES_CACHE|APP_ROUTES_CACHE|APP_EVENTS_CACHE)$/', $name)) {
                throw new RuntimeException('Inherited key, application path or cache override refused: '.$name);
            }
            if (preg_match('/^(AWS_|BREVO_|OPENAI_|RESEND_|POSTMARK_|MAILGUN_|PUSHER_|REVERB_|GOOGLE_|GMAIL_|MESSENGER_|SLACK_|MAIL_USERNAME$|MAIL_PASSWORD$|MAIL_URL$|COMPOSER_AUTH$)/', $name)) {
                if (! str_ends_with($name, '_ENABLED') || ! in_array(strtolower($value), ['false', '0'], true)) {
                    throw new RuntimeException('Inherited external service configuration refused; variable name only: '.$name);
                }
            }
        }
        foreach (['http', 'https', 'ftp', 'ftps'] as $wrapper) {
            if (in_array($wrapper, stream_get_wrappers(), true) && ! stream_wrapper_unregister($wrapper)) {
                throw new RuntimeException('A network stream wrapper could not be disabled.');
            }
        }
        $temporary = realpath(sys_get_temp_dir());
        if ($temporary === false || str_starts_with($temporary, '\\\\') || str_starts_with($temporary, '//')) {
            throw new RuntimeException('A local temporary directory cannot be verified.');
        }
        $runtime = $temporary.'/jknc-transmittal-v1-'.bin2hex(random_bytes(12));
        if (file_exists($runtime)) {
            throw new RuntimeException('The isolated runtime destination already exists.');
        }
        $values = [
            'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)), 'APP_PREVIOUS_KEYS' => '',
            'APP_URL' => 'https://transmittal-v1.invalid',
            'APP_CONFIG_CACHE' => $runtime.'/cache/config.php',
            'APP_PACKAGES_CACHE' => $runtime.'/cache/packages.php',
            'APP_SERVICES_CACHE' => $runtime.'/cache/services.php',
            'APP_ROUTES_CACHE' => $runtime.'/cache/routes.php',
            'APP_EVENTS_CACHE' => $runtime.'/cache/events.php',
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_FOREIGN_KEYS' => 'true',
            'DB_URL' => '', 'DATABASE_URL' => '', 'DB_USERNAME' => '', 'DB_PASSWORD' => '',
            'DB_HOST' => '', 'DB_PORT' => '', 'DB_SOCKET' => '',
            'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'null', 'SESSION_DRIVER' => 'array',
            'CACHE_STORE' => 'array', 'BROADCAST_CONNECTION' => 'null', 'FILESYSTEM_DISK' => 'local',
            'LOG_CHANNEL' => 'null', 'GMAIL_ENABLED' => 'false', 'MESSENGER_ENABLED' => 'false',
            'MESSENGER_OUTBOUND_ENABLED' => 'false', 'MESSENGER_QUEUE_CONNECTION' => 'null',
        ];
        foreach ($values as $name => $value) {
            if (! putenv($name.'='.$value)) {
                throw new RuntimeException('An isolated process variable could not be enforced.');
            }
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        self::$state = ['root' => $root, 'runtime' => $runtime, 'environment' => $values];
        self::assertEnvironment();
        return self::$state;
    }

    public static function assertEnvironment(): void
    {
        if (self::$state === null) {
            throw new RuntimeException('Isolated preflight has not completed.');
        }
        foreach (self::$state['environment'] as $name => $value) {
            if (getenv($name) !== $value || ($_ENV[$name] ?? null) !== $value || ($_SERVER[$name] ?? null) !== $value) {
                throw new RuntimeException('Isolated process configuration drifted; variable name only: '.$name);
            }
        }
        if (array_intersect(['http', 'https', 'ftp', 'ftps'], stream_get_wrappers()) !== []) {
            throw new RuntimeException('A disabled network wrapper was re-enabled.');
        }
    }

    public static function createRuntimeDirectories(): void
    {
        self::assertEnvironment();
        foreach (['cache', 'storage/framework/cache/data', 'storage/framework/sessions',
            'storage/framework/views', 'storage/logs', 'storage/app/private', 'storage/app/public'] as $suffix) {
            $directory = self::$state['runtime'].'/'.$suffix;
            if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new RuntimeException('An isolated runtime directory could not be created.');
            }
            if (! self::inside(self::$state['runtime'], (string) realpath($directory))) {
                throw new RuntimeException('An isolated runtime directory resolves outside its root.');
            }
        }
    }

    public static function configure($app): void
    {
        self::assertEnvironment();
        $runtime = self::$state['runtime'];
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'url' => null,
            'prefix' => '', 'foreign_key_constraints' => true];
        $app['config']->set([
            'app.env' => 'testing', 'app.debug' => false, 'app.key' => self::$state['environment']['APP_KEY'],
            'database.default' => 'sqlite', 'database.connections' => ['sqlite' => $sqlite], 'database.redis' => [],
            'mail.default' => 'array', 'mail.mailers' => ['array' => ['transport' => 'array']],
            'queue.default' => 'null', 'queue.connections' => ['null' => ['driver' => 'null'], 'sync' => ['driver' => 'sync']],
            'broadcasting.default' => 'null', 'broadcasting.connections' => ['null' => ['driver' => 'null']],
            'cache.default' => 'array', 'cache.stores' => ['array' => ['driver' => 'array', 'serialize' => false]],
            'session.driver' => 'array', 'session.files' => $runtime.'/storage/framework/sessions',
            'filesystems.default' => 'local', 'filesystems.disks' => [
                'local' => ['driver' => 'local', 'root' => $runtime.'/storage/app/private'],
                'public' => ['driver' => 'local', 'root' => $runtime.'/storage/app/public', 'url' => 'https://transmittal-v1.invalid/storage'],
            ],
            'view.compiled' => $runtime.'/storage/framework/views', 'logging.default' => 'null',
            'services' => [], 'messaging.messenger.enabled' => false,
            'messaging.messenger.outbound_enabled' => false, 'messaging.messenger.queue_connection' => 'null',
            'ordo.brevo.api_key' => null,
        ]);
        // Bind a refusing HTTP client before application/package providers register.
        $app->singleton(\Illuminate\Http\Client\Factory::class, function () {
            $factory = new \Illuminate\Http\Client\Factory;
            $factory->preventStrayRequests();
            return $factory;
        });
        self::assertConfiguration($app);
    }

    public static function fakeExternalEffects(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Bus::fake();
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }

    public static function assertConfiguration($app): void
    {
        self::assertEnvironment();
        if ($app['config']->get('app.env') !== 'testing'
            || $app['config']->get('app.key') !== self::$state['environment']['APP_KEY']
            || $app['config']->get('database.default') !== 'sqlite') {
            throw new RuntimeException('The isolated application configuration was not retained.');
        }
        foreach ($app['config']->get('database.connections', []) as $connection) {
            if (($connection['driver'] ?? null) !== 'sqlite'
                || ($connection['database'] ?? null) !== ':memory:' || ! empty($connection['url'])) {
                throw new RuntimeException('A non-memory database connection was introduced during startup.');
            }
        }
    }

    public static function assertBootedGuards($app): void
    {
        self::assertConfiguration($app);
        foreach (['mail.default' => 'array', 'queue.default' => 'null',
            'broadcasting.default' => 'null', 'cache.default' => 'array', 'session.driver' => 'array'] as $key => $expected) {
            if ($app['config']->get($key) !== $expected) {
                throw new RuntimeException('An isolated service setting changed during startup: '.$key);
            }
        }
        foreach ([
            \Illuminate\Support\Facades\Mail::class => \Illuminate\Support\Testing\Fakes\MailFake::class,
            \Illuminate\Support\Facades\Notification::class => \Illuminate\Support\Testing\Fakes\NotificationFake::class,
            \Illuminate\Support\Facades\Bus::class => \Illuminate\Support\Testing\Fakes\BusFake::class,
            \Illuminate\Support\Facades\Queue::class => \Illuminate\Support\Testing\Fakes\QueueFake::class,
        ] as $facade => $fake) {
            if (! ($facade::getFacadeRoot() instanceof $fake)) {
                throw new RuntimeException('An external-effect fake was replaced during application startup.');
            }
        }
        foreach ($app['config']->get('filesystems.disks', []) as $disk) {
            if (($disk['driver'] ?? null) !== 'local'
                || ! self::inside(self::$state['runtime'], (string) ($disk['root'] ?? ''))) {
                throw new RuntimeException('A filesystem disk escaped the temporary isolated runtime.');
            }
        }
    }

    private static function inside(string $root, string $path): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/').'/';
        $path = rtrim(str_replace('\\', '/', $path), '/').'/';
        return str_starts_with(strtolower($path), strtolower($root));
    }
}
