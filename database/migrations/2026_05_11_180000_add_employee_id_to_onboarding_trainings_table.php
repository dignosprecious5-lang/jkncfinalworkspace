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
        Schema::table('onboarding_trainings', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('employee_name')->constrained('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_trainings', function (Blueprint $table) {
            $table->dropForeignIdFor('employees');
        });
    }
};
