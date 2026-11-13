<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TransmittalV2\TransmittalUploadBudget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/** Standalone logic and source contracts; no Laravel application or database. */
final class TransmittalV2UploadBudgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ValidatorFacade::swap(new Factory(new Translator(new ArrayLoader(), 'en')));
    }

    protected function tearDown(): void
    {
        ValidatorFacade::clearResolvedInstance('validator');
        parent::tearDown();
    }

    private function file(string $identity, int $bytes): UploadedFile
    {
        // Sizes are test doubles, so boundary checks do not allocate 100 MiB files.
        $file = $this->createStub(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getClientOriginalName')->willReturn('same-name.pdf');
        $file->method('getRealPath')->willReturn('/temporary/'.$identity);
        $file->method('getSize')->willReturn($bytes);

        return $file;
    }

    private function files(int $count): array
    {
        return array_map(fn (int $i): UploadedFile => $this->file('file-'.$i, 1), range(1, $count));
    }

    private function rejects(array $files, string $message, array $completed = []): void
    {
        try {
            (new TransmittalUploadBudget())->validate($files, $completed);
            self::fail('Expected upload-budget rejection.');
        } catch (ValidationException $exception) {
            self::assertSame(422, $exception->status);
            self::assertStringContainsString($message, $exception->errors()['documents'][0]);
        }
    }

    public function test_49_and_50_files_are_accepted(): void
    {
        foreach ([49, 50] as $count) {
            (new TransmittalUploadBudget())->validate($this->files($count));
        }
        $this->addToAssertionCount(2);
    }

    public function test_51_files_are_rejected(): void
    {
        $this->rejects($this->files(51), 'maximum of 50');
    }

    public function test_exact_combined_byte_limit_is_accepted(): void
    {
        (new TransmittalUploadBudget())->validate([
            $this->file('a', 52428800), $this->file('b', 52428800),
        ]);
        $this->addToAssertionCount(1);
    }

    public function test_one_byte_over_combined_limit_is_rejected(): void
    {
        $this->rejects([
            $this->file('a', 52428800), $this->file('b', 52428800), $this->file('c', 1),
        ], '100 MiB');
    }

    public function test_exact_individual_limit_is_accepted(): void
    {
        (new TransmittalUploadBudget())->validate([$this->file('a', 52428800)]);
        $this->addToAssertionCount(1);
    }

    public function test_one_byte_over_individual_limit_is_rejected(): void
    {
        $this->rejects([$this->file('a', 52428801)], '50 MiB');
    }

    public function test_mixed_categories_and_both_nested_attachment_shapes_share_one_count(): void
    {
        $files = [
            'documents' => $this->files(47),
            'supporting_attachments' => [$this->file('supporting', 1)],
            'attachments' => [$this->file('attachment', 1)],
            'items' => [['attachment' => $this->file('item', 1)]],
        ];
        (new TransmittalUploadBudget())->validate($files);
        $files['items'][] = ['attachments' => [$this->file('nested-extra', 1)]];
        $this->rejects($files, 'maximum of 50');
    }

    public function test_mixed_nested_categories_share_one_byte_budget(): void
    {
        $this->rejects([
            'documents' => [$this->file('document', 52428800)],
            'supporting_attachments' => [$this->file('supporting', 26214400)],
            'attachments' => [$this->file('attachment', 26214400)],
            'items' => [['attachments' => [$this->file('nested', 1)]]],
        ], '100 MiB');
    }

    public function test_direct_plus_completed_resumable_files_share_both_budgets(): void
    {
        $direct = $this->files(49);
        $completed = [$this->file('assembled-1', 1)];
        (new TransmittalUploadBudget())->validate($direct, $completed);
        $this->rejects($direct, 'maximum of 50', [...$completed, $this->file('assembled-2', 1)]);
        $this->rejects([$this->file('direct-large', 52428800)], '100 MiB', [
            $this->file('assembled-large', 52428800), $this->file('assembled-extra', 1),
        ]);
    }

    public function test_repeated_references_to_same_temp_file_are_counted_once(): void
    {
        $file = $this->file('shared', 52428800);
        // Includes another UploadedFile wrapper pointing to the same assembled path.
        (new TransmittalUploadBudget())->validate(
            ['documents' => [$file], 'items' => [['attachment' => $file]]],
            [$this->file('shared', 52428800), $this->file('other', 52428800)],
        );
        $this->addToAssertionCount(1);
    }

    public function test_separate_uploads_with_same_content_or_name_are_not_deduplicated(): void
    {
        $this->rejects($this->files(51), 'maximum of 50');
    }

    public function test_completed_resumable_file_is_measured_and_cannot_bypass_per_file_limit(): void
    {
        $this->rejects([], '50 MiB', [$this->file('assembled', 52428801)]);
    }

    public function test_invalid_sizes_and_unverifiable_files_are_rejected(): void
    {
        $this->rejects([$this->file('zero', 0)], 'invalid size');
        $this->rejects([$this->file('negative', -1)], 'invalid size');
        $file = $this->createStub(UploadedFile::class);
        $file->method('isValid')->willReturn(false);
        $file->method('getRealPath')->willReturn(false);
        $this->rejects([$file], 'could not be verified');
    }

    private function source(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/'.$path);
    }

    private function method(string $path, string $name): string
    {
        $reflection = new \ReflectionMethod('App\\Http\\Controllers\\TransmittalV2\\'.basename($path, '.php'), $name);
        $lines = file(dirname(__DIR__, 2).'/'.$path);

        return implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
    }

    private function before(string $source, string $first, string $second): void
    {
        self::assertStringContainsString($first, $source);
        self::assertStringContainsString($second, $source);
        self::assertLessThan(strpos($source, $second), strpos($source, $first));
    }

    public function test_submission_budget_precedes_all_permanent_writes_and_resumable_claims(): void
    {
        $path = 'app/Http/Controllers/TransmittalV2/TransmittalController.php';
        foreach (['store', 'update'] as $method) {
            $this->before($this->method($path, $method), '$this->validateTransmittal($request)', 'DB::transaction(');
        }
        self::assertStringContainsString('(new TransmittalUploadBudget())->validate($request->allFiles());', $this->method($path, 'validateTransmittal'));
        $smart = $this->method($path, 'createFromSmartUpload');
        $this->before($smart, '$uploads->temporaryFile($upload)', '(new TransmittalUploadBudget())->validate(');
        $this->before($smart, '(new TransmittalUploadBudget())->validate(', 'DB::transaction(');
        self::assertStringContainsString('array_column($fileEntries, \'file\')', $smart);
        self::assertStringContainsString("'upload_ids.*' => ['required', 'uuid', 'distinct']", $smart);
        foreach (['uploadProof', 'updateProof'] as $method) {
            $source = $this->method($path, $method);
            $this->before($source, '(new TransmittalUploadBudget())->validate(', '$this->storeDocument(');
        }
        $this->before($this->method($path, 'analyze'), '(new TransmittalUploadBudget())->validate(', '$ocr->analyze(');
    }

    public function test_budget_failure_prevents_downstream_side_effects(): void
    {
        $stored = [];
        try {
            (new TransmittalUploadBudget())->validate($this->files(51));
            $stored[] = 'permanent-file';
            $stored[] = 'business-record';
            self::fail('Invalid budget unexpectedly reached persistence.');
        } catch (ValidationException) {
            self::assertSame([], $stored);
        }
        $budget = $this->source('app/Services/TransmittalV2/TransmittalUploadBudget.php');
        foreach (['DB::', 'Storage::', '->save(', '->create(', '->storeAs('] as $write) {
            self::assertStringNotContainsString($write, $budget);
        }
    }

    public function test_resumable_analysis_and_transfer_keep_byte_ceilings_without_counting_chunks(): void
    {
        $path = 'app/Http/Controllers/TransmittalV2/TransmittalUploadController.php';
        $this->before($this->method($path, 'analyze'), '(new TransmittalUploadBudget())->validate(', '$ocr->analyze(');
        self::assertStringNotContainsString('TransmittalUploadBudget())->validate', $this->method($path, 'putChunk'));
        $service = $this->source('app/Services/TransmittalV2/ResumableTransmittalUploadService.php');
        self::assertStringContainsString("min((int) config('transmittal_v2.upload.max_bytes'), TransmittalUploadBudget::MAX_FILE_BYTES)", $service);
        self::assertStringContainsString('$upload->expected_size > TransmittalUploadBudget::MAX_FILE_BYTES', $service);
        self::assertStringContainsString('$this->verifyCompletedFile($upload)', $service);
    }

    public function test_v1_sources_match_phase16b_hashes_and_v2_registration_keeps_v1_routes(): void
    {
        $hashes = [
            'app/Http/Controllers/TransmittalController.php' => '29EA4670BD54CEAA132272F3AA7458DC92120E25223F2904B6D63B7A68C00FC0',
            'app/Models/Transmittal.php' => '30B382E9D230F996A2BBEE0AB36202FE0BB3F90086A2973C11A1BAFD487B9C17',
            'app/Models/TransmittalItem.php' => '213299BBBAA4337EC1A5799B693C131AF90C4179F21D06302A7B3CEA903FE0E5',
            'app/Models/TransmittalReceipt.php' => '767909707224221CA938B4C3922F4F9C4D72B4817523F6AC3F367A07C5F719EE',
            'resources/views/transmittal/index.blade.php' => '4C563F9CED89D808DDCC2BC30C7C0DB65CAD08BE50101974647D5E5E38E3FDF0',
            'vite.config.js' => 'D57014EED9865FA5F296C3FBA8760770F7096B65073D7C3B51AEF9F7DFC6D232',
            'app/Services/TransmittalOcrService.php' => '8A7C4F06AD998A57AE8FAE388B2140B535A78ECC59C02220C1F30896239A188C',
            'patches/transmittal-v2/staged/TransmittalController.php.candidate' => '6ED477450E4A0815F7AA38A741915D3722F75B4953D2F5E39C35A15A7EB72083',
        ];
        foreach ($hashes as $path => $hash) {
            self::assertSame(strtolower($hash), hash_file('sha256', dirname(__DIR__, 2).'/'.$path), $path);
        }
        $controller = $this->source('app/Http/Controllers/TransmittalV2/TransmittalController.php');
        self::assertStringContainsString('return [EnsureTransmittalV2Enabled::class];', $controller);
        foreach (['approve', 'reject', 'transmit', 'upload-proof', 'view', 'view-attachments'] as $ability) {
            self::assertStringContainsString("'transmittal-v2.".$ability."'", $controller);
        }
        $legacyRoutes = $this->source('routes/web.php');
        self::assertStringContainsString("Route::get('/transmittal'", $legacyRoutes);
        self::assertStringContainsString("Route::post('/transmittal'", $legacyRoutes);
        self::assertStringContainsString("require __DIR__.'/transmittal-v2.php';", $legacyRoutes);
    }
}
