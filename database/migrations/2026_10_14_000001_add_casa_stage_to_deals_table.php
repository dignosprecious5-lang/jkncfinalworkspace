<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deals') && !Schema::hasColumn('deals', 'casa_stage')) {
            Schema::table('deals', function (Blueprint $table): void {
                $table->string('casa_stage')->nullable()->after('pipeline_stage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deals') && Schema::hasColumn('deals', 'casa_stage')) {
            Schema::table('deals', function (Blueprint $table): void {
                $table->dropColumn('casa_stage');
            });
        }
    }
};
