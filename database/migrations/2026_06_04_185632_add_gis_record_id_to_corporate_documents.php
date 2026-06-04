<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['notices', 'minutes', 'resolutions', 'secretary_certificates'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'gis_record_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('gis_record_id')->nullable()->after('company_id')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['notices', 'minutes', 'resolutions', 'secretary_certificates'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'gis_record_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['gis_record_id']);
                $table->dropColumn('gis_record_id');
            });
        }
    }
};
