<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('manpower_requests')) {
            Schema::table('manpower_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('manpower_requests', 'address_id')) {
                    $table->foreignId('address_id')->nullable()->after('id')->constrained('organizational_addresses')->nullOnDelete();
                }

                if (!Schema::hasColumn('manpower_requests', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('address_id')->constrained('branches')->nullOnDelete();
                }

                if (!Schema::hasColumn('manpower_requests', 'office_id')) {
                    $table->foreignId('office_id')->nullable()->after('branch_id')->constrained('offices')->nullOnDelete();
                }

                if (!Schema::hasColumn('manpower_requests', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('office_id')->constrained('departments')->nullOnDelete();
                }

                if (!Schema::hasColumn('manpower_requests', 'division_id')) {
                    $table->foreignId('division_id')->nullable()->after('department_id')->constrained('divisions')->nullOnDelete();
                }

                if (!Schema::hasColumn('manpower_requests', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('division_id')->constrained('units')->nullOnDelete();
                }

                if (!Schema::hasColumn('manpower_requests', 'position_id')) {
                    $table->foreignId('position_id')->nullable()->after('unit_id')->constrained('positions')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('job_postings')) {
            Schema::table('job_postings', function (Blueprint $table) {
                if (!Schema::hasColumn('job_postings', 'mrf_id')) {
                    $table->foreignId('mrf_id')->nullable()->after('id')->constrained('manpower_requests')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'address_id')) {
                    $table->foreignId('address_id')->nullable()->after('mrf_id')->constrained('organizational_addresses')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('address_id')->constrained('branches')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'office_id')) {
                    $table->foreignId('office_id')->nullable()->after('branch_id')->constrained('offices')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('office_id')->constrained('departments')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'division_id')) {
                    $table->foreignId('division_id')->nullable()->after('department_id')->constrained('divisions')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('division_id')->constrained('units')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'position_id')) {
                    $table->foreignId('position_id')->nullable()->after('unit_id')->constrained('positions')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_postings', 'salary_grade_id')) {
                    $table->foreignId('salary_grade_id')->nullable()->after('position_id')->constrained('salary_grades')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('job_offers')) {
            Schema::table('job_offers', function (Blueprint $table) {
                if (!Schema::hasColumn('job_offers', 'job_posting_id')) {
                    $table->foreignId('job_posting_id')->nullable()->after('id')->constrained('job_postings')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'address_id')) {
                    $table->foreignId('address_id')->nullable()->after('job_posting_id')->constrained('organizational_addresses')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('address_id')->constrained('branches')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'office_id')) {
                    $table->foreignId('office_id')->nullable()->after('branch_id')->constrained('offices')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('office_id')->constrained('departments')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'division_id')) {
                    $table->foreignId('division_id')->nullable()->after('department_id')->constrained('divisions')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'unit_id')) {
                    $table->foreignId('unit_id')->nullable()->after('division_id')->constrained('units')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'position_id')) {
                    $table->foreignId('position_id')->nullable()->after('unit_id')->constrained('positions')->nullOnDelete();
                }

                if (!Schema::hasColumn('job_offers', 'salary_grade_id')) {
                    $table->foreignId('salary_grade_id')->nullable()->after('position_id')->constrained('salary_grades')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_offers')) {
            Schema::table('job_offers', function (Blueprint $table) {
                foreach ([
                    'salary_grade_id',
                    'position_id',
                    'unit_id',
                    'division_id',
                    'department_id',
                    'office_id',
                    'branch_id',
                    'address_id',
                    'job_posting_id',
                ] as $column) {
                    if (Schema::hasColumn('job_offers', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }

        if (Schema::hasTable('job_postings')) {
            Schema::table('job_postings', function (Blueprint $table) {
                foreach ([
                    'salary_grade_id',
                    'position_id',
                    'unit_id',
                    'division_id',
                    'department_id',
                    'office_id',
                    'branch_id',
                    'address_id',
                    'mrf_id',
                ] as $column) {
                    if (Schema::hasColumn('job_postings', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }

        if (Schema::hasTable('manpower_requests')) {
            Schema::table('manpower_requests', function (Blueprint $table) {
                foreach ([
                    'position_id',
                    'unit_id',
                    'division_id',
                    'department_id',
                    'office_id',
                    'branch_id',
                    'address_id',
                ] as $column) {
                    if (Schema::hasColumn('manpower_requests', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }
    }
};