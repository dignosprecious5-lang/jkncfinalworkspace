<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('performance_evaluations', 'action_plan')) {
                $table->json('action_plan')->nullable()->after('areas_for_improvement');
            }
        });
    }

    public function down(): void
    {
        Schema::table('performance_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('performance_evaluations', 'action_plan')) {
                $table->dropColumn('action_plan');
            }
        });
    }
};
