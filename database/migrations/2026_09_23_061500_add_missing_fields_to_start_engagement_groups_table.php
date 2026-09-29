<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('start_engagement_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('start_engagement_groups', 'engagement_type')) {
                $table->string('engagement_type')->nullable()->after('start_record_id');
            }
            if (!Schema::hasColumn('start_engagement_groups', 'group_code')) {
                $table->string('group_code')->nullable()->after('engagement_type');
            }
            if (!Schema::hasColumn('start_engagement_groups', 'deal_item_id')) {
                $table->unsignedBigInteger('deal_item_id')->nullable()->after('title');
            }
            if (!Schema::hasColumn('start_engagement_groups', 'status')) {
                $table->string('status')->nullable()->default('Structuring')->after('reporting_frequency');
            }
            if (!Schema::hasColumn('start_engagement_groups', 'project_manager')) {
                $table->string('project_manager')->nullable()->after('status');
            }
            if (!Schema::hasColumn('start_engagement_groups', 'notes')) {
                $table->text('notes')->nullable()->after('project_manager');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('start_engagement_groups', function (Blueprint $table) {
            $table->dropColumn([
                'engagement_type',
                'group_code',
                'deal_item_id',
                'status',
                'project_manager',
                'notes',
            ]);
        });
    }
};
