<?php

namespace Tests\Feature;

use App\Enums\FileRole;
use App\Enums\OcrStatus;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordFile;
use App\Models\RecordIndex;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * What ORDO will accept, and how each accepted type is shown.
 *
 * Two defects, both found the same way — by trying the file a reader would
 * actually try:
 *
 * 1. The intake rule was `mimes:`, which maps a file's *sniffed* MIME to the
 *    first extension Symfony knows for it. A legacy .xls sniffs as
 *    application/x-ole-storage, which maps to no extension at all, so it was
 *    refused; a .txt sniffs as text/plain and was refused too. Word files
 *    happened to sniff cleanly, which is why only .doc seemed to work.
 *
 * 2. The drawer decided how to render a file from one rule and the streaming
 *    endpoint decided what to send from another. A PDF the sniffer could not
 *    identify was put in an iframe and served as application/octet-stream, so
 *    the browser saved it instead of showing it.
 *
 * The third thing here is deliberate and is the reason the allowlist validates
 * extensions rather than content: serving sniffed bytes inline means a file full
 * of HTML renamed to .doc is served as text/html from this domain. That is a
 * stored XSS, and it is why only the renderable set goes inline.
 */
class UploadedDocumentTypesTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordClassification $classification;

    private RecordIndex $index;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);

        $this->classification = new RecordClassification;
        $this->classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity & Ownership',
            'is_active' => true,
        ])->save();

        $this->index = new RecordIndex;
        $this->index->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_classification_id' => $this->classification->id,
            'code' => '301-6',
            'name' => 'GIS',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();
    }

    // ---------------------------------------------------------- the allowlist

    /**
     * The types the reader was refused and could not work around.
     *
     * @return array<string, array{0: string}>
     */
    public static function acceptedTypes(): array
    {
        return [
            'plain text' => ['notes.txt'],
            'comma separated' => ['ledger.csv'],
            'pdf' => ['minutes.pdf'],
            'legacy word' => ['contract.doc'],
            'modern word' => ['contract.docx'],
            'legacy excel' => ['ledger.xls'],
            'modern excel' => ['ledger.xlsx'],
            'jpeg' => ['scan.jpg'],
            'png' => ['scan.png'],
        ];
    }

    #[DataProvider('acceptedTypes')]
    public function test_intake_accepts_the_document_types_readers_try(string $name): void
    {
        $this->post(route('records.intake.store'), [
            'files' => [$this->fixture($name)],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('record_intake_items', [
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rejectedTypes(): array
    {
        return [
            'windows executable' => ['payload.exe'],
            'php script' => ['shell.php'],
            'html page' => ['invoice.html'],
            'javascript' => ['script.js'],
            'archive' => ['bundle.zip'],
        ];
    }

    #[DataProvider('rejectedTypes')]
    public function test_intake_still_refuses_anything_that_is_not_a_document(string $name): void
    {
        $this->post(route('records.intake.store'), [
            'files' => [$this->fixture($name)],
        ])->assertSessionHasErrors('files.0');
    }

    public function test_a_file_named_like_a_document_but_holding_markup_is_still_stored(): void
    {
        // Refusing it would be wrong here: the extension is on the list, and the
        // point of recording the sniffed type is that the reader is shown what
        // they actually stored. The safety work happens at serve time.
        $this->post(route('records.intake.store'), [
            'files' => [new UploadedFile(
                $this->bytes('<html><script>alert(1)</script></html>'),
                'contract.doc',
                'application/msword',
                null,
                true,
            )],
        ])->assertRedirect();

        $stored = RecordFile::query()->where('original_name', 'contract.doc')->firstOrFail();

        $this->assertSame('text/html', $stored->mime_type, 'The sniffed type should be recorded as found.');
        $this->assertSame(RecordFile::RENDER_DOWNLOAD, $stored->renderMode());
    }

    // ------------------------------------------------ the two unguarded routes

    public function test_a_supporting_file_is_type_checked_too(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fixture('payload.exe'),
        ])->assertSessionHasErrors('file');
    }

    public function test_upload_as_record_is_type_checked_too(): void
    {
        $this->post(route('records.files.upload-as-record'), [
            'file' => $this->fixture('payload.exe'),
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
        ])->assertSessionHasErrors('file');
    }

    public function test_a_supporting_file_accepts_the_same_list_as_intake(): void
    {
        $record = $this->filedRecord();

        $this->post(route('records.files.supporting', ['record' => $record->id]), [
            'file' => $this->fixture('notes.txt'),
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    // ------------------------------------------------------------ render mode

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function renderModes(): array
    {
        return [
            'pdf renders' => ['minutes.pdf', 'application/pdf', RecordFile::RENDER_PDF],
            'jpeg renders' => ['scan.jpg', 'image/jpeg', RecordFile::RENDER_IMAGE],
            'png renders' => ['scan.png', 'image/png', RecordFile::RENDER_IMAGE],
            'text renders' => ['notes.txt', 'text/plain', RecordFile::RENDER_TEXT],
            'csv renders' => ['ledger.csv', 'text/csv', RecordFile::RENDER_TEXT],
            'legacy excel does not' => ['legacy.xls', 'application/vnd.ms-excel', RecordFile::RENDER_DOWNLOAD],
            'excel is converted' => ['ledger.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', RecordFile::RENDER_HTML],
            'word is converted' => ['contract.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', RecordFile::RENDER_HTML],
            // Legacy Office is a binary format nothing here can read. It stays a
            // download, and saying otherwise would be a promise we cannot keep.
            'legacy word does not' => ['contract.doc', 'application/msword', RecordFile::RENDER_DOWNLOAD],
        ];
    }

    #[DataProvider('renderModes')]
    public function test_each_type_is_shown_the_way_a_browser_actually_can(
        string $name,
        string $mime,
        string $expected,
    ): void {
        $file = $this->stored($name, $mime);

        $this->assertSame($expected, $file->renderMode());
    }

    /**
     * A PDF the sniffer could not identify is still shown as a PDF.
     *
     * This is the trap the drawer and the endpoint fell into from opposite
     * sides: the extension said PDF so an iframe was drawn, while the endpoint
     * echoed the sniffer's octet-stream, and the browser saved the file instead
     * of rendering it. The render mode decides, and for a PDF that is the type
     * stated rather than the type guessed.
     */
    public function test_an_unidentified_pdf_is_served_as_a_pdf_not_as_octet_stream(): void
    {
        $file = $this->stored('scan.pdf', 'application/octet-stream');

        $this->assertSame(RecordFile::RENDER_PDF, $file->renderMode());
        $this->assertSame('application/pdf', $file->renderMimeType());
        $this->assertTrue($file->isStreamableInline());
    }

    // ------------------------------------------------------ how it is served

    public function test_a_renderable_type_is_served_inline(): void
    {
        $file = $this->stored('minutes.pdf', 'application/pdf');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]));

        $response->assertOk();
        $this->assertStringContainsString('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * The stored-XSS boundary.
     *
     * A document whose bytes are markup is served as an opaque download, so the
     * browser is never asked to interpret it. Without this, any record carrying
     * an HTML file would hand an attacker script execution on this origin.
     */
    public function test_a_file_containing_markup_is_served_as_a_download_and_never_inline(): void
    {
        // A .doc whose bytes are HTML. Nothing about it renders, and nothing
        // about it may be interpreted: it goes out as an opaque attachment.
        $file = $this->stored('contract.doc', 'text/html');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]));

        $response->assertOk();
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * The same bytes under a .pdf name take the PDF branch instead.
     *
     * Still safe — it is declared application/pdf, sent with nosniff and inside
     * a sandbox, so the browser hands it to the PDF viewer and never to the HTML
     * engine. The PDF branch asserting the type rather than echoing the sniffer
     * is exactly what keeps that true for a PDF the sniffer could not identify.
     */
    public function test_markup_behind_a_pdf_name_is_still_declared_as_a_pdf(): void
    {
        $file = $this->stored('invoice.pdf', 'text/html');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame("sandbox; default-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_every_preview_is_sandboxed(): void
    {
        foreach (['minutes.pdf', 'scan.jpg', 'notes.txt', 'contract.doc'] as $name) {
            $file = $this->stored($name, $this->mimeFor($name));

            $this->assertSame(
                "sandbox; default-src 'none'",
                $this->get(route('records.files.preview', ['file' => $file->id]))
                    ->headers->get('Content-Security-Policy'),
                "{$name} was not sandboxed.",
            );
        }
    }

    public function test_a_filename_cannot_inject_a_header(): void
    {
        $file = $this->stored('minutes.pdf', 'application/pdf');

        $file->forceFill(['original_name' => 'a"; filename="evil.exe'."\r\nX-Injected: 1"])->save();

        $response = $this->get(route('records.files.preview', ['file' => $file->id]));

        // The reader-supplied name is inert: the newlines that would end the
        // header line are gone, the quotes that would add a parameter are gone,
        // and no header of the attacker's choosing exists.
        $this->assertNull($response->headers->get('X-Injected'));

        $disposition = (string) $response->headers->get('Content-Disposition');

        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);

        // Exactly the two delimiters. The attacker's own `filename=` text survives
        // inside the value, which is inert — without a quote to close the value
        // and open a second parameter, it is just characters in a filename.
        $this->assertSame(2, substr_count($disposition, '"'), 'The filename value was not properly delimited.');
    }

    // ----------------------------------------------------------- range support

    public function test_a_browser_can_ask_for_part_of_a_large_document(): void
    {
        $file = $this->stored('minutes.pdf', 'application/pdf', 'abcdefghij');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]), [
            'Range' => 'bytes=2-5',
        ]);

        $response->assertStatus(206);
        $this->assertSame('bytes 2-5/10', $response->headers->get('Content-Range'));
        $this->assertSame('4', $response->headers->get('Content-Length'));
        $this->assertSame('cdef', $response->streamedContent());
        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
    }

    public function test_an_open_ended_range_runs_to_the_end_of_the_document(): void
    {
        $file = $this->stored('minutes.pdf', 'application/pdf', 'abcdefghij');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]), ['Range' => 'bytes=5-']);

        $response->assertStatus(206);
        $this->assertSame('fghij', $response->streamedContent());
    }

    public function test_a_range_past_the_end_of_the_document_serves_the_whole_thing(): void
    {
        $file = $this->stored('minutes.pdf', 'application/pdf', 'abcdefghij');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]), ['Range' => 'bytes=50-']);

        $response->assertOk();
        $this->assertSame('abcdefghij', $response->streamedContent());
    }

    public function test_a_request_without_a_range_is_served_whole(): void
    {
        $file = $this->stored('minutes.pdf', 'application/pdf', 'abcdefghij');

        $response = $this->get(route('records.files.preview', ['file' => $file->id]));

        $response->assertOk();
        $this->assertSame('10', $response->headers->get('Content-Length'));
        $this->assertSame('abcdefghij', $response->streamedContent());
    }

    // ------------------------------------------------- the drawer agrees with it

    public function test_the_drawer_is_told_the_same_render_mode_the_endpoint_applies(): void
    {
        $record = $this->filedRecord('minutes.pdf');
        $file = $record->files()->firstOrFail();

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString(
            sprintf('renderMode\\u0022:\\u0022%s', $file->renderMode()),
            $content,
            'The payload did not carry the render mode the streamer will use.',
        );
    }

    public function test_plain_text_is_drawn_in_the_pane_rather_than_refused(): void
    {
        $this->filedRecord('notes.txt');

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('master && master.isText', $content);
        // And the card must not claim a .txt is unrenderable.
        $this->assertStringContainsString('! master.isText', $content);
    }

    // ---------------------------------------------------------------- helpers

    /**
     * A file whose bytes really are what its name says.
     */
    private function fixture(string $name): UploadedFile
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $bytes = match ($extension) {
            'pdf' => '%PDF-1.4'."\n".'1 0 obj<</Type/Page>>endobj'."\ntrailer<</Root 1 0 R>>"."\n%%EOF\n",
            'jpg', 'jpeg' => (string) base64_decode(
                '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
                .'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAA'
                .'AAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==',
                true,
            ),
            'png' => (string) base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                true,
            ),
            // The OLE compound-file header. Its sniffed type maps to no extension,
            // which is what got legacy .xls and .doc refused under `mimes`.
            'doc', 'xls' => hex2bin('d0cf11e0a1b11ae1').str_repeat("\0", 64),
            'docx', 'xlsx' => hex2bin('504b0304').str_repeat("\0", 64),
            'txt', 'csv' => "col a,col b\n1,2\n",
            default => 'payload',
        };

        return new UploadedFile($this->bytes($bytes), $name, null, null, true);
    }

    private function mimeFor(string $name): string
    {
        return match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            default => 'application/msword',
        };
    }

    /**
     * A stored file row with its bytes on disk.
     */
    private function stored(string $name, string $mime, ?string $contents = null): RecordFile
    {
        $path = 'records/'.uniqid('preview-', true).'/'.uniqid().'.bin';

        Storage::disk((string) config('filesystems.default'))
            ->put($path, $contents ?? $this->bytes('placeholder'));

        return RecordFile::query()->create([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_id' => $this->filedRecord()->id,
            'file_role' => FileRole::Supporting,
            'disk' => (string) config('filesystems.default'),
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mime,
            'extension' => pathinfo($name, PATHINFO_EXTENSION),
            'size_bytes' => strlen($contents ?? 'placeholder'),
            'ocr_status' => OcrStatus::Ready,
        ]);
    }

    private function bytes(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ordo-test-');
        file_put_contents($path, $contents);

        return $path;
    }

    private function filedRecord(): Record
    {
        $item = $this->app->make(RecordIntakeService::class)
            ->createIntakeItem([$this->fixture('minutes.pdf')]);

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $this->classification->id,
            'record_index_id' => $this->index->id,
        ])->refresh();
    }
}
