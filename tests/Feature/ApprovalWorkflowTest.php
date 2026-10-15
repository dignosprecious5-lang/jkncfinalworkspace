<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'product_audit_logs',
            'service_audit_logs',
            'product_versions',
            'service_versions',
            'products',
            'services',
            'audit_logs',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code')->unique();
            $table->string('name');
            $table->string('service_area')->nullable();
            $table->string('category')->nullable();
            $table->string('engagement_behavior')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('active_version_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('service_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('version_number')->default('V1.0');
            $table->string('status')->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('user_name')->nullable();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('product_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('version_number')->default('V1.0');
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('product_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('user_name')->nullable();
            $table->string('action')->nullable();
            $table->string('title')->nullable();
            $table->text('details')->nullable();
            $table->text('description')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action')->nullable();
            $table->string('module')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Notification::fake();
    }

    public function test_admin_can_approve_service_once(): void
    {
        $creator = $this->createUser();
        $admin = $this->createUser('admin');
        $service = $this->createService($creator);

        $this->actingAs($creator)
            ->post(route('services.submit_approval', $service))
            ->assertRedirect(route('services.workspace', [
                'service' => $service->id,
                'mode' => 'view',
            ]));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'status' => 'pending_approval',
        ]);
        $this->assertDatabaseHas('service_versions', [
            'service_id' => $service->id,
            'status' => 'pending_approval',
        ]);

        $this->actingAs($admin)
            ->post(route('services.approve', $service))
            ->assertRedirect(route('services.workspace', [
                'service' => $service->id,
                'mode' => 'view',
            ]));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'status' => 'active',
            'approved_by' => $admin->id,
        ]);
        $this->assertNotNull($service->fresh()->approved_at);
        $this->assertDatabaseHas('service_versions', [
            'service_id' => $service->id,
            'status' => 'approved',
        ]);

        $this->post(route('services.approve', $service))->assertStatus(409);
    }

    public function test_admin_can_reject_service_without_comment(): void
    {
        $creator = $this->createUser();
        $admin = $this->createUser('admin');
        $service = $this->createService($creator);

        $this->actingAs($creator)
            ->post(route('services.submit_approval', $service));

        $this->actingAs($admin)
            ->post(route('services.reject', $service))
            ->assertRedirect(route('services.workspace', [
            'service' => $service->id,
            'mode' => 'view',
        ]));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'status' => 'rejected',
            'rejected_by' => $admin->id,
            'rejection_reason' => null,
        ]);
        $this->assertNotNull($service->fresh()->rejected_at);
    }

    public function test_admin_can_approve_product(): void
    {
        $creator = $this->createUser();
        $admin = $this->createUser('admin');
        $product = $this->createProduct($creator);

        $this->actingAs($creator)
            ->post(route('products.submit-approval', $product))
            ->assertRedirect(route('products.workspace', [
                'id' => $product->id,
                'mode' => 'view',
            ]));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'status' => 'pending_approval',
        ]);
        $this->assertDatabaseHas('product_versions', [
            'product_id' => $product->id,
            'status' => 'pending_approval',
        ]);

        $this->actingAs($admin)
            ->post(route('products.approve', $product))
            ->assertRedirect(route('products.workspace', [
                'id' => $product->id,
                'mode' => 'view',
            ]));

        $version = $product->versions()->firstOrFail();
        $this->assertSame('active', $product->fresh()->status);
        $this->assertSame('published', $version->fresh()->status);
        $this->assertSame($admin->id, $version->fresh()->approved_by);
        $this->assertNotNull($version->fresh()->approved_at);

        $this->post(route('products.approve', $product))->assertStatus(409);
    }

    public function test_admin_can_reject_product_with_optional_comment(): void
    {
        $creator = $this->createUser();
        $admin = $this->createUser('admin');
        $product = $this->createProduct($creator);

        $this->actingAs($creator)
            ->post(route('products.submit-approval', $product));

        $this->actingAs($admin)
            ->post(route('products.reject', $product), [
            'rejection_reason' => 'Please revise the product description.',
        ])->assertRedirect(route('products.workspace', [
            'id' => $product->id,
            'mode' => 'view',
        ]));

        $version = $product->versions()->firstOrFail()->fresh();
        $this->assertSame('rejected', $product->fresh()->status);
        $this->assertSame('rejected', $version->status);
        $this->assertSame($admin->id, $version->rejected_by);
        $this->assertSame('Please revise the product description.', $version->rejection_reason);
        $this->assertNotNull($version->rejected_at);
    }

    public function test_creator_and_non_admin_cannot_decide(): void
    {
        $creator = $this->createUser();
        $otherUser = $this->createUser();
        $service = $this->createService($creator);

        $this->actingAs($creator)
            ->post(route('services.submit_approval', $service));

        $this->actingAs($creator)
            ->post(route('services.approve', $service))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('services.approve', $service))
            ->assertForbidden();

        $this->assertSame('pending_approval', $service->fresh()->status);

        $product = $this->createProduct($creator);
        $this->actingAs($creator)
            ->post(route('products.submit-approval', $product));

        $this->actingAs($creator)
            ->post(route('products.approve', $product))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->post(route('products.approve', $product))
            ->assertForbidden();

        $this->assertSame('pending_approval', $product->fresh()->status);
    }

    public function test_admin_role_can_approve_another_users_submission(): void
    {
        $creator = $this->createUser();
        $admin = $this->createUser('admin');
        $service = $this->createService($creator);

        $this->actingAs($creator)
            ->post(route('services.submit_approval', $service));

        $this->actingAs($admin)
            ->post(route('services.approve', $service))
            ->assertRedirect(route('services.workspace', [
                'service' => $service->id,
                'mode' => 'view',
            ]));

        $this->assertSame($admin->id, $service->fresh()->approved_by);

        $product = $this->createProduct($creator);
        $this->actingAs($creator)
            ->post(route('products.submit-approval', $product));

        $this->actingAs($admin)
            ->post(route('products.approve', $product))
            ->assertRedirect(route('products.workspace', [
                'id' => $product->id,
                'mode' => 'view',
            ]));

        $this->assertSame(
            $admin->id,
            $product->versions()->firstOrFail()->fresh()->approved_by
        );

        $manager = $this->createUser('manager');
        $anotherService = $this->createService($creator);
        $this->actingAs($creator)
            ->post(route('services.submit_approval', $anotherService));
        $this->actingAs($manager)
            ->post(route('services.approve', $anotherService))
            ->assertForbidden();
    }

    private function createUser(?string $role = null): User
    {
        $user = new User();
        $user->name = 'Workflow User';
        $user->email = uniqid('workflow-', true) . '@example.test';
        $user->password = 'password';
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function createService(User $creator): Service
    {
        $service = Service::create([
            'service_code' => uniqid('SVC-'),
            'name' => 'Approval Test Service',
            'service_area' => 'Test',
            'engagement_behavior' => 'project',
            'status' => 'draft',
            'created_by' => $creator->id,
        ]);

        $versionId = DB::table('service_versions')->insertGetId([
            'service_id' => $service->id,
            'version_number' => 'V1.0',
            'status' => 'draft',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service->update(['active_version_id' => $versionId]);

        return $service;
    }

    private function createProduct(User $creator): Product
    {
        $product = Product::create([
            'name' => 'Approval Test Product',
            'sku' => uniqid('PRD-'),
            'status' => 'draft',
        ]);

        DB::table('product_versions')->insert([
            'product_id' => $product->id,
            'version_number' => 'V1.0',
            'status' => 'draft',
            'is_active' => true,
            'created_by' => $creator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $product;
    }
}
