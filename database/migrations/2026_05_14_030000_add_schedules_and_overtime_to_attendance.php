<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'schedule_start_time')) {
                $table->time('schedule_start_time')->nullable()->after('hourly_rate');
            }

            if (! Schema::hasColumn('employees', 'schedule_end_time')) {
                $table->time('schedule_end_time')->nullable()->after('schedule_start_time');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'work_type')) {
                $table->string('work_type')->default('regular')->after('date');
            }

            if (! Schema::hasColumn('attendances', 'employee_request_id')) {
                $table->foreignId('employee_request_id')
                    ->nullable()
                    ->after('work_type')
                    ->constrained('employee_requests')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'employee_request_id')) {
                $table->dropForeign(['employee_request_id']);
                $table->dropColumn('employee_request_id');
            }

            if (Schema::hasColumn('attendances', 'work_type')) {
                $table->dropColumn('work_type');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (['schedule_start_time', 'schedule_end_time'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
