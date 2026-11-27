<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\TransmittalOcrService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

final class TransmittalSmartUploadAnalyzeRegressionTest extends BaseTestCase
{
    public function createApplication()
    {
        $baseDir = dirname(__DIR__, 2);
        $app = require $baseDir . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        spl_autoload_register(function (string $class): void {
            if (str_starts_with($class, 'App\\')) {
                $relative = str_replace('\\', '/', substr($class, 4));
                $file = dirname(__DIR__, 2) . '/app/' . $relative . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        }, true, true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'transmittal_v2.enabled' => true,
            'transmittal_v2.optional_features.smart_upload' => true,
            'transmittal_v2.optional_features.ocr' => true,
        ]);
        Gate::define('matchExistingRecords', fn () => false);
    }

    private function createTestUser(): User
    {
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@jknc.io',
            'role' => 'Standard',
        ]);
        $user->must_change_password = false;
        return $user;
    }

    public function test_real_ocr_execution_with_tesseract_and_poppler_on_original_pdf(): void
    {
        $user = $this->createTestUser();

        $tesseractPath = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
        $popplerPath = 'C:\\Users\\Admin\\Downloads\\poppler-26.09.0\\Library\\bin\\pdftoppm.exe';
        $pdfPath = 'C:\\Users\\Admin\\Downloads\\ORDO_Smart_Upload_OCR_Test.pdf';

        if (! is_file($tesseractPath) || ! is_file($popplerPath) || ! is_file($pdfPath)) {
            $this->markTestSkipped('Local OCR binaries or original test PDF are not available.');
        }

        config([
            'transmittal_ocr.binary' => $tesseractPath,
            'transmittal_ocr.pdf_binary' => $popplerPath,
        ]);

        $file = new UploadedFile($pdfPath, 'ORDO_Smart_Upload_OCR_Test.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($user)
            ->post(route('transmittals.analyze'), [
                'documents' => [$file],
            ]);

        $response->assertOk();
        $json = $response->json();

        $this->assertArrayHasKey('documents', $json);
        $this->assertCount(1, $json['documents']);

        $doc = $json['documents'][0];
        $this->assertSame('ORDO_Smart_Upload_OCR_Test.pdf', $doc['name']);
        $this->assertSame('document', $doc['category']);
        $this->assertIsArray($doc['fields']);
        $this->assertIsArray($doc['field_metadata']);
        $this->assertNotEmpty($doc['sha256']);
        $this->assertNotEmpty($doc['analysis_token']);

        $fields = $doc['fields'];

        // Expected header values from original PDF
        $this->assertSame('TEST-OCR-001', $fields['reference']);
        $this->assertSame('Example Training Corporation', $fields['company']);
        $this->assertSame('Test Recipient', $fields['recipient_name']);
        $this->assertSame('recipient@example.com', $fields['recipient_email']);
        $this->assertSame('Sample Records Department', $fields['office_name']);
        $this->assertSame('For review', $fields['purpose']);
        $this->assertSame('Electronic', $fields['delivery_type']);
        $this->assertSame('2026-11-27', $fields['transmittal_date']);

        // Expected all 3 document particulars & quantities preserved
        $this->assertArrayHasKey('items', $fields);
        $this->assertCount(3, $fields['items']);

        // Item 1: Annual Compliance Report — Quantity 2
        $this->assertSame('Annual Compliance Report', $fields['items'][0]['particular']);
        $this->assertSame('2', $fields['items'][0]['qty']);
        $this->assertStringContainsString('2 copies', $fields['items'][0]['remarks']);

        // Item 2: Board Resolution — Quantity 1
        $this->assertSame('Board Resolution', $fields['items'][1]['particular']);
        $this->assertSame('1', $fields['items'][1]['qty']);
        $this->assertStringContainsString('1 copy', $fields['items'][1]['remarks']);

        // Item 3: Document Inventory List — Quantity 3
        $this->assertSame('Document Inventory List', $fields['items'][2]['particular']);
        $this->assertSame('3', $fields['items'][2]['qty']);
        $this->assertStringContainsString('3 copies', $fields['items'][2]['remarks']);

        // Total quantity check: 2 + 1 + 3 = 6
        $totalQty = (int) $fields['items'][0]['qty'] + (int) $fields['items'][1]['qty'] + (int) $fields['items'][2]['qty'];
        $this->assertSame(6, $totalQty);

        // Confidence metadata is unmeasured from raw text OCR (not fabricated)
        $this->assertSame('tesseract_ocr', $doc['field_metadata']['reference']['source']);
        $this->assertNull($doc['field_metadata']['reference']['confidence']);

        // Verification of analysis token integrity
        $decrypted = json_decode(Crypt::decryptString($doc['analysis_token']), true);
        $this->assertSame('ORDO_Smart_Upload_OCR_Test.pdf', $decrypted['name']);
        $this->assertSame('document', $decrypted['category']);
        $this->assertSame($doc['sha256'], $decrypted['sha256']);
        $this->assertSame('TEST-OCR-001', $decrypted['fields']['reference']);
        $this->assertCount(3, $decrypted['fields']['items']);
    }

    public function test_array_merge_overwrite_regression_prevents_null_or_empty_from_overwriting_valid_fields(): void
    {
        $user = $this->createTestUser();

        config([
            'transmittal_ocr.binary' => PHP_BINARY,
        ]);

        $phpScriptPayload = implode("\n", [
            '<?php',
            'echo "Particular: Valid Particular Fallback\n";',
            'echo "Reference No.: VALID-REF-99\n";',
            'echo "To: Valid Company Fallback\n";',
            'echo "Attention: Valid Recipient Contact\n";',
            'echo "Email: valid@test.com\n";',
            'echo "Item No. Document Particulars Quantity Unit\n";',
            'echo "1 Valid Particular Fallback 5 copies\n";',
            'exit(0);',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.png', $phpScriptPayload);

        $response = $this->actingAs($user)
            ->post(route('transmittals.analyze'), [
                'documents' => [$file],
            ]);

        $response->assertOk();
        $doc = $response->json('documents.0');

        // Confirm computed fields were NOT overwritten by nulls or empty values
        $this->assertSame('Valid Particular Fallback', $doc['fields']['subject']);
        $this->assertSame('VALID-REF-99', $doc['fields']['reference']);
        $this->assertSame('Valid Company Fallback', $doc['fields']['company']);
        $this->assertSame('Valid Recipient Contact', $doc['fields']['recipient_name']);
        $this->assertSame('valid@test.com', $doc['fields']['recipient_email']);
        $this->assertSame('5', $doc['fields']['items'][0]['qty']);
    }

    public function test_structured_ocr_payload_compatibility(): void
    {
        $user = $this->createTestUser();

        config([
            'transmittal_ocr.binary' => PHP_BINARY,
        ]);

        $phpScriptPayload = implode("\n", [
            '<?php',
            'echo "Subject: Official Acknowledgment Receipt\n";',
            'echo "Reference No.: RCPT-2026-0042\n";',
            'echo "To: Prime Contractors Ltd.\n";',
            'echo "Email: finance@prime.test\n";',
            'echo "Item No. Document Particulars Quantity Unit\n";',
            'echo "1 Signed Acknowledgment Copy 1 copy\n";',
            'exit(0);',
        ]);

        $file = UploadedFile::fake()->createWithContent('receipt.png', $phpScriptPayload);

        $response = $this->actingAs($user)
            ->post(route('transmittals.analyze'), [
                'documents' => [$file],
            ]);

        $response->assertOk();
        $doc = $response->json('documents.0');

        $this->assertSame('document', $doc['category']);
        $this->assertSame('Official Acknowledgment Receipt', $doc['fields']['subject']);
        $this->assertSame('RCPT-2026-0042', $doc['fields']['reference']);
        $this->assertSame('Prime Contractors Ltd.', $doc['fields']['company']);
        $this->assertSame('finance@prime.test', $doc['fields']['recipient_email']);
        $this->assertSame('tesseract_ocr', $doc['field_metadata']['subject']['source']);
        $this->assertNull($doc['field_metadata']['subject']['confidence']);
    }

    public function test_multi_document_ocr_workflow_compatibility(): void
    {
        $user = $this->createTestUser();

        config([
            'transmittal_ocr.binary' => PHP_BINARY,
        ]);

        $phpScript1 = implode("\n", [
            '<?php',
            'echo "Subject: Batch Doc 1\n";',
            'echo "Reference No.: BATCH-001\n";',
            'echo "To: First Client\n";',
            'exit(0);',
        ]);

        $phpScript2 = implode("\n", [
            '<?php',
            'echo "Subject: Batch Doc 2\n";',
            'echo "Reference No.: BATCH-002\n";',
            'echo "To: Second Client\n";',
            'exit(0);',
        ]);

        $file1 = UploadedFile::fake()->createWithContent('first_batch_doc.png', $phpScript1);
        $file2 = UploadedFile::fake()->createWithContent('second_batch_doc.png', $phpScript2);

        $response = $this->actingAs($user)
            ->post(route('transmittals.analyze'), [
                'documents' => [$file1, $file2],
            ]);

        $response->assertOk();
        $docs = $response->json('documents');

        $this->assertCount(2, $docs);
        $this->assertSame('first_batch_doc.png', $docs[0]['name']);
        $this->assertSame('second_batch_doc.png', $docs[1]['name']);
        $this->assertSame('BATCH-001', $docs[0]['fields']['reference']);
        $this->assertSame('BATCH-002', $docs[1]['fields']['reference']);
        $this->assertSame('First Client', $docs[0]['fields']['company']);
        $this->assertSame('Second Client', $docs[1]['fields']['company']);
        $this->assertNull($docs[0]['field_metadata']['reference']['confidence']);
        $this->assertNull($docs[1]['field_metadata']['reference']['confidence']);
    }

    public function test_analyze_endpoint_rejects_unsupported_document_types(): void
    {
        $user = $this->createTestUser();

        $file = UploadedFile::fake()->create('unsupported.exe', 50, 'application/x-msdownload');

        $response = $this->actingAs($user)
            ->post(route('transmittals.analyze'), [
                'documents' => [$file],
            ]);

        $response->assertStatus(302); // Redirect with validation errors
        $response->assertSessionHasErrors('documents.0');
    }
}
