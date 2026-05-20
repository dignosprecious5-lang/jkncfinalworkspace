<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_records', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->string('form_type');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('employee_code')->nullable();
            $table->string('employee_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->json('details')->nullable();
            $table->string('status')->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_records');
    }
};
