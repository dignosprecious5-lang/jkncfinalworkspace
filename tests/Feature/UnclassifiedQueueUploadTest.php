<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\RecordIntakeItem;
use Illuminate\Http\UploadedFile;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * Uploading straight from the Unclassified Records queue.
 *
 * The reference mock drops files onto the queue page itself. It previously only
 * looked like it did: the dropzone was presentational, and both of its buttons
 * navigated to the separate upload screen. These cover the queue taking files
 * itself, and the queue posting them to the same endpoint that screen uses.
 */
class UnclassifiedQueueUploadTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
        $this->actingInCompany($this->company);
    }

    // ------------------------------------------------------- the dropzone

    public function test_the_queue_carries_a_working_dropzone(): void
    {
        $response = $this->get(route('records.unclassified'));

        $response->assertOk();
        // The upload scope, bound to the intake endpoint.
        $response->assertSee('queueUpload(', false);
        // @js() JSON-encodes for an Alpine attribute, and JSON escapes forward
        // slashes. Asserting the bare URL here only passed while a stale compiled
        // view was still being served; the assertion has to match what renders.
        $encoded = trim(json_encode(route('records.intake.store'), JSON_THROW_ON_ERROR), '"');

        $response->assertSee($encoded, false);
        // A real file picker, and real drag handlers.
        $response->assertSee('type="file"', false);
        $response->assertSee('x-on:drop.prevent', false);
        $response->assertSee('x-on:dragover.prevent', false);
    }

    /**
     * The regression this test exists for.
     *
     * The dropzone read "Drop files here to upload" while offering no way to
     * upload: both of its buttons were links to another page, so a user who
     * dropped a file on it was met with silence and an empty queue.
     */
    public function test_the_upload_buttons_open_the_picker_rather_than_navigating(): void
    {
        $response = $this->get(route('records.unclassified'));

        $response->assertOk();
        // Both are in-page buttons now.
        $response->assertSee('x-on:click="$refs.input.click()"', false);
        $response->assertDontSee('>Choose Files</a>', false);
        $response->assertDontSee('>+ Upload Records</a>', false);
    }

    /**
     * The dropzone must accept documents rather than directories of them.
     */
    public function test_the_picker_only_offers_supported_document_types(): void
    {
        $response = $this->get(route('records.unclassified'));

        $response->assertOk();
        $response->assertSee('.pdf,.txt,.csv,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx', false);
        $response->assertSee('multiple', false);
    }

    /**
     * The dropzone used to link out to the richer form instead of taking a camera
     * capture, so it now no longer advertises it.
     *
     * The form itself is untouched and still reachable: from the register header,
     * from the dashboard, and from each queued row's Details button. Only the
     * dropzone's second button changed.
     */
    public function test_the_dropzone_no_longer_links_out_to_the_detailed_form(): void
    {
        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertDontSee('Add details first', false);

        // Still a working page, just no longer the dropzone's way in.
        $this->get(route('records.intake.upload'))->assertOk();
    }

    /**
     * A file dropped outside the dropzone would otherwise navigate the browser
     * to it and lose the upload. The guard only engages once a drag is active.
     */
    public function test_a_drag_that_leaves_the_dropzone_is_caught_by_the_window(): void
    {
        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertSee('x-on:dragover.window="if (dragging) $event.preventDefault()"', false)
            ->assertSee('x-on:drop.window="if (dragging)', false);
    }

    // ---------------------------------------------------- camera capture

    /**
     * The dropzone's second action.
     *
     * All four parts have to be present together or the button does nothing: the
     * label, the dispatch that opens the camera, the camera itself mounted on this
     * page, and the listener that hands the photo back to the component that
     * posts it.
     */
    public function test_the_dropzone_offers_camera_capture(): void
    {
        $response = $this->get(route('records.unclassified'));

        $response->assertOk();
        $response->assertSee('Capture from Camera', false);
        $response->assertSee('x-on:click="$dispatch(\'ordo:camera-open\')"', false);
        $response->assertSee('x-data="cameraCapture()"', false);
        $response->assertSee('x-on:ordo:camera-photo.window="acceptPhoto($event.detail)"', false);
    }

    /**
     * The camera is a separate Alpine root, so it cannot reach the queue's state.
     *
     * It posts the photo back over a bubbling event instead, and the queue has to
     * be listening for it. Without the listener the button opens, a photo is
     * taken, and nothing is ever stored.
     */
    public function test_the_queue_listens_for_the_photo_the_camera_hands_back(): void
    {
        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertSee('x-on:ordo:camera-photo.window="acceptPhoto($event.detail)"', false);
    }

    /**
     * A captured photo is one file, so the count-based inference the picker and
     * the drop rely on would record it as a plain upload. The queue declares the
     * source instead, which the intake endpoint already accepts.
     */
    public function test_a_captured_photo_is_recorded_as_a_camera_capture(): void
    {
        $this->postJson(route('records.intake.store'), [
            'files' => [$this->fakeImage('camera-capture.jpg')],
            'capture_source' => 'camera',
        ])->assertRedirect();

        $this->assertDatabaseHas('record_intake_items', [
            'company_id' => $this->company->id,
            'capture_source' => 'camera',
        ]);

        // And it lands in the queue like anything else.
        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertSee('camera-capture.jpg')
            ->assertDontSee('No unclassified records');
    }

    /**
     * A camera capture costs the same as any other intake item: storage only, and
     * no Record Unit until it is filed.
     *
     * Asserted through recordUnits() rather than the table, because the units are
     * computed from the item's role — there is no column to assert on.
     */
    public function test_a_captured_photo_consumes_no_record_unit(): void
    {
        $this->postJson(route('records.intake.store'), [
            'files' => [$this->fakeImage('camera-capture.jpg')],
            'capture_source' => 'camera',
        ])->assertRedirect();

        $item = RecordIntakeItem::query()
            ->where('company_id', $this->company->id)
            ->where('capture_source', 'camera')
            ->firstOrFail();

        $this->assertSame(0, $item->recordUnits());
    }

    // ------------------------------------------------------ the JSON upload

    /**
     * The queue posts with Accept: application/json, so a rejected file comes
     * back as a 422 carrying the reason rather than a redirect to a form the
     * user is no longer standing on. Without this the browser would show the
     * queue with no indication that anything had failed.
     */
    public function test_a_rejected_file_returns_its_reason_as_json(): void
    {
        $response = $this->postJson(route('records.intake.store'), [
            'files' => [UploadedFile::fake()->create('payload.exe', 10)],
        ]);

        $response->assertStatus(422);

        // Read the bag directly: the key is literally "files.0", so a dotted
        // data_get path would look for a nested array that is not there.
        $errors = $response->json('errors');

        $this->assertArrayHasKey('files.0', $errors);
        $this->assertSame(
            'Each file must be a PDF, text, CSV, Word, Excel or image document.',
            $errors['files.0'][0],
        );
    }

    public function test_an_accepted_upload_creates_an_open_intake_item(): void
    {
        $response = $this->postJson(route('records.intake.store'), [
            'files' => [$this->fakeImage('dropped.jpg')],
            'capture_source' => 'upload',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('record_intake_items', [
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);

        // It is immediately visible in the queue, which is the whole point of
        // uploading from here rather than being sent elsewhere to do it.
        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertSee('dropped.jpg')
            ->assertDontSee('No unclassified records');
    }

    /**
     * A multi-file drop is recorded as one multi-file upload, not N singles.
     */
    public function test_a_multi_file_drop_is_recorded_as_one_upload(): void
    {
        $this->postJson(route('records.intake.store'), [
            'files' => [$this->fakeImage('one.jpg'), $this->fakeImage('two.jpg')],
            'capture_source' => 'multi_upload',
        ])->assertRedirect();

        $this->assertDatabaseHas('record_intake_items', [
            'company_id' => $this->company->id,
            'capture_source' => 'multi_upload',
        ]);
    }

    public function test_the_queue_upload_is_scoped_to_the_active_company(): void
    {
        $other = $this->seedSecondCompany($this->company);
        $this->actingInCompany($other);

        $this->postJson(route('records.intake.store'), [
            'files' => [$this->fakeImage('theirs.jpg')],
        ])->assertRedirect();

        // The item belongs to the company whose context was active, so the first
        // company's queue cannot show it.
        $this->actingInCompany($this->company);

        $this->get(route('records.unclassified'))
            ->assertOk()
            ->assertDontSee('theirs.jpg');
    }
}
