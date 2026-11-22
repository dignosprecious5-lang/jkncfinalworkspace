<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Record;
use App\Models\RecordActivityLog;
use App\Models\RecordClassification;
use App\Models\RecordIndex;
use App\Models\RecordIntakeItem;
use App\Models\User;
use App\Services\Records\RecordFilingService;
use App\Services\Records\RecordIntakeService;
use Illuminate\Http\UploadedFile;
use Tests\Support\FakeUploads;
use Tests\TestCase;

class RecordEndpointSecurityTest extends TestCase
{
    use FakeUploads;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->seedTenantAndCompany();
    }

    public function test_anonymous_users_cannot_access_records_index(): void
    {
        // Unauthenticated request
        $response = $this->get(route('records.index'));

        $this->assertTrue(
            $response->status() === 401 || $response->isRedirect(route('login')),
            "Expected 401 or redirect to login, got {$response->status()}"
        );
    }

    public function test_anonymous_users_cannot_access_unclassified_queue(): void
    {
        $response = $this->get(route('records.unclassified'));

        $this->assertTrue(
            $response->status() === 401 || $response->isRedirect(route('login')),
            "Expected 401 or redirect to login, got {$response->status()}"
        );
    }

    public function test_anonymous_users_cannot_access_dashboard(): void
    {
        $response = $this->get(route('records.dashboard'));

        $this->assertTrue(
            $response->status() === 401 || $response->isRedirect(route('login')),
            "Expected 401 or redirect to login, got {$response->status()}"
        );
    }

    public function test_anonymous_users_cannot_access_scheme_setup(): void
    {
        $response = $this->get(route('records.setup.tab', ['tab' => 'classifications']));

        $this->assertTrue(
            $response->status() === 401 || $response->isRedirect(route('login')),
            "Expected 401 or redirect to login, got {$response->status()}"
        );
    }

    public function test_standard_users_are_forbidden_from_managing_scheme(): void
    {
        $standardUser = User::factory()->create(['role' => UserRole::StandardUser]);
        $this->actingInCompany($this->company, $standardUser);

        // Standard user cannot post new classifications
        $this->post(route('records.classifications.store'), [
            'code' => '999',
            'name' => 'Unauthorized Scheme',
        ])->assertStatus(403);

        // Standard user cannot modify plan limits
        $this->put(route('records.plan-limits.update'), [
            'plan_tier' => 'enterprise',
        ])->assertStatus(403);

        // Standard user cannot modify numbering settings
        $this->put(route('records.numbering.update'), [
            'prefix' => 'HACK',
        ])->assertStatus(403);
    }

    public function test_standard_users_are_forbidden_from_archiving_records(): void
    {
        $adminUser = User::factory()->create(['role' => UserRole::SystemAdmin]);
        $record = $this->createTestRecord($adminUser);

        $standardUser = User::factory()->create(['role' => UserRole::StandardUser]);
        $this->actingInCompany($this->company, $standardUser);

        $this->post(route('records.archive', ['record' => $record->id]))
            ->assertStatus(403);
    }

    public function test_cross_company_record_access_returns_404(): void
    {
        $adminUser = User::factory()->create(['role' => UserRole::SystemAdmin]);
        $recordInCompany1 = $this->createTestRecord($adminUser);

        // Switch to Company 2
        $company2 = $this->seedSecondCompany($this->company);
        $userInCompany2 = User::factory()->create(['role' => UserRole::RecordsManager]);
        $this->actingInCompany($company2, $userInCompany2);

        // Attempting to access Company 1's record from Company 2 scope must return 404 (not 403)
        $this->get(route('records.preview', ['record' => $recordInCompany1->id]))
            ->assertNotFound();

        $this->put(route('records.update', ['record' => $recordInCompany1->id]), [
            'title' => 'Compromised Record',
        ])->assertNotFound();
    }

    public function test_authorized_users_retain_intended_workflow_access(): void
    {
        $manager = User::factory()->create(['role' => UserRole::RecordsManager]);
        $this->actingInCompany($this->company, $manager);

        $this->get(route('records.index'))->assertOk();
        $this->get(route('records.unclassified'))->assertOk();
        $this->get(route('records.dashboard'))->assertOk();
    }

    public function test_viewing_record_label_creates_activity_audit_trail_entry(): void
    {
        $manager = User::factory()->create(['role' => UserRole::RecordsManager]);
        $record = $this->createTestRecord($manager);

        $this->actingInCompany($this->company, $manager);

        $initialLogsCount = RecordActivityLog::query()->where('record_id', $record->id)->count();

        $response = $this->get(route('records.label', ['record' => $record->id]));
        $response->assertOk();
        $response->assertViewIs('records.label');

        $this->assertDatabaseHas('record_activity_logs', [
            'record_id' => $record->id,
            'action' => 'record.label_printed',
            'user_id' => $manager->id,
        ]);

        $this->assertSame(
            $initialLogsCount + 1,
            RecordActivityLog::query()->where('record_id', $record->id)->count()
        );
    }

    public function test_upload_limits_are_enforced_from_configuration(): void
    {
        config(['ordo.uploads.intake_max_file_size_mb' => 2]); // 2 MB ceiling
        $manager = User::factory()->create(['role' => UserRole::RecordsManager]);
        $this->actingInCompany($this->company, $manager);

        // Uploading a 3 MB file should fail validation
        $oversizedFile = UploadedFile::fake()->create('large.pdf', 3072, 'application/pdf');

        $response = $this->post(route('records.intake.store'), [
            'files' => [$oversizedFile],
        ]);

        $response->assertSessionHasErrors('files.0');
    }

    private function createTestRecord(User $user): Record
    {
        $this->actingInCompany($this->company, $user);

        $classification = new RecordClassification;
        $classification->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'code' => '301',
            'name' => 'Entity and Ownership',
            'is_active' => true,
        ])->save();

        $index = new RecordIndex;
        $index->forceFill([
            'tenant_id' => $this->company->tenant_id,
            'company_id' => $this->company->id,
            'record_classification_id' => $classification->id,
            'code' => '301-1',
            'name' => 'Articles of Incorporation',
            'sort_order' => 0,
            'is_active' => true,
        ])->save();

        $item = app(RecordIntakeService::class)->createIntakeItem(
            [$this->fakeImage('cert.jpg')],
            ['title' => 'Articles of Incorporation Official']
        );

        return app(RecordFilingService::class)->file($item, [
            'record_classification_id' => $classification->id,
            'record_index_id' => $index->id,
            'title' => 'Articles of Incorporation Official',
        ]);
    }
}
