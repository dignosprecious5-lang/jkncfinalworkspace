<?php

namespace Database\Seeders;

use App\Models\RegularProject;
use Illuminate\Database\Seeder;

class RegularProjectSeeder extends Seeder
{
    public function run(): void
    {
        $seedFile = database_path('seed-projects.json');
        if (!file_exists($seedFile)) {
            return;
        }

        $projects = json_decode(file_get_contents($seedFile), true, flags: JSON_THROW_ON_ERROR);
        foreach ($projects as $data) {
            RegularProject::updateOrCreate(
                ['id' => $data['id']],
                [
                    'ref' => $data['ref'] ?? null,
                    'title' => $data['title'] ?? null,
                    'data' => $data,
                ]
            );
        }
    }
}
