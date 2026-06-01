<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('townhall_approval_audits')) {
            Schema::create('townhall_approval_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('townhall_communication_id')->nullable()->index();
                $table->string('communication_ref_no')->nullable()->index();
                $table->string('communication_subject')->nullable();

                $table->unsignedBigInteger('requestor_user_id')->nullable()->index();
                $table->string('requestor_name')->nullable();

                $table->string('action')->index();
                $table->string('approval_level')->nullable()->index();

                $table->unsignedBigInteger('approver_user_id')->nullable()->index();
                $table->string('approver_name')->nullable();
                $table->string('approver_position')->nullable();
                $table->string('approver_department')->nullable();

                $table->string('approval_status')->nullable()->index();
                $table->text('remarks')->nullable();

                $table->timestamp('acted_at')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();

                $table->timestamps();

                $table->index(['townhall_communication_id', 'acted_at'], 'tha_communication_acted_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('townhall_approval_audits');
    }
};
