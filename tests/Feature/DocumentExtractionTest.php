<?php

namespace Tests\Feature;

use App\Enums\OcrStatus;
use App\Jobs\ProcessIntakeOcr;
use App\Models\Company;
use App\Models\RecordFile;
use App\Models\RecordIntakeItem;
use App\Services\Records\RecordIntakeService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * The reading pipeline now actually reads.
 *
 * Until this the job proved the async path and settled every file to Ready with an
 * ocr_error saying extraction was not implemented. That combination was the worst of
 * both: a file looked successfully read and carried an error message saying it had
 * not been. Extraction is now real, and the states it produces have to mean what
 * they say — a document with no text layer is a Needs Review case, not a success.
 *
 * The queue assertions here use a fake, which proves dispatch. Running the job
 * directly proves what it does when a worker picks it up. Neither substitutes for
 * a real `queue:work`, which is the only way to catch a missing tenant scope.
 */
class DocumentExtractionTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordIntakeItem $item;

    private RecordFile $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);

        $this->item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('scan.jpg')],
            ['title' => 'Readable Document'],
        );

        $this->file = $this->item->files()->firstOrFail();
    }

    // ---------------------------------------------------------------------
    // Dispatch.
    // ---------------------------------------------------------------------

    /**
 * The async wiring itself. Reading happens on a worker, never in the request, so a
 * user uploading twenty documents does not wait for twenty extractions.
 */
public function test_uploading_an_intake_item_queues_the_reading_job(): void
    {
        Queue::fake();

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakePdf('notes.pdf')],
            ['title' => 'Queued'],
        );

        Queue::assertPushed(ProcessIntakeOcr::class, function (ProcessIntakeOcr $job) use ($item): bool {
            // A worker has no session, so the job has to arrive knowing which
            // company it is reading for. Dispatching without these would make every
            // extraction a no-op at best.
            return $job->recordIntakeItemId === (string) $item->id
                && $job->tenantId === (string) $item->tenant_id
                && $job->companyId === (string) $item->company_id;
        });
    }

/**
 * A freshly uploaded file starts queued, so the drawer can show real progress
 * rather than guessing at one.
 */
