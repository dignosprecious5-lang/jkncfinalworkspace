<?php

namespace Tests\Feature;

use App\Enums\OcrStatus;
use App\Http\Controllers\Records\RecordFileReadingController;
use App\Jobs\ProcessIntakeOcr;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordFile;
use App\Models\RecordIndex;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionMethod;
use Tests\Support\FakeUploads;
use Tests\TestCase;

/**
 * "Re-read Document" in the record and classify previews.
 *
 * The control puts one file back on the queue and the preview follows it to a
 * terminal state. These cover the parts that could quietly go wrong: a file read
 * belonging to somebody else, a click that stacks duplicate jobs, and a file with
 * no intake item behind it — which is the case the original job could not reach.
 */
class RecordFileReReadTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    private RecordIntakeService $intake;

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

        $this->schemeFor($this->company);
    }

    // ------------------------------------------------------------- re-reading

    public function test_it_queues_the_file_and_reports_its_state(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $this->postJson($this->reReadUrl($record, $file))
            ->assertOk()
            ->assertJson([
                'ocrStatus' => OcrStatus::Queued->value,
                'ocrTerminal' => false,
            ]);

        $this->assertSame(OcrStatus::Queued, $file->refresh()->ocr_status);
    }

    public function test_the_job_is_scoped_to_the_one_file_that_was_asked_for(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $this->postJson($this->reReadUrl($record, $file))->assertOk();

        Queue::assertPushed(ProcessIntakeOcr::class, function (ProcessIntakeOcr $job) use ($file, $record): bool {
            return $job->recordFileId === (string) $file->id
                && $job->tenantId === (string) $record->tenant_id
                && $job->companyId === (string) $record->company_id;
        });
    }

    /**
     * A re-read is for one document. Without the per-file scope the job would
     * sweep every file the original upload happened to carry.
     */
    public function test_re_reading_one_file_leaves_its_siblings_alone(): void
    {
        Queue::fake();

        $item = $this->intake->createIntakeItem([
            $this->fakePdf('first.pdf', 64),
            $this->fakePdf('second.pdf', 64),
        ]);

        $target = $item->files->first();
        $sibling = $item->files->last();

        (new ProcessIntakeOcr((string) $item->id, (string) $this->company->tenant_id, (string) $this->company->id, (string) $target->id))
            ->handle();

        $this->assertNotNull(
            $target->refresh()->ocr_completed_at,
            'The named file was not read.',
        );
        $this->assertNull(
            $sibling->refresh()->ocr_completed_at,
            'A file-scoped read reached the other files in the intake.',
        );
    }

    /**
     * A supporting file or a new version carries no intake item at all, so the
     * original intake-scoped lookup returned nothing for one and the read was a
     * silent no-op. This is the case the per-file scope exists to fix.
     */
    public function test_a_file_with_no_intake_item_behind_it_is_still_read(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $file->forceFill(['record_intake_item_id' => null])->save();

        (new ProcessIntakeOcr(null, (string) $this->company->tenant_id, (string) $this->company->id, (string) $file->id))
            ->handle();

        $this->assertNotNull(
            $file->refresh()->ocr_completed_at,
            'A file with no intake item was never read.',
        );
    }

    public function test_the_previous_error_is_cleared_when_the_file_is_queued_again(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $file->forceFill([
            'ocr_status' => OcrStatus::NeedsReview,
            'ocr_error' => 'The reading service did not respond.',
            'ocr_completed_at' => now(),
        ])->save();

        $this->postJson($this->reReadUrl($record, $file))->assertOk()->assertJson(['ocrError' => null]);

        $refreshed = $file->refresh();

        $this->assertSame(OcrStatus::Queued, $refreshed->ocr_status);
        $this->assertNull($refreshed->ocr_error);
        $this->assertNull($refreshed->ocr_completed_at);
    }

    /**
     * A read that is genuinely in progress must not be stacked on top of.
     */
    public function test_clicking_again_mid_read_does_not_stack_another_job(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        // A worker has picked the job up: the file is actively being read.
        $file->forceFill(['ocr_status' => OcrStatus::Reading])->save();

        // Cleared again so only this request's dispatch is counted; filing and
        // intake both queue a job of their own.
        Queue::fake();

        $this->postJson($this->reReadUrl($record, $file))
            ->assertOk()
            ->assertJson(['ocrStatus' => OcrStatus::Reading->value]);

        Queue::assertNothingPushed();
    }

    /**
     * Queued is not proof that anything is happening. The queue is
     * database-backed and the prototype may have no worker running, in which case
     * a file sits on Queued indefinitely — which is precisely when a reader
     * reaches for this control.
     */
    public function test_a_file_stuck_on_queued_can_be_queued_again(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $file->forceFill(['ocr_status' => OcrStatus::Queued])->save();

        Queue::fake();

        $this->postJson($this->reReadUrl($record, $file))
            ->assertOk()
            ->assertJson(['ocrStatus' => OcrStatus::Queued->value]);

        Queue::assertPushed(ProcessIntakeOcr::class, 1);
    }

    public function test_it_records_the_request_in_the_trail(): void
    {
        Queue::fake();

        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $this->postJson($this->reReadUrl($record, $file))->assertOk();

        $this->assertSame(
            1,
            RecordActivityLog::query()
                ->where('action', 'record.reread_requested')
                ->where('record_id', $record->id)
                ->where('record_file_id', $file->id)
                ->count(),
            'The re-read was not written to the activity trail.',
        );
    }

    // ---------------------------------------------------------------- status

    public function test_the_status_route_reports_the_files_real_state(): void
    {
        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $this->getJson($this->statusUrl($record, $file))
            ->assertOk()
            ->assertJson([
                'id' => (string) $file->id,
                'ocrTerminal' => true,
            ])
            ->assertJsonStructure([
                'ocrStatus', 'ocrStatusLabel', 'ocrTerminal', 'ocrError', 'ocrText', 'ocrCompletedAt',
            ]);
    }

    /**
     * The preview explains an empty OCR pane with the file's own reason rather
     * than a generic message, so that reason has to survive to the reader.
     */
    public function test_the_status_route_returns_the_recorded_reason_verbatim(): void
    {
        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $file->forceFill([
            'ocr_status' => OcrStatus::Ready,
            'ocr_error' => 'Document reading is not implemented yet.',
        ])->save();

        $this->getJson($this->statusUrl($record, $file))
            ->assertOk()
            ->assertJson(['ocrError' => 'Document reading is not implemented yet.']);
    }

    // --------------------------------------------------------------- scoping

    public function test_another_companys_file_cannot_be_re_read(): void
    {
        Queue::fake();

        $other = $this->seedSecondCompany($this->company);

        $this->actingInCompany($other);
        $theirs = $this->filedRecord('Theirs', $other);

        $this->actingInCompany($this->company);

        $theirFile = $theirs->files->first()->refresh();
        $before = $theirFile->ocr_status;

        $this->postJson(route('records.files.re-read', ['file' => $theirFile->id]))->assertNotFound();

        // Compared against the state before the request rather than against
        // "not queued": filing itself leaves a file Queued, so asserting an
        // absolute value would pass even if this request had queued it again.
        $this->assertSame(
            $before,
            $theirFile->refresh()->ocr_status,
            "Another company's file was queued by a request scoped to this one.",
        );
    }

    public function test_a_file_that_does_not_exist_is_a_404(): void
    {
        Queue::fake();

        $this->postJson(route('records.files.re-read', ['file' => '01ARZ3NDEKTSV4RRFFQ69G5FAV']))
            ->assertNotFound();
    }

    public function test_a_status_request_for_another_companys_file_is_a_404(): void
    {
        $other = $this->seedSecondCompany($this->company);

        $this->actingInCompany($other);
        $theirs = $this->filedRecord('Theirs', $other);

        $this->actingInCompany($this->company);

        $theirFile = $theirs->files->first()->refresh();

        $this->getJson(route('records.files.status', ['file' => $theirFile->id]))->assertNotFound();
    }

    // --------------------------------------------------- unfiled intake files

    public function test_an_unfiled_intake_file_can_be_re_read(): void
    {
        Queue::fake();

        $item = $this->intake->createIntakeItem([$this->fakePdf('pending.pdf', 64)]);
        $file = $item->files->first()->refresh();

        $this->postJson(route('records.files.re-read', ['file' => $file->id]))
            ->assertOk()
            ->assertJson(['ocrStatus' => OcrStatus::Queued->value]);

        // No Record exists yet, so the trail entry hangs off the intake item.
        $this->assertSame(
            1,
            RecordActivityLog::query()
                ->where('action', 'record.reread_requested')
                ->where('record_intake_item_id', $item->id)
                ->count(),
            'An unfiled file\'s re-read was not recorded against its intake item.',
        );
    }

    // ------------------------------------------------------------- rendering

    public function test_the_record_preview_offers_the_control(): void
    {
        $record = $this->filedRecord();

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('Re-read Document', $content);
        $this->assertStringContainsString('Document Holder', $content);
    }

    public function test_the_classify_preview_offers_the_control(): void
    {
        $this->intake->createIntakeItem([$this->fakePdf('pending.pdf', 64)]);

        $content = $this->get(route('records.unclassified'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('Re-read Document', $content);
    }

    /**
     * The pane carries the file's own reading reason, so an empty OCR pane says
     * why rather than just that there is nothing.
     */
    public function test_the_workspace_payload_carries_the_reading_reason(): void
    {
        $record = $this->filedRecord();
        $file = $record->files->first()->refresh();

        $file->forceFill(['ocr_error' => 'Document reading is not implemented yet.'])->save();

        $content = $this->get(route('records.index'))->assertOk()->getContent() ?? '';

        $this->assertStringContainsString('Document reading is not implemented yet.', $content);
    }

    // ------------------------------------------------------------- route shape

    /**
     * The trap this feature fell into once.
     *
     * Laravel hands route parameters to a controller by position and only
     * rewrites one into a method parameter when that parameter is class-typed. A
     * second path parameter is therefore swallowed: `string $file` against
     * /records/{record}/files/{file}/… receives the record, and a model casts to
     * its own JSON when a string is demanded, so it arrives as a plausible-looking
     * id and then matches nothing. Nothing warns — every request is simply a 404.
     *
     * Pinning both the route's parameter count and the method's keeps that from
     * coming back the next time somebody adds a parameter here.
     */
    public function test_the_re_read_routes_carry_exactly_one_parameter(): void
    {
        foreach (['records.files.re-read', 'records.files.status'] as $name) {
            $route = RouteFacade::getRoutes()->getByName($name);

            $this->assertSame(
                ['file'],
                $route->parameterNames(),
                "The {$name} route does not carry exactly the file parameter.",
            );
        }
    }

    public function test_the_controller_methods_declare_exactly_one_parameter(): void
    {
        foreach (['reRead', 'status'] as $method) {
            $parameters = (new ReflectionMethod(RecordFileReadingController::class, $method))->getParameters();

            $this->assertCount(
                1,
                $parameters,
                "RecordFileReadingController::{$method}() takes ".count($parameters).' parameters; '
                    .'anything past the first is never filled by the router.',
            );
            $this->assertSame('file', $parameters[0]->getName());
        }
    }

    // ---------------------------------------------------------------- helpers

    private function reReadUrl(Record $record, RecordFile $file): string
    {
        return route('records.files.re-read', ['file' => $file->id]);
    }

    private function statusUrl(Record $record, RecordFile $file): string
    {
        return route('records.files.status', ['file' => $file->id]);
    }

    /**
     * File a record under whichever company is currently in context.
     *
     * The scheme is built per company rather than reused from setUp, because the
     * scoping tests file into a second company and the filing service refuses a
     * Classification that belongs to somebody else.
     */
    private function filedRecord(string $title = 'Minutes', ?Company $company = null): Record
    {
        $company ??= $this->company;

        [$classification, $index] = $this->schemeFor($company);

        $item = $this->intake->createIntakeItem([$this->fakePdf($title.'.pdf', 64)]);

        return $this->app->make(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
        ])->refresh();
    }

    /**
     * A Classification and Index pair for one company, built once and reused.
     *
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
}
