<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            if (!Schema::hasColumn('townhall_communications', 'workflow_status')) {
                $table->string('workflow_status')->default('Submitted')->after('approval_status');
            }

            if (!Schema::hasColumn('townhall_communications', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approver_id')) {
                $table->unsignedBigInteger('management_approver_id')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approver_user_id')) {
                $table->unsignedBigInteger('management_approver_user_id')->nullable()->after('management_approver_id');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approver_name')) {
                $table->string('management_approver_name')->nullable()->after('management_approver_user_id');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approver_position')) {
                $table->string('management_approver_position')->nullable()->after('management_approver_name');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approver_department')) {
                $table->string('management_approver_department')->nullable()->after('management_approver_position');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approval_status')) {
                $table->string('management_approval_status')->default('Pending')->after('management_approver_department');
            }

            if (!Schema::hasColumn('townhall_communications', 'management_approved_at')) {
                $table->timestamp('management_approved_at')->nullable()->after('management_approval_status');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approver_id')) {
                $table->unsignedBigInteger('executive_approver_id')->nullable()->after('management_approved_at');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approver_user_id')) {
                $table->unsignedBigInteger('executive_approver_user_id')->nullable()->after('executive_approver_id');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approver_name')) {
                $table->string('executive_approver_name')->nullable()->after('executive_approver_user_id');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approver_position')) {
                $table->string('executive_approver_position')->nullable()->after('executive_approver_name');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approver_department')) {
                $table->string('executive_approver_department')->nullable()->after('executive_approver_position');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approval_status')) {
                $table->string('executive_approval_status')->default('Pending')->after('executive_approver_department');
            }

            if (!Schema::hasColumn('townhall_communications', 'executive_approved_at')) {
                $table->timestamp('executive_approved_at')->nullable()->after('executive_approval_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('townhall_communications', function (Blueprint $table) {
            $columns = [
                'workflow_status',
                'submitted_at',
                'management_approver_id',
                'management_approver_user_id',
                'management_approver_name',
                'management_approver_position',
                'management_approver_department',
                'management_approval_status',
                'management_approved_at',
                'executive_approver_id',
                'executive_approver_user_id',
                'executive_approver_name',
                'executive_approver_position',
                'executive_approver_department',
                'executive_approval_status',
                'executive_approved_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('townhall_communications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
