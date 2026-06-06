<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_assistance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('registered_email');
            $table->string('contact_number')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['registered_email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_assistance_requests');
    }
};
