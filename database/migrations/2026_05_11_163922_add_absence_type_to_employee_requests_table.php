<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_requests', 'absence_type')) {
                $table->string('absence_type')->nullable()->after('correct_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_requests', 'absence_type')) {
                $table->dropColumn('absence_type');
            }
        });
    }
};
