<?php

namespace Tests\Feature;

use App\Enums\FileRole;
use App\Http\Controllers\Records\RecordFilePreviewController;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordClassification;
use App\Models\RecordFile;
use App\Models\RecordIndex;
use App\Services\Records\RecordAttachmentService;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Previewing a stored file.
 *
 * The defect these exist for: RecordController::preview() ordered a record's
 * files by the file_role *string*, and 'supporting' sorts before 'master', so a
 * record carrying any attachment previewed the attachment. The pane rendered an
 * iframe labelled with the master's name and its isPdf flag, pointed at a URL
 * streaming a supporting .txt, and showed nothing.
 *
 * Nothing caught it because no test asserted that a preview serves the right
 * bytes. The security tests only checked that a preview 404s for somebody else's
 * record — which it still did, while serving the wrong document to the right one.
 */
class RecordFilePreviewTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordIntakeService $intake;

    private RecordClassification $classification;

    private RecordIndex $index;

    /**
     * Per-company Classification and Index pairs, built on first use.
     *
     * @var array<string, array{0: RecordClassification, 1: RecordIndex}>
     */
    private array $schemes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
        $this->intake = $this->app->make(RecordIntakeService::class);

        [$this->classification, $this->index] = $this->schemeFor($this->company);
    }

    // ------------------------------------------- the regression, restated

    /**
     * The bug, as a test.
     *
     * Ordering by the role string put 'supporting' first, so the record preview
     * streamed the annex instead of the minutes.
     */
    public function test_the_preview_serves_the_master_and_not_a_supporting_file(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $master = $record->files()->where('file_role', FileRole::Master->value)->firstOrFail();

        $this->app->make(RecordAttachmentService::class)
            ->addSupportingFile($record, $this->realPdf('annex.pdf'));

        $response = $this->get(route('records.preview', ['record' => $record->id]));

        $response->assertOk();
        $this->assertStringContainsString(
            $master->original_name,
            (string) $response->headers->get('Content-Disposition'),
            'The preview served a supporting file instead of the master.',
        );
    }

    public function test_the_preview_serves_the_masters_bytes_not_another_files(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $master = $record->files()->where('file_role', FileRole::Master->value)->firstOrFail();

        $this->app->make(RecordAttachmentService::class)
            ->addSupportingFile($record, $this->realPdf('annex.pdf'));

        $response = $this->get(route('records.preview', ['record' => $record->id]));

        $this->assertSame(
            Storage::disk($master->disk)->get($master->path),
            $response->streamedContent(),
            'The preview streamed different bytes than the master document.',
        );
    }

    public function test_the_preview_serves_the_current_version_of_the_master(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $first = $record->files()->where('file_role', FileRole::Master->value)->firstOrFail();

        $this->app->make(RecordAttachmentService::class)
            ->addVersion($record, $this->realPdf('minutes-v2.pdf'));

        $current = $record->files()->where('file_role', FileRole::Master->value)
            ->orderByDesc('version')->firstOrFail();

        $this->assertNotSame($first->id, $current->id, 'The version was not stored.');

        $response = $this->get(route('records.preview', ['record' => $record->id]));

        $this->assertStringContainsString(
            'minutes-v2.pdf',
            (string) $response->headers->get('Content-Disposition'),
            'The preview served a superseded version of the master.',
        );
    }

    public function test_a_supporting_file_stored_last_does_not_become_the_preview(): void
    {
        // The ordering trap in its purest form: a supporting file with the
        // highest version number on the record, added last.
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));

        $this->app->make(RecordAttachmentService::class)
            ->addVersion($record, $this->realPdf('minutes-v2.pdf'));
        $this->app->make(RecordAttachmentService::class)
            ->addSupportingFile($record, $this->realPdf('annex.pdf'));

        $latest = $record->files()->orderByDesc('version')->firstOrFail();
        $this->assertSame(FileRole::Supporting, $latest->file_role, 'The fixture no longer covers the trap.');

        $response = $this->get(route('records.preview', ['record' => $record->id]));

        $this->assertStringContainsString(
            'minutes-v2.pdf',
            (string) $response->headers->get('Content-Disposition'),
            'The highest version number won, even though it was a supporting file.',
        );
    }

    // ------------------------------------------------------------- headers

    public function test_a_preview_is_served_inline_with_the_files_own_content_type(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $master = $record->files()->firstOrFail();

        $response = $this->get(route('records.preview', ['record' => $record->id]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        // inline, not attachment: a download disposition is what made the pane
        // offer a file rather than show one.
        $this->assertStringContainsString('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame((string) $master->size_bytes, (string) $response->headers->get('Content-Length'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    // --------------------------------------------------------- the file route

    public function test_the_file_route_serves_the_named_file(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $annex = $this->app->make(RecordAttachmentService::class)
            ->addSupportingFile($record, $this->realPdf('annex.pdf'));

        $response = $this->get(route('records.files.preview', ['file' => $annex->id]));

        $response->assertOk();
        $this->assertStringContainsString('annex.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame(
            Storage::disk($annex->disk)->get($annex->path),
            $response->streamedContent(),
            'The file route did not serve the file it named.',
        );
    }

    public function test_the_file_route_gives_every_file_in_a_record_its_own_bytes(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $attachments = $this->app->make(RecordAttachmentService::class);

        $annex = $attachments->addSupportingFile($record, $this->realPdf('annex.pdf'));
        $second = $attachments->addSupportingFile($record, $this->realPdf('exhibit-b.pdf'));

        $served = $record->files()->get()->map(
            fn (RecordFile $file): string => (string) $this->get(route('records.files.preview', ['file' => $file->id]))
                ->headers->get('Content-Disposition'),
        );

        $this->assertCount(3, $served->unique(), 'Two files served the same disposition.');
        $this->assertTrue($served->contains(fn (string $h): bool => str_contains($h, 'annex.pdf')));
        $this->assertTrue($served->contains(fn (string $h): bool => str_contains($h, 'exhibit-b.pdf')));
        $this->assertNotSame($annex->id, $second->id);
    }

    public function test_the_file_route_will_not_serve_another_companys_file(): void
    {
        $theirs = $this->seedSecondCompany($this->company);
        $this->actingInCompany($theirs);
        $theirRecord = $this->filedRecord($this->realPdf('theirs.pdf'), $theirs);
        $theirFile = $theirRecord->files()->firstOrFail();

        $this->actingInCompany($this->company);

        $this->get(route('records.files.preview', ['file' => $theirFile->id]))->assertNotFound();
    }

    public function test_the_file_route_404s_for_an_id_that_does_not_exist(): void
    {
        $this->get(route('records.files.preview', ['file' => '01JZZZZZZZZZZZZZZZZZZZZZZ']))
            ->assertNotFound();
    }

    public function test_the_file_route_carries_exactly_one_parameter(): void
    {
        // Laravel resolves only class-typed parameters by name; everything else
        // arrives positionally in route order, so a second parameter here would
        // silently hand this method the wrong value.
        $this->assertCount(1, $this->route('records.files.preview')->parameterNames());

        $action = new \ReflectionMethod(RecordFilePreviewController::class, 'preview');

        $this->assertSame(1, $action->getNumberOfParameters());
        $this->assertSame('string', (string) $action->getParameters()[0]->getType());
    }

    // -------------------------------------------------------------- payloads

    public function test_each_file_in_the_payload_carries_its_own_preview_url(): void
    {
        $record = $this->filedRecord($this->realPdf('minutes.pdf'));
        $this->app->make(RecordAttachmentService::class)
            ->addSupportingFile($record, $this->realPdf('annex.pdf'));

        $files = $record->files()->get();

        $this->assertCount(2, $files, 'The fixture did not produce a master and a supporting file.');

        $urls = $files->map(
            fn (RecordFile $file): string => route('records.files.preview', ['file' => $file->id]),
        );

        $this->assertCount($files->count(), $urls->unique(), 'Files shared a preview URL.');
    }

    public function test_the_intake_preview_uses_the_same_rule_as_the_record_preview(): void
    {
        $item = $this->intake->createIntakeItem([$this->realPdf('queued.pdf')]);
        $first = $item->files()->firstOrFail();

        $response = $this->get(route('records.unclassified.preview', ['intake' => $item->id]));

        $response->assertOk();
        $this->assertStringContainsString(
            $first->original_name,
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertSame(
            Storage::disk($first->disk)->get($first->path),
            $response->streamedContent(),
        );
    }

    // ------------------------------------------------------------- rendering

    public function test_an_unrenderable_file_is_described_rather_than_merely_refused(): void
    {
        $this->filedRecord($this->wordDocument('minutes.doc'));

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        // The new AGENTS.md rule: extension, size and MIME type, plus an action.
        $this->assertStringContainsString('Download Original', $content);
        $this->assertStringContainsString('x-text="humanSize(master.sizeBytes)"', $content);
        $this->assertStringContainsString('master.extension', $content);
        $this->assertStringContainsString('master.mimeType', $content);
    }

    public function test_the_files_tab_opens_each_file_individually(): void
    {
        $this->filedRecord($this->realPdf('minutes.pdf'));

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        // One row template for supporting files, one for version history, and the
        // master chip. Each is bound to that row's own file, not the record's.
        $this->assertSame(2, substr_count($content, 'x-bind:href="file.previewUrl"'));

        // Two, not one: the preview pane's own Open File bar carries the same
        // expression as the Files tab's master chip.
        $this->assertSame(2, substr_count($content, 'x-bind:href="master ? master.previewUrl'));
    }

    // ---------------------------------------------------------------- helpers

    private function filedRecord(UploadedFile $upload, ?Company $company = null): Record
    {
        $company ??= $this->company;

        [$classification, $index] = $this->schemeFor($company);

        $item = $this->intake->createIntakeItem([$upload]);

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ])->refresh();
    }

    /**
     * A file with real PDF bytes.
     *
     * FakeUploads::fakePdf() writes a run of 'a's with a declared PDF type, and
     * FileMetadata sniffs the stored bytes rather than trusting the client — so a
     * fixture has to actually start with %PDF- to be stored as a PDF.
     */
    private function realPdf(string $name, int $pages = 1): UploadedFile
    {
        $body = "%PDF-1.4\n";

        for ($page = 1; $page <= $pages; $page++) {
            $body .= "1 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n";
        }

        $body .= 'trailer<</Root 1 0 R/Size '.($pages + 1).'>>'."\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'ordo-test-');
        file_put_contents($path, $body);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    /**
     * A legacy Word document, with the OLE compound-file magic a browser cannot
     * render and a MIME sniffer will not call a PDF or an image.
     *
     * That is the whole point of the fixture: this is the case the fallback card
     * exists for, and a .doc whose bytes were really a PDF would quietly stop
     * covering it.
     */
    private function wordDocument(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ordo-test-');
        file_put_contents(
            $path,
            hex2bin('d0cf11e0a1b11ae1').str_repeat("\0", 120),
        );

        return new UploadedFile($path, $name, 'application/msword', null, true);
    }

    /**
     * @return array{0: RecordClassification, 1: RecordIndex}
     */
    private function schemeFor(Company $company): array
    {
        if (! isset($this->schemes[$company->id])) {
            $classification = new RecordClassification;
            $classification->forceFill([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
                'code' => '301',
                'name' => 'Entity & Ownership',
                'is_active' => true,
            ])->save();

            $index = new RecordIndex;
            $index->forceFill([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
                'record_classification_id' => $classification->id,
                'code' => '301-6',
                'name' => 'GIS',
                'sort_order' => 0,
                'is_active' => true,
            ])->save();

            $this->schemes[$company->id] = [$classification, $index];
        }

        return $this->schemes[$company->id];
    }

    private function route(string $name): RoutingRoute
    {
        return app('router')->getRoutes()->getByName($name) ?? $this->fail("Route [{$name}] is not registered.");
    }
}
