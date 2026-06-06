<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_system_accesses')) {
            return;
        }

        Schema::create('employee_system_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('system_platform_name');
            $table->string('account_type')->nullable();
            $table->string('username_email')->nullable();
            $table->string('role_access_level')->nullable();
            $table->string('access_status')->default('Active');
            $table->date('date_access_created')->nullable();
            $table->date('date_access_removed')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_status')->default('Pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'access_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_system_accesses');
    }
};
