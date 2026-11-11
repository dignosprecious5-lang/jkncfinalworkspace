<?php

namespace Tests\Feature;

use App\Http\Controllers\ContactsController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDealController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\DealProposalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegularController;
use App\Models\Deal;
use App\Models\DealMembership;
use App\Models\Project;
use App\Models\User;
use App\Policies\DealPolicy;
use App\Services\DealMembershipService;
use App\Services\ProjectProvisioner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\DealsMysqlTestCase;

class DealArchivalTest extends DealsMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDealsMysqlFixtureTables();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_approved_deletion_request_archives_and_retains_related_history(): void
    {
        $admin = $this->makeUser('Admin');
        $deal = $this->makeDeal();
        $membershipId = DB::table('deal_memberships')->insertGetId([
            'deal_id' => $deal->id, 'user_id' => $admin->id, 'membership_role' => 'consultant',
            'assignment_status' => 'inactive', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $proposalId = DB::table('deal_proposals')->insertGetId(['deal_id' => $deal->id]);
        $versionId = DB::table('deal_proposal_versions')->insertGetId(['deal_id' => $deal->id, 'deal_proposal_id' => $proposalId]);
        $projectId = DB::table('projects')->insertGetId([
            'project_code' => 'ARCHIVE-'.uniqid(),
            'deal_id' => $deal->id,
        ]);
        $deal->forceFill(['delete_request_status' => 'pending'])->save();

        $request = Request::create('/deals/'.$deal->id.'/approve-deletion', 'POST');
        $request->setUserResolver(fn () => $admin);
        app(DealController::class)->approveDeletion($request, $deal->id);

        $archived = $deal->fresh();
        $this->assertNotNull($archived->archived_at);
        $this->assertSame($admin->id, (int) $archived->archived_by_user_id);
        $this->assertSame('archived', $archived->delete_request_status);
        $this->assertSame(1, DB::table('deal_memberships')->where('id', $membershipId)->count());
        $this->assertSame(1, DB::table('deal_proposals')->where('id', $proposalId)->count());
        $this->assertSame(1, DB::table('deal_proposal_versions')->where('id', $versionId)->count());
        $this->assertSame(1, DB::table('projects')->where('id', $projectId)->count());

        $firstArchivedAt = $archived->archived_at->copy();
        app(DealController::class)->approveDeletion($request, $deal->id);
        $this->assertTrue($firstArchivedAt->equalTo($deal->fresh()->archived_at));
    }

    public function test_archived_deals_are_readable_but_not_editable_or_membership_mutable(): void
    {
        $user = $this->makeUser();
        $deal = $this->makeDeal(['assigned_consultant' => $user->name, 'archived_at' => now()]);
        $policy = app(DealPolicy::class);

        $this->assertTrue($policy->view($user, $deal));
        $this->assertFalse($policy->update($user, $deal));
        $this->assertFalse($policy->update($this->makeUser('Admin'), $deal));

        try {
            app(DealMembershipService::class)->assign($deal, $this->makeUser('Admin'), $user->id, DealMembership::ROLE_ASSOCIATE);
            $this->fail('Archived Deals must reject membership assignment.');
        } catch (AuthorizationException) {
            $this->assertSame(0, DB::table('deal_memberships')->count());
        }

        $membershipId = DB::table('deal_memberships')->insertGetId([
            'deal_id' => $deal->id, 'user_id' => $user->id, 'membership_role' => 'associate',
            'assignment_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            app(DealMembershipService::class)->revoke($deal, $this->makeUser('Admin'), $membershipId);
            $this->fail('Archived Deals must reject membership revocation.');
        } catch (AuthorizationException) {
            $this->assertSame('active', DB::table('deal_memberships')->where('id', $membershipId)->value('assignment_status'));
        }

        $request = Request::create('/deals/'.$deal->id.'/stage', 'PATCH', ['stage_id' => 'Proposal'], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $this->makeUser());
        $response = app(DealController::class)->updateDealStage($request, $deal->id);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_archived_deals_reject_all_finance_mutations_before_side_effects_for_every_role(): void
    {
        Storage::fake('public');
        Mail::fake();
        $deal = $this->makeDeal(['archived_at' => now()]);
        $finance = $this->makeUser('Employee');

        foreach ([$finance, $this->makeUser('Admin'), $this->makeUser('SuperAdmin')] as $actor) {
            $this->actingAs($actor);
            $controller = app(DealProposalController::class);
            $request = Request::create('/deals/'.$deal->id, 'POST', [], [], [
                'quotation_file' => \Illuminate\Http\UploadedFile::fake()->create('quote.pdf', 10, 'application/pdf'),
                'invoice_file' => \Illuminate\Http\UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf'),
            ]);
            $request->setUserResolver(fn () => $actor);

            foreach ([
                fn () => $controller->approveQuotation($deal),
                fn () => $controller->uploadFinanceQuotation($request, $deal),
                fn () => $controller->uploadInvoice($request, $deal),
                fn () => $controller->confirmPayment($deal),
            ] as $action) {
                try {
                    $action();
                    $this->fail('Archived Deal Finance mutations must be denied.');
                } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        }

        $this->assertSame(0, DB::table('deal_proposals')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Mail::assertNothingOutgoing();
    }

    public function test_start_approval_rejects_archived_linked_deal_before_any_side_effect(): void
    {
        Storage::fake('public');
        $this->createProjectApprovalPermissionSchema();
        $reviewer = $this->makeUser('Admin');
        DB::table('user_permissions')->insert([
            'user_id' => $reviewer->id,
            'access_admin_dashboard' => true,
            'approve_townhall' => false,
        ]);
        $deal = $this->makeDeal(['archived_at' => now(), 'stage' => 'Proposal']);
        [$project, $start] = $this->makeProjectStartForDeal($deal->id, 'pending_approval');
        $request = Request::create('/projects/'.$project->id.'/start/approve', 'POST');
        $request->setUserResolver(fn () => $reviewer);

        try {
            app(ProjectController::class)->approveStart($request, $project);
            $this->fail('START approval for an archived linked Deal must be denied.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('pending_approval', $start->fresh()->status);
        $this->assertNull($start->fresh()->approved_at);
        $this->assertSame('Proposal', $deal->fresh()->stage);
        $this->assertSame('Shell', $project->fresh()->status);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_start_approval_still_works_for_active_linked_deal_and_rejects_unauthorized_actor(): void
    {
        Storage::fake('public');
        $this->createProjectApprovalPermissionSchema();
        $activeReviewer = $this->makeUser('Admin');
        DB::table('user_permissions')->insert([
            'user_id' => $activeReviewer->id,
            'access_admin_dashboard' => true,
            'approve_townhall' => false,
        ]);
        $deal = $this->makeDeal(['stage' => 'Proposal']);
        [$project, $start] = $this->makeProjectStartForDeal($deal->id, 'pending_approval');
        $unauthorized = $this->makeUser('Employee');
        $unauthorizedRequest = Request::create('/projects/'.$project->id.'/start/approve', 'POST');
        $unauthorizedRequest->setUserResolver(fn () => $unauthorized);

        try {
            app(ProjectController::class)->approveStart($unauthorizedRequest, $project);
            $this->fail('START approval must retain its reviewer authorization check.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('pending_approval', $start->fresh()->status);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $controller = new class extends ProjectController
        {
            protected function generatePdfPreview(string $view, array $data, string $targetPath): ?string
            {
                Storage::disk('public')->put($targetPath, 'memo');

                return $targetPath;
            }
        };
        $authorizedRequest = Request::create('/projects/'.$project->id.'/start/approve', 'POST');
        $authorizedRequest->setUserResolver(fn () => $activeReviewer);
        $controller->approveStart($authorizedRequest, $project);

        $this->assertSame('approved', $start->fresh()->status);
        $this->assertSame('Closed Won', $deal->fresh()->stage);
        $this->assertNotEmpty(Storage::disk('public')->allFiles());
    }

    public function test_start_approval_failure_rolls_back_start_and_linked_deal_changes(): void
    {
        Storage::fake('public');
        $this->createProjectApprovalPermissionSchema();
        $reviewer = $this->makeUser('Admin');
        DB::table('user_permissions')->insert([
            'user_id' => $reviewer->id,
            'access_admin_dashboard' => true,
            'approve_townhall' => false,
        ]);
        $deal = $this->makeDeal(['stage' => 'Proposal']);
        [$project, $start] = $this->makeProjectStartForDeal($deal->id, 'pending_approval');
        $request = Request::create('/projects/'.$project->id.'/start/approve', 'POST');
        $request->setUserResolver(fn () => $reviewer);
        $controller = new class extends ProjectController
        {
            protected function generatePdfPreview(string $view, array $data, string $targetPath): ?string
            {
                return null;
            }
        };

        try {
            $controller->approveStart($request, $project);
            $this->fail('START approval should fail when its required service memo cannot be generated.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(500, $exception->getStatusCode());
        }

        $this->assertSame('pending_approval', $start->fresh()->status);
        $this->assertNull($start->fresh()->approved_at);
        $this->assertSame('Proposal', $deal->fresh()->stage);
        $this->assertSame('Shell', $project->fresh()->status);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_archived_deals_are_excluded_from_active_scope_and_project_provisioning(): void
    {
        $active = $this->makeDeal();
        $archived = $this->makeDeal(['archived_at' => now(), 'deal_status' => 'approved', 'engagement_type' => 'Project']);

        $this->assertSame([$active->id], Deal::query()->active()->pluck('id')->all());
        $this->assertSame([$archived->id], Deal::query()->archived()->pluck('id')->all());
        $this->assertNull(app(ProjectProvisioner::class)->createFromApprovedDeal($archived));
        $this->assertSame(0, DB::table('projects')->count());
    }

    public function test_deal_model_rejects_hard_deletion_and_updates_after_archival(): void
    {
        $archived = $this->makeDeal(['archived_at' => now()]);
        $this->expectException(LogicException::class);
        $archived->delete();
    }

    public function test_archived_deal_model_rejects_ordinary_updates(): void
    {
        $archived = $this->makeDeal(['archived_at' => now()]);
        $this->expectException(LogicException::class);
        $archived->deal_status = 'approved';
        $archived->save();
    }

    public function test_contact_deletion_is_blocked_for_active_and_archived_deals(): void
    {
        $admin = $this->makeUser('Admin');
        foreach ([null, now()] as $archivedAt) {
            $contactId = DB::table('contacts')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
            $this->makeDeal(['contact_id' => $contactId, 'archived_at' => $archivedAt]);
            $request = Request::create('/contacts/bulk-delete', 'DELETE', ['selected_contacts' => [$contactId]]);
            $request->setUserResolver(fn () => $admin);

            try {
                app(ContactsController::class)->bulkDelete($request);
                $this->fail('A Contact linked to any Deal must not be deleted.');
            } catch (ValidationException) {
                $this->assertSame(1, DB::table('contacts')->where('id', $contactId)->count());
            }
        }
    }

    public function test_contact_without_deals_remains_deletable(): void
    {
        $admin = $this->makeUser('Admin');
        $contactId = DB::table('contacts')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $request = Request::create('/contacts/bulk-delete', 'DELETE', ['selected_contacts' => [$contactId]]);
        $request->setUserResolver(fn () => $admin);

        app(ContactsController::class)->bulkDelete($request);

        $this->assertSame(0, DB::table('contacts')->where('id', $contactId)->count());
    }

    public function test_contact_foreign_key_restricts_direct_deletion_of_linked_contact(): void
    {
        $contactId = DB::table('contacts')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->makeDeal(['contact_id' => $contactId]);

        try {
            DB::table('contacts')->where('id', $contactId)->delete();
            $this->fail('The Deals foreign key must protect a directly deleted linked Contact.');
        } catch (\Illuminate\Database\QueryException) {
            $this->assertSame(1, DB::table('contacts')->where('id', $contactId)->count());
        }
    }

    public function test_project_and_regular_creation_reject_archived_deals_before_writes(): void
    {
        $archivedProjectDeal = $this->makeDeal(['archived_at' => now()]);
        $archivedRegularDeal = $this->makeDeal(['archived_at' => now()]);

        foreach ([
            [ProjectController::class, $archivedProjectDeal->id],
            [RegularController::class, $archivedRegularDeal->id],
        ] as [$controller, $dealId]) {
            try {
                app($controller)->storeManual(Request::create('/store-manual', 'POST', ['deal_id' => $dealId, 'name' => 'A2 fixture']));
                $this->fail('Archived Deal references must be rejected before project creation.');
            } catch (HttpExceptionInterface $exception) {
                $this->assertSame(422, $exception->getStatusCode());
                $this->assertSame(0, DB::table('projects')->count());
                $this->assertSame(0, DB::table('project_starts')->count());
                $this->assertSame(0, DB::table('project_sow_reports')->count());
            }
        }
    }

    public function test_active_deals_can_still_create_project_and_regular_records(): void
    {
        $projectDeal = $this->makeDeal();
        $regularDeal = $this->makeDeal();

        app(ProjectController::class)->storeManual(Request::create('/projects/store-manual', 'POST', [
            'deal_id' => $projectDeal->id, 'name' => 'Active project',
        ]));
        app(RegularController::class)->storeManual(Request::create('/regular/store-manual', 'POST', [
            'deal_id' => $regularDeal->id, 'name' => 'Active regular engagement',
        ]));

        $this->assertSame(2, DB::table('projects')->count());
        $this->assertSame(2, DB::table('project_starts')->count());
        $this->assertSame(2, DB::table('project_sow_reports')->count());
        $this->assertSame('draft', DB::table('project_sows')->where('project_id', DB::table('projects')->where('deal_id', $projectDeal->id)->value('id'))->value('approval_status'));
        $this->assertSame('pending', DB::table('project_sows')->where('project_id', DB::table('projects')->where('deal_id', $projectDeal->id)->value('id'))->value('ntp_status'));
    }

    public function test_manual_project_uses_valid_template_approval_status(): void
    {
        $this->createFormTemplateSchema();
        $deal = $this->makeDeal();
        $templateId = $this->createApprovedSowTemplate([
            'approval_status' => 'approved',
            'ntp_status' => 'approved',
        ]);

        app(ProjectController::class)->storeManual(Request::create('/projects/store-manual', 'POST', [
            'deal_id' => $deal->id,
            'name' => 'Template status project',
            'template_id' => $templateId,
        ]));

        $projectId = DB::table('projects')->where('deal_id', $deal->id)->value('id');
        $this->assertSame('approved', DB::table('project_sows')->where('project_id', $projectId)->value('approval_status'));
        $this->assertSame('approved', DB::table('project_sows')->where('project_id', $projectId)->value('ntp_status'));
    }

    public function test_manual_project_defaults_to_draft_when_template_omits_approval_status(): void
    {
        $this->createFormTemplateSchema();
        $deal = $this->makeDeal();
        $templateId = $this->createApprovedSowTemplate([]);

        app(ProjectController::class)->storeManual(Request::create('/projects/store-manual', 'POST', [
            'deal_id' => $deal->id,
            'name' => 'Template without approval status',
            'template_id' => $templateId,
        ]));

        $projectId = DB::table('projects')->where('deal_id', $deal->id)->value('id');
        $this->assertSame('draft', DB::table('project_sows')->where('project_id', $projectId)->value('approval_status'));
    }

    public function test_invalid_or_null_template_approval_status_falls_back_to_draft(): void
    {
        $this->createFormTemplateSchema();

        foreach ([null, '', 'not-a-valid-status'] as $index => $invalidStatus) {
            $deal = $this->makeDeal();
            $templateId = $this->createApprovedSowTemplate([
                'approval_status' => $invalidStatus,
                'ntp_status' => $invalidStatus,
            ]);

            app(ProjectController::class)->storeManual(Request::create('/projects/store-manual', 'POST', [
                'deal_id' => $deal->id,
                'name' => 'Invalid template status '.$index,
                'template_id' => $templateId,
            ]));

            $projectId = DB::table('projects')->where('deal_id', $deal->id)->value('id');
            $this->assertSame('draft', DB::table('project_sows')->where('project_id', $projectId)->value('approval_status'));
            $this->assertSame('pending', DB::table('project_sows')->where('project_id', $projectId)->value('ntp_status'));
        }
    }

    public function test_company_deal_listing_excludes_archived_and_keeps_company_scope(): void
    {
        $companyId = DB::table('companies')->insertGetId(['company_name' => 'Company A', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('companies')->insert(['company_name' => 'Company B', 'created_at' => now(), 'updated_at' => now()]);
        $activeForCompany = $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Visible active Deal']);
        $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Hidden archived Deal', 'archived_at' => now()]);
        $this->makeDeal(['company_name' => 'Company B', 'deal_name' => 'Other company Deal']);

        $view = app(CompanyController::class)->deals(Request::create('/company/'.$companyId.'/deals'), $companyId);
        $deals = $view->getData()['deals'];

        $this->assertCount(1, $deals);
        $this->assertSame('Visible active Deal', $deals->first()['name']);
        $this->assertSame($activeForCompany->company_name, 'Company A');
    }

    public function test_company_deal_page_applies_search_and_stage_filters_to_active_deals(): void
    {
        $admin = $this->makeUser('Admin');
        $companyId = DB::table('companies')->insertGetId(['company_name' => 'Company A', 'created_at' => now(), 'updated_at' => now()]);
        $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Visible Won Deal', 'stage' => 'Won']);
        $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Visible Open Deal', 'stage' => 'Qualification']);
        $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Hidden Archived Won Deal', 'stage' => 'Won', 'archived_at' => now()]);
        $request = Request::create('/company/'.$companyId.'/deals', 'GET', ['search' => 'Visible', 'stage' => 'Won']);
        $request->setUserResolver(fn () => $admin);

        $view = app(CompanyDealController::class)->index($request, $companyId);
        $deals = $view->getData()['deals'];

        $this->assertCount(1, $deals);
        $this->assertSame('Visible Won Deal', $deals->first()['name']);
        $this->assertSame('Won', $deals->first()['status']);
    }

    public function test_company_deal_listing_excludes_unauthorized_and_revoked_members(): void
    {
        $companyId = DB::table('companies')->insertGetId(['company_name' => 'Company A', 'created_at' => now(), 'updated_at' => now()]);
        $user = $this->makeUser();
        $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Authorized legacy Deal', 'assigned_consultant' => $user->name]);
        $revokedDeal = $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Stale assignment Deal', 'assigned_consultant' => $user->name]);
        $this->makeDeal(['company_name' => 'Company A', 'deal_name' => 'Other user Deal', 'assigned_consultant' => $this->makeUser()->name]);
        DB::table('deal_memberships')->insert([
            'deal_id' => $revokedDeal->id,
            'user_id' => $user->id,
            'membership_role' => 'consultant',
            'assignment_status' => 'inactive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/company/'.$companyId.'/deals');
        $request->setUserResolver(fn () => $user);
        $view = app(CompanyDealController::class)->index($request, $companyId);
        $deals = $view->getData()['deals'];

        $this->assertCount(1, $deals);
        $this->assertSame('Authorized legacy Deal', $deals->first()['name']);
        $this->assertSame(1, $view->getData()['summary']['total']);
    }

    public function test_deals_dashboard_filters_out_archived_rows_and_applies_search(): void
    {
        $this->createProjectApprovalPermissionSchema();
        $this->createDashboardFormTemplateSchema();
        $admin = $this->makeUser('Admin');
        DB::table('user_permissions')->insert([
            'user_id' => $admin->id,
            'access_admin_dashboard' => true,
            'approve_townhall' => false,
        ]);
        Auth::setUser($admin);
        $visible = $this->makeDeal(['deal_name' => 'Visible dashboard Deal', 'deal_status' => 'pending', 'stage' => 'Qualification']);
        $this->makeDeal(['deal_name' => 'Hidden archived dashboard Deal', 'deal_status' => 'pending', 'stage' => 'Qualification', 'archived_at' => now()]);
        $request = Request::create('/admin-dashboard/deals', 'GET', ['search' => $visible->deal_code, 'module' => 'Deals']);

        $view = app(AdminDashboardController::class)->index($request, 'deals');
        $items = $view->getData()['items'];

        $this->assertCount(1, $items);
        $this->assertSame($visible->deal_code, $items->first()->file_name);
        $this->assertSame('Deals', $items->first()->module);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('password')->nullable();
            $table->string('role')->default('Employee'); $table->boolean('is_active')->default(true);
            $table->timestamp('disabled_at')->nullable(); $table->timestamp('archived_at')->nullable(); $table->timestamps();
        });
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            foreach (['first_name', 'last_name', 'email', 'company_name'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table): void { $table->id(); $table->string('company_name')->nullable(); $table->timestamps(); });
        Schema::create('company_bifs', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('company_id'); $table->timestamps(); });
        Schema::create('deals', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('contact_id')->nullable(); $table->string('deal_code')->nullable();
            $table->string('deal_name')->nullable(); $table->string('deal_status')->nullable(); $table->string('stage')->nullable();
            $table->string('assigned_consultant')->nullable(); $table->string('engagement_type')->nullable(); $table->string('company_name')->nullable();
            $table->string('internal_sales_marketing')->nullable(); $table->string('internal_finance')->nullable(); $table->string('internal_president')->nullable();
            $table->string('first_name')->nullable(); $table->string('last_name')->nullable(); $table->string('service_area')->nullable();
            $table->text('services')->nullable(); $table->text('products')->nullable(); $table->text('scope_of_work')->nullable();
            $table->string('delete_request_status')->nullable(); $table->unsignedBigInteger('delete_requested_by')->nullable();
            $table->timestamp('delete_requested_at')->nullable(); $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by_user_id')->nullable(); $table->text('archive_reason')->nullable(); $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('contacts')->restrictOnDelete();
        });
        Schema::create('deal_memberships', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('deal_id'); $table->unsignedBigInteger('user_id');
            $table->string('membership_role'); $table->string('assignment_status'); $table->timestamps();
        });
        Schema::create('deal_proposals', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('deal_id'); });
        Schema::create('deal_proposal_versions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('deal_id'); $table->unsignedBigInteger('deal_proposal_id');
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id(); $table->string('project_code')->unique(); $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable(); $table->unsignedBigInteger('company_id')->nullable();
            foreach (['name', 'engagement_type', 'status', 'current_phase', 'current_step', 'assigned_project_manager', 'assigned_consultant', 'assigned_associate', 'client_name', 'business_name', 'service_area', 'services', 'products', 'client_confirmation_name'] as $column) {
                $table->string($column)->nullable();
            }
            $table->date('planned_start_date')->nullable(); $table->date('target_completion_date')->nullable();
            $table->text('scope_summary')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('opened_at')->nullable(); $table->timestamps();
        });
        Schema::create('project_starts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('project_id'); $table->string('start_code')->unique();
            $table->date('form_date')->nullable(); $table->date('date_started')->nullable(); $table->string('status')->nullable();
            $table->timestamp('approved_at')->nullable(); $table->string('approved_by_name')->nullable();
            $table->timestamp('rejected_at')->nullable(); $table->string('rejected_by_name')->nullable();
            $table->text('rejection_reason')->nullable(); $table->json('attachments')->nullable();
            foreach (['checklist', 'kyc_requirements', 'engagement_requirements', 'approval_steps', 'routing', 'clearance'] as $column) {
                $table->json($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('project_sows', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('project_id'); $table->string('sow_number')->unique();
            $table->string('version_number')->nullable(); $table->date('date_prepared')->nullable();
            $table->json('within_scope_items')->nullable(); $table->json('out_of_scope_items')->nullable();
            $table->string('client_confirmation_name')->nullable(); $table->json('internal_approval')->nullable();
            $table->string('approval_status')->nullable(); $table->string('ntp_status')->nullable(); $table->timestamps();
        });
        Schema::create('project_sow_reports', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('project_id'); $table->string('report_number')->unique();
            $table->string('version_number')->nullable(); $table->date('date_prepared')->nullable();
            $table->json('within_scope_items')->nullable(); $table->json('out_of_scope_items')->nullable();
            $table->json('status_summary')->nullable(); $table->decimal('project_completion_percentage', 5, 2)->nullable();
            $table->string('client_confirmation_name')->nullable(); $table->json('internal_approval')->nullable(); $table->timestamps();
        });
    }

    private function makeUser(string $role = 'Employee'): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'A2 User '.uniqid(), 'email' => uniqid().'@example.test', 'role' => $role,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function makeDeal(array $attributes = []): Deal
    {
        $id = DB::table('deals')->insertGetId($attributes + [
            'deal_code' => 'A2-'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Deal::query()->findOrFail($id);
    }

    private function createProjectApprovalPermissionSchema(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('role')->unique();
                $table->boolean('access_admin_dashboard')->default(false);
                $table->boolean('approve_townhall')->default(false);
            });
        }
        if (! Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->boolean('access_admin_dashboard')->default(false);
                $table->boolean('approve_townhall')->default(false);
            });
        }
    }

    private function createFormTemplateSchema(): void
    {
        Schema::create('form_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 50);
            $table->string('name');
            $table->json('payload');
            $table->string('status', 40)->default('approved');
            $table->timestamps();
        });
    }

    private function createApprovedSowTemplate(array $payload): int
    {
        return DB::table('form_templates')->insertGetId([
            'type' => 'project_sow',
            'name' => 'A5-F2 template '.uniqid(),
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDashboardFormTemplateSchema(): void
    {
        Schema::create('form_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 50);
            $table->string('name');
            $table->string('status')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    private function makeProjectStartForDeal(int $dealId, string $startStatus): array
    {
        $projectId = DB::table('projects')->insertGetId([
            'project_code' => 'A4R3-'.uniqid(),
            'deal_id' => $dealId,
            'name' => 'A4-R3 project',
            'status' => 'Shell',
            'metadata' => json_encode(['is_shell' => false]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $startId = DB::table('project_starts')->insertGetId([
            'project_id' => $projectId,
            'start_code' => 'A4R3-START-'.uniqid(),
            'status' => $startStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [Project::query()->findOrFail($projectId), \App\Models\ProjectStart::query()->findOrFail($startId)];
    }
}
