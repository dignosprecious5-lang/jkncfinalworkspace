<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_applications', 'applicant_type')) {
                $table->string('applicant_type')->default('New Applicant')->after('cover_letter');
            }

            if (!Schema::hasColumn('candidate_applications', 'internal_remarks')) {
                $table->text('internal_remarks')->nullable()->after('applicant_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_applications', 'internal_remarks')) {
                $table->dropColumn('internal_remarks');
            }

            if (Schema::hasColumn('candidate_applications', 'applicant_type')) {
                $table->dropColumn('applicant_type');
            }
        });
    }
};
