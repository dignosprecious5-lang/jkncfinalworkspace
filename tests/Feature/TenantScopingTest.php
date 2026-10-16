<?php

namespace Tests\Feature;

use App\Enums\IntakeStatus;
use App\Models\Company;
use App\Models\RecordFile;
use App\Models\RecordIntakeItem;
use App\Services\CompanyContext;
use App\Services\Records\RecordIntakeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantScopingTest extends TestCase
{
    private RecordIntakeService $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->intake = $this->app->make(RecordIntakeService::class);
    }

    public function test_an_intake_item_is_scoped_to_the_active_company(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            UploadedFile::fake()->create('gis.pdf', 10, 'application/pdf'),
        ]);

        $this->assertSame($company->tenant_id, $item->tenant_id);
        $this->assertSame($company->id, $item->company_id);
    }

    public function test_a_company_cannot_see_another_companys_intake_items(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($first);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($second);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]);

        $visible = RecordIntakeItem::query()
            ->where('company_id', $second->id)
            ->with('files')
            ->get();

        $this->assertCount(1, $visible);
        $this->assertSame('second.pdf', $visible->first()->files->first()->original_name);
    }

    public function test_the_upload_view_does_not_expose_another_companys_intake_item(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($first);
        $item = $this->intake->createIntakeItem([
            UploadedFile::fake()->create('confidential.pdf', 10, 'application/pdf'),
        ]);

        $this->actingInCompany($second);

        $response = $this->get(route('records.intake.upload', ['intake' => $item->id]));

        $response->assertOk();
        $response->assertDontSee('confidential.pdf');
        $this->assertNull($response->viewData('intake'));
    }

    public function test_the_upload_view_counts_only_the_active_companys_open_items(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($first);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($second);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('c.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($first);
        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('unclassifiedCount'));
    }

    public function test_the_upload_view_lists_the_active_companys_open_items(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($first);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($second);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($first);
        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('first.pdf');
        $response->assertDontSee('second.pdf');
        $this->assertCount(1, $response->viewData('intakeItems'));
    }

    public function test_the_upload_view_lists_open_items_without_an_intake_parameter(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $item = $this->intake->createIntakeItem([
            UploadedFile::fake()->create('confidential.pdf', 10, 'application/pdf'),
        ]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertNull($response->viewData('intake'));
        $this->assertSame(
            $item->id,
            $response->viewData('intakeItems')->first()->id,
        );
    }

    public function test_the_upload_view_hides_filed_and_discarded_items(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $this->intake->createIntakeItem([UploadedFile::fake()->create('open.pdf', 10, 'application/pdf')]);

        $filed = $this->intake->createIntakeItem([UploadedFile::fake()->create('filed.pdf', 10, 'application/pdf')]);
        $filed->update(['status' => IntakeStatus::Filed]);

        $discarded = $this->intake->createIntakeItem([UploadedFile::fake()->create('discarded.pdf', 10, 'application/pdf')]);
        $discarded->update(['status' => IntakeStatus::Discarded]);

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertSee('open.pdf');
        $response->assertDontSee('filed.pdf');
        $response->assertDontSee('discarded.pdf');
        $this->assertSame(1, $response->viewData('unclassifiedCount'));
        $this->assertCount(1, $response->viewData('intakeItems'));
    }

    public function test_the_upload_view_does_not_list_another_companys_items(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($second);
        $this->intake->createIntakeItem([UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($first);
        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $response->assertDontSee('second.pdf');
        $this->assertCount(0, $response->viewData('intakeItems'));
    }

    public function test_a_bogus_session_company_is_rejected_with_a_403(): void
    {
        $this->seedTenantAndCompany();

        $this->withSession([CompanyContext::SESSION_KEY => '01JZZZZZZZZZZZZZZZZZZZZZZ'])
            ->get(route('records.intake.upload'))
            ->assertForbidden();
    }

    public function test_a_stale_company_is_cleared_from_the_session(): void
    {
        $company = $this->seedTenantAndCompany();
        $this->actingInCompany($company);

        $context = $this->app->make(CompanyContext::class);
        $context->forget();

        $this->withSession([CompanyContext::SESSION_KEY => '01JZZZZZZZZZZZZZZZZZZZZZZ'])
            ->get(route('records.intake.upload'))
            ->assertForbidden();

        $this->assertFalse($context->has());
    }

    public function test_a_missing_session_company_falls_back_to_the_first_company(): void
    {
        $first = $this->seedTenantAndCompany();
        $this->seedSecondCompany($first);

        $this->app->make(CompanyContext::class)->forget();

        $response = $this->get(route('records.intake.upload'));

        $response->assertOk();
        $this->assertSame($first->id, $this->app->make(CompanyContext::class)->id());
    }

    public function test_the_company_fallback_is_deterministic_when_created_at_ties(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        Company::query()->update(['created_at' => now()]);

        $this->app->make(CompanyContext::class)->forget();

        $resolved = $this->get(route('records.intake.upload'))
            ->assertOk()
            ->viewData('intakeItems');

        $expected = $first->id <= $second->id ? $first : $second;

        $this->assertSame($expected->id, $this->app->make(CompanyContext::class)->id());
        $this->assertCount(0, $resolved);
    }

    public function test_stored_files_live_under_their_own_tenant_and_company_prefix(): void
    {
        $first = $this->seedTenantAndCompany();
        $second = $this->seedSecondCompany($first);

        $this->actingInCompany($first);
        $firstItem = $this->intake->createIntakeItem([UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')]);

        $this->actingInCompany($second);
        $secondItem = $this->intake->createIntakeItem([UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]);

        $disk = Storage::disk((string) config('filesystems.default'));
        $firstFile = RecordFile::query()->where('record_intake_item_id', $firstItem->id)->firstOrFail();
        $secondFile = RecordFile::query()->where('record_intake_item_id', $secondItem->id)->firstOrFail();

        $this->assertStringStartsWith("intake/{$first->tenant_id}/{$first->id}/", $firstFile->path);
        $this->assertStringStartsWith("intake/{$second->tenant_id}/{$second->id}/", $secondFile->path);
        $disk->assertExists($firstFile->path);
        $disk->assertExists($secondFile->path);
    }
}
