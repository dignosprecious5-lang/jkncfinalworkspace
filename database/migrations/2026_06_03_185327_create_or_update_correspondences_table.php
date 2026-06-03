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
            Schema::create('correspondences', function (Blueprint $table) {
                $table->id();
                $table->string('ref_no')->nullable()->index();
                $table->date('correspondence_date')->nullable();
                $table->string('type')->nullable()->index();

                $table->string('company_name')->nullable();
                $table->string('registration_number')->nullable();
                $table->text('principal_address')->nullable();

                $table->string('tin')->nullable();
                $table->string('to_for')->nullable();
                $table->string('from_name')->nullable();
                $table->string('department_stakeholder')->nullable();
                $table->string('subject')->nullable();
                $table->longText('body')->nullable();
                $table->string('cc')->nullable();
                $table->string('additional')->nullable();
                $table->date('deadline')->nullable();
                $table->string('sent_via')->nullable();

                $table->string('status')->default('Open');
                $table->string('workflow_status')->default('Uploaded')->index();
                $table->string('approval_status')->default('Draft')->index();
                $table->text('review_note')->nullable();
                $table->string('attachment')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                $table->unsignedBigInteger('management_approver_id')->nullable();
                $table->unsignedBigInteger('management_approver_user_id')->nullable();
                $table->string('management_approver_name')->nullable();
                $table->string('management_approver_position')->nullable();
                $table->string('management_approver_department')->nullable();
                $table->string('management_approval_status')->nullable();
                $table->timestamp('management_approved_at')->nullable();

                $table->unsignedBigInteger('executive_approver_id')->nullable();
                $table->unsignedBigInteger('executive_approver_user_id')->nullable();
                $table->string('executive_approver_name')->nullable();
                $table->string('executive_approver_position')->nullable();
                $table->string('executive_approver_department')->nullable();
                $table->string('executive_approval_status')->nullable();
                $table->timestamp('executive_approved_at')->nullable();

                $table->boolean('is_archived')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('correspondences', function (Blueprint $table) {
            $tableName = 'correspondences';

            $this->addColumnIfMissing($table, $tableName, 'ref_no', fn($table) => $table->string('ref_no')->nullable()->index());
            $this->addColumnIfMissing($table, $tableName, 'correspondence_date', fn($table) => $table->date('correspondence_date')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'type', fn($table) => $table->string('type')->nullable()->index());

            $this->addColumnIfMissing($table, $tableName, 'company_name', fn($table) => $table->string('company_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'registration_number', fn($table) => $table->string('registration_number')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'principal_address', fn($table) => $table->text('principal_address')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'tin', fn($table) => $table->string('tin')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'to_for', fn($table) => $table->string('to_for')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'from_name', fn($table) => $table->string('from_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'department_stakeholder', fn($table) => $table->string('department_stakeholder')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'subject', fn($table) => $table->string('subject')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'body', fn($table) => $table->longText('body')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'cc', fn($table) => $table->string('cc')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'additional', fn($table) => $table->string('additional')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'deadline', fn($table) => $table->date('deadline')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'sent_via', fn($table) => $table->string('sent_via')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'status', fn($table) => $table->string('status')->default('Open'));
            $this->addColumnIfMissing($table, $tableName, 'workflow_status', fn($table) => $table->string('workflow_status')->default('Uploaded')->index());
            $this->addColumnIfMissing($table, $tableName, 'approval_status', fn($table) => $table->string('approval_status')->default('Draft')->index());
            $this->addColumnIfMissing($table, $tableName, 'review_note', fn($table) => $table->text('review_note')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'attachment', fn($table) => $table->string('attachment')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'created_by', fn($table) => $table->unsignedBigInteger('created_by')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'submitted_at', fn($table) => $table->timestamp('submitted_at')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'posted_at', fn($table) => $table->timestamp('posted_at')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'posted_by', fn($table) => $table->unsignedBigInteger('posted_by')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'approved_by', fn($table) => $table->unsignedBigInteger('approved_by')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'approved_at', fn($table) => $table->timestamp('approved_at')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'management_approver_id', fn($table) => $table->unsignedBigInteger('management_approver_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_user_id', fn($table) => $table->unsignedBigInteger('management_approver_user_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_name', fn($table) => $table->string('management_approver_name')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_position', fn($table) => $table->string('management_approver_position')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approver_department', fn($table) => $table->string('management_approver_department')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approval_status', fn($table) => $table->string('management_approval_status')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'management_approved_at', fn($table) => $table->timestamp('management_approved_at')->nullable());

            $this->addColumnIfMissing($table, $tableName, 'executive_approver_id', fn($table) => $table->unsignedBigInteger('executive_approver_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_user_id', fn($table) => $table->unsignedBigInteger('executive_approver_user_id')->nullable());
            $this->addColumnIfMissing($table, $tableName, 'executive_approver_name', fn($table) => $table->string('executive_approver_name')->nullable());
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
        // Safe no-op because this migration may be used to add missing columns to an existing module.
        // Drop manually only if this migration created the table and you are sure no data must be kept.
    }
};
