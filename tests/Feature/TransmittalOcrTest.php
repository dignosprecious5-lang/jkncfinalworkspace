<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TransmittalOcrService;
use App\Exceptions\OcrUnavailableException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TransmittalOcrFixtures as Fixtures;
use Tests\Support\TransmittalOcrIsolation as Isolation;
use Tests\Support\TransmittalOcrProcessDouble as Double;
use Tests\Support\TransmittalOcrTestCase;

final class TransmittalOcrTest extends TransmittalOcrTestCase
{
    public static function allowedRoles(): array { return [['Admin'], ['SuperAdmin']]; }

    #[DataProvider('allowedRoles')]
    public function test_admin_and_superadmin_can_analyze_without_writing_data(string $role): void
    {
        $this->actor($role);
        Double::reset([['kind' => 'ocr', 'stdout' => Fixtures::TEXT]]);
        $this->ocr(Fixtures::upload('png'))->assertOk()->assertExactJson(['suggestions' => Fixtures::expected()]);
        $this->assertCount(1, Double::calls());
    }

    public function test_guest_is_rejected_before_ocr(): void
    {
        $this->ocr(Fixtures::upload('png'))->assertUnauthorized();
        $this->assertSame([], Double::calls());
    }

    public static function deniedRoles(): array { return [['Employee'], ['Client'], ['Director'], ['']]; }

    #[DataProvider('deniedRoles')]
    public function test_other_roles_are_forbidden(string $role): void
    {
        $this->actor($role);
        $this->ocr(Fixtures::upload('png'))->assertForbidden();
        $this->assertSame([], Double::calls());
    }

    public function test_password_change_is_required_before_analysis(): void
    {
        $this->actor('Admin', 1, true);
        $this->ocr(Fixtures::upload('png'))->assertRedirect(route('password.change'));
        $this->assertSame([], Double::calls());
    }

    public function test_csrf_is_enforced_despite_laravel_unit_test_mode(): void
    {
        $this->actor();
        $this->ocr(Fixtures::upload('png'), '')->assertStatus(419);
        $this->ocr(Fixtures::upload('png'), 'incorrect-token')->assertStatus(419);
        $this->assertSame([], Double::calls());
        Double::reset([['kind' => 'ocr', 'stdout' => Fixtures::TEXT]]);
        $this->ocr(Fixtures::upload('png'))->assertOk();
    }

    public function test_five_requests_per_user_are_allowed_then_throttled(): void
    {
        $this->actor();
        config(['transmittal_ocr.binary' => null]);
        for ($attempt = 0; $attempt < 5; $attempt++) $this->ocr(Fixtures::upload('png'))->assertStatus(503);
        $this->ocr(Fixtures::upload('png'))->assertStatus(429)->assertHeader('Retry-After');
        $this->actor('Admin', 2);
        $this->ocr(Fixtures::upload('png'))->assertStatus(503);
        $this->assertSame([], Double::calls());
    }

