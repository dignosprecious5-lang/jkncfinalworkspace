<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['regular_projects', 'projects'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'data')) {
                DB::table($tableName)->orderBy('id')->chunkById(100, function ($records) use ($tableName) {
                    foreach ($records as $record) {
                        if (empty($record->data)) {
                            continue;
                        }
                        $updated = preg_replace('/\bPROJ-(?=[A-Z0-9])/', 'REG-', $record->data);
                        if ($updated !== $record->data) {
                            DB::table($tableName)->where('id', $record->id)->update(['data' => $updated]);
                        }
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // Deliberately retain issued Regular references; a rollback must not rename them.
    }
};
