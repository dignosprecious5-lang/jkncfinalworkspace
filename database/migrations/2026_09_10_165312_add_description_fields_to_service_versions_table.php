<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_versions', 'internal_description')) {
                $table->text('internal_description')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'client_description')) {
                $table->text('client_description')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'about_service')) {
                $table->text('about_service')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'when_to_use')) {
                $table->text('when_to_use')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'what_it_is_not')) {
                $table->text('what_it_is_not')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn([
                'internal_description',
                'client_description',
                'about_service',
                'when_to_use',
                'what_it_is_not',
            ]);
        });
    }
};