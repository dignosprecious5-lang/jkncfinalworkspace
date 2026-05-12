<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (!Schema::hasColumn('job_offers', 'interview_id')) {
                $table->unsignedBigInteger('interview_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('job_offers', 'job_posting_id')) {
                $table->unsignedBigInteger('job_posting_id')->nullable()->after('interview_id');
            }

            if (!Schema::hasColumn('job_offers', 'address_id')) {
                $table->unsignedBigInteger('address_id')->nullable()->after('job_posting_id');
            }

            if (!Schema::hasColumn('job_offers', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('address_id');
            }

            if (!Schema::hasColumn('job_offers', 'office_id')) {
                $table->unsignedBigInteger('office_id')->nullable()->after('branch_id');
            }

            if (!Schema::hasColumn('job_offers', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('office_id');
            }

            if (!Schema::hasColumn('job_offers', 'division_id')) {
                $table->unsignedBigInteger('division_id')->nullable()->after('department_id');
            }

            if (!Schema::hasColumn('job_offers', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('division_id');
            }

            if (!Schema::hasColumn('job_offers', 'position_id')) {
                $table->unsignedBigInteger('position_id')->nullable()->after('unit_id');
            }

            if (!Schema::hasColumn('job_offers', 'salary_grade_id')) {
                $table->unsignedBigInteger('salary_grade_id')->nullable()->after('position_id');
            }

            if (!Schema::hasColumn('job_offers', 'candidate_email')) {
                $table->string('candidate_email')->nullable()->after('salary_grade_id');
            }

            if (!Schema::hasColumn('job_offers', 'company_address')) {
                $table->text('company_address')->nullable()->after('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $columns = [
                'interview_id',
                'job_posting_id',
                'address_id',
                'branch_id',
                'office_id',
                'department_id',
                'division_id',
                'unit_id',
                'position_id',
                'salary_grade_id',
                'candidate_email',
                'company_address',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('job_offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
