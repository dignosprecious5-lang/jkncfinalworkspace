<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_versions') && ! Schema::hasColumn('service_versions', 'effective_date')) {
            Schema::table('service_versions', function (Blueprint $table) {
                $table->date('effective_date')->nullable();
            });
        }
        if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'effective_date')) {
            Schema::table('services', function (Blueprint $table) {
                $table->date('effective_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive to preserve service effective_date values.
    }
};
