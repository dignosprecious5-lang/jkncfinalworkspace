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
            if (!Schema::hasColumn('candidate_interviews', 'application_details')) {
                $table->json('application_details')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('candidate_interviews')) {
            return;
        }

        Schema::table('candidate_interviews', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_interviews', 'application_details')) {
                $table->dropColumn('application_details');
            }
        });
    }
};
