<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'deal_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `projects` DROP FOREIGN KEY `projects_deal_id_foreign`');
        } catch (Throwable) {
            // The constraint may already have been removed or renamed in an existing database.
        }

        DB::statement('ALTER TABLE `projects` MODIFY `deal_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `projects` ADD CONSTRAINT `projects_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'deal_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        try {
            DB::statement('ALTER TABLE `projects` DROP FOREIGN KEY `projects_deal_id_foreign`');
        } catch (Throwable) {
        }

        DB::statement('ALTER TABLE `projects` MODIFY `deal_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `projects` ADD CONSTRAINT `projects_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE');
    }
};
