<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminUserPermissionController;
use App\Http\Controllers\RolePermissionController;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DealPermissionEditorIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role')->default('Employee');
            $table->boolean('is_active')->default(true);
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('access_activities')->default(false);
            $table->boolean('manage_deal_stages')->default(false);
            $table->boolean('manage_deals_finance_settings')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->unique();
            $table->boolean('access_activities')->default(false);
            $table->boolean('manage_deal_stages')->default(false);
            $table->boolean('manage_deals_finance_settings')->default(false);
            $table->timestamps();
        });
    }

    public function test_user_permission_grant_and_denial_preserve_activities_and_role_permissions(): void
    {
        $employeeRole = RolePermission::query()->create([
            'role' => 'Employee',
            'access_activities' => true,
            'manage_deal_stages' => false,
            'manage_deals_finance_settings' => false,
        ]);
        $target = $this->makeUser('Employee');
        $userPermission = UserPermission::query()->create([
            'user_id' => $target->id,
            'access_activities' => true,
            'manage_deal_stages' => false,
            'manage_deals_finance_settings' => false,
        ]);

        $this->actAsPermissionEditor();
        $this->updateUserPermissions($target, true);

        $this->assertSame(1, (int) $userPermission->fresh()->access_activities);
        $this->assertSame(1, (int) $userPermission->fresh()->manage_deal_stages);
        $this->assertSame(1, (int) $userPermission->fresh()->manage_deals_finance_settings);
        $this->assertSame(1, (int) $employeeRole->fresh()->access_activities);
        $this->assertTrue($target->fresh()->hasPermission('access_activities'));

        $this->updateUserPermissions($target, false);

        $this->assertSame(1, (int) $userPermission->fresh()->access_activities);
        $this->assertSame(0, (int) $userPermission->fresh()->manage_deal_stages);
        $this->assertSame(0, (int) $userPermission->fresh()->manage_deals_finance_settings);
        $this->assertSame(1, (int) $employeeRole->fresh()->access_activities);
        $this->assertTrue($target->fresh()->hasPermission('access_activities'));
    }

    public function test_role_permission_grant_and_denial_preserve_activities_and_user_overrides(): void
    {
        $employeeRole = RolePermission::query()->create([
            'role' => 'Employee',
            'access_activities' => true,
            'manage_deal_stages' => false,
            'manage_deals_finance_settings' => false,
        ]);
        $target = $this->makeUser('Employee');
        $userPermission = UserPermission::query()->create([
            'user_id' => $target->id,
            'access_activities' => false,
            'manage_deal_stages' => false,
            'manage_deals_finance_settings' => false,
        ]);

        $this->actAsPermissionEditor();
        $this->updateRolePermissions($employeeRole, true);

        $this->assertSame(1, (int) $employeeRole->fresh()->access_activities);
        $this->assertSame(1, (int) $employeeRole->fresh()->manage_deal_stages);
        $this->assertSame(1, (int) $employeeRole->fresh()->manage_deals_finance_settings);
        $this->assertSame(0, (int) $userPermission->fresh()->access_activities);
        $this->assertFalse($target->fresh()->hasPermission('access_activities'));

        $this->updateRolePermissions($employeeRole, false);

        $this->assertSame(1, (int) $employeeRole->fresh()->access_activities);
        $this->assertSame(0, (int) $employeeRole->fresh()->manage_deal_stages);
        $this->assertSame(0, (int) $employeeRole->fresh()->manage_deals_finance_settings);
        $this->assertSame(0, (int) $userPermission->fresh()->access_activities);
        $this->assertFalse($target->fresh()->hasPermission('access_activities'));
    }

    private function makeUser(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Permission editor test '.$role,
            'email' => uniqid('permission-editor-', true).'@example.test',
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function actAsPermissionEditor(): void
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Test Permission Administrator',
            'email' => uniqid('permission-admin-', true).'@example.test',
            'role' => 'SuperAdmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin = User::query()->findOrFail($id);

        Auth::shouldReceive('user')->andReturn($admin);
        Auth::shouldReceive('id')->andReturn($admin->id);
    }

    private function updateUserPermissions(User $target, bool $grantDealPermissions): void
    {
        $payload = ['access_activities' => '1'];
        if ($grantDealPermissions) {
            $payload['manage_deal_stages'] = '1';
            $payload['manage_deals_finance_settings'] = '1';
        }

        app(AdminUserPermissionController::class)->update(
            Request::create('/admin/user-permissions/'.$target->id, 'POST', $payload),
            $target->id
        );
    }

    private function updateRolePermissions(RolePermission $role, bool $grantDealPermissions): void
    {
        $payload = ['access_activities' => '1'];
        if ($grantDealPermissions) {
            $payload['manage_deal_stages'] = '1';
            $payload['manage_deals_finance_settings'] = '1';
        }

        app(RolePermissionController::class)->update(
            Request::create('/admin/role-permissions/'.$role->id, 'POST', $payload),
            $role->id
        );
    }
}
