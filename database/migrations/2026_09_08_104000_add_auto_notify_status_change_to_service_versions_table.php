<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('service_versions', 'auto_notify_status_change')) {
                $table->boolean('auto_notify_status_change')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_versions', 'auto_notify_status_change')) {
            Schema::table('service_versions', function (Blueprint $table) {
                $table->dropColumn('auto_notify_status_change');
            });
        }
    }
};
