<?php

namespace Tests\Feature;

use App\Models\Engagement;
use App\Models\EngagementPeriod;
use App\Models\OperationalTask;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Service;
use App\Models\ServiceTerm;
use App\Models\ServiceVersion;
use App\Models\User;
use App\Models\UserPermission;
use App\Notifications\WorkspaceUpdatedNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class ProductServiceReleaseRemediationTest extends TestCase
{
    public function refreshDatabase(): void {}

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'mail.default' => 'array',
        ]);
        DB::purge('sqlite');
        Cache::flush();

        $this->buildIsolatedCatalogSchema();
    }

    private function buildIsolatedCatalogSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('Employee');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique();
            $table->boolean('access_product')->default(false);
            $table->boolean('access_services')->default(false);
            $table->boolean('create_sales_marketing')->default(false);
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('access_product')->default(false);
            $table->boolean('access_services')->default(false);
            $table->boolean('create_sales_marketing')->default(false);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('service_id', 10)->nullable()->unique();
            $table->string('service_code')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('service_name')->nullable();
            $table->string('short_name')->nullable();
            $table->text('service_description')->nullable();
            $table->text('service_activity_output')->nullable();
            $table->text('service_area')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->text('engagement_structure')->nullable();
            $table->string('engagement_behavior')->default('project');
            $table->decimal('price_fee', 12, 2)->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });

        Schema::create('service_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('version_number')->default('V1.0');
            $table->string('short_name')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_active')->default(true);
            $table->string('currency')->default('PHP');
            $table->string('pricing_model')->nullable();
            $table->string('tax_treatment')->nullable();
            $table->string('payment_structure')->nullable();
            $table->string('instantiation_mode')->nullable();
            $table->boolean('auto_carryover')->default(true);
            $table->integer('instantiation_lead_days')->default(15);
            $table->integer('escalation_threshold_days')->default(2);
            $table->decimal('standard_price', 12, 2)->nullable();
            $table->text('internal_description')->nullable();
            $table->text('client_description')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->text('deliverables')->nullable();
            $table->boolean('send_due_reminders')->default(false);
            $table->boolean('auto_send_due_reminders')->default(false);
            $table->integer('internal_reminder_days')->default(3);
            $table->string('notification_channel')->nullable();
            $table->string('client_reminder_cadence')->nullable();
            $table->timestamps();
        });

        Schema::create('service_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_version_id')->constrained('service_versions')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('scope')->default('service_specific');
            $table->integer('sort_order')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('service_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action')->nullable();
            $table->string('title')->nullable();
            $table->text('details')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('service_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('service_version_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('service_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_version_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->integer('sequence')->default(1);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id', 10)->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->string('product_type')->default('Service');
            $table->text('product_area')->nullable();
            $table->text('product_description')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('pricing_type')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('tax_type')->nullable();
            $table->string('tax_treatment')->nullable();
            $table->string('inventory_type')->nullable();
            $table->integer('stock_qty')->default(0);
            $table->integer('inventory_stock')->default(0);
            $table->string('service_area')->nullable();
            $table->string('client_followup_cadence')->nullable();
            $table->string('notification_channel')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('product_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->string('version_number')->default('V1.0');
            $table->boolean('is_active')->default(true);
            $table->integer('inventory_stock')->default(0);
            $table->boolean('auto_carryover')->default(false);
            $table->boolean('auto_carryover_automation')->default(false);
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action')->nullable();
            $table->string('title')->nullable();
            $table->text('details')->nullable();
            $table->text('description')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_version_id')->nullable();
            $table->string('engagement_type')->default('regular');
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->timestamps();
        });

        Schema::create('engagement_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('engagement_id')->nullable();
            $table->unsignedBigInteger('service_version_id')->nullable();
            $table->string('period_name')->nullable();
            $table->date('period_start_date')->nullable();
            $table->date('period_end_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operational_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('engagement_id')->nullable();
            $table->unsignedBigInteger('engagement_period_id')->nullable();
            $table->unsignedBigInteger('service_activity_id')->nullable();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operational_task_id');
            $table->string('reminder_type');
            $table->date('target_due_date');
            $table->timestamps();
        });
    }

    private function makeUser(string $role, array $perms = [], bool $active = true): User
    {
        $user = User::create([
            'name' => $role . ' User',
            'email' => strtolower($role) . '_' . uniqid() . '@example.test',
            'password' => bcrypt('password123'),
            'role' => $role,
            'is_active' => $active,
            'disabled_at' => $active ? null : now(),
        ]);

        if ($perms !== []) {
            UserPermission::create(array_merge(['user_id' => $user->id], $perms));
        }

        return $user;
    }

    public function test_unauthorized_users_are_blocked_across_product_and_service_controllers(): void
    {
        $client = $this->makeUser('Client');
        $service = Service::create(['name' => 'Compliance Advisory', 'service_code' => 'SRV-SEC1', 'status' => 'draft']);
        $version = ServiceVersion::create(['service_id' => $service->id, 'version_number' => 'V1.0', 'status' => 'draft']);
        $service->update(['active_version_id' => $version->id]);
        $term = ServiceTerm::create(['service_version_id' => $version->id, 'title' => 'T1', 'content' => 'C1', 'scope' => 'service_specific', 'sort_order' => 1, 'status' => 'active']);
        $product = Product::create(['name' => 'Tax Kit', 'sku' => 'SKU-SEC1', 'price' => 1000, 'status' => 'draft']);
        $pVersion = ProductVersion::create(['product_id' => $product->id, 'name' => 'Tax Kit', 'version_number' => 'V1.0', 'status' => 'draft']);

        $this->actingAs($client)->get(route('services.export_all', ['format' => 'csv']))->assertForbidden();
        $this->actingAs($client)->post(route('services.import'))->assertForbidden();
        $this->actingAs($client)->post(route('service-terms.store', $version), ['title' => 'X', 'content' => 'Y', 'scope' => 'global'])->assertForbidden();
        $this->actingAs($client)->patch(route('service-terms.disable', $term))->assertForbidden();
        $this->actingAs($client)->get(route('products.import.form'))->assertForbidden();
        $this->actingAs($client)->post(route('products.import.store'))->assertForbidden();
        $this->actingAs($client)->get(route('products.export.all', ['format' => 'csv']))->assertForbidden();
        $this->actingAs($client)->get(route('product-versions.index', $product))->assertForbidden();
        $this->actingAs($client)->post(route('product-versions.store', $product))->assertForbidden();
        $this->actingAs($client)->patch(route('product-versions.activate', $pVersion))->assertForbidden();
    }

    public function test_read_only_staff_can_export_but_cannot_mutate_or_publish(): void
    {
        $viewer = $this->makeUser('Employee', ['access_services' => true, 'access_product' => true, 'create_sales_marketing' => false]);
        $editor = $this->makeUser('Employee', ['access_services' => true, 'access_product' => true, 'create_sales_marketing' => true]);

        $service = Service::create(['name' => '=CMD|calc', 'service_code' => 'SRV-CSV1', 'status' => 'active']);
        $product = Product::create(['name' => '+SUM(1,2)', 'sku' => 'SKU-CSV1', 'price' => 500, 'status' => 'active']);
        $pVersion = ProductVersion::create(['product_id' => $product->id, 'name' => 'V1', 'version_number' => 'V1.0', 'status' => 'draft']);

        // Viewer can export CSV with formula injection neutralized
        $svcCsv = $this->actingAs($viewer)->get(route('services.export_all', ['format' => 'csv']));
        $svcCsv->assertOk();
        $this->assertStringContainsString("'=CMD|calc", $svcCsv->streamedContent());

        $prodCsv = $this->actingAs($viewer)->get(route('products.export.all', ['format' => 'csv']));
        $prodCsv->assertOk();
        $this->assertStringContainsString("'+SUM(1,2)", $prodCsv->streamedContent());

        // Viewer cannot archive or import
        $this->actingAs($viewer)->patch(route('services.archive', $service))->assertForbidden();
        $this->actingAs($viewer)->post(route('products.import.store'))->assertForbidden();

        // Non-approver editor cannot activate/publish product version
        $this->actingAs($editor)->patch(route('product-versions.activate', $pVersion))->assertForbidden();
        $this->actingAs($editor)->patch(route('product-versions.status', $pVersion), ['status' => 'published'])->assertForbidden();
    }

    public function test_state_changing_get_routes_are_rejected_with_405(): void
    {
        $admin = $this->makeUser('Admin');
        $service = Service::create(['name' => 'Audit Svc', 'service_code' => 'SRV-GET1', 'status' => 'draft']);
        $version = ServiceVersion::create(['service_id' => $service->id, 'version_number' => 'V1.0', 'status' => 'draft']);
        $product = Product::create(['name' => 'Audit Prod', 'sku' => 'SKU-GET1', 'price' => 100, 'status' => 'draft']);

        $this->actingAs($admin)->get("/products/{$product->id}/duplicate")->assertStatus(405);
        $this->actingAs($admin)->get("/products/{$product->id}/archive")->assertStatus(405);
        $this->actingAs($admin)->get("/services/{$service->id}/archive")->assertStatus(405);
        $this->actingAs($admin)->get("/service-versions/{$version->id}/terms/template")->assertStatus(405);
        $this->actingAs($admin)->get("/service-versions/{$version->id}/terms/apply-template")->assertStatus(405);
    }

    public function test_service_import_supports_json_csv_xlsx_duplicates_and_rejects_invalid(): void
    {
        $admin = $this->makeUser('Admin');

        // 1. JSON import (create + update duplicate)
        $jsonPayload = json_encode([
            'services' => [
                ['service_code' => 'SRV-IMP-1', 'name' => 'Tax Filing', 'service_area' => 'Tax', 'standard_price' => 15000, 'engagement_behavior' => 'regular'],
            ],
        ]);
        $jsonFile = UploadedFile::fake()->createWithContent('services.json', $jsonPayload);
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $jsonFile])->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', ['service_code' => 'SRV-IMP-1', 'name' => 'Tax Filing']);

        // Update same service_code via JSON (no duplicate created)
        $jsonUpdate = UploadedFile::fake()->createWithContent('services_upd.json', json_encode([
            'service_code' => 'SRV-IMP-1',
            'name' => 'Tax Filing Updated',
            'standard_price' => 18000,
        ]));
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $jsonUpdate])->assertRedirect(route('services.index'));
        $this->assertSame(1, Service::where('service_code', 'SRV-IMP-1')->count());
        $this->assertDatabaseHas('services', ['service_code' => 'SRV-IMP-1', 'name' => 'Tax Filing Updated']);

        // 2. CSV import
        $csvContent = "service_code,name,category,standard_price\nSRV-CSV-2,Corporate Audit,Audit,25000\n";
        $csvFile = UploadedFile::fake()->createWithContent('services.csv', $csvContent);
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $csvFile])->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', ['service_code' => 'SRV-CSV-2', 'name' => 'Corporate Audit']);

        // 3. Real XLSX import built with ZipArchive
        $xlsxPath = sys_get_temp_dir() . '/test_services_' . uniqid() . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>service_code</t></si><si><t>name</t></si><si><t>standard_price</t></si><si><t>SRV-XLSX-3</t></si><si><t>SEC Registration</t></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row><row r="2"><c r="A2" t="s"><v>3</v></c><c r="B2" t="s"><v>4</v></c><c r="C2"><v>32000</v></c></row></sheetData></worksheet>');
        $zip->close();
        $xlsxFile = new UploadedFile($xlsxPath, 'services.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $xlsxFile])->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', ['service_code' => 'SRV-XLSX-3', 'name' => 'SEC Registration']);
        @unlink($xlsxPath);

        // 4. Invalid JSON returns validation error
        $badFile = UploadedFile::fake()->createWithContent('bad.json', '{not valid json');
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $badFile])->assertSessionHasErrors('json_file');
    }

    public function test_automated_reminders_are_idempotent_and_exclude_clients_and_disabled_users(): void
    {
        Notification::fake();

        $admin = $this->makeUser('Admin');
        $disabledAdmin = $this->makeUser('Admin', [], false);
        $client = $this->makeUser('Client');

        $service = Service::create(['name' => 'Monthly Retainer', 'service_code' => 'SRV-REM1', 'status' => 'active']);
        $version = ServiceVersion::create([
            'service_id' => $service->id,
            'version_number' => 'V1.0',
            'status' => 'approved',
            'send_due_reminders' => true,
            'auto_send_due_reminders' => true,
            'internal_reminder_days' => 5,
            'notification_channel' => 'app_only',
            'client_reminder_cadence' => 'daily',
        ]);
        $engagement = Engagement::create(['service_version_id' => $version->id, 'status' => 'active']);
        $period = EngagementPeriod::create(['engagement_id' => $engagement->id, 'service_version_id' => $version->id, 'period_name' => 'Nov 2026']);
        OperationalTask::create([
            'engagement_id' => $engagement->id,
            'engagement_period_id' => $period->id,
            'title' => 'Submit VAT Return',
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        // Run services:send-reminders twice -> only 1 internal + 1 followup for active admin, 0 for client/disabled
        $this->artisan('services:send-reminders')->assertSuccessful();
        $this->artisan('services:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($admin, WorkspaceUpdatedNotification::class, 2);
        Notification::assertNotSentTo($client, WorkspaceUpdatedNotification::class);
        Notification::assertNotSentTo($disabledAdmin, WorkspaceUpdatedNotification::class);

        // Run products:send-reminders twice -> only 1 notification for active admin
        $product = Product::create([
            'name' => 'Compliance Toolkit',
            'sku' => 'SKU-REM1',
            'price' => 2000,
            'status' => 'active',
            'client_followup_cadence' => 'daily',
            'notification_channel' => 'app_only',
        ]);
        DB::table('products')->where('id', $product->id)->update(['updated_at' => now()->subDays(2)]);

        $this->artisan('products:send-reminders')->assertSuccessful();
        $this->artisan('products:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($admin, WorkspaceUpdatedNotification::class, 3);
        Notification::assertNotSentTo($client, WorkspaceUpdatedNotification::class);
        Notification::assertNotSentTo($disabledAdmin, WorkspaceUpdatedNotification::class);
    }

    public function test_approval_workflow_migration_down_preserves_users_role(): void
    {
        $migration = require base_path('database/migrations/2026_10_13_000000_add_approval_workflow_fields.php');
        $migration->down();

        $this->assertTrue(Schema::hasColumn('users', 'role'));
        $this->assertTrue(Schema::hasColumn('services', 'created_by'));
        $this->assertTrue(Schema::hasColumn('services', 'approved_by'));
    }

    public function test_xlsx_import_rejects_malformed_zip_and_xxe_doctype_and_concurrency_lock_blocks_overlap(): void
    {
        $admin = $this->makeUser('Admin');

        // 1. Malformed ZIP masquerading as XLSX
        $corrupt = UploadedFile::fake()->createWithContent('corrupt.xlsx', 'NOT_A_REAL_ZIP_ARCHIVE');
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $corrupt])->assertSessionHasErrors('json_file');

        // 2. XXE / DOCTYPE injection inside XLSX worksheet
        $xxePath = sys_get_temp_dir() . '/test_xxe_' . uniqid() . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($xxePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><worksheet><sheetData/></worksheet>');
        $zip->close();
        $xxeFile = new UploadedFile($xxePath, 'xxe.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $this->actingAs($admin)->post(route('services.import'), ['json_file' => $xxeFile])->assertSessionHasErrors('json_file');
        @unlink($xxePath);

        // 3. Concurrency lock prevents overlapping services:send-reminders execution
        Notification::fake();
        $lock = Cache::lock('command:services:send-reminders', 60);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('services:send-reminders')
                ->expectsOutputToContain('already running')
                ->assertSuccessful();
            Notification::assertNothingSent();
        } finally {
            $lock->release();
        }
    }
}
