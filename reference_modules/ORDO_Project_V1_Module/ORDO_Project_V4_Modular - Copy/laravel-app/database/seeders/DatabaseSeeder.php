<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (json_decode(file_get_contents(database_path('seed-projects.json')), true, flags: JSON_THROW_ON_ERROR) as $data) {
            Project::firstOrCreate(['id' => $data['id']], ['data' => $data]);
        }
    }
}
