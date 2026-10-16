<?php

namespace Tests\Feature;

use App\Enums\CaptureSource;
use App\Enums\IntakeStatus;
use App\Models\Company;
use App\Models\RecordIntakeItem;
use App\Services\Records\RecordIntakeService;
use App\Support\FileSize;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class IntakeUploadPageTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordIntakeService $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
        $this->intake = $this->app->make(RecordIntakeService::class);
    }

    public function test_it_shows_the_empty_state_when_no_open_items_exist(): void
    {
        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('No unclassified records yet. Uploaded documents appear here until they are filed.');
        $response->assertDontSee('Ext. Ref No.');
        $response->assertDontSee('Awaiting classification and filing');
        $this->assertSame(0, $response->viewData('unclassifiedCount'));
    }

    public function test_it_renders_every_results_table_header(): void
    {
        $this->intakeItem([$this->fakePdf('gis.pdf', 20)]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('Ext. Ref No.', false);
        $response->assertSee('Record Units', false);
        $response->assertSee('Awaiting classification and filing');
    }

    public function test_the_upload_form_carries_the_attributes_the_javascript_depends_on(): void
    {
        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('enctype="multipart/form-data"', false);
        // Both the picker and the upload rule read config('ordo.document_extensions'),
        // so the dialog and the server cannot disagree about what may be uploaded.
        $response->assertSee($this->expectedAcceptAttribute(), false);
        $response->assertSee('name="files[]"', false);
        $response->assertSee('multiple x-ref="input"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('action="'.route('records.intake.store').'"', false);
        $response->assertSee('Upload to Unclassified Records', false);
    }

    public function test_the_upload_form_exposes_the_optional_intake_detail_fields(): void
    {
        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('Add Intake Details', false);
        $response->assertSee('name="title"', false);
        $response->assertSee('name="document_date"', false);
        $response->assertSee('name="external_reference_number"', false);
        $response->assertSee('name="source"', false);
        $response->assertSee('name="capture_source"', false);
    }

    public function test_it_renders_a_row_with_the_metadata_supplied_at_upload(): void
    {
        $this->intakeItem([$this->fakePdf('form-2307.pdf', 20)], [
            'title' => 'BIR Form 2307',
            'source' => 'Securities and Exchange Commission',
            'document_date' => '2026-01-15',
            'external_reference_number' => 'BIR-2307-XYZ',
        ]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('BIR Form 2307');
        $response->assertSee('Securities and Exchange Commission');
        $response->assertSee('2026-01-15');
        $response->assertSee('BIR-2307-XYZ');
    }

    public function test_it_falls_back_to_an_em_dash_for_blank_row_metadata(): void
    {
        $this->intakeItem([$this->fakePdf('scan.pdf', 20)]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertSame(
            3,
            preg_match_all('/<td class="px-3 py-2\.5 text-ordo-muted">—<\/td>/', $response->getContent()),
        );
    }

    public function test_it_shows_only_the_first_two_file_names_and_an_overflow_count(): void
    {
        $this->intakeItem([
            $this->fakePdf('one.pdf', 10),
            $this->fakePdf('two.pdf', 10),
            $this->fakePdf('three.pdf', 10),
            $this->fakePdf('four.pdf', 10),
        ]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('one.pdf');
        $response->assertSee('two.pdf');
        $response->assertSee('+2 more');
        $response->assertDontSee('three.pdf');
        $response->assertDontSee('four.pdf');
    }

    public function test_it_reports_the_humanised_size_and_states_that_intake_uses_no_record_units(): void
    {
        $item = $this->intakeItem([$this->fakePdf('quarterly.pdf', 1536)]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertSame('1.5 KB', FileSize::humanize($item->storage_bytes));
        $response->assertSee('1.5 KB');
        $response->assertSee('0 (storage only)');
    }

    #[DataProvider('openStatuses')]
    public function test_it_renders_the_status_label_and_tone_for_an_open_status(
        IntakeStatus $status,
        string $label,
        string $toneClasses,
    ): void {
        $item = $this->intakeItem([$this->fakePdf('gis.pdf', 10)]);
        $item->update(['status' => $status]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee($label);
        $response->assertSee($toneClasses, false);
    }

    /**
     * @return array<string, array{IntakeStatus, string, string}>
     */
    public static function openStatuses(): array
    {
        return [
            'pending' => [IntakeStatus::Pending, 'Pending', 'bg-[#f3f4f6] text-[#4b5563]'],
            'processing' => [IntakeStatus::Processing, 'Processing', 'bg-ordo-blue-soft text-ordo-blue'],
            'ready to file' => [IntakeStatus::ReadyToFile, 'Ready to file', 'bg-[#e9f7f0] text-ordo-success'],
            'needs review' => [IntakeStatus::NeedsReview, 'Needs review', 'bg-[#fff6e5] text-ordo-warn'],
        ];
    }

    public function test_it_highlights_the_selected_row_and_opens_the_details_panel(): void
    {
        $item = $this->intakeItem([$this->fakePdf('gis.pdf', 10)]);

        $response = $this->get(route('records.intake.upload', ['intake' => $item->id]));

        $response->assertOk();
        $this->assertTrue($response->viewData('intake')->is($item));
        $response->assertSee('Intake Item Details');
        $this->assertMatchesRegularExpression(
            '/<tr class="border-b border-ordo-line text-\[11px\] bg-ordo-blue-soft"/',
            $response->getContent(),
        );
    }

    public function test_it_omits_the_details_panel_when_no_item_is_selected(): void
    {
        $this->intakeItem([$this->fakePdf('gis.pdf', 10)]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertNull($response->viewData('intake'));
        $response->assertDontSee('Intake Item Details');
    }

    public function test_it_refuses_to_select_an_item_that_is_no_longer_open(): void
    {
        $item = $this->intakeItem([$this->fakePdf('filed.pdf', 10)]);
        $item->update(['status' => IntakeStatus::Filed]);

        $response = $this->get(route('records.intake.upload', ['intake' => $item->id]));

        $response->assertOk();
        $this->assertNull($response->viewData('intake'));
        $response->assertDontSee('Intake Item Details');
        $response->assertDontSee('filed.pdf');
    }

    public function test_the_details_panel_describes_every_attached_file(): void
    {
        $item = $this->intakeItem([$this->fakeImage('gis.jpg'), $this->fakePdf('map.pdf', 20)]);

        $response = $this->get(route('records.intake.upload', ['intake' => $item->id]));

        $response->assertOk();
        $response->assertSee('IMG');
        $response->assertSee('JPEG Image');
        $response->assertSee('image/jpeg');
        $response->assertSee('PDF');
        $response->assertSee('PDF Document');
        $response->assertSee('Upload');
        $response->assertSee('0 (storage only)');

        foreach ($item->files as $file) {
            $response->assertSee($file->refresh()->ocr_status->label());
            $response->assertSee(FileSize::humanize($file->size_bytes));
        }
    }

    public function test_the_details_panel_degrades_to_placeholders_for_an_unidentified_file(): void
    {
        $item = $this->intakeItem([$this->fakeFile('scan', 20, 'text/plain')], [
            'source' => 'Scan batch',
            'document_date' => '2026-03-01',
            'external_reference_number' => 'SCAN-1',
        ]);

        $item->files->first()->forceFill([
            'mime_type' => null,
            'extension' => null,
            'page_count' => null,
        ])->save();

        $response = $this->get(route('records.intake.upload', ['intake' => $item->id]));

        $response->assertOk();
        $response->assertSee('FILE');
        $response->assertDontSee('PDF Document');
        $response->assertDontSee('IMG');
        $this->assertSame(
            2,
            preg_match_all('/<td class="px-3 py-2\.5 text-ordo-muted">—<\/td>/', $response->getContent()),
        );
    }

    public function test_it_paginates_open_items_at_twenty_five_per_page(): void
    {
        foreach (range(1, 26) as $index) {
            $this->makeItem(['title' => sprintf('Item %02d', $index)]);
        }

        $first = $this->get(route('records.intake.upload'));

        $first->assertOk();
        $this->assertSame(26, $first->viewData('unclassifiedCount'));
        $this->assertCount(25, $first->viewData('intakeItems'));
        $this->assertTrue($first->viewData('intakeItems')->hasMorePages());
        $first->assertSee('Item 26');
        $first->assertDontSee('Item 01');
        $first->assertSee('Showing', false);

        $second = $this->get(route('records.intake.upload').'?page=2');

        $second->assertOk();
        $this->assertCount(1, $second->viewData('intakeItems'));
        $second->assertSee('Item 01');
        $second->assertDontSee('Item 26');
    }

    public function test_submitting_without_a_file_surfaces_the_chosen_copy(): void
    {
        $this->from(route('records.intake.upload'))
            ->followingRedirects()
            ->post(route('records.intake.store'), ['title' => 'Nothing attached'])
            ->assertOk()
            ->assertSee('Choose at least one file to upload.', false);
    }

    public function test_submitting_more_than_twenty_five_files_surfaces_the_chosen_copy(): void
    {
        $files = array_map(
            fn (int $index): UploadedFile => UploadedFile::fake()->create("scan-{$index}.jpg", 10, 'image/jpeg'),
            range(1, 26),
        );

        $this->from(route('records.intake.upload'))
            ->followingRedirects()
            ->post(route('records.intake.store'), ['files' => $files])
            ->assertOk()
            ->assertSee('A maximum of 25 files can be uploaded at once.', false);
    }

    public function test_submitting_an_unsupported_file_type_surfaces_the_chosen_copy(): void
    {
        $this->from(route('records.intake.upload'))
            ->followingRedirects()
            ->post(route('records.intake.store'), [
                'files' => [UploadedFile::fake()->create('payload.exe', 10)],
            ])
            ->assertOk()
            ->assertSee('Each file must be a PDF, text, CSV, Word, Excel or image document.', false);
    }

    public function test_submitting_a_file_over_twenty_megabytes_surfaces_the_chosen_copy(): void
    {
        $this->from(route('records.intake.upload'))
            ->followingRedirects()
            ->post(route('records.intake.store'), [
                'files' => [UploadedFile::fake()->create('huge.jpg', 20481, 'image/jpeg')],
            ])
            ->assertOk()
            ->assertSee('Each file must be 20 MB or smaller.', false);
    }

    public function test_validation_errors_are_keyed_by_file_position(): void
    {
        $response = $this->post(route('records.intake.store'), [
            'files' => [
                UploadedFile::fake()->create('first.exe', 10),
                UploadedFile::fake()->create('second.exe', 10),
            ],
        ]);

        $response->assertSessionHasErrors(['files.0', 'files.1']);
    }

    public function test_a_rejected_submission_still_flashes_the_typed_details_back(): void
    {
        $response = $this->post(route('records.intake.store'), ['title' => 'Retyped title']);

        $response->assertSessionHasErrors(['files']);
        $response->assertSessionHasInput('title', 'Retyped title');
    }

    public function test_a_successful_upload_redirects_to_the_new_item_and_confirms_on_screen(): void
    {
        $response = $this->post(route('records.intake.store'), [
            'files' => [$this->fakeImage('one.jpg'), $this->fakeImage('two.jpg')],
        ]);

        $item = RecordIntakeItem::query()->sole();

        $response->assertRedirect(route('records.intake.upload', ['intake' => $item->id]));

        $this->followingRedirects()
            ->post(route('records.intake.store'), [
                'files' => [$this->fakeImage('one.jpg'), $this->fakeImage('two.jpg')],
            ])
            ->assertOk()
            ->assertSee('2 file(s) added to Unclassified Records', false);
    }

    public function test_the_details_entered_in_the_form_reach_the_record_and_the_ui(): void
    {
        $this->post(route('records.intake.store'), [
            'files' => [$this->fakeImage('capture.jpg')],
            'capture_source' => 'camera',
            'title' => 'SEC Registration',
            'source' => 'Securities and Exchange Commission',
            'document_date' => '2026-02-01',
            'external_reference_number' => 'SEC-9-1',
        ]);

        $item = RecordIntakeItem::query()->sole();

        $this->assertSame(CaptureSource::Camera, $item->capture_source);
        $this->assertSame('SEC Registration', $item->title);
        $this->assertSame('Securities and Exchange Commission', $item->source);
        $this->assertSame('2026-02-01', $item->document_date->format('Y-m-d'));
        $this->assertSame('SEC-9-1', $item->external_reference_number);

        $this->get(route('records.intake.upload', ['intake' => $item->id]))
            ->assertOk()
            ->assertSee('Camera capture');
    }

    public function test_the_badge_counts_the_item_just_uploaded(): void
    {
        $this->post(route('records.intake.store'), [
            'files' => [$this->fakeImage('gis.jpg')],
        ]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('unclassifiedCount'));
        $response->assertSee('Awaiting classification and filing');
    }

    public function test_discarding_an_item_removes_it_from_the_unclassified_queue(): void
    {
        $item = $this->intake->createIntakeItem([$this->fakeImage('gis.jpg')], ['title' => 'Abandoned scan']);

        $this->get(route('records.unclassified'))->assertOk()->assertSee('Abandoned scan');

        $this->delete(route('records.unclassified.destroy', ['intake' => $item->id]))
            ->assertRedirect(route('records.unclassified'))
            ->assertSessionHas('status');

        $this->assertSame(IntakeStatus::Discarded, $item->fresh()->status);

        // The confirmation banner echoes the title, so assert the row itself is gone.
        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertSee('No unclassified records. Upload documents to begin.')
            ->assertDontSee('data-item-row=');
    }

    public function test_the_unclassified_queue_offers_a_discard_control_per_row(): void
    {
        $this->intake->createIntakeItem([$this->fakeImage('gis.jpg')], ['title' => 'Discardable']);

        $response = $this->get(route('records.unclassified'));

        $response->assertOk();
        $response->assertSee('Discard');
        $response->assertSee('Discard Unclassified Record');
        $response->assertSee('Nothing is deleted.', false);
    }

    /**
     * Blade directives are not compiled inside a component's attribute values, so a
     * stray @js() there ships to the browser as a JavaScript syntax error and the
     * control silently does nothing.
     */
    public function test_no_action_control_ships_an_uncompiled_blade_directive(): void
    {
        $this->intake->createIntakeItem([$this->fakeImage('gis.jpg')], ['title' => 'Directive check']);

        $content = $this->get(route('records.unclassified'))
            ->assertOk()
            ->getContent();

        foreach (['ordo:classify-open', 'ordo:discard-open'] as $directive) {
            $this->assertStringContainsString($directive, $content);
        }

        $this->assertStringNotContainsString('@js(', $content);
        $this->assertStringNotContainsString('@php', $content);
        $this->assertStringNotContainsString('{{', $content);

        // The discard payload must travel as data- attributes the browser can read.
        $this->assertStringContainsString('data-discard=', $content);
        $this->assertStringContainsString('dataset.discard', $content);
    }

    public function test_a_discarded_item_cannot_be_discarded_again_through_the_route(): void
    {
        $item = $this->intake->createIntakeItem([$this->fakeImage('gis.jpg')]);
        $item->update(['status' => IntakeStatus::Discarded]);

        $this->delete(route('records.unclassified.destroy', ['intake' => $item->id]))
            ->assertNotFound();
    }

    public function test_another_companys_intake_item_cannot_be_discarded(): void
    {
        $second = $this->seedSecondCompany($this->company);

        $this->actingInCompany($second);
        $item = $this->intake->createIntakeItem([$this->fakeImage('gis.jpg')]);

        $this->actingInCompany($this->company);

        $this->delete(route('records.unclassified.destroy', ['intake' => $item->id]))
            ->assertNotFound();

        $this->assertSame(IntakeStatus::Pending, $item->fresh()->status);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $attributes
     */
    private function intakeItem(array $files, array $attributes = []): RecordIntakeItem
    {
        return $this->intake->createIntakeItem($files, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeItem(array $attributes = []): RecordIntakeItem
    {
        $item = new RecordIntakeItem;
        $item->forceFill(array_merge([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'status' => IntakeStatus::Pending,
            'capture_source' => CaptureSource::Upload,
            'storage_bytes' => 0,
        ], $attributes));
        $item->save();

        return $item;
    }
}
