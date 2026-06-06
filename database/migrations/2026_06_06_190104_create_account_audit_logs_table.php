<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_affected_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_performed');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['user_affected_id', 'action_performed']);
            $table->index(['performed_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_audit_logs');
    }
};
