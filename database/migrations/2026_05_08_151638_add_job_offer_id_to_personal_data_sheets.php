<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_data_sheets', 'job_offer_id')) {
                $table->unsignedBigInteger('job_offer_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('personal_data_sheets', 'job_offer_id')) {
                $table->dropColumn('job_offer_id');
            }
        });
    }
};
