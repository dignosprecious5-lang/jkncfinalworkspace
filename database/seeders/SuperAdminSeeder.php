<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'corporate@uversalmanagement.com'],
            [
                'name' => 'System Super Admin',
                'password' => Hash::make('Uv3s@lM@n@g3m3nt!'),
                'role' => 'SuperAdmin',
            ]
        );
    }
}
