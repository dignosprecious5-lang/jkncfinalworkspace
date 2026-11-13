<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Testing\TestResponse;

if (PHP_SAPI !== 'cli' || ! defined('TRANSMITTAL_OCR_MOCKED_BOOTSTRAPPED')) {
    throw new \RuntimeException('The OCR TestCase requires the isolated mocked bootstrap.');
}

// Force the real CSRF token comparison even while Laravel reports unit-test mode.
final class TransmittalOcrCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests() { return false; }
}

abstract class TransmittalOcrTestCase extends TestCase
{
    private string $csrfToken;

    public function createApplication()
    {
        $root = dirname(__DIR__, 2);
        $state = TransmittalOcrIsolation::prepare($root);
        TransmittalOcrIsolation::createRuntimeDirectories();
        TransmittalOcrIsolation::assertDouble();
        $app = require $root.'/bootstrap/app.php';
        $app->addAbsoluteCachePathPrefix($state['runtime'].'/cache/');
        $app->useStoragePath($state['runtime'].'/storage');
        $app->loadEnvironmentFrom('.env.transmittal-ocr-never-load');
        $app->afterBootstrapping(LoadConfiguration::class, function ($app): void {
            TransmittalOcrIsolation::configure($app);
            $app->bind(ValidateCsrfToken::class, TransmittalOcrCsrfToken::class);
        });
        $app->afterBootstrapping(RegisterProviders::class, function (): void {
            TransmittalOcrIsolation::fakeExternalEffects();
        });
        $app->make(Kernel::class)->bootstrap();
        TransmittalOcrIsolation::assertBootedGuards($app);
        if (! ($app->make(ValidateCsrfToken::class) instanceof TransmittalOcrCsrfToken)) {
            throw new \RuntimeException('The explicit CSRF enforcement binding was replaced.');
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        TransmittalOcrProcessDouble::reset();
        $this->csrfToken = bin2hex(random_bytes(24));
        $this->withSession(['_token' => $this->csrfToken]);
        // Synthetic schema only. No migrations, RefreshDatabase, client records, or seeders.
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email');
            $table->string('role'); $table->boolean('must_change_password')->default(false);
        });
        foreach (['transmittals', 'transmittal_items', 'transmittal_attachments', 'transmittal_receipts'] as $table) {
            Schema::create($table, function (Blueprint $definition): void {
                $definition->id(); $definition->string('synthetic_marker');
            });
            DB::table($table)->insert(['synthetic_marker' => 'OCR-SYNTHETIC-SENTINEL']);
        }
        config(['transmittal_ocr.binary' => PHP_BINARY, 'transmittal_ocr.pdf_binary' => PHP_BINARY]);
    }

    protected function actor(string $role = 'Admin', int $id = 1, bool $mustChangePassword = false): User
    {
        $user = new User;
        $user->forceFill(['id' => $id, 'name' => 'Synthetic OCR User', 'email' => 'ocr-'.$id.'@example.test',
            'role' => $role, 'must_change_password' => $mustChangePassword, 'is_active' => true]);
        $user->exists = true;
        $this->actingAs($user);
        return $user;
    }

    private function businessSnapshot(): array
    {
        $snapshot = [];
        foreach (['transmittals', 'transmittal_items', 'transmittal_attachments', 'transmittal_receipts'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        return $snapshot;
    }

    protected function ocr(?UploadedFile $file = null, ?string $csrf = null): TestResponse
    {
        TransmittalOcrIsolation::assertBootedGuards($this->app);
        $before = $this->businessSnapshot();
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            // MakesHttpRequests invokes the Laravel HTTP kernel in this PHP process.
            $response = $this->call('POST', '/transmittal/ocr/analyze', [], [],
                $file === null ? [] : ['document' => $file],
                ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $csrf ?? $this->csrfToken]);
        } finally {
            $queries = $connection->getQueryLog();
            $connection->disableQueryLog();
        }
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(insert|update|delete|replace|create|alter|drop|truncate)\b/i', $query['query'],
                'OCR HTTP requests must not perform database writes.');
        }
        $this->assertSame($before, $this->businessSnapshot(), 'OCR changed synthetic Transmittal data.');
        TransmittalOcrIsolation::assertBootedGuards($this->app);
        return $response;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->app) {
                TransmittalOcrIsolation::assertBootedGuards($this->app);
                $this->assertSame([], TransmittalOcrProcessDouble::violations(), 'A strict Process-double violation was swallowed.');
                $this->assertSame([], TransmittalOcrProcessDouble::pending(), 'A planned process was not reached.');
                $this->assertSame([], glob(TransmittalOcrIsolation::runtime().'/tmp/jknc-transmittal-ocr-*') ?: [],
                    'An OCR workspace was left behind.');
            }
        } finally {
            parent::tearDown();
        }
    }
}
