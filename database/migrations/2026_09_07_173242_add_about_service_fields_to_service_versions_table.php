<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('service_versions', 'about_service')) {
                $table->text('about_service')->nullable();
            }

            if (! Schema::hasColumn('service_versions', 'purpose')) {
                $table->text('purpose')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (Schema::hasColumn('service_versions', 'purpose')) {
                $table->dropColumn('purpose');
            }
        });
    }
};
