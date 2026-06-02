<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'company_id')) {
            return;
        }

        $nullRows = DB::table('services')->whereNull('company_id')->count();
        if ($nullRows > 0) {
            throw new RuntimeException("Cannot enforce required services.company_id: {$nullRows} row(s) have null company_id.");
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `services` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'company_id')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `services` MODIFY `company_id` BIGINT UNSIGNED NULL');
        }

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }
};
