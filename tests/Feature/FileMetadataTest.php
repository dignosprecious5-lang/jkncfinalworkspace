<?php

namespace Tests\Feature;

use App\Services\Records\FileMetadata;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Document type is decided by the stored bytes, never by what the browser said.
 *
 * These cover the extraction that intake, filing and the workspace drawer all
 * read from. Nothing else in the suite asserts this behaviour directly, so a
 * change to it would otherwise only show up as a mislabelled document.
 */
class FileMetadataTest extends TestCase
{
    use FakeUploads;

    private FileMetadata $metadata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata = $this->app->make(FileMetadata::class);
    }

    public function test_it_identifies_a_pdf_from_its_bytes(): void
    {
        $details = $this->extract($this->pdfUpload('report.pdf'), 'report.pdf');

        $this->assertSame('application/pdf', $details['mime_type']);
        $this->assertSame('PDF Document', $details['document_type']);
        $this->assertSame('pdf', $details['extension']);
    }

    public function test_it_counts_the_pages_of_a_real_pdf(): void
    {
        $details = $this->extract($this->pdfUpload('three-pages.pdf'), 'three-pages.pdf');

        $this->assertSame(3, $details['page_count']);
    }

    public function test_a_jpeg_is_one_page(): void
    {
        $details = $this->extract($this->fakeImage('scan.jpg'), 'scan.jpg');

        $this->assertSame('image/jpeg', $details['mime_type']);
        $this->assertSame('JPEG Image', $details['document_type']);
        $this->assertSame(1, $details['page_count']);
    }

    /**
     * The central rule: a file that lies about its type is stored as what it
     * actually is, so a document cannot be mislabelled by renaming it.
     *
     * The mime is image/jpeg even though the file is named .pdf. The label still
     * says JPEG Image here because the bytes win, and documentType() consults
     * the extension first for formats whose bytes are indistinguishable — see
     * the test below for that half of the rule.
     */
    public function test_the_stored_bytes_win_over_a_lying_client_type(): void
    {
        $details = $this->extract($this->fakeImage('not-really.pdf'), 'not-really.pdf');

        $this->assertSame('image/jpeg', $details['mime_type']);
        $this->assertSame('pdf', $details['extension']);
    }

    /**
     * The other half of the rule: the human-facing label follows the extension,
     * because that is what the user will see on disk. The mime still reports
     * what the file actually is and is what drives preview and page counting.
     */
    public function test_the_document_label_follows_the_extension(): void
    {
        $details = $this->extract($this->fakeImage('not-really.pdf'), 'not-really.pdf');

        $this->assertSame('PDF Document', $details['document_type']);
    }

    public function test_text_bytes_are_identified_rather_than_left_unknown(): void
    {
        $details = $this->extract($this->fakeFile('mystery.bin', 32, 'application/octet-stream'), 'mystery.bin');

        $this->assertSame('text/plain', $details['mime_type']);
        $this->assertNull($details['page_count']);
    }

    public function test_a_word_document_is_labelled_as_such(): void
    {
        $details = $this->extract(
            $this->fakeFile('memo.docx', 32, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'memo.docx',
        );

        $this->assertSame('Word Document', $details['document_type']);
    }

    public function test_a_spreadsheet_is_labelled_as_such(): void
    {
        $details = $this->extract(
            $this->fakeFile('ledger.xlsx', 32, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'ledger.xlsx',
        );

        $this->assertSame('Excel Spreadsheet', $details['document_type']);
    }

    /**
     * A malformed PDF must not throw: an unreadable page count is never a reason
     * to refuse the upload.
     */
    public function test_a_malformed_pdf_does_not_throw_and_has_no_page_count(): void
    {
        $details = $this->extract($this->fakePdf('broken.pdf', 8), 'broken.pdf');

        $this->assertNull($details['page_count']);
    }

    public function test_it_reports_the_size_and_checksum_of_the_stored_bytes(): void
    {
        $details = $this->extract($this->fakeFile('ledger.pdf', 64, 'application/pdf'), 'ledger.pdf');

        $this->assertSame(64, $details['size_bytes']);
        $this->assertSame(
            hash_file('sha256', $this->storedPath('ledger.pdf')),
            $details['sha256'],
            'The checksum was not taken from the stored bytes.',
        );
    }

    public function test_two_identical_uploads_share_a_checksum(): void
    {
        $first = $this->extract($this->fakeFile('same.pdf', 64, 'application/pdf'), 'same.pdf');
        $second = $this->extract($this->fakeFile('same-copy.pdf', 64, 'application/pdf'), 'same-copy.pdf');

        $this->assertSame($first['sha256'], $second['sha256']);
    }

    /**
     * Store the upload and read its metadata back, the way intake does.
     *
     * @return array{sha256: string, size_bytes: int, mime_type: ?string, extension: ?string, page_count: ?int, document_type: string}
     */
    private function extract(UploadedFile $upload, string $path): array
    {
        Storage::disk((string) config('filesystems.default'))
            ->put($path, file_get_contents($upload->getPathname()));

        return $this->metadata->extract(
            $upload,
            (string) config('filesystems.default'),
            $path,
        );
    }

    private function storedPath(string $path): string
    {
        return Storage::disk((string) config('filesystems.default'))->path($path);
    }

    /**
     * A genuine minimal PDF whose page tree declares three pages, so the counter
     * has real structure to read rather than a fixture it cannot parse.
     */
    private function pdfUpload(string $name): UploadedFile
    {
        $pdf = "%PDF-1.4\n"
            ."1 0 obj<</Type /Catalog /Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type /Pages /Kids [3 0 R 4 0 R 5 0 R] /Count 3>>endobj\n"
            ."3 0 obj<</Type /Page /Parent 2 0 R>>endobj\n"
            ."4 0 obj<</Type /Page /Parent 2 0 R>>endobj\n"
            ."5 0 obj<</Type /Page /Parent 2 0 R>>endobj\n"
            ."trailer<</Root 1 0 R>>\n%%EOF";

        $path = tempnam(sys_get_temp_dir(), 'ordo-test-');
        file_put_contents($path, $pdf);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }
}
