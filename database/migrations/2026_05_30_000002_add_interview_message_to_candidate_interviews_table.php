<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidate_interviews')) {
            return;
        }

        Schema::table('candidate_interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_interviews', 'message')) {
                $table->text('message')->nullable()->after('meeting_link');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidate_interviews')) {
            return;
        }

        Schema::table('candidate_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_interviews', 'message')) {
                $table->dropColumn('message');
            }
        });
    }
};
