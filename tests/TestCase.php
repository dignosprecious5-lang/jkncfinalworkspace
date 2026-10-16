<?php

namespace Tests;

use App\Models\Company;
use App\Models\Tenant;
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
        $tenant = Tenant::factory()->create(['name' => 'ORDO', 'slug' => 'ordo']);

        return Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Default Company',
            'code' => 'DEFAULT',
        ]);
    }

    protected function seedSecondCompany(Company $first): Company
    {
        return Company::factory()->create([
            'tenant_id' => $first->tenant_id,
            'name' => 'Second Company',
            'code' => 'SECOND',
        ]);
    }

    protected function actingInCompany(Company $company): static
    {
        $this->app->make(CompanyContext::class)->set($company);

        return $this;
    }

    /**
     * The accept attribute a file picker should carry, built from the same config
     * key the server-side rule and every picker read.
     *
     * Tests that assert the picker offers the right types used to pin a
     * hand-copied literal, which is how the copies drifted from the validator in
     * the first place. Deriving it here keeps the assertion honest.
     */
    protected function expectedAcceptAttribute(): string
    {
        return collect(config('ordo.document_extensions'))
            ->map(fn ($extension) => ".{$extension}")
            ->join(',');
    }
}
