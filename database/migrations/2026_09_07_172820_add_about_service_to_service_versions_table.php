<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_versions', 'about_service')) {
                $table->text('about_service')->nullable()->after('version_number');
            }
            if (!Schema::hasColumn('service_versions', 'purpose')) {
                $table->text('purpose')->nullable()->after('about_service');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('service_versions', 'about_service') ? 'about_service' : null,
                Schema::hasColumn('service_versions', 'purpose') ? 'purpose' : null,
            ]));
        });
    }
};