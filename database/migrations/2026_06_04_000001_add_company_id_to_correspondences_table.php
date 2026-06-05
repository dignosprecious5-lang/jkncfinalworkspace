<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('correspondences') || Schema::hasColumn('correspondences', 'company_id')) {
            return;
        }

        Schema::table('correspondences', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('type')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('correspondences') || ! Schema::hasColumn('correspondences', 'company_id')) {
            return;
        }

        Schema::table('correspondences', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
