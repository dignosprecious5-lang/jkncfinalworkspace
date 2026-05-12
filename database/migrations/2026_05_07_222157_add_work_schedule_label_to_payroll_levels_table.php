<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_levels', 'work_schedule_label')) {
                $table->string('work_schedule_label')->nullable()->after('work_schedule');
            }
        });

        DB::table('payroll_levels')
            ->whereIn('work_schedule_label', ['every_day', 'no_sunday', 'no_saturday', 'no_sat_sun', 'no_sat_sun_holidays'])
            ->orWhereNull('work_schedule_label')
            ->orderBy('id')
            ->get()
            ->each(function ($level) {
                $raw = $level->work_schedule_label ?: $level->work_schedule;

                $label = match ($raw) {
                    'every_day' => 'Monday to Sunday – 8:00 AM to 5:00 PM',
                    'no_sunday' => 'Monday to Saturday – 8:00 AM to 5:00 PM',
                    'no_saturday', 'no_sat_sun', 'no_sat_sun_holidays' => 'Monday to Friday – 8:00 AM to 5:00 PM',
                    default => $level->work_schedule_label,
                };

                if ($label) {
                    DB::table('payroll_levels')->where('id', $level->id)->update([
                        'work_schedule_label' => $label,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('payroll_levels', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_levels', 'work_schedule_label')) {
                $table->dropColumn('work_schedule_label');
            }
        });
    }
};
