<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_employee_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_employee_registrations', 'onboarding_checklist_id')) {
                $table->unsignedBigInteger('onboarding_checklist_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('onboarding_employee_registrations', 'employee_profile_id')) {
                $table->unsignedBigInteger('employee_profile_id')->nullable()->after('onboarding_checklist_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_employee_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('onboarding_employee_registrations', 'employee_profile_id')) {
                $table->dropColumn('employee_profile_id');
            }

            if (Schema::hasColumn('onboarding_employee_registrations', 'onboarding_checklist_id')) {
                $table->dropColumn('onboarding_checklist_id');
            }
        });
    }
};