public function test_a_newly_uploaded_file_starts_queued(): void
    {
        // The suite runs QUEUE_CONNECTION=sync, so without this the dispatch would
        // run the extraction inline and the file would already be settled by the
        // time this looked at it.
        Queue::fake();

        $item = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakePdf('notes.pdf')],
            ['title' => 'Queued'],
        );

        $this->assertSame(OcrStatus::Queued, $item->files()->firstOrFail()->ocr_status);
    }

    public function test_the_job_carries_its_tenant_and_company(): void
    {
        $job = new ProcessIntakeOcr(
            (string) $this->item->id,
            (string) $this->item->tenant_id,
            (string) $this->item->company_id,
        );

        $this->assertSame((string) $this->item->tenant_id, $job->tenantId);
        $this->assertSame((string) $this->item->company_id, $job->companyId);
    }

    // ---------------------------------------------------------------------
    // What the job does when it runs.
    // ---------------------------------------------------------------------

    public function test_a_readable_pdf_is_stored_with_its_text_and_marked_ready(): void
    {
        $this->storePdfWithText($this->file, 'The annual return was filed on time.');

        $this->runJob();

        $file = $this->file->fresh();

        $this->assertSame(OcrStatus::Ready, $file->ocr_status);
        $this->assertStringContainsString('The annual return was filed on time.', (string) $file->ocr_text);
        $this->assertNull($file->ocr_error);
        $this->assertNotNull($file->ocr_completed_at);
    }

    /**
     * The behaviour change that matters most. The old stub settled everything to
     * Ready, so a scan with no text layer looked identical to a document that had
     * been read.
     */
    public function test_a_pdf_with_no_text_layer_lands_in_needs_review(): void
    {
        $this->storeRaw($this->file, $this->minimalPdf(), 'scan.pdf');

        $this->runJob();

        $file = $this->file->fresh();

        $this->assertSame(OcrStatus::NeedsReview, $file->ocr_status);
        $this->assertStringContainsString('text layer', (string) $file->ocr_error);
        $this->assertSame('', (string) $file->ocr_text);
    }

    /**
     * AGENTS.md: an OCR failure must never cost us the original file.
     */
    public function test_a_failed_read_preserves_the_stored_file(): void
    {
        $this->storeRaw($this->file, 'this is not a pdf at all', 'broken.pdf');

        // Captured after the store, because storeRaw is what puts the bytes there:
        // the point is that the read leaves this path alone, not that it equals
        // whatever the intake service happened to allocate.
        $before = (string) $this->file->fresh()->path;

        $this->runJob();

        $file = $this->file->fresh();

        $this->assertSame(OcrStatus::NeedsReview, $file->ocr_status);
        $this->assertSame($before, $file->path);
        $this->assertTrue(Storage::disk($file->disk)->exists($file->path), 'The stored bytes must survive a failed read.');
    }

    /**
     * A scanned-image PDF is the common real case and must not throw: it simply has
     * no text to find.
     */
    public function test_a_corrupt_binary_does_not_throw(): void
    {
        $this->storeRaw($this->file, "\x00\x01\x02 not a pdf", 'corrupt.pdf');

        $this->runJob();

        $this->assertSame(OcrStatus::NeedsReview, $this->file->fresh()->ocr_status);
    }

    public function test_an_image_is_not_read_as_text(): void
    {
        $this->runJob();

        // The fixture is a JPEG, which carries no text layer. It must settle as
        // Needs Review rather than reporting empty text as a successful read.
        $this->assertSame(OcrStatus::NeedsReview, $this->file->fresh()->ocr_status);
    }

    // ---------------------------------------------------------------------
    // Scoping.
    // ---------------------------------------------------------------------

    /**
     * A worker has no session, so the job's own tenant and company are the only
     * thing between a queued job and another company's bytes.
     */
    public function test_the_job_never_touches_a_file_outside_its_company(): void
    {
        $this->storePdfWithText($this->file, 'Mine.');

        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);
        $foreignItem = $this->app->make(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('other.jpg')],
            ['title' => 'Theirs'],
        );
        $foreignFile = $foreignItem->files()->firstOrFail();

        // Scoped to the first company, so the second company's file is out of reach.
        (new ProcessIntakeOcr(
            (string) $this->item->id,
            (string) $this->item->tenant_id,
            (string) $this->item->company_id,
        ))->handle();

        $this->assertNotSame(OcrStatus::Ready, $foreignFile->fresh()->ocr_status);
        $this->assertSame(
            OcrStatus::Ready,
            $this->file->fresh()->ocr_status,
            'The file inside the job scope must still have been read.',
        );
    }

    private function runJob(): void
    {
        (new ProcessIntakeOcr(
            (string) $this->item->id,
            (string) $this->item->tenant_id,
            (string) $this->item->company_id,
        ))->handle();
    }

    /**
     * A structurally valid single-page PDF with a correct cross-reference table.
     *
     * Written out by hand rather than checked in as a fixture because the xref table
     * holds byte offsets, which go stale the moment the file is edited in a text
     * editor — and a PDF whose offsets are wrong fails to parse at all, which would
     * make every extraction assertion fail for a reason that has nothing to do with
     * extraction.
     *
     * @param  string|null  $text  Page text, or null for a page that draws nothing.
     */
private function pdf(?string $text): string
    {
        $content = $text === null
            ? ''
            : "BT /F1 12 Tf 20 100 Td (".$this->escapePdfText($text).") Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] /Contents 4 0 R'
                .' /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            // Offsets are byte positions, so this has to count bytes and not
            // characters — a non-ASCII title would shift every later entry.
            $offsets[$number] = strlen($pdf);

            $pdf .= ($number + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xref = strlen($pdf);

        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xref."\n%%EOF\n";

        return $pdf;
    }

    /**
     * PDF string literals escape three characters; an unescaped one truncates the
     * text or ends the string early.
     */
    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
    }

    private function storePdfWithText(RecordFile $file, string $text): void
    {
        $this->storeRaw($file, $this->pdf($text), 'readable.pdf');
    }

    /**
     * A valid PDF whose page carries no text-drawing operator, which is exactly what
     * a scan looks like to a text extractor.
     */
    private function minimalPdf(): string
    {
        return $this->pdf(null);
    }

    private function storeRaw(RecordFile $file, string $bytes, string $name): void
    {
        $path = 'intake/test/'.md5($name).'.pdf';

        Storage::disk($file->disk)->put($path, $bytes);

        $file->forceFill([
            'path' => $path,
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => strlen($bytes),
            'ocr_status' => OcrStatus::Queued,
            'ocr_text' => null,
            'ocr_error' => null,
        ])->save();
    }
}