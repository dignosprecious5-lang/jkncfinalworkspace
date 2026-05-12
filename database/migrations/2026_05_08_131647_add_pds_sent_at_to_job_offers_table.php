<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (!Schema::hasColumn('job_offers', 'pds_sent_at')) {
                $table->timestamp('pds_sent_at')->nullable()->after('declined_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            if (Schema::hasColumn('job_offers', 'pds_sent_at')) {
                $table->dropColumn('pds_sent_at');
            }
        });
    }
};
