<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Safe detection at paglilinis ng existing duplicates bago maglagay ng constraint
        $duplicates = DB::table('operational_tasks')
            ->select('engagement_period_id', 'service_activity_id', DB::raw('count(*) as total'))
            ->groupBy('engagement_period_id', 'service_activity_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $ids = DB::table('operational_tasks')
                ->where('engagement_period_id', $dup->engagement_period_id)
                ->where('service_activity_id', $dup->service_activity_id)
                ->orderBy('id')
                ->pluck('id');
            
            $idsTopping = $ids->slice(1);
            DB::table('operational_tasks')->whereIn('id', $idsTopping)->delete();
        }

        // 2. Magdagdag ng unique index
        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->unique(['engagement_period_id', 'service_activity_id'], 'op_tasks_period_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->dropUnique('op_tasks_period_activity_unique');
        });
    }
};