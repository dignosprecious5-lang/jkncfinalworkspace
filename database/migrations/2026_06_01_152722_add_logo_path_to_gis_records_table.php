<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gis_records', function (Blueprint $table) {
            if (! Schema::hasColumn('gis_records', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('notary_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gis_records', function (Blueprint $table) {
            if (Schema::hasColumn('gis_records', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
