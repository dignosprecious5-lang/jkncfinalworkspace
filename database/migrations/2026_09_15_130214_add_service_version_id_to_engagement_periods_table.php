<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('engagement_periods') && ! Schema::hasColumn('engagement_periods', 'service_version_id')) {
            Schema::table('engagement_periods', function (Blueprint $table) {
                $table->foreignId('service_version_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive to preserve engagement_periods.service_version_id when created earlier.
    }
};
