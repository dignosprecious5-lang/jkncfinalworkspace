<?php

namespace Tests\Feature;

use App\Enums\FileRole;
use App\Enums\OcrStatus;
use App\Models\Company;
use App\Models\RecordFile;
use App\Services\Records\DocumentRenderService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Word and Excel documents shown as a page.
 *
 * Both formats are ZIP archives of XML, so this needs no package, no converter
 * binary and nothing installed on the server — which is the entire reason it is
 * worth doing. The tests build real archives and read real files rather than
 * mocking the parser, because a mocked parser proves nothing about whether a
 * document comes out right.
 *
 * The test that matters most is the escaping one. The content here is a file the
 * reader uploaded, parsed and re-emitted as HTML, so anything that reaches the
 * page unescaped is a stored XSS on the application's own origin.
 */
class DocumentRenderTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    // ------------------------------------------------------------------ docx

    public function test_a_docx_is_shown_as_its_own_words(): void
    {
        $file = $this->docx('minutes.docx', [
            'Minutes of the Board Meeting',
            'Held 12 March 2026',
            'The Chair opened the meeting.',
        ]);

        $page = $this->render($file);

        $this->assertStringContainsString('Minutes of the Board Meeting', $page);
        $this->assertStringContainsString('Held 12 March 2026', $page);
        $this->assertStringContainsString('The Chair opened the meeting.', $page);
    }

    public function test_a_docx_heading_becomes_a_heading(): void
    {
        $file = $this->docx('report.docx', [], [['text' => 'RESOLVED', 'style' => 'Heading2']]);

        $this->assertStringContainsString('<h3>RESOLVED</h3>', $this->render($file));
    }

    public function test_a_docx_table_becomes_a_table(): void
    {
        $file = $this->docx('minutes.docx', [], [], <<<'XML'
            <w:tbl>
              <w:tr><w:tc><w:p><w:r><w:t>Name</w:t></w:r></w:p></w:tc>
                     <w:tc><w:p><w:r><w:t>Share</w:t></w:r></w:p></w:tc></w:tr>
              <w:tr><w:tc><w:p><w:r><w:t>Acme</w:t></w:r></w:p></w:tc>
                     <w:tc><w:p><w:r><w:t>60%</w:t></w:r></w:p></w:tc></w:tr>
            </w:tbl>
            XML);

        $page = $this->render($file);

        $this->assertStringContainsString('<table>', $page);
        $this->assertStringContainsString('<th>Name</th>', $page);
        $this->assertStringContainsString('<td>Acme</td>', $page);
    }

    public function test_a_docx_list_becomes_a_closed_list(): void
    {
        $file = $this->docx('minutes.docx', ['First item', 'Second item', 'A closing paragraph'], [], '', numbered: [true, true, false]);

        $page = $this->render($file);

        $this->assertStringContainsString('<ul>', $page);
        $this->assertStringContainsString('</ul>', $page);
        // A list left open would swallow the paragraph after it.
        $this->assertStringContainsString('</ul><p>A closing paragraph</p>', $page);
    }

    /**
     * The test the whole feature rests on.
     *
     * A .docx is a ZIP a reader uploaded. Its paragraph text is attacker-supplied,
     * and this output is served as HTML, so anything that reaches the page
     * unescaped is script running on the application's own origin.
     */
    public function test_markup_inside_a_docx_comes_out_escaped(): void
    {
        $file = $this->docx('invoice.docx', [
            '<script>alert(document.cookie)</script>',
            '"><img src=x onerror=alert(1)>',
        ]);

        $page = $this->render($file);

        // The escaped form still reads "onerror=" as plain characters, which is
        // harmless. What must not appear is anything the browser would parse as
        // a tag or an attribute.
        $this->assertStringNotContainsString('<script>', $page);
        $this->assertStringNotContainsString('<img', $page);
        $this->assertStringContainsString('&lt;script&gt;', $page);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $page);
    }

    public function test_a_docx_with_no_body_says_so_rather_than_failing(): void
    {
        $file = $this->docx('empty.docx', []);

        $this->assertStringContainsString('no readable content', $this->render($file));
    }

    // ------------------------------------------------------------------ xlsx

    public function test_an_xlsx_is_shown_as_a_table_of_its_values(): void
    {
        $file = $this->xlsx('ledger.xlsx', [
            ['Name', 'Share'],
            ['Acme Holdings', 60],
            ['Beta Trust', 40],
        ]);

        $page = $this->render($file);

        $this->assertStringContainsString('<table>', $page);
        $this->assertStringContainsString('<td>Acme Holdings</td>', $page);
        $this->assertStringContainsString('<td>60</td>', $page);
        $this->assertStringContainsString('Ledger', $page, 'The sheet name should head the table.');
    }

    /**
     * Cell text almost never lives in the sheet. Excel writes a number and an
     * index into a shared table held elsewhere in the archive, so a renderer that
     * does not resolve that table shows a sheet of numbers where the names were.
     */
    public function test_an_xlsx_resolves_its_shared_strings(): void
    {
        $file = $this->xlsx('shared.xlsx', [['Registered Name'], ['ABC Corporation']]);

        $page = $this->render($file);

        $this->assertStringContainsString('Registered Name', $page);
        $this->assertStringContainsString('ABC Corporation', $page);
    }

    public function test_markup_inside_a_cell_comes_out_escaped(): void
    {
        $file = $this->xlsx('payload.xlsx', [['<script>alert(1)</script>']]);

        $page = $this->render($file);

        $this->assertStringNotContainsString('<script>', $page);
        $this->assertStringContainsString('&lt;script&gt;', $page);
    }

    public function test_a_huge_sheet_is_capped_and_says_so(): void
    {
        $rows = [];

        for ($i = 0; $i < 400; $i++) {
            $rows[] = ['Row '.$i];
        }

        $file = $this->xlsx('big.xlsx', $rows);

        $page = $this->render($file);

        $this->assertStringContainsString('Row 0', $page);
        $this->assertStringNotContainsString('Row 399', $page);
        $this->assertStringContainsString('Download the original', $page);
    }

    // ------------------------------------------------------ damaged archives

    /**
     * A damaged or mislabelled file is a normal thing to be handed, not a fault.
     * It is stored perfectly well, so the page says so and the original still
     * downloads.
     */
    public function test_a_file_that_is_not_really_an_office_archive_degrades_to_a_message(): void
    {
        $path = 'records/'.uniqid('render-', true).'/broken.docx';
        Storage::disk((string) config('filesystems.default'))
            ->put($path, 'this is not a zip archive, it is a legacy OLE file in disguise');

        $file = $this->stored($path, 'broken.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->get(route('records.files.rendered', ['file' => $file->id]));

        $response->assertOk();
        $response->assertSee('could not be opened for preview');
    }

    public function test_a_legacy_doc_has_nothing_to_render(): void
    {
        $file = $this->stored('records/x/legacy.doc', 'contract.doc', 'application/msword');

        $this->assertNull(app(DocumentRenderService::class)->page($file));
        $this->assertFalse(app(DocumentRenderService::class)->canRender($file));

        $this->get(route('records.files.rendered', ['file' => $file->id]))->assertNotFound();
    }

    // ------------------------------------------------------------- the surface

    public function test_a_rendered_document_is_sandboxed_and_never_cached(): void
    {
        $file = $this->docx('minutes.docx', ['Minutes']);

        $response = $this->get(route('records.files.rendered', ['file' => $file->id]));

        $response->assertOk();
        $this->assertSame('text/html; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('sandbox', $csp);
        // No allow-same-origin means the document runs in an opaque origin and
        // cannot read this session's cookies.
        $this->assertStringNotContainsString('allow-same-origin', $csp);
        // default-src 'none' denies scripts even though no script-src is named.
        $this->assertStringContainsString("default-src 'none'", $csp);
        $this->assertStringNotContainsString('script-src', $csp);
    }

    /**
     * The XSS boundary must not widen just because we can now show Office files.
     *
     * An .xlsx is drawn from HTML we generated, but its *bytes* are still a ZIP
     * archive, and they still go to the browser as a download. Rendering a
     * document and trusting its bytes are two different decisions.
     */
    public function test_an_office_archive_is_never_streamed_inline_from_the_preview_route(): void
    {
        foreach (['minutes.docx', 'ledger.xlsx'] as $name) {
            $file = $name === 'minutes.docx'
                ? $this->docx($name, ['Minutes'])
                : $this->xlsx($name, [['Cell']]);

            $response = $this->get(route('records.files.preview', ['file' => $file->id]));

            $response->assertOk();
            $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'), $name.' was served inline.');
            $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        }
    }

    public function test_the_rendered_route_will_not_serve_another_companys_document(): void
    {
        $theirs = $this->seedSecondCompany($this->company);
        $this->actingInCompany($theirs);

        $path = 'records/'.uniqid('render-', true).'/theirs.docx';
        Storage::disk((string) config('filesystems.default'))->put($path, 'x');

        $file = $this->stored($path, 'theirs.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $theirs->id);

        $this->actingInCompany($this->company);

        $this->get(route('records.files.rendered', ['file' => $file->id]))->assertNotFound();
    }

    public function test_the_drawer_is_offered_a_rendered_frame_for_office_documents(): void
    {
        $this->assertStringContainsString(
            'master && master.renderedUrl',
            file_get_contents(resource_path('views/records/partials/record-drawer.blade.php')),
        );

        $this->assertStringContainsString(
            'master && master.renderedUrl',
            file_get_contents(resource_path('views/records/partials/classify-drawer.blade.php')),
        );
    }

    // ---------------------------------------------------------------- helpers

    private function render(RecordFile $file): string
    {
        $page = app(DocumentRenderService::class)->page($file);

        $this->assertNotNull($page, 'The document could not be rendered at all.');

        return $page;
    }

    /**
     * A real .docx, built as a ZIP the way Word builds one.
     *
     * @param  list<string>  $paragraphs
     * @param  list<array{text: string, style?: string}>  $styled
     * @param  list<bool>  $numbered  per paragraph
     */
    private function docx(
        string $name,
        array $paragraphs,
        array $styled = [],
        string $table = '',
        array $numbered = [],
    ): RecordFile {
        $body = '';

        foreach ($paragraphs as $index => $text) {
            $style = ($numbered[$index] ?? false)
                ? '<w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr>'
                : '';

            $body .= '<w:p>'.$style.'<w:r><w:t xml:space="preserve">'
                .htmlspecialchars($text, ENT_XML1).'</w:t></w:r></w:p>';
        }

        foreach ($styled as $item) {
            $style = sprintf('<w:pPr><w:pStyle w:val="%s"/></w:pPr>', $item['style']);

            $body .= '<w:p>'.$style.'<w:r><w:t xml:space="preserve">'
                .htmlspecialchars($item['text'], ENT_XML1).'</w:t></w:r></w:p>';
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$body.$table.'</w:body></w:document>';

        $parts = [
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>',
            'word/document.xml' => $document,
        ];

        return $this->archive($name, $parts);
    }

    /**
     * A real .xlsx, built the way Excel builds one: values in the sheet, and
     * every string held once in the shared table and referenced by index.
     *
     * @param  list<list<string|int>>  $rows
     */
    private function xlsx(string $name, array $rows): RecordFile
    {
        $shared = [];
        $index = [];

        foreach ($rows as $row) {
            foreach ($row as $value) {
                if (is_int($value)) {
                    continue;
                }

                if (! array_key_exists($value, $index)) {
                    $index[$value] = count($shared);
                    $shared[] = $value;
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        $rowNumber = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            $column = 'A';
            $xml .= '<row r="'.$rowNumber.'">';

            foreach ($row as $value) {
                if (is_int($value)) {
                    $xml .= '<c r="'.$column.$rowNumber.'"><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$column.$rowNumber.'" t="s"><v>'.$index[$value].'</v></c>';
                }

                $column++;
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($shared).'" uniqueCount="'.count($shared).'">';

        foreach ($shared as $value) {
            $sharedXml .= '<si><t xml:space="preserve">'.htmlspecialchars($value, ENT_XML1).'</t></si>';
        }

        $sharedXml .= '</sst>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheets><sheet name="Ledger" sheetId="1" r:id="rId1"/></sheets></workbook>';

        return $this->archive($name, [
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>',
            'xl/workbook.xml' => $workbook,
            'xl/sharedStrings.xml' => $sharedXml,
            'xl/worksheets/sheet1.xml' => $xml,
        ]);
    }

    /**
     * @param  array<string, string>  $parts
     */
    private function archive(string $name, array $parts): RecordFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'ordo-archive-');
        $zip = new ZipArchive;
        $zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($parts as $entry => $contents) {
            $zip->addFromString($entry, $contents);
        }

        $zip->close();

        $path = 'records/'.uniqid('render-', true).'/'.uniqid().'-'.$name;

        Storage::disk((string) config('filesystems.default'))->put($path, file_get_contents($temp));
        @unlink($temp);

        return $this->stored(
            $path,
            $name,
            match (pathinfo($name, PATHINFO_EXTENSION)) {
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
        );
    }

    private function stored(string $path, string $name, string $mime, ?string $company = null): RecordFile
    {
        $owner = $company ?? $this->company->id;

        return RecordFile::query()->create([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $owner,
            'file_role' => FileRole::Master,
            'disk' => (string) config('filesystems.default'),
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mime,
            'extension' => pathinfo($name, PATHINFO_EXTENSION),
            'size_bytes' => 128,
            'checksum_sha256' => hash('sha256', $path),
            'ocr_status' => OcrStatus::Ready,
        ]);
    }
}
