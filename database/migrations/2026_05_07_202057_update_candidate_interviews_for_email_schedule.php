<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidate_interviews')) {
            return;
        }

        Schema::table('candidate_interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_interviews', 'email')) {
                $table->string('email')->nullable()->after('name');
            }

            if (!Schema::hasColumn('candidate_interviews', 'type')) {
                $table->string('type')->nullable()->after('position');
            }

            if (!Schema::hasColumn('candidate_interviews', 'duration')) {
                $table->unsignedInteger('duration')->nullable()->default(60)->after('interview_date');
            }

            if (!Schema::hasColumn('candidate_interviews', 'meeting_link')) {
                $table->text('meeting_link')->nullable()->after('duration');
            }
        });

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE candidate_interviews MODIFY interview_date DATETIME NULL');
                DB::statement('ALTER TABLE candidate_interviews MODIFY round VARCHAR(255) NULL');
            }
        } catch (\Throwable $e) {
            // Safe fallback: existing column type will still work, but datetime may be truncated on some databases.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidate_interviews')) {
            return;
        }

        Schema::table('candidate_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_interviews', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }

            if (Schema::hasColumn('candidate_interviews', 'duration')) {
                $table->dropColumn('duration');
            }

            if (Schema::hasColumn('candidate_interviews', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('candidate_interviews', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
