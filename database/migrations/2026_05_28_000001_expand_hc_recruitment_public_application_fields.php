<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manpower_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('manpower_requests', 'immediate_supervisor')) {
                $table->string('immediate_supervisor')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('manpower_requests', 'target_start_date')) {
                $table->date('target_start_date')->nullable()->after('immediate_supervisor');
            }
            if (!Schema::hasColumn('manpower_requests', 'job_level_rank')) {
                $table->string('job_level_rank')->nullable()->after('target_start_date');
            }
            if (!Schema::hasColumn('manpower_requests', 'work_classification')) {
                $table->string('work_classification')->nullable()->after('job_level_rank');
            }
            if (!Schema::hasColumn('manpower_requests', 'work_arrangement')) {
                $table->string('work_arrangement')->nullable()->after('work_classification');
            }
            if (!Schema::hasColumn('manpower_requests', 'work_schedule')) {
                $table->string('work_schedule')->nullable()->after('work_arrangement');
            }
            if (!Schema::hasColumn('manpower_requests', 'required_skills')) {
                $table->text('required_skills')->nullable()->after('qualifications');
            }
            if (!Schema::hasColumn('manpower_requests', 'benefits_checklist')) {
                $table->json('benefits_checklist')->nullable()->after('required_skills');
            }
            if (!Schema::hasColumn('manpower_requests', 'required_licenses')) {
                $table->text('required_licenses')->nullable()->after('benefits_checklist');
            }
            if (!Schema::hasColumn('manpower_requests', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('required_licenses');
            }
            if (!Schema::hasColumn('manpower_requests', 'salary_min')) {
                $table->decimal('salary_min', 15, 2)->nullable()->after('required_documents');
            }
            if (!Schema::hasColumn('manpower_requests', 'salary_max')) {
                $table->decimal('salary_max', 15, 2)->nullable()->after('salary_min');
            }
            if (!Schema::hasColumn('manpower_requests', 'contract_duration')) {
                $table->string('contract_duration')->nullable()->after('salary_max');
            }
            if (!Schema::hasColumn('manpower_requests', 'urgency_level')) {
                $table->string('urgency_level')->nullable()->after('contract_duration');
            }
            if (!Schema::hasColumn('manpower_requests', 'candidate_profile_attached')) {
                $table->boolean('candidate_profile_attached')->default(false)->after('urgency_level');
            }
            if (!Schema::hasColumn('manpower_requests', 'job_description_attached')) {
                $table->boolean('job_description_attached')->default(false)->after('candidate_profile_attached');
            }
            if (!Schema::hasColumn('manpower_requests', 'endorsements')) {
                $table->json('endorsements')->nullable()->after('job_description_attached');
            }
        });

        Schema::table('candidate_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_applications', 'applicant_id')) {
                $table->string('applicant_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('candidate_applications', 'application_data')) {
                $table->json('application_data')->nullable()->after('cover_letter');
            }
            if (!Schema::hasColumn('candidate_applications', 'attachment_paths')) {
                $table->json('attachment_paths')->nullable()->after('application_data');
            }
            if (!Schema::hasColumn('candidate_applications', 'consent_accepted_at')) {
                $table->timestamp('consent_accepted_at')->nullable()->after('attachment_paths');
            }
            if (!Schema::hasColumn('candidate_applications', 'consent_version')) {
                $table->string('consent_version')->nullable()->after('consent_accepted_at');
            }
            if (!Schema::hasColumn('candidate_applications', 'submission_metadata')) {
                $table->json('submission_metadata')->nullable()->after('consent_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidate_applications', function (Blueprint $table) {
            foreach (['submission_metadata', 'consent_version', 'consent_accepted_at', 'attachment_paths', 'application_data', 'applicant_id'] as $column) {
                if (Schema::hasColumn('candidate_applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('manpower_requests', function (Blueprint $table) {
            foreach ([
                'endorsements',
                'job_description_attached',
                'candidate_profile_attached',
                'urgency_level',
                'contract_duration',
                'salary_max',
                'salary_min',
                'required_documents',
                'required_licenses',
                'benefits_checklist',
                'required_skills',
                'work_schedule',
                'work_arrangement',
                'work_classification',
                'job_level_rank',
                'target_start_date',
                'immediate_supervisor',
            ] as $column) {
                if (Schema::hasColumn('manpower_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
