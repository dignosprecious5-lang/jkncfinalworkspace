<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('manpower_requests', 'candidate_profile_path')) {
                $table->string('candidate_profile_path')->nullable()->after('candidate_profile_attached');
            }

            if (!Schema::hasColumn('manpower_requests', 'job_description_path')) {
                $table->string('job_description_path')->nullable()->after('job_description_attached');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            foreach (['job_description_path', 'candidate_profile_path'] as $column) {
                if (Schema::hasColumn('manpower_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
