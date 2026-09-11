<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->text('about_service')->nullable()->after('version_number');
            $table->text('purpose')->nullable()->after('about_service');
        });
    }

    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn(['about_service', 'purpose']);
        });
    }
};