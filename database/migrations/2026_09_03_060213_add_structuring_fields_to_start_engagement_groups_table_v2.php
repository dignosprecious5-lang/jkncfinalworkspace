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

            /*
            |--------------------------------------------------------------------------
            | STRUCTURING
            |--------------------------------------------------------------------------
            */

            // Temporary REG-01 / PROJ-01 while START is still in draft.
            $table->string('temporary_group_key')
                ->nullable()
                ->after('group_code');

            // Professional operational title.
            $table->string('title')
                ->nullable()
                ->after('group_name');

            /*
            |--------------------------------------------------------------------------
            | MAPPED DEAL ITEMS
            |--------------------------------------------------------------------------
            |
            | One engagement group may contain multiple approved
            | Deal services/products.
            |
            */

            $table->json('deal_item_ids')
                ->nullable()
                ->after('deal_item_id');

            /*
            |--------------------------------------------------------------------------
            | SCOPE / DELIVERABLES
            |--------------------------------------------------------------------------
            */

            $table->text('scope_deliverables')
                ->nullable()
                ->after('deal_item_ids');

            /*
            |--------------------------------------------------------------------------
            | OPERATIONAL DATES
            |--------------------------------------------------------------------------
            */

            $table->date('start_date')
                ->nullable()
                ->after('scope_deliverables');

            $table->date('target_end_date')
                ->nullable()
                ->after('start_date');

            $table->unsignedInteger('duration_days')
                ->nullable()
                ->after('target_end_date');

            /*
            |--------------------------------------------------------------------------
            | FREQUENCY
            |--------------------------------------------------------------------------
            |
            | Regular engagements may have different service,
            | billing and reporting frequencies.
            |
            */

            $table->string('service_frequency')
                ->nullable()
                ->after('duration_days');

            $table->string('billing_frequency')
                ->nullable()
                ->after('service_frequency');

            $table->string('reporting_frequency')
                ->nullable()
                ->after('billing_frequency');

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index('temporary_group_key');
            $table->index('start_date');
            $table->index('target_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('start_engagement_groups', function (Blueprint $table) {
            $table->dropIndex([
                'start_engagement_groups_temporary_group_key_index'
            ]);

            $table->dropIndex([
                'start_engagement_groups_start_date_index'
            ]);

            $table->dropIndex([
                'start_engagement_groups_target_end_date_index'
            ]);

            $table->dropColumn([
                'temporary_group_key',
                'title',
                'deal_item_ids',
                'scope_deliverables',
                'start_date',
                'target_end_date',
                'duration_days',
                'service_frequency',
                'billing_frequency',
                'reporting_frequency',
            ]);
        });
    }
};