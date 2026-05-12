<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_checklists', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_checklists', 'personal_data_sheet_id')) {
                $table->unsignedBigInteger('personal_data_sheet_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('onboarding_checklists', 'employee_email')) {
                $table->string('employee_email')->nullable()->after('employee_name');
            }

            if (!Schema::hasColumn('onboarding_checklists', 'position')) {
                $table->string('position')->nullable()->after('employee_email');
            }

            if (!Schema::hasColumn('onboarding_checklists', 'docs_approved')) {
                $table->unsignedInteger('docs_approved')->default(0)->after('docs_submitted');
            }

            if (!Schema::hasColumn('onboarding_checklists', 'status')) {
                $table->string('status')->default('For Review')->after('total_docs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_checklists', function (Blueprint $table) {
            $columns = [
                'personal_data_sheet_id',
                'employee_email',
                'position',
                'docs_approved',
                'status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('onboarding_checklists', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
