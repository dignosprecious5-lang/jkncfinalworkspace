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
        Schema::table('start_records', function (Blueprint $table) {
            if (!Schema::hasColumn('start_records', 'start_code')) {
                $table->string('start_code')->nullable()->after('deal_id');
            }
            if (!Schema::hasColumn('start_records', 'batch_name')) {
                $table->string('batch_name')->nullable()->after('start_code');
            }
            if (!Schema::hasColumn('start_records', 'activated_items')) {
                $table->json('activated_items')->nullable()->after('activation_summary');
            }
            if (!Schema::hasColumn('start_records', 'service_memo_ref')) {
                $table->string('service_memo_ref')->nullable()->after('memo_status');
            }
            if (!Schema::hasColumn('start_records', 'service_memo_data')) {
                $table->json('service_memo_data')->nullable()->after('service_memo_ref');
            }
            if (!Schema::hasColumn('start_records', 'service_memo_revisions')) {
                $table->json('service_memo_revisions')->nullable()->after('service_memo_data');
            }
            if (!Schema::hasColumn('start_records', 'history_logs')) {
                $table->json('history_logs')->nullable()->after('service_memo_revisions');
            }
            if (!Schema::hasColumn('start_records', 'authorized_by')) {
                $table->string('authorized_by')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('start_records', 'created_by')) {
                $table->string('created_by')->nullable()->after('authorized_by');
            }
        });

        Schema::table('engagement_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('engagement_assignments', 'start_record_id')) {
                $table->unsignedBigInteger('start_record_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('engagement_assignments', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('engagement_assignments', 'response')) {
                $table->string('response')->nullable()->after('acknowledged_at');
            }
            if (!Schema::hasColumn('engagement_assignments', 'decline_reason')) {
                $table->text('decline_reason')->nullable()->after('response');
            }
            if (!Schema::hasColumn('engagement_assignments', 'is_required')) {
                $table->boolean('is_required')->default(true)->after('decline_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('start_records', function (Blueprint $table) {
            $table->dropColumn([
                'start_code',
                'batch_name',
                'activated_items',
                'service_memo_ref',
                'service_memo_data',
                'service_memo_revisions',
                'history_logs',
                'authorized_by',
                'created_by',
            ]);
        });

        Schema::table('engagement_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'start_record_id',
                'acknowledged_at',
                'response',
                'decline_reason',
                'is_required',
            ]);
        });
    }
};
