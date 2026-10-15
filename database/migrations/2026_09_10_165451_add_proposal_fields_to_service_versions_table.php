<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_versions', 'deliverables')) {
                $table->text('deliverables')->nullable();
            }
            if (!Schema::hasColumn('service_versions', 'client_responsibilities')) {
                $table->text('client_responsibilities')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_versions', function (Blueprint $table) {
            $table->dropColumn([
                'deliverables',
                'client_responsibilities',
            ]);
        });
    }
};