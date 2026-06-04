<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addColumnIfMissing(Blueprint $table, string $tableName, string $column, callable $definition): void
    {
        if (!Schema::hasColumn($tableName, $column)) {
            $definition($table);
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('correspondences')) {
            return;
        }

        Schema::table('correspondences', function (Blueprint $table) {
            $tableName = 'correspondences';

            $this->addColumnIfMissing($table, $tableName, 'ref_no', fn($table) => $table->string('ref_no')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'correspondence_date', fn($table) => $table->date('correspondence_date')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'company_name', fn($table) => $table->string('company_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'registration_number', fn($table) => $table->string('registration_number')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'principal_address', fn($table) => $table->text('principal_address')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'to_for_label', fn($table) => $table->string('to_for_label')->default('To'));
            $this->addColumnIfMissing($table, $tableName, 'to_for', fn($table) => $table->string('to_for')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'from_name', fn($table) => $table->string('from_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'department_stakeholder', fn($table) => $table->string('department_stakeholder')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'body', fn($table) => $table->longText('body')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'cc', fn($table) => $table->string('cc')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'additional', fn($table) => $table->string('additional')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'created_by', fn($table) => $table->unsignedBigInteger('created_by')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'submitted_at', fn($table) => $table->timestamp('submitted_at')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'posted_at', fn($table) => $table->timestamp('posted_at')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'posted_by', fn($table) => $table->unsignedBigInteger('posted_by')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'management_approver_id', fn($table) => $table->unsignedBigInteger('management_approver_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_user_id', fn($table) => $table->unsignedBigInteger('management_approver_user_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_name', fn($table) => $table->string('management_approver_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_email', fn($table) => $table->string('management_approver_email')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_position', fn($table) => $table->string('management_approver_position')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_department', fn($table) => $table->string('management_approver_department')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approval_status', fn($table) => $table->string('management_approval_status')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approved_at', fn($table) => $table->timestamp('management_approved_at')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'executive_approver_id', fn($table) => $table->unsignedBigInteger('executive_approver_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_user_id', fn($table) => $table->unsignedBigInteger('executive_approver_user_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_name', fn($table) => $table->string('executive_approver_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_email', fn($table) => $table->string('executive_approver_email')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_position', fn($table) => $table->string('executive_approver_position')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_department', fn($table) => $table->string('executive_approver_department')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approval_status', fn($table) => $table->string('executive_approval_status')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approved_at', fn($table) => $table->timestamp('executive_approved_at')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'is_archived', fn($table) => $table->boolean('is_archived')->default(false));
            $this->addColumnIfMissing($table, $tableName, 'archived_at', fn($table) => $table->timestamp('archived_at')->nullable());
        });
    }

    public function down(): void
    {
        // Intentionally left empty to avoid accidentally deleting existing production data columns.
    }
};
