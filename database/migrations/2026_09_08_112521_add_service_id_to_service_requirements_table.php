<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requirements', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('id')->constrained('services')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requirements', function (Blueprint $table) {
            if (Schema::hasColumn('service_requirements', 'service_id')) {
                $table->dropColumn('service_id');
            }
        });
    }
};