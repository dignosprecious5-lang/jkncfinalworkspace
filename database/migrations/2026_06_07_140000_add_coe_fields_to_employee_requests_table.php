<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_requests', 'coe_type')) {
                $table->string('coe_type')->nullable()->after('purpose');
            }

            if (! Schema::hasColumn('employee_requests', 'coe_purpose_other')) {
                $table->string('coe_purpose_other')->nullable()->after('coe_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_requests', 'coe_purpose_other')) {
                $table->dropColumn('coe_purpose_other');
            }

            if (Schema::hasColumn('employee_requests', 'coe_type')) {
                $table->dropColumn('coe_type');
            }
        });
    }
};
