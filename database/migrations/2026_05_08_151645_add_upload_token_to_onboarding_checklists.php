<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_checklists', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_checklists', 'upload_token')) {
                $table->string('upload_token', 100)->nullable()->unique()->after('status');
            }

            if (!Schema::hasColumn('onboarding_checklists', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('upload_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_checklists', function (Blueprint $table) {
            if (Schema::hasColumn('onboarding_checklists', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            if (Schema::hasColumn('onboarding_checklists', 'upload_token')) {
                $table->dropColumn('upload_token');
            }
        });
    }
};
