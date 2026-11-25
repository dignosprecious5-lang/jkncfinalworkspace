<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('service_versions')) {
            Schema::table('service_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('service_versions', 'expected_turnaround')) {
                    $table->string('expected_turnaround')->nullable();
                }
                if (!Schema::hasColumn('service_versions', 'internal_guide_url')) {
                    $table->string('internal_guide_url')->nullable();
                }
                if (!Schema::hasColumn('service_versions', 'client_facing_summary')) {
                    $table->text('client_facing_summary')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('service_versions')) {
            Schema::table('service_versions', function (Blueprint $table) {
                $columnsToDrop = [];

                if (Schema::hasColumn('service_versions', 'expected_turnaround')) {
                    $columnsToDrop[] = 'expected_turnaround';
                }
                if (Schema::hasColumn('service_versions', 'internal_guide_url')) {
                    $columnsToDrop[] = 'internal_guide_url';
                }
                if (Schema::hasColumn('service_versions', 'client_facing_summary')) {
                    $columnsToDrop[] = 'client_facing_summary';
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};