    public function test_route_is_post_only_and_retains_its_security_middleware(): void
    {
        $route = Route::getRoutes()->getByName('transmittal.ocr.analyze');
        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        foreach (['web', 'auth', 'prevent-back-history', \App\Http\Middleware\EnsurePasswordChanged::class,
            'throttle:5,1,transmittal-ocr:'] as $middleware) {
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
        $this->getJson('/transmittal/ocr/analyze')->assertStatus(405);
    }

    public static function imageFormats(): array { return [['png'], ['jpeg']]; }

    #[DataProvider('imageFormats')]
    public function test_real_mime_detection_accepts_supported_images(string $kind): void
    {
        $this->actor();
        Double::reset([['kind' => 'ocr', 'stdout' => Fixtures::TEXT]]);
        $this->ocr(Fixtures::upload($kind))->assertOk()->assertExactJson(['suggestions' => Fixtures::expected()]);
    }

    public function test_pdf_uses_bounded_renderer_and_whitelisted_suggestions(): void
    {
        $this->actor();
        Double::reset([['kind' => 'pdf', 'pages' => 1], ['kind' => 'ocr', 'stdout' => Fixtures::TEXT]]);
        $this->ocr(Fixtures::upload('pdf'))->assertOk()->assertExactJson(['suggestions' => Fixtures::expected()]);
        $this->assertSame(['-f', '1', '-l', '5', '-scale-to', '1800', '-png'], array_slice(Double::calls()[0]['arguments'], 1, 7));
    }

    public static function invalidUploads(): array
    {
        return [['spoofed'], ['invalid'], ['empty'], ['unsupported'], ['oversized'], ['missing']];
    }

    #[DataProvider('invalidUploads')]
    public function test_invalid_and_spoofed_files_are_rejected_before_processes(string $kind): void
    {
        $this->actor();
        $file = $kind === 'missing' ? null : ($kind === 'oversized'
            ? Fixtures::upload('png', 10 * 1024 * 1024 + 1) : Fixtures::upload($kind));
        $this->ocr($file)->assertUnprocessable()->assertJsonValidationErrors('document');
        $this->assertSame([], Double::calls());
    }

    public function test_exactly_ten_megabytes_is_accepted(): void
    {
        $this->actor();
        Double::reset([['kind' => 'ocr', 'stdout' => Fixtures::TEXT]]);
        $this->ocr(Fixtures::upload('png', 10 * 1024 * 1024))->assertOk();
    }

    public static function invalidExecutablePaths(): array { return [[null], [''], ['tesseract'], ["invalid\0path"]]; }

    #[DataProvider('invalidExecutablePaths')]
    public function test_missing_or_invalid_configuration_fails_closed(?string $path): void
    {
        $this->actor();
        config(['transmittal_ocr.binary' => $path]);
        $this->ocr(Fixtures::upload('png'))->assertStatus(503)->assertExactJson(['message' => 'Document analysis is temporarily unavailable.']);
        $this->assertSame([], Double::calls());
    }

    public function test_missing_pdf_executable_fails_closed(): void
    {
        $this->actor();
        config(['transmittal_ocr.pdf_binary' => null]);
        $this->ocr(Fixtures::upload('pdf'))->assertStatus(503);
        $this->assertSame([], Double::calls());
    }

    public function test_nonexistent_absolute_executable_and_directory_fail_closed(): void
    {
        $this->actor();
        foreach ([Isolation::runtime().'/missing-ocr.exe', Isolation::runtime()] as $path) {
            config(['transmittal_ocr.binary' => $path]);
            $this->ocr(Fixtures::upload('png'))->assertStatus(503);
        }
        $this->assertSame([], Double::calls());
    }

    public static function processFailures(): array
    {
        return [['exit'], ['timeout'], ['failure']];
    }

    #[DataProvider('processFailures')]
    public function test_process_errors_are_safe_and_cleanup_runs(string $failure): void
    {
        $this->actor();
        $step = ['kind' => 'ocr'];
        if ($failure === 'exit') $step['success'] = false;
        else $step['throw'] = $failure;
        Double::reset([$step]);
        $this->ocr(Fixtures::upload('png'))->assertStatus(503)->assertExactJson(['message' => 'Document analysis is temporarily unavailable.']);
        $this->assertSame(25.0, Double::calls()[0]['timeout']);
    }

    public function test_pdf_page_count_is_capped_even_if_renderer_returns_extra_pages(): void
    {
        $this->actor();
        $steps = [['kind' => 'pdf', 'pages' => 6]];
        for ($page = 1; $page <= 5; $page++) $steps[] = ['kind' => 'ocr', 'stdout' => ''];
        Double::reset($steps);
        $this->ocr(Fixtures::upload('pdf'))->assertOk()->assertExactJson(['suggestions' => []]);
        $this->assertCount(6, Double::calls());
    }

    public function test_pdf_pages_are_cleaned_when_tesseract_fails(): void
    {
        $this->actor();
        Double::reset([['kind' => 'pdf', 'pages' => 3], ['kind' => 'ocr', 'throw' => 'timeout']]);
        $this->ocr(Fixtures::upload('pdf'))->assertStatus(503);
    }

    public function test_pdf_renderer_failure_cleans_partially_rendered_pages(): void
    {
        $this->actor();
        Double::reset([['kind' => 'pdf', 'pages' => 3, 'success' => false]]);
        $this->ocr(Fixtures::upload('pdf'))->assertStatus(503);
    }

    public function test_pdf_with_no_rendered_pages_fails_and_cleans_up(): void
    {
        $this->actor();
        Double::reset([['kind' => 'pdf', 'pages' => 0]]);
        $this->ocr(Fixtures::upload('pdf'))->assertStatus(503);
    }

    public function test_total_deadline_stops_later_processes_without_sleeping(): void
    {
        $this->actor();
        Double::reset([['kind' => 'pdf', 'pages' => 1, 'advance' => 90]]);
        $this->ocr(Fixtures::upload('pdf'))->assertStatus(503);
        $this->assertCount(1, Double::calls());
    }

    public function test_remaining_deadline_reduces_the_next_timeout(): void
    {
        $this->actor();
        Double::reset([['kind' => 'pdf', 'pages' => 1, 'advance' => 80], ['kind' => 'ocr', 'stdout' => '']]);
        $this->ocr(Fixtures::upload('pdf'))->assertOk();
        $this->assertSame(10.0, Double::calls()[1]['timeout']);
    }

    public function test_untrusted_ocr_text_is_only_json_data_and_keys_are_whitelisted(): void
    {
        $this->actor();
        $text = "Party Name: <img src=x onerror=alert(1)>\nDate: 2026-02-30\nworkflow_status: accepted\nQty: 4";
        Double::reset([['kind' => 'ocr', 'stdout' => $text]]);
        $this->ocr(Fixtures::upload('png'))->assertOk()->assertExactJson([
            'suggestions' => ['party_name' => '<img src=x onerror=alert(1)>', 'items' => [['qty' => '4']]],
        ]);
    }

    public function test_service_itself_rejects_oversized_files_before_processes(): void
    {
        $this->expectException(OcrUnavailableException::class);
        (new TransmittalOcrService)->analyze(Fixtures::upload('png', 10 * 1024 * 1024 + 1));
    }

    public function test_service_itself_rejects_spoofed_mime_before_processes(): void
    {
        $this->expectException(OcrUnavailableException::class);
        (new TransmittalOcrService)->analyze(Fixtures::upload('spoofed'));
    }

    public function test_guards_use_only_memory_database_and_nonexecuting_double(): void
    {
        Isolation::assertBootedGuards($this->app);
        $this->assertSame(['sqlite'], \PDO::getAvailableDrivers());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertFalse(function_exists('proc_open'));
        $this->assertFalse(function_exists('stream_socket_client'));
        $this->assertSame(realpath(dirname(__DIR__).'/Support/TransmittalOcrProcessDouble.php'),
            realpath((string) (new \ReflectionClass(\Symfony\Component\Process\Process::class))->getFileName()));
    }
}
