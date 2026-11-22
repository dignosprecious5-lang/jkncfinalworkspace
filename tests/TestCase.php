<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake((string) config('filesystems.default'));
    }

    protected function seedTenantAndCompany(): Company
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'ordo'],
            ['name' => 'ORDO']
        );

        return Company::firstOrCreate(
            ['code' => 'DEFAULT'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Default Company',
                'company_name' => 'Default Company',
            ]
        );
    }

    protected function seedSecondCompany(Company $first): Company
    {
        return Company::firstOrCreate(
            ['code' => 'SECOND'],
            [
                'tenant_id' => $first->tenant_id,
                'name' => 'Second Company',
                'company_name' => 'Second Company',
            ]
        );
    }

    protected function actingInCompany(Company $company, ?User $user = null): static
    {
        $this->app->make(CompanyContext::class)->set($company);

        if ($user !== null) {
            $this->actingAs($user);
        } elseif (! auth()->check()) {
            $user = User::firstOrCreate(
                ['email' => 'admin@ordo.test'],
                [
                    'name' => 'System Admin',
                    'password' => bcrypt('password'),
                    'role' => UserRole::SystemAdmin,
                ]
            );
            $this->actingAs($user);
        }

        return $this;
    }
}
