<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->raw([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]),
        );

        $this->seedCompanies();

        $this->call([
            RecordClassificationSeeder::class,
            RecordSourceSeeder::class,
        ]);
    }

    /**
     * A second company makes tenant and company isolation verifiable during review.
     */
    private function seedCompanies(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'ordo'],
            ['name' => 'ORDO'],
        );

        Company::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'DEFAULT'],
            ['name' => 'Default Company', 'business_name' => 'Default Company'],
        );

        Company::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SECOND'],
            ['name' => 'Second Company', 'business_name' => 'Second Company'],
        );
    }
}
