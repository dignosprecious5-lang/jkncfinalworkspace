<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('start_engagement_groups', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | MAPPED DEAL LINE ITEMS
            |--------------------------------------------------------------------------
            |
            | A group may contain multiple approved Deal services/products.
            |
            */

            $table->json('mapped_deal_item_ids')
                ->nullable()
                ->after('deal_item_id');

            /*
            |--------------------------------------------------------------------------
            | SCOPE / DELIVERABLES
            |--------------------------------------------------------------------------
            */

            $table->text('scope_deliverables')
                ->nullable()
                ->after('group_name');

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
            | SERVICE / BILLING / REPORTING FREQUENCY
            |--------------------------------------------------------------------------
            |
            | These remain separate because the V2.2 brief explicitly says
            | not to collapse them into one field.
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
        });
    }

    public function down(): void
    {
        Schema::table('start_engagement_groups', function (Blueprint $table) {

            $table->dropColumn([
                'mapped_deal_item_ids',
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