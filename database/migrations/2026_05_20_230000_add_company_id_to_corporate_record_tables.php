<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['notices', 'minutes', 'resolutions', 'secretary_certificates'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['notices', 'minutes', 'resolutions', 'secretary_certificates'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $indexName = $table . '_company_id_index';
                    if (Schema::hasColumn($table, 'company_id')) {
                        $blueprint->dropIndex($indexName);
                        $blueprint->dropColumn('company_id');
                    }
                });
            }
        }
    }
};